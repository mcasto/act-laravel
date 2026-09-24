<?php

namespace App\Http\Controllers;

use App\Helpers\ActiveSeason;
use App\Helpers\TheaterSeason;
use App\Models\Angel;
use App\Models\Patron;
use App\Models\PatronFlexPackage;
use App\Models\PaymentMethod;
use App\Models\TicketSale;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PatronController extends Controller
{
    /**
     * Patron management listing for the admin — every patron, with their
     * most recent Angel level (if any) and active-season flex balance (if
     * they have a flex package for that season). "Active season" is the
     * site's manually-overridable season (see App\Helpers\ActiveSeason),
     * not strictly the real calendar-current one — so a newly-purchased
     * package under an early season flip shows up here immediately rather
     * than waiting for the real calendar to catch up. The usage-window
     * dates are derived from that same season string, not calendar-current,
     * so purchased/used stay consistent with each other.
     */
    public function index(): JsonResponse
    {
        $season = ActiveSeason::get();
        $seasonDates = TheaterSeason::datesForSeason($season);

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
                'phone' => $patron->phone,
                'founding_angel' => $patron->founding_angel,
                'is_angel' => (bool) $latestAngel,
                'angel_level' => $latestAngel?->angelLevel?->label,
                'flex_remaining' => $hasFlexThisSeason
                    ? $flexPurchased->get($patron->id, 0) - $flexUsed->get($patron->id, 0)
                    : null,
                'front_row' => $patron->front_row,
                'comments' => $patron->comments,
            ];
        });

        return response()->json($result->values());
    }

    /**
     * Manual admin create — for a patron who needs a record before any
     * ticket sale/donation/purchase would otherwise create one via
     * firstOrCreate(). email is treated as the de facto dedup key
     * everywhere else in the app (Ticket Sales, Angels, Flex), so it's
     * enforced unique here too even though the column itself has no DB
     * constraint (kept loose historically for the many firstOrCreate call
     * sites that predate this form).
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:patrons,email',
            'phone' => 'nullable|string|max:255',
            'founding_angel' => 'sometimes|boolean',
            'front_row' => 'sometimes|integer|min:0|max:3',
            'comments' => 'sometimes|nullable|string',
        ]);

        $patron = Patron::create($validated);

        return response()->json(['status' => 'success', 'patron' => $patron], 201);
    }

    /**
     * Update a patron's admin-editable fields from the Patron Management
     * page. Every field is "sometimes" so the inline front_row/comments
     * editors can keep saving independently of the full Edit Patron
     * dialog's name/email/phone/founding_angel fields.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $patron = Patron::findOrFail($id);

        $validated = $request->validate([
            'first_name' => 'sometimes|required|string|max:255',
            'last_name' => 'sometimes|required|string|max:255',
            'email' => ['sometimes', 'required', 'email', 'max:255', Rule::unique('patrons', 'email')->ignore($patron->id)],
            'phone' => 'sometimes|nullable|string|max:255',
            'founding_angel' => 'sometimes|boolean',
            'front_row' => 'sometimes|required|integer|min:0|max:3',
            'comments' => 'sometimes|nullable|string',
        ]);

        $patron->update($validated);

        return response()->json([
            'status' => 'success',
            'patron' => $patron,
            // Kept alongside `patron` for the existing inline front_row/
            // comments editors, which read these two flat keys directly.
            'front_row' => $patron->front_row,
            'comments' => $patron->comments,
        ]);
    }

    /**
     * Soft-deletes a patron — blocked if they have any ticket sales, Angel
     * donations, or Flex purchases on record, since those rows' `patron`
     * relation would silently resolve to null once the parent is
     * soft-deleted (Patron's SoftDeletes global scope excludes trashed
     * rows from eager loads too), breaking the name shown on historical
     * records. A patron with no history at all is safe to remove outright
     * — this is mainly used to clean up test patrons created while trying
     * something out, so the message names exactly what's still attached,
     * as a reminder of what to go delete first.
     */
    public function destroy(int $id): JsonResponse
    {
        $patron = Patron::findOrFail($id);

        $blockers = [];
        if ($count = $patron->ticketSales()->count()) {
            $blockers[] = "{$count} " . Str::plural('ticket sale', $count);
        }
        if ($count = Angel::where('patron_id', $patron->id)->count()) {
            $blockers[] = "{$count} " . Str::plural('Angel donation', $count);
        }
        if ($count = $patron->flexPackages()->count()) {
            $blockers[] = "{$count} " . Str::plural('Flex purchase', $count);
        }

        if ($blockers) {
            // Deliberately not a 4xx status — {status: 'error', message}
            // with a normal 200 is this app's convention for an
            // expected/handled failure, so the frontend's callApi() shows
            // it via its usual response.status === 'error' branch instead
            // of throwing (which it only shows for unhandled failures).
            return response()->json([
                'status' => 'error',
                'message' => "Can't delete — this patron still has " . implode(' and ', $blockers) . ' on record. Delete those first.',
            ]);
        }

        $patron->delete();

        return response()->json(['status' => 'success']);
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
            ->get(['id', 'first_name', 'last_name', 'email', 'phone', 'founding_angel']);

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
            'founding_angel' => $patron->founding_angel,
            'flex_packages' => $flexPackages,
        ]);
    }
}
