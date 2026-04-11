<?php

namespace App\Http\Controllers;

use App\Models\Patron;
use App\Models\TicketSale;
use Carbon\Carbon;
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

        $season = $this->currentSeasonString();
        [$startYear] = explode('-', $season);
        $startYear = (int) ('20' . $startYear);
        $seasonStart = "{$startYear}-10-01";
        $seasonEnd   = ($startYear + 1) . '-08-31';

        $flexUsage = TicketSale::where('patron_id', $patron->id)
            ->whereHas('paymentMethod', fn ($q) => $q->where('value', 'flex'))
            ->whereHas('performance', fn ($q) => $q->whereBetween('date', [$seasonStart, $seasonEnd]))
            ->with('performance.show')
            ->get()
            ->map(fn ($sale) => [
                'show'      => $sale->performance?->show?->name,
                'date'      => $sale->performance?->date,
                'quantity'  => $sale->quantity,
            ]);

        $flexPackages = $patron->flexPackages()
            ->where('season', $season)
            ->get()
            ->map(fn ($pkg) => [
                'id'                => $pkg->id,
                'season'            => $pkg->season,
                'tickets_purchased' => $pkg->tickets_purchased,
                'tickets_remaining' => $pkg->ticketsRemaining(),
                'purchased_at'      => $pkg->purchased_at,
                'usage'             => $flexUsage,
            ]);

        return response()->json([
            'last_name'    => $patron->last_name,
            'first_name'   => $patron->first_name,
            'phone'        => $patron->phone,
            'flex_packages' => $flexPackages,
        ]);
    }

    private function currentSeasonString(): string
    {
        $now = Carbon::now();
        $startYear = $now->month >= 10 ? $now->year : $now->year - 1;

        return substr($startYear, -2) . '-' . substr($startYear + 1, -2);
    }
}
