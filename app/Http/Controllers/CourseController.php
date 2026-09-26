<?php

namespace App\Http\Controllers;

use App\Helpers\PatronMail;
use App\Helpers\RefId;
use App\Mail\CourseEnrollmentConfirmedMailer;
use App\Mail\CourseInquiryConfirmationMailer;
use App\Mail\CourseInquiryMailer;
use App\Models\Course;
use App\Models\CourseContact;
use App\Models\Patron;
use App\Models\PaymentMethod;
use App\Models\SiteConfig;
use App\Models\StandardButton;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Intervention\Image\Laravel\Facades\Image;
use League\HTMLToMarkdown\HtmlConverter;
use Illuminate\Support\Str;

class CourseController extends Controller
{
    public function index(): JsonResponse
    {
        $courses = Course::with(['sessions', 'contacts.paymentMethod'])
            ->orderBy('enrollment_start', 'desc')
            ->get()
            ->each(function ($course) {
                $course->append('message');
            });

        return response()->json($courses);
    }

    public function show(string|int $id): JsonResponse
    {
        if ($id == 'new') {
            return response()->json([
                'cost' => 0,
                'name' => null,
                'instructor_name' => null,
                'enrollment_start' => null,
                'enrollment_end' => null,
                'fixr' => null,
                'instructor_email' => null,
                'instructor_info' => "",
                'instructor_photo' => null,
                'location' => "Azuay Community Theater, 14-46 Atonio Vega Muñoz between Estevez de Toral and Coronel Talbot",
                'max_participants' => null,
                'message' => "",
                'poster' => null,
                'sessions' => [],
                'contacts' => [],
                'tagline' => null,
            ]);
        }

        $course = Course::with(['sessions', 'contacts'])
            ->where('id', '=', $id)
            ->first();

        $course->append('message');

        return response()->json($course);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name'             => 'required|string|max:255',
            'instructor_name'  => 'required|string|max:255',
            'instructor_email' => 'required|email|max:255',
            'enrollment_start' => 'required|date',
            'enrollment_end'   => 'required|date|after_or_equal:enrollment_start',
            'cost'             => 'required|numeric|min:0',
            'max_participants' => 'nullable|integer|min:1',
            'poster'           => 'required|string|max:255',
            'tagline'          => 'required|string|max:255',
            'location'         => 'required|string|max:255',
            'fixr'             => 'nullable|string|max:255',
            'instructor_photo' => 'required|string|max:255',
            'instructor_info'  => 'required|string',
            'message'         => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => implode(' ', $validator->errors()->all()),
                'errors' => $validator->errors(),
            ]);
        }

        $rec = $validator->validated();
        $baseSlug = Str::slug($rec['name'], '-') . '-' . date("Y");
        $rec['slug'] = $this->resolveUniqueSlug($baseSlug);

        if (Storage::disk('local')->exists("uploads/{$rec['poster']}")) {
            $ext = pathinfo($rec['poster'], PATHINFO_EXTENSION);
            $rec['poster'] = $this->publishUpload($rec['poster'], 'posters', "{$rec['slug']}.{$ext}");
        }

        if (Storage::disk('local')->exists("uploads/{$rec['instructor_photo']}")) {
            $ext = pathinfo($rec['instructor_photo'], PATHINFO_EXTENSION);
            $rec['instructor_photo'] = $this->publishUpload($rec['instructor_photo'], 'images', "{$rec['slug']}-instructor.{$ext}");
        }

        $course = Course::create($rec);
        $rec['id'] = $course->id;

        // Create the blade template view file
        $viewPath = resource_path("views/courses/{$rec['slug']}.blade.php");
        $viewDir = dirname($viewPath);

        // Ensure the directory exists
        if (!file_exists($viewDir)) {
            mkdir($viewDir, 0755, true);
        }

        // Write the message content to the blade view file
        file_put_contents($viewPath, $rec['message']);

        // Course::getMessageAttribute() caches the rendered view for an
        // hour, keyed by slug — clear it defensively in case a prior
        // (deleted) course ever cached under this same slug.
        Cache::forget("course-message-{$rec['slug']}");

        return response()->json($course);
    }

    public function update(int $id, Request $request): JsonResponse
    {
        $course = Course::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name'             => 'required|string|max:255',
            'instructor_name'  => 'required|string|max:255',
            'instructor_email' => 'required|email|max:255',
            'enrollment_start' => 'required|date',
            'enrollment_end'   => 'required|date|after_or_equal:enrollment_start',
            'cost'             => 'required|numeric|min:0',
            'max_participants' => 'nullable|integer|min:1',
            'poster'           => 'required|string|max:255',
            'tagline'          => 'required|string|max:255',
            'location'         => 'required|string|max:255',
            'fixr'             => 'nullable|string|max:255',
            'instructor_photo' => 'required|string|max:255',
            'instructor_info'  => 'required|string',
            'message'         => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => implode(' ', $validator->errors()->all()),
                'errors' => $validator->errors(),
            ]);
        }

        $rec = $validator->validated();
        $oldSlug = $course->slug;

        // The view file is looked up by slug (Course::getMessageAttribute()),
        // so renaming the course needs to rename its slug — and the file on
        // disk — to match, or that lookup silently 404s against the old
        // file/name forever (this is exactly what happened to this course:
        // renamed from "Booth Camp" to "Tech Booth Camp" without this, so
        // its slug moved on but booth-camp-2026.blade.php never did).
        // Keyed off created_at's year, not today's, so re-saving a course
        // in a later year than it was created doesn't drift its slug.
        if ($rec['name'] !== $course->name) {
            $baseSlug = Str::slug($rec['name'], '-') . '-' . $course->created_at->format('Y');
            $rec['slug'] = $this->resolveUniqueSlug($baseSlug, $course->id);
        }

        if (Storage::disk('local')->exists("uploads/{$rec['poster']}")) {
            $ext = pathinfo($rec['poster'], PATHINFO_EXTENSION);
            $rec['poster'] = $this->publishUpload($rec['poster'], 'posters', "{$course->slug}.{$ext}");
        }

        if (Storage::disk('local')->exists("uploads/{$rec['instructor_photo']}")) {
            $ext = pathinfo($rec['instructor_photo'], PATHINFO_EXTENSION);
            $rec['instructor_photo'] = $this->publishUpload($rec['instructor_photo'], 'images', "{$course->slug}-instructor.{$ext}");
        }

        $course->update($rec);

        $viewPath = resource_path("views/courses/{$course->slug}.blade.php");
        $oldViewPath = resource_path("views/courses/{$oldSlug}.blade.php");

        if ($course->slug !== $oldSlug && file_exists($oldViewPath)) {
            rename($oldViewPath, $viewPath);
        }

        // Write the message content to the blade view file
        file_put_contents($viewPath, $rec['message']);

        // Without this, Course::getMessageAttribute()'s hour-long cache
        // keeps serving the pre-edit rendered content — the admin form
        // itself shows what was just typed (it's local state), but
        // reopening this course (or viewing it publicly) afterward would
        // show stale content until the cache happened to expire.
        Cache::forget("course-message-{$oldSlug}");
        Cache::forget("course-message-{$course->slug}");

        return response()->json($course);
    }

    /**
     * Appends a numeric suffix if $baseSlug already belongs to another
     * course, mirroring the display-name collision a duplicate slug would
     * otherwise cause. $excludeCourseId lets update() re-check a rename
     * without the course colliding with its own current row.
     */
    private function resolveUniqueSlug(string $baseSlug, ?int $excludeCourseId = null): string
    {
        $collision = Course::where('slug', $baseSlug)
            ->when($excludeCourseId, fn ($query) => $query->where('id', '!=', $excludeCourseId))
            ->exists();

        if (! $collision) {
            return $baseSlug;
        }

        $existingSlugs = Course::where('slug', 'like', $baseSlug . '%')
            ->when($excludeCourseId, fn ($query) => $query->where('id', '!=', $excludeCourseId))
            ->pluck('slug')
            ->map(function ($slug) use ($baseSlug) {
                if (preg_match('/^' . preg_quote($baseSlug, '/') . '-(\d+)$/', $slug, $matches)) {
                    return (int) $matches[1];
                }
                return 0;
            })
            ->max();

        $nextNumber = $existingSlugs ? $existingSlugs + 1 : 1;

        return $baseSlug . '-' . sprintf('%03s', $nextNumber);
    }

    private function publishUpload(string $tempFilename, string $dir, string $newFilename): string
    {
        $tempPath   = Storage::disk('local')->path("uploads/{$tempFilename}");
        $targetPath = Storage::disk('public')->path("{$dir}/{$newFilename}");

        Storage::disk('public')->makeDirectory($dir);

        Image::read($tempPath)
            ->scaleDown(width: 1200)
            ->save($targetPath, quality: 80);

        Storage::disk('local')->delete("uploads/{$tempFilename}");
        return $newFilename;
    }

    public function destroy(int $id)
    {
        $course = Course::findOrFail($id);
        $course->delete();

        return response()->json(['status' => 'success']);
    }

    public function uploadPoster(Request $request, int|string $id)
    {
        if ($request->hasFile('poster')) {
            $file     = $request->file('poster');
            $filename = uniqid() . '.' . $file->getClientOriginalExtension();
            $file->storeAs('uploads', $filename);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'No file uploaded',
            ]);
        }

        return response()->json([
            'status' => 'success',
            'poster' => $filename
        ]);
    }

    public function uploadInstructorPhoto(Request $request, int|string $id)
    {
        if ($request->hasFile('instructor_photo')) {
            $file     = $request->file('instructor_photo');
            $filename = uniqid() . '.' . $file->getClientOriginalExtension();
            $file->storeAs('uploads', $filename);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'No file uploaded',
            ]);
        }

        return response()->json([
            'status' => 'success',
            'instructor_photo' => $filename
        ]);
    }

    /**
     * Get courses with open enrollment
     *
     * Retrieves all courses that are currently accepting enrollments
     * (between enrollment_start and enrollment_end dates).
     *
     * @return JsonResponse Courses with their sessions
     *
     * @source Database Model: Course (reads with sessions relationship)
     */
    public function openEnrollment(): JsonResponse
    {
        $courses = Course::with(['sessions'])->where('enrollment_start', '<=', now())
            ->where('enrollment_end', '>=', now())->get();

        return response()->json($courses);
    }

    /**
     * Preview version of openEnrollment() — the next class coming up,
     * regardless of whether its enrollment window has actually opened yet.
     * Still excludes classes whose enrollment has already fully closed, so
     * this only ever surfaces something genuinely upcoming. Returned as a
     * single-item array (same shape as openEnrollment()) so the same
     * CoursesPage.vue / "exactly one result" redirect logic works unchanged.
     */
    public function previewEnrollment(): JsonResponse
    {
        $courses = Course::with(['sessions'])
            ->where('enrollment_end', '>=', now())
            ->orderBy('enrollment_start')
            ->limit(1)
            ->get();

        return response()->json($courses);
    }

    /**
     * Get detailed information for a specific course
     *
     * Retrieves course details by slug, including session information and
     * HTML content rendered from the course's blade view.
     *
     * @param string $slug The course slug identifier
     * @return JsonResponse Course data with HTML content
     *
     * @source
     *   Database Model: Course (reads with sessions relationship)
     *   View: resources/views/courses/{slug}.blade.php
     */
    public function courseDetails(string $slug): JsonResponse
    {
        $course = Course::with(['sessions'])->where('slug', '=', $slug)->first()->toArray();

        $course['html'] = view("courses.{$slug}")->render();

        // Same standard_buttons-driven payment options as ticket purchases
        // (ShowController::homeShows()) — filtered to paypal/transfer only,
        // since flex/questions don't apply to a class enrollment. FixR is a
        // separate, always-available synthetic entry the frontend adds
        // itself (only when this course has its own fixr link configured).
        $course['fixrLabel'] = 'Pay with Credit / Debit';
        $course['buttons'] = Cache::remember('standard-buttons', 3600, fn () => StandardButton::orderBy('sort_order')->get())
            ->filter(fn ($rec) => in_array($rec->key, ['paypal', 'transfer']))
            ->map(function ($rec) use ($course) {
                $param = "\${$course['cost']} for this class";

                $rec->popupText = Cache::remember(
                    "standard-button-{$rec->key}-course-{$course['cost']}",
                    3600,
                    fn () => view("standard-buttons.{$rec->key}", [
                        'param' => $param,
                        'subject' => 'enrolled in this class',
                    ])->render()
                );

                return $rec;
            })
            ->values();

        return response()->json($course);
    }

    /**
     * Handle course enrollment contact submission
     *
     * Validates and creates a course contact record, retrieves course and
     * configuration details, formats an enrollment email, and sends it via
     * SendGrid to the course instructor.
     *
     * @param Request $request Contains contact info (first_name, last_name, email, phone, questions, course_id)
     * @return JsonResponse SendGrid response or validation errors
     *
     * @source Database Models:
     *   - CourseContact (validates and creates)
     *   - SiteConfig (reads latest config)
     *   - Course (reads with sessions relationship)
     */
    public function courseContact(Request $request): JsonResponse
    {
        $course = Course::findOrFail($request->input('course_id'));

        $validated = CourseContact::validate($request->all(), $course->cost > 0);
        if (isset($validated['errors'])) {
            return response()->json($validated);
        }

        $patron = Patron::firstOrCreate(
            ['email' => $validated['email']],
            [
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'phone' => $validated['phone'],
            ]
        );
        $validated['patron_id'] = $patron->id;

        $paymentMethod = null;
        if (! empty($validated['payment_method_value'])) {
            $paymentMethod = PaymentMethod::where('value', $validated['payment_method_value'])->first();
            $validated['payment_method_id'] = $paymentMethod?->id;
        }
        unset($validated['payment_method_value']);

        // Nothing to confirm on a free enrollment; a paid one starts
        // unconfirmed until an admin verifies the PayPal/Transfer payment
        // actually came through (the Fixr webhook path sets this true
        // itself, since Fixr clears the payment immediately).
        $validated['confirmed'] = $course->cost == 0;

        $enrollment = CourseContact::create($validated);
        $enrollment->transaction_id = RefId::ref_id($enrollment->id);
        $enrollment->save();

        $data = array_merge($validated, [
            'course_name'     => $course->name,
            'instructor_name' => $course->instructor_name,
            'cost'            => $course->cost,
            'payment_method_label' => $paymentMethod?->label,
            'transaction_id'  => $enrollment->transaction_id,
        ]);

        // The instructor may not have admin access to verify a PayPal/
        // Transfer payment, so the box office gets notified of every new
        // enrollment instead — the instructor only hears about it once it's
        // actually confirmed (instantly here for a free course; otherwise
        // via updateConfirmed() below).
        Mail::to(config('mail.admin_to.address'))->send(new CourseInquiryMailer($data));
        PatronMail::to($validated['email'])->send(new CourseInquiryConfirmationMailer($data));

        if ($enrollment->confirmed) {
            $this->notifyInstructorOfConfirmedEnrollment($course, $data);
        }

        return response()->json(['status' => 'success']);
    }

    /**
     * Admin marks a class enrollment's payment as confirmed (or reverts
     * it) — same shape as TicketSaleController::updateNoShow(). Only the
     * false -> true transition notifies the instructor (see
     * notifyInstructorOfConfirmedEnrollment()) — reverting a confirmation,
     * or saving the same value again, sends nothing.
     */
    public function updateConfirmed(Request $request, int $id): JsonResponse
    {
        $enrollment = CourseContact::findOrFail($id);
        $wasConfirmed = $enrollment->confirmed;
        $enrollment->confirmed = $request->input('confirmed');
        $enrollment->save();

        if (! $wasConfirmed && $enrollment->confirmed) {
            $course = $enrollment->course;

            $this->notifyInstructorOfConfirmedEnrollment($course, [
                'course_name'           => $course->name,
                'first_name'            => $enrollment->first_name,
                'last_name'             => $enrollment->last_name,
                'email'                 => $enrollment->email,
                'phone'                 => $enrollment->phone,
                'payment_method_label'  => $enrollment->paymentMethod?->label,
                'transaction_id'        => $enrollment->transaction_id,
            ]);
        }

        return response()->json(['status' => 'success', 'confirmed' => $enrollment->confirmed]);
    }

    /**
     * The one place that actually emails the instructor about an
     * enrollment — deliberately only reached once payment is confirmed
     * (see courseContact() and updateConfirmed() above, and the Fixr
     * webhook's course branch, which calls this same mailer directly since
     * it lives in a different controller).
     */
    private function notifyInstructorOfConfirmedEnrollment(Course $course, array $data): void
    {
        try {
            Mail::to($course->instructor_email)->send(new CourseEnrollmentConfirmedMailer($data));
        } catch (\Exception $e) {
            logger()->error('Failed to send course enrollment confirmation email to instructor', [
                'error' => $e->getMessage(),
                'course_id' => $course->id,
            ]);
        }
    }
}
