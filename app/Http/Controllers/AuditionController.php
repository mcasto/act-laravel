<?php

namespace App\Http\Controllers;

use App\Exceptions\AuditionAiException;
use App\Helpers\LocalTime;
use App\Models\Audition;
use App\Models\AuditionPreview;
use App\Models\AuditionRole;
use App\Models\AuditionSession;
use App\Models\Show;
use App\Services\GeminiAuditionGenerator;
use App\Support\AuditionNotice;
use App\Support\PosterPalette;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AuditionController extends Controller
{
    /**
     * Get audition information for a specific show
     *
     * @param int $id The show ID to retrieve audition information for
     * @return array Status and audition data
     *
     * @source Database Model: Audition (reads)
     */
    public function show(int $id)
    {
        $show = Show::find($id);

        if (!$show) {
            return ['status' => 'error', 'message' => 'Show not found'];
        }

        $audition = Audition::with(['sessions', 'callbacks', 'roles'])
            ->where('show_id', $id)
            ->first();

        return [
            'status' => 'success',
            'audition' => $audition ? self::present($audition) : null,
            'defaults' => AuditionNotice::defaults($show),
            'ai_available' => GeminiAuditionGenerator::configured(),
            'preview_uid' => AuditionPreview::forShow($id)->uid,
        ];
    }

    /**
     * Create a new audition record
     *
     * Validates the incoming request data and creates a new audition
     * using the model's custom createWithHtml method which handles
     * HTML content storage.
     *
     * @param Request $request Contains display_date, end_display_date, html, show_id
     * @return array Status and created audition or error message
     *
     * @source Database Model: Audition (creates)
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), self::rules(), [], self::attributeNames());

        if ($validator->fails()) {
            return ['status' => 'error', 'message' => $validator->errors()->first()];
        }

        try {
            $audition = DB::transaction(function () use ($validator) {
                $data = $validator->validated();
                $audition = Audition::createWithHtml(self::auditionAttributes($data));
                self::syncChildren($audition, $data);
                return $audition;
            });

            return ['status' => 'success', 'audition' => self::present($audition->fresh(['sessions', 'callbacks', 'roles']))];
        } catch (Exception $e) {
            logger()->error($e->getMessage());
            return ['status' => 'error', 'message' => 'Unable to create audition record'];
        }
    }

    /**
     * Update an existing audition record
     *
     * Validates the incoming request data and updates the audition
     * using the model's custom updateWithHtml method which handles
     * HTML content storage.
     *
     * @param Request $request Contains display_date, end_display_date, html, show_id
     * @param int $id The audition ID to update
     * @return array Status and updated audition or error message
     *
     * @source Database Model: Audition (updates)
     */
    public function update(Request $request, int $id)
    {
        $validator = Validator::make($request->all(), self::rules(), [], self::attributeNames());

        if ($validator->fails()) {
            return ['status' => 'error', 'message' => $validator->errors()->first()];
        }

        try {
            $audition = Audition::find($id);

            if (!$audition) {
                return ['status' => 'error', 'message' => 'Audition not found'];
            }

            DB::transaction(function () use ($audition, $validator) {
                $data = $validator->validated();
                $audition->updateWithHtml(self::auditionAttributes($data));
                self::syncChildren($audition, $data);
            });

            return ['status' => 'success', 'audition' => self::present($audition->fresh(['sessions', 'callbacks', 'roles']))];
        } catch (Exception $e) {
            logger()->error($e->getMessage());
            return ['status' => 'error', 'message' => 'Unable to update audition record'];
        }
    }

    /**
     * Build the audition page's HTML from the form's current (unsaved)
     * values. engine "ai" restyles it with Gemini from the poster; "plain"
     * is the Blade template alone. Nothing is saved — the HTML goes into the
     * editor, and Save stores it.
     *
     * "plain" answers at once. "ai" answers {status: 'pending', token}; poll
     * generationStatus(token). Gemini being overloaded (503 etc., already
     * retried) ends as status "error" with code "busy".
     *
     * @return array {status, html} | {status: 'pending', token} | {status: 'error', code, message}
     */
    public function generateHtml(Request $request)
    {
        $validator = Validator::make($request->all(), self::rules() + [
            'engine' => 'required|in:ai,plain',
        ], [], self::attributeNames());

        if ($validator->fails()) {
            return ['status' => 'error', 'code' => 'invalid', 'message' => $validator->errors()->first()];
        }

        $data = $validator->validated();
        $show = Show::find($data['show_id']);

        if (!$show) {
            return ['status' => 'error', 'code' => 'invalid', 'message' => 'Show not found'];
        }

        $notice = AuditionNotice::data($data, $show);
        $posterFile = AuditionNotice::posterFile($show);
        $plain = view('auditions.notice', $notice + ['palette' => PosterPalette::fromFile($posterFile)])->render();
        $plain = GeminiAuditionGenerator::clean($plain);

        if ($data['engine'] === 'plain') {
            return ['status' => 'success', 'html' => $plain];
        }

        // Gemini answers slowly (and its 503s take about a minute each), so
        // the AI version runs after this response is sent — shared hosting
        // would time the request out otherwise — and the page polls
        // generationStatus() for the result.
        $token = (string) Str::uuid();
        Cache::put(self::generationKey($token), ['status' => 'pending'], now()->addMinutes(30));

        app()->terminating(function () use ($token, $plain, $notice, $posterFile) {
            set_time_limit(600);
            ignore_user_abort(true);

            try {
                $result = [
                    'status' => 'success',
                    'html' => app(GeminiAuditionGenerator::class)->generate($plain, $notice, $posterFile),
                ];
            } catch (AuditionAiException $e) {
                $result = ['status' => 'error', 'code' => $e->kind, 'message' => $e->getMessage()];
            } catch (\Throwable $e) {
                logger()->error('Audition AI generation failed: ' . $e->getMessage());
                $result = ['status' => 'error', 'code' => 'failed', 'message' => 'Something went wrong generating the page. Try again, or use "Build without AI".'];
            }

            Cache::put(self::generationKey($token), $result, now()->addMinutes(30));
        });

        return ['status' => 'pending', 'token' => $token];
    }

    /**
     * Poll an AI generation started by generateHtml().
     *
     * @return array {status: 'pending'} | {status: 'success', html} | {status: 'error', code, message}
     */
    public function generationStatus(string $token)
    {
        return Cache::get(self::generationKey($token))
            ?? ['status' => 'error', 'code' => 'failed', 'message' => 'That generation has expired. Try again.'];
    }

    private static function generationKey(string $token): string
    {
        return 'audition-ai:' . $token;
    }

    /** Validation shared by store, update and generateHtml. */
    private static function rules(): array
    {
        return [
            'show_id' => 'required|integer|exists:shows,id',
            'display_date' => 'required|date',
            'end_display_date' => 'required|date|after_or_equal:display_date',
            'html' => 'nullable|string',
            'what_to_expect' => 'nullable|string',
            'location' => 'nullable|string|max:2000',
            'registration_url' => 'nullable|url|max:500',
            'registration_note' => 'nullable|string|max:500',
            'script_url' => 'nullable|url|max:500',
            'rehearsal_start' => 'nullable|date',
            'rehearsal_schedule' => 'nullable|string',
            'contacts' => 'nullable|array',
            'contacts.*.name' => 'nullable|string|max:255',
            'contacts.*.title' => 'nullable|string|max:255',
            'contacts.*.phone' => 'nullable|string|max:255',
            'contacts.*.email' => 'nullable|email|max:255',
            'sessions' => 'nullable|array',
            'sessions.*.id' => 'nullable|integer',
            'sessions.*.date' => 'required|date_format:Y-m-d',
            'sessions.*.start_time' => 'required|date_format:H:i:s',
            'sessions.*.end_time' => 'nullable|date_format:H:i:s|after:sessions.*.start_time',
            'sessions.*.note' => 'nullable|string|max:500',
            'callbacks' => 'nullable|array',
            'callbacks.*.id' => 'nullable|integer',
            'callbacks.*.date' => 'required|date_format:Y-m-d',
            'callbacks.*.start_time' => 'required|date_format:H:i:s',
            'callbacks.*.end_time' => 'nullable|date_format:H:i:s|after:callbacks.*.start_time',
            'callbacks.*.note' => 'nullable|string|max:500',
            'roles' => 'nullable|array',
            'roles.*.id' => 'nullable|integer',
            'roles.*.name' => 'required|string|max:255',
            'roles.*.gender_age' => 'nullable|string|max:255',
            'roles.*.notes' => 'nullable|string',
        ];
    }

    /** Readable field names for validation messages ("audition end time", not "sessions.0.end_time"). */
    private static function attributeNames(): array
    {
        return [
            'sessions.*.date' => 'audition date',
            'sessions.*.start_time' => 'audition start time',
            'sessions.*.end_time' => 'audition end time',
            'callbacks.*.date' => 'callback date',
            'callbacks.*.start_time' => 'callback start time',
            'callbacks.*.end_time' => 'callback end time',
            'roles.*.name' => 'role name',
            'contacts.*.email' => 'contact email',
            'end_display_date' => 'end display date',
            'registration_url' => 'registration form URL',
            'script_url' => 'script/excerpts URL',
        ];
    }

    /**
     * The auditions-table columns from validated input. A location or
     * registration note equal to the default is stored as null ("use the
     * default"), so it follows the default if that ever changes.
     */
    private static function auditionAttributes(array $data): array
    {
        $attrs = collect($data)->only([
            'show_id', 'display_date', 'end_display_date', 'html', 'what_to_expect',
            'location', 'registration_url', 'registration_note', 'script_url',
            'rehearsal_start', 'rehearsal_schedule', 'contacts',
        ])->all();

        foreach (['location' => 'act.address', 'registration_note' => 'act.audition_registration_note'] as $field => $key) {
            if (array_key_exists($field, $attrs) && self::sameText($attrs[$field], config($key))) {
                $attrs[$field] = null;
            }
        }

        return $attrs;
    }

    private static function sameText(?string $a, ?string $b): bool
    {
        $norm = fn ($s) => preg_replace('/\s+/', ' ', trim((string) $s));

        return $norm($a) === '' || $norm($a) === $norm($b);
    }

    /** Upsert sessions, callbacks and roles by id; rows no longer listed are deleted. */
    private static function syncChildren(Audition $audition, array $data): void
    {
        $keepSessions = [];

        foreach (['sessions' => false, 'callbacks' => true] as $key => $isCallback) {
            foreach ($data[$key] ?? [] as $row) {
                $attrs = [
                    'session' => $row['date'] . ' ' . $row['start_time'],
                    'end_time' => $row['end_time'] ?? null,
                    'note' => $row['note'] ?? null,
                    'is_callback' => $isCallback,
                ];

                $session = !empty($row['id'])
                    ? AuditionSession::where('audition_id', $audition->id)->find($row['id'])
                    : null;

                $session
                    ? $session->update($attrs)
                    : $session = AuditionSession::create($attrs + ['audition_id' => $audition->id]);

                $keepSessions[] = $session->id;
            }
        }

        AuditionSession::where('audition_id', $audition->id)
            ->whereNotIn('id', $keepSessions)
            ->get()->each->delete();

        $keepRoles = [];

        foreach (array_values($data['roles'] ?? []) as $i => $row) {
            $attrs = [
                'name' => $row['name'],
                'gender_age' => $row['gender_age'] ?? null,
                'notes' => $row['notes'] ?? null,
                'sort_order' => $i,
            ];

            $role = !empty($row['id'])
                ? AuditionRole::where('audition_id', $audition->id)->find($row['id'])
                : null;

            $role
                ? $role->update($attrs)
                : $role = AuditionRole::create($attrs + ['audition_id' => $audition->id]);

            $keepRoles[] = $role->id;
        }

        AuditionRole::where('audition_id', $audition->id)
            ->whereNotIn('id', $keepRoles)
            ->get()->each->delete();
    }

    /** The audition as the admin form edits it (sessions split into date + times). */
    private static function present(Audition $audition): array
    {
        $session = fn (AuditionSession $s) => [
            'id' => $s->id,
            'date' => $s->session->format('Y-m-d'),
            'start_time' => $s->session->format('H:i:s'),
            'end_time' => $s->end_time,
            'note' => $s->note,
        ];

        return [
            ...$audition->only([
                'id', 'show_id', 'display_date', 'end_display_date', 'html', 'what_to_expect',
                'location', 'registration_url', 'registration_note', 'script_url',
                'rehearsal_schedule', 'contacts',
            ]),
            'rehearsal_start' => $audition->rehearsal_start?->format('Y-m-d H:i:s'),
            'sessions' => $audition->sessions->map($session)->values(),
            'callbacks' => $audition->callbacks->map($session)->values(),
            'roles' => $audition->roles->map(fn (AuditionRole $r) => $r->only(['id', 'name', 'gender_age', 'notes']))->values(),
        ];
    }

    /**
     * Get the currently active audition
     *
     * Retrieves auditions that are currently within their display date range
     * (between display_date and end_display_date), including the related show data.
     *
     * @return JsonResponse The current audition with show relationship or null
     *
     * @source Database Model: Audition (reads with Show relationship)
     */
    public function current(): JsonResponse
    {
        $currentAudition = Audition::with(['show'])
            ->where('display_date', '<=', LocalTime::todayString())
            ->where('end_display_date', '>=', LocalTime::todayString())
            ->first();

        return response()->json($currentAudition);
    }

    /**
     * A show's saved audition, by its preview link's uid, ignoring the
     * display dates — backs the public /audition/preview/{uid} page.
     *
     * @return JsonResponse The audition with show relationship, or null for
     *                      an unknown uid or a show with no audition saved yet
     *
     * @source Database Models: AuditionPreview, Audition (reads with Show relationship)
     */
    public function preview(string $uid): JsonResponse
    {
        $preview = AuditionPreview::where('uid', $uid)->first();

        return response()->json(
            $preview ? Audition::with(['show'])->where('show_id', $preview->show_id)->first() : null
        );
    }

    /**
     * Handle audition contact submission
     *
     * Simple passthrough method that returns the request data.
     *
     * @param Request $request The contact data
     * @return JsonResponse The request data as JSON
     *
     * @source None (passthrough)
     */
    public function contact(Request $request): JsonResponse
    {
        $contact = $request->all();
        return response()->json($contact);
    }
}
