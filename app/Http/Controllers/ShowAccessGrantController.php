<?php

namespace App\Http\Controllers;

use App\Models\Show;
use App\Models\ShowAccessGrant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Web-admin CRUD for a show's production-team access (who can see the show
 * in the desktop director app, and when). Routes are gated by
 * permission:shows — reads at read-only, writes at full.
 *
 * Grants are history: once a grant has started, "removing" it ends it
 * (ends_at = yesterday) instead of deleting the row. Only a grant that has
 * never been in effect (starts in the future) can actually be deleted.
 * Dates are saved exactly as submitted — ShowAccessGrant::defaultWindow()
 * is only offered to the UI as a pre-fill.
 */
class ShowAccessGrantController extends Controller
{
    /**
     * A show's grants, the suggested default window, and the people who can
     * be picked (included here so a Shows-only admin doesn't also need Users
     * access just to fill the picker).
     *
     * @return JsonResponse {grants: [...], default_window: {starts_at, ends_at}, users: [{id, name, email}]}
     *
     * @source Database Models: ShowAccessGrant, Show, User (reads)
     */
    public function index(int $showId): JsonResponse
    {
        $show = Show::findOrFail($showId);

        return response()->json([
            'grants'         => $this->grantsFor($show),
            'default_window' => ShowAccessGrant::defaultWindow($show),
            'users'          => User::orderBy('name')->get(['id', 'name', 'email'])
                ->map(fn (User $u) => ['id' => $u->id, 'name' => $u->name, 'email' => $u->email]),
        ]);
    }

    /**
     * Add a person to the show's production team
     *
     * @source Database Model: ShowAccessGrant (creates)
     */
    public function store(Request $request, int $showId): JsonResponse
    {
        $show = Show::findOrFail($showId);

        $validated = $request->validate($this->rules() + [
            'user_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->whereNull('deleted_at'),
                Rule::unique('show_access_grants', 'user_id')->where('show_id', $show->id),
            ],
        ], [
            'user_id.unique' => 'That person is already on this show — edit their existing row instead.',
        ]);

        ShowAccessGrant::create(['show_id' => $show->id] + $validated);

        return response()->json(['status' => 'success', 'grants' => $this->grantsFor($show)]);
    }

    /**
     * Change a grant's role or dates (the person can't be changed)
     *
     * @source Database Model: ShowAccessGrant (updates)
     */
    public function update(Request $request, int $showId, int $grantId): JsonResponse
    {
        $show = Show::findOrFail($showId);
        $grant = ShowAccessGrant::where('show_id', $show->id)->findOrFail($grantId);

        $grant->update($request->validate($this->rules()));

        return response()->json(['status' => 'success', 'grants' => $this->grantsFor($show)]);
    }

    /**
     * Remove a grant: deletes it if it has never been in effect, otherwise
     * ends it as of yesterday so the history is kept.
     *
     * @return JsonResponse {status, outcome: 'deleted'|'ended', grants}
     *
     * @source Database Model: ShowAccessGrant (deletes or updates ends_at)
     */
    public function destroy(int $showId, int $grantId): JsonResponse
    {
        $show = Show::findOrFail($showId);
        $grant = ShowAccessGrant::where('show_id', $show->id)->findOrFail($grantId);
        $today = Carbon::today();

        if ($grant->starts_at && $grant->starts_at->gt($today)) {
            $grant->delete();
            $outcome = 'deleted';
        } else {
            $yesterday = $today->copy()->subDay();
            // Already ended earlier: leave its real end date alone.
            if (! $grant->ends_at || $grant->ends_at->gt($yesterday)) {
                $grant->update(['ends_at' => $yesterday]);
            }
            $outcome = 'ended';
        }

        return response()->json(['status' => 'success', 'outcome' => $outcome, 'grants' => $this->grantsFor($show)]);
    }

    private function rules(): array
    {
        return [
            'role'      => ['nullable', 'string', 'max:100'],
            'starts_at' => ['nullable', 'date_format:Y-m-d'],
            'ends_at'   => ['nullable', 'date_format:Y-m-d', 'after_or_equal:starts_at'],
        ];
    }

    private function grantsFor(Show $show)
    {
        $today = Carbon::today();

        return ShowAccessGrant::where('show_id', $show->id)
            ->with('user:id,name,email')
            ->get()
            ->map(fn (ShowAccessGrant $g) => [
                'id'         => $g->id,
                'user_id'    => $g->user_id,
                'user_name'  => $g->user?->name ?? '(deleted user)',
                'user_email' => $g->user?->email,
                'role'       => $g->role,
                'starts_at'  => $g->starts_at?->toDateString(),
                'ends_at'    => $g->ends_at?->toDateString(),
                'status'     => $g->isActive() ? 'active' : ($g->starts_at && $g->starts_at->gt($today) ? 'upcoming' : 'ended'),
            ])
            ->sortBy(fn ($g) => [['active' => 0, 'upcoming' => 1, 'ended' => 2][$g['status']], $g['user_name']])
            ->values();
    }
}
