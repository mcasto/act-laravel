<?php

namespace App\Http\Controllers;

use App\Http\Middleware\EnsureDirectorShowAccess;
use App\Models\Performance;
use App\Models\Show;
use App\Models\ShowAccessGrant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API for the director page (/director, a PWA). Read-only: ticket-sales
 * aggregates for the shows the caller has a grant for. Every response is
 * built field-by-field (never a raw model) so nothing beyond what's listed
 * here can leak — in particular no patron, ticket-sale, or comp-recipient
 * data, only aggregates. Per-show routes are gated by EnsureDirectorShowAccess,
 * which supplies the Show via the 'director_show' request attribute.
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
     * @return JsonResponse {show_id, performances: [{id, date, start_time, reserved, seats_taken, sold_out_target, remaining, sold_out}]}
     *
     * @source Database Models: Performance (reads), TicketSale/CompTicket (counts via Performance::seatSummariesForShow)
     */
    public function sales(Request $request): JsonResponse
    {
        /** @var Show $show */
        $show = $request->attributes->get('director_show');

        return response()->json([
            'show_id'      => $show->id,
            'performances' => Performance::seatSummariesForShow($show->id),
        ]);
    }

    private static function posterUrl(Show $show): ?string
    {
        // Relative, like the rest of the site (POSTER_BASE_URL): url() takes the
        // scheme/host from the request, which can come out as http:// behind
        // the host's TLS and get blocked as mixed content on iOS.
        return $show->poster ? '/storage/posters/' . $show->poster : null;
    }
}
