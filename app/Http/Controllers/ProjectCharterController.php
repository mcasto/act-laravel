<?php

namespace App\Http\Controllers;

use App\Models\ProjectCharter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View as ViewFacade;

class ProjectCharterController extends Controller
{
    private const MILESTONE_KEYS = [
        'planning_completed',
        'partners_confirmed',
        'launch',
        'main_activity',
        'evaluation',
        'final_report',
    ];

    /**
     * Serves the fillable form as rendered HTML (not JSON) — the public
     * ProjectCharterPage.vue fetches this and injects it into the page.
     */
    public function form()
    {
        return ViewFacade::make('project-charter-form');
    }

    /**
     * Public submission — no auth, no email (deliberately, per the current
     * scope: this isn't linked/announced anywhere yet).
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'sponsor' => 'required|string|max:255',
            'description' => 'required|string',
            'importance' => 'nullable|string',
            'goal' => 'nullable|string',
            'success_measures' => 'nullable|array',
            'success_measures.*' => 'nullable|string|max:500',
            'measurement_methods' => 'nullable|array',
            'measurement_methods.*' => 'string',
            'measurement_approach' => 'nullable|string',
            'start_date' => 'nullable|date',
            'completion_date' => 'nullable|date',
            'milestones' => 'nullable|array',
            'milestones.*' => 'nullable|date',
            'team_members' => 'nullable|string',
            'community_partners' => 'nullable|string',
            'resources_needed' => 'nullable|array',
            'resources_needed.*' => 'string',
            'resources_other' => 'nullable|string|max:255',
            'documentation_methods' => 'nullable|array',
            'documentation_methods.*' => 'string',
            'documentation_plan' => 'nullable|string',
            'risks' => 'nullable|array',
            'risks.*.challenge' => 'nullable|string|max:500',
            'risks.*.mitigation' => 'nullable|string|max:500',
            'leadership_reflection' => 'nullable|string',
            'commitment_signature' => 'nullable|string|max:255',
            'commitment_date' => 'nullable|date',
        ]);

        // Blank rows are just unfilled form slots, not real data.
        $validated['success_measures'] = array_values(array_filter(
            $validated['success_measures'] ?? [],
            fn ($measure) => trim((string) $measure) !== '',
        ));

        $validated['risks'] = array_values(array_filter(
            $validated['risks'] ?? [],
            fn ($risk) => trim($risk['challenge'] ?? '') !== '' || trim($risk['mitigation'] ?? '') !== '',
        ));

        $validated['milestones'] = array_filter(
            array_intersect_key($validated['milestones'] ?? [], array_flip(self::MILESTONE_KEYS)),
            fn ($date) => ! empty($date),
        );

        ProjectCharter::create($validated);

        return response()->json(['status' => 'success']);
    }

    /**
     * Admin list — ?archived=1 switches to soft-deleted records instead of
     * active ones. Returns full rows (every field) in one call; the admin
     * table's View dialog just displays the already-loaded row, same
     * pattern as AdminFlexPurchases.vue.
     */
    public function index(Request $request): JsonResponse
    {
        $query = $request->boolean('archived')
            ? ProjectCharter::onlyTrashed()
            : ProjectCharter::query();

        return response()->json($query->orderByDesc('created_at')->get());
    }

    /**
     * Admin-only feasibility scoring + private notes — deliberately a
     * separate endpoint from the public store(), touching only these 5
     * columns, never the sponsor-submitted content.
     */
    public function updateFeasibility(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'price_score' => 'nullable|integer|min:1|max:5',
            'complexity_score' => 'nullable|integer|min:1|max:5',
            'time_score' => 'nullable|integer|min:1|max:5',
            'risk_score' => 'nullable|integer|min:1|max:5',
            'admin_notes' => 'nullable|string',
        ]);

        // forceFill, not update() — these 5 columns are deliberately excluded
        // from the model's $fillable so the public store() endpoint can never
        // mass-assign them; that same protection would silently no-op update()
        // here too.
        $charter = ProjectCharter::findOrFail($id);
        $charter->forceFill($validated)->save();

        return response()->json(['status' => 'success', 'charter' => $charter]);
    }

    public function destroy(int $id): JsonResponse
    {
        ProjectCharter::findOrFail($id)->delete();

        return response()->json(['status' => 'success']);
    }

    public function restore(int $id): JsonResponse
    {
        ProjectCharter::onlyTrashed()->findOrFail($id)->restore();

        return response()->json(['status' => 'success']);
    }
}
