<?php

namespace App\Http\Controllers;

use App\Helpers\TheaterSeason;
use App\Models\Patron;
use App\Models\TicketSale;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PatronController extends Controller
{
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
