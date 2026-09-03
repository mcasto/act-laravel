<?php

namespace App\Http\Controllers;

use App\Helpers\TheaterSeason;
use App\Models\Angel;
use App\Models\Patron;
use App\Models\PatronFlexPackage;
use App\Models\PaymentMethod;
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

    /**
     * One row per individual purchase transaction (not aggregated by
     * patron+season) — this is the editable unit for the admin Flex
     * Purchases CRUD page. Per-patron remaining balance is shown elsewhere
     * (index()/flexHistory() above), so it isn't duplicated here.
     */
    public function flexPurchases(): JsonResponse
    {
        $purchases = PatronFlexPackage::with('patron', 'paymentMethod')
            ->orderByDesc('purchased_at')
            ->get()
            ->map(fn (PatronFlexPackage $pkg) => [
                'id'                => $pkg->id,
                'patron_id'         => $pkg->patron_id,
                'first_name'        => $pkg->patron->first_name,
                'last_name'         => $pkg->patron->last_name,
                'email'             => $pkg->patron->email,
                'season'            => $pkg->season,
                'tickets_purchased' => $pkg->tickets_purchased,
                'payment_method'    => $pkg->paymentMethod ? [
                    'id' => $pkg->paymentMethod->id,
                    'value' => $pkg->paymentMethod->value,
                    'label' => $pkg->paymentMethod->label,
                ] : null,
                'purchased_at'      => $pkg->purchased_at,
            ]);

        return response()->json($purchases->values());
    }

    /**
     * Manual admin entry — for backfilling/reconciling purchases made
     * outside the live flow (e.g. matching up FixR payouts, cash sales).
     * Deliberately sends no email, unlike FlexPurchaseController::store()
     * and the FixR webhook branch, which record genuine live purchases.
     */
    public function storeFlexPackage(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:255',
            'season' => 'required|string|max:255',
            'tickets_purchased' => 'required|integer|min:1',
            'payment_method_value' => 'required|string|exists:payment_methods,value',
        ]);

        $paymentMethod = PaymentMethod::where('value', $validated['payment_method_value'])->first();

        $patron = Patron::firstOrCreate(
            ['email' => $validated['email']],
            [
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'phone' => $validated['phone'] ?? null,
            ]
        );

        $package = PatronFlexPackage::create([
            'patron_id' => $patron->id,
            'season' => $validated['season'],
            'tickets_purchased' => $validated['tickets_purchased'],
            'payment_method_id' => $paymentMethod->id,
            'purchased_at' => now(),
        ]);

        $package->load('patron', 'paymentMethod');

        return response()->json(['status' => 'success', 'package' => $package]);
    }

    /**
     * patron_id is deliberately not editable here — if the wrong patron
     * was picked, delete and re-add rather than reassign whose
     * entitlement the row represents.
     */
    public function updateFlexPackage(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'season' => 'required|string|max:255',
            'tickets_purchased' => 'required|integer|min:1',
            'payment_method_value' => 'required|string|exists:payment_methods,value',
        ]);

        $paymentMethod = PaymentMethod::where('value', $validated['payment_method_value'])->first();

        $package = PatronFlexPackage::findOrFail($id);
        $package->update([
            'season' => $validated['season'],
            'tickets_purchased' => $validated['tickets_purchased'],
            'payment_method_id' => $paymentMethod->id,
        ]);

        $package->load('patron', 'paymentMethod');

        return response()->json(['status' => 'success', 'package' => $package]);
    }

    public function destroyFlexPackage(int $id): JsonResponse
    {
        PatronFlexPackage::findOrFail($id)->delete();

        return response()->json(['status' => 'success']);
    }

    /**
     * Typeahead patron search by name or email — auth:sanctum only, not
     * gated by the 'patrons' permission, since this is shared lookup data
     * used by other admin sections' "find or create a patron" forms
     * (e.g. AdminFlexPurchases.vue's Add Purchase dialog), not the Patron
     * Management screen itself.
     */
    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate(['q' => 'required|string|min:2']);
        $q = $validated['q'];

        $patrons = Patron::where('first_name', 'like', "%{$q}%")
            ->orWhere('last_name', 'like', "%{$q}%")
            ->orWhere('email', 'like', "%{$q}%")
            ->orWhereRaw("CONCAT(first_name, ' ', last_name) like ?", ["%{$q}%"])
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->limit(15)
            ->get(['id', 'first_name', 'last_name', 'email', 'phone']);

        return response()->json($patrons);
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
