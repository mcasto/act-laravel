<?php

namespace App\Http\Controllers;

use App\Helpers\TheaterSeason;
use App\Models\Angel;
use App\Models\Patron;
use App\Models\PatronFlexPackage;
use App\Models\TicketSale;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PatronController extends Controller
{
    /**
     * Patron management listing for the admin — every patron, with their
     * most recent Angel level (if any) and current-season flex balance
     * (if they have a flex package this season).
     */
    public function index(): JsonResponse
    {
        $season = TheaterSeason::currentString();
        $seasonDates = TheaterSeason::currentDates();

        $patrons = Patron::orderBy('last_name')->orderBy('first_name')->get();

        $latestAngelByPatron = Angel::whereNotNull('patron_id')
            ->with('angelLevel')
            ->orderByDesc('created_at')
            ->get()
            ->groupBy('patron_id')
            ->map(fn ($angels) => $angels->first());

        $flexPurchased = PatronFlexPackage::where('season', $season)
            ->get()
            ->groupBy('patron_id')
            ->map(fn ($pkgs) => $pkgs->sum('tickets_purchased'));

        $flexUsed = TicketSale::whereHas('paymentMethod', fn ($q) => $q->where('value', 'flex'))
            ->whereHas('performance', fn ($q) => $q->whereBetween('date', [$seasonDates['start'], $seasonDates['end']]))
            ->get()
            ->groupBy('patron_id')
            ->map(fn ($sales) => $sales->sum('quantity'));

        $result = $patrons->map(function (Patron $patron) use ($latestAngelByPatron, $flexPurchased, $flexUsed) {
            $latestAngel = $latestAngelByPatron->get($patron->id);
            $hasFlexThisSeason = $flexPurchased->has($patron->id);

            return [
                'id' => $patron->id,
                'first_name' => $patron->first_name,
                'last_name' => $patron->last_name,
                'email' => $patron->email,
                'is_angel' => (bool) $latestAngel,
                'angel_level' => $latestAngel?->angelLevel?->label,
                'flex_remaining' => $hasFlexThisSeason
                    ? $flexPurchased->get($patron->id, 0) - $flexUsed->get($patron->id, 0)
                    : null,
            ];
        });

        return response()->json($result->values());
    }

    /**
     * Full flex-package history for one patron, across every season —
     * used by the admin Patron Management "flex remaining" drill-down.
     */
    public function flexHistory(int $id): JsonResponse
    {
        $patron = Patron::findOrFail($id);

        $seasons = PatronFlexPackage::where('patron_id', $id)
            ->get()
            ->groupBy('season')
            ->map(function ($pkgs, $season) use ($patron) {
                $purchased = $pkgs->sum('tickets_purchased');
                $dates = TheaterSeason::datesForSeason($season);

                $usage = TicketSale::where('patron_id', $patron->id)
                    ->whereHas('paymentMethod', fn ($q) => $q->where('value', 'flex'))
                    ->whereHas('performance', fn ($q) => $q->whereBetween('date', [$dates['start'], $dates['end']]))
                    ->with('performance.show')
                    ->get()
                    ->map(fn ($sale) => [
                        'show' => $sale->performance?->show?->name,
                        'date' => $sale->performance?->date,
                        'quantity' => $sale->quantity,
                    ])
                    ->values();

                return [
                    'season' => $season,
                    'tickets_purchased' => $purchased,
                    'tickets_used' => $usage->sum('quantity'),
                    'tickets_remaining' => $purchased - $usage->sum('quantity'),
                    'usage' => $usage,
                ];
            })
            ->sortByDesc('season')
            ->values();

        return response()->json([
            'patron' => [
                'id' => $patron->id,
                'name' => trim("{$patron->first_name} {$patron->last_name}"),
                'email' => $patron->email,
            ],
            'seasons' => $seasons,
        ]);
    }

    public function flexPurchases(): JsonResponse
    {
        $purchases = PatronFlexPackage::with('patron')
            ->get()
            ->groupBy(fn ($pkg) => "{$pkg->patron_id}|{$pkg->season}")
            ->map(function ($group) {
                $first = $group->first();
                $ticketsPurchased = $group->sum('tickets_purchased');
                $ticketsUsed = $ticketsPurchased - $first->ticketsRemaining();

                return [
                    'season'            => $first->season,
                    'first_name'        => $first->patron->first_name,
                    'last_name'         => $first->patron->last_name,
                    'email'             => $first->patron->email,
                    'tickets_purchased' => $ticketsPurchased,
                    'tickets_used'      => $ticketsUsed,
                ];
            })
            ->sortByDesc('season')
            ->values();

        return response()->json($purchases);
    }

    public function lookup(Request $request): JsonResponse
    {
        $request->validate(['email' => 'required|email']);

        $patron = Patron::where('email', $request->email)->first();

        if (! $patron) {
            return response()->json(null, 404);
        }

        $season = TheaterSeason::currentString();
        $seasonDates = TheaterSeason::currentDates();
        $seasonStart = $seasonDates['start'];
        $seasonEnd   = $seasonDates['end'];

        $flexUsage = TicketSale::where('patron_id', $patron->id)
            ->whereHas('paymentMethod', fn($q) => $q->where('value', 'flex'))
            ->whereHas('performance', fn($q) => $q->whereBetween('date', [$seasonStart, $seasonEnd]))
            ->with('performance.show')
            ->get()
            ->map(fn($sale) => [
                'show'      => $sale->performance?->show?->name,
                'date'      => $sale->performance?->date,
                'quantity'  => $sale->quantity,
            ]);

        $flexPackages = $patron->flexPackages()
            ->where('season', $season)
            ->get()
            ->map(fn($pkg) => [
                'id'                => $pkg->id,
                'season'            => $pkg->season,
                'tickets_purchased' => $pkg->tickets_purchased,
                'tickets_remaining' => $pkg->ticketsRemaining(),
                'purchased_at'      => $pkg->purchased_at,
                'usage'             => $flexUsage,
            ]);

        return response()->json([
            'email' => $patron->email,
            'last_name'    => $patron->last_name,
            'first_name'   => $patron->first_name,
            'phone'        => $patron->phone,
            'flex_packages' => $flexPackages,
        ]);
    }
}
