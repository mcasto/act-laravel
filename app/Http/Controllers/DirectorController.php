<?php

namespace App\Http\Controllers;

use App\Http\Middleware\EnsureDirectorShowAccess;
use App\Models\Audition;
use App\Models\Performance;
use App\Models\Show;
use App\Models\ShowAccessGrant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API for the director page (/director, a PWA). Read-only except for the
 * show's own audition notice (updateAuditions). Every response is built
 * field-by-field (never a raw model) so nothing beyond what's listed here can
 * leak — in particular no patron, ticket-sale, or comp-recipient data, only
 * aggregates. Per-show routes are gated by EnsureDirectorShowAccess, which
 * supplies the Show via the 'director_show' request attribute.
 */
class DirectorController extends Controller
{
    /**
     * Shows the caller currently has an active grant for
     *
     * @return JsonResponse [{id, name, poster_url, first_performance, last_performance, role, starts_at, ends_at}]
     *
     * @source Database Models: ShowAccessGrant, Show, Performance (reads)
     */
    public function shows(Request $request): JsonResponse
    {
        if ($rejection = EnsureDirectorShowAccess::rejectNonAppToken($request)) {
            return $rejection;
        }

        $grants = ShowAccessGrant::active()
            ->where('user_id', $request->user()->id)
            ->whereHas('show')
            ->with(['show.performances'])
            ->get();

        $shows = $grants
            ->map(fn (ShowAccessGrant $grant) => [
                'id'                => $grant->show->id,
                'name'              => $grant->show->name,
                'poster_url'        => self::posterUrl($grant->show),
                'first_performance' => $grant->show->performances->min('date'),
                'last_performance'  => $grant->show->performances->max('date'),
                'role'              => $grant->role,
                'starts_at'         => $grant->starts_at?->toDateString(),
                'ends_at'           => $grant->ends_at?->toDateString(),
            ])
            ->sortBy(fn ($s) => $s['first_performance'] ?? '9999-12-31')
            ->values();

        return response()->json($shows);
    }

    /**
     * Per-performance seat counts for one show (aggregates only)
     *
     * @return JsonResponse {show_id, performances: [{id, date, start_time, reserved, comps, sold_out_target, remaining, sold_out}]}
     *
     * @source Database Models: Performance (reads), TicketSale/CompTicket (counts via Performance::salesSummary)
     */
    public function sales(Request $request): JsonResponse
    {
        /** @var Show $show */
        $show = $request->attributes->get('director_show');

        $performances = Performance::where('show_id', $show->id)
            ->orderBy('date')
            ->orderBy('start_time')
            ->get()
            ->map(function (Performance $performance) {
                $summary = $performance->salesSummary();

                return [
                    'id'              => $performance->id,
                    'date'            => $performance->date,
                    'start_time'      => $performance->start_time,
                    'reserved'        => $summary['reserved'],
                    'comps'           => $summary['comps'],
                    'sold_out_target' => $summary['sold_out_target'],
                    'remaining'       => $summary['remaining'],
                    'sold_out'        => (bool) $performance->sold_out,
                ];
            });

        return response()->json([
            'show_id'      => $show->id,
            'performances' => $performances,
        ]);
    }

    /**
     * The show's audition notice: display window + the HTML description
     *
     * Same record AuditionController::show() serves the web admin, limited
     * to the show the grant covers. Auditions have no submissions/contacts
     * table, so there's nothing per-auditionee to return.
     *
     * @return JsonResponse {show_id, audition: {id, display_date, end_display_date, html} | null}
     *
     * @source Database Model: Audition (reads; html from storage/app/private/audition-html)
     */
    public function auditions(Request $request): JsonResponse
    {
        /** @var Show $show */
        $show = $request->attributes->get('director_show');

        return response()->json($this->auditionPayload($show));
    }

    /**
     * Create or update the show's audition notice from the director page
     *
     * The one write the director API allows: a production-team member can
     * edit their own show's notice (the same record the admin's Auditions
     * page edits). The HTML is saved as submitted, same as from the admin.
     *
     * @return JsonResponse Same shape as auditions()
     *
     * @source Database Model: Audition (creates/updates; html to storage/app/private/audition-html)
     */
    public function updateAuditions(Request $request): JsonResponse
    {
        /** @var Show $show */
        $show = $request->attributes->get('director_show');

        $validated = $request->validate([
            'display_date'     => ['required', 'date_format:Y-m-d'],
            'end_display_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:display_date'],
            'html'             => ['nullable', 'string', 'max:200000'],
        ], [
            'end_display_date.after_or_equal' => 'The end date must be on or after the start date.',
        ]);

        $attributes = [
            'display_date'     => $validated['display_date'],
            'end_display_date' => $validated['end_display_date'],
            // '' (not null) so clearing the content actually removes it.
            'html'             => $validated['html'] ?? '',
        ];

        $audition = Audition::where('show_id', $show->id)->first();
        if ($audition) {
            $audition->updateWithHtml($attributes);
        } else {
            Audition::createWithHtml(['show_id' => $show->id] + $attributes);
        }

        return response()->json($this->auditionPayload($show));
    }

    private function auditionPayload(Show $show): array
    {
        $audition = Audition::where('show_id', $show->id)->first();

        return [
            'show_id'  => $show->id,
            'audition' => $audition ? [
                'id'               => $audition->id,
                'display_date'     => $audition->display_date,
                'end_display_date' => $audition->end_display_date,
                'html'             => $audition->html,
            ] : null,
        ];
    }

    private static function posterUrl(Show $show): ?string
    {
        // Relative, like the rest of the site (POSTER_BASE_URL): url() takes the
        // scheme/host from the request, which can come out as http:// behind
        // the host's TLS and get blocked as mixed content on iOS.
        return $show->poster ? '/api/storage/posters/' . $show->poster : null;
    }
}
