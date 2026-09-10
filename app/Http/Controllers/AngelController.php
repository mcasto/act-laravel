<?php

namespace App\Http\Controllers;

use App\Helpers\ActiveSeason;
use App\Mail\AngelDonationConfirmationMailer;
use App\Mail\AngelDonationMailer;
use App\Models\Angel;
use App\Models\AngelLevel;
use App\Models\PaymentMethod;
use App\Models\Patron;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class AngelController extends Controller
{
    /**
     * Public "Donate" form submission — separate from store()/update(), which
     * are the admin's manual add/edit flow. angel_level_id, donation_amount,
     * and payment_method_id are already known by the time this form is shown
     * (picked from the level's "Donate" button and payment-method selector),
     * so they arrive as given rather than being re-derived here.
     */
    public function donate(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'recognition_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'angel_level_id' => 'required|exists:angel_levels,id',
            'donation_amount' => 'required|numeric|min:0',
            'payment_method_value' => 'required|string|exists:payment_methods,value',
        ]);

        $level = AngelLevel::findOrFail($validated['angel_level_id']);
        $paymentMethod = PaymentMethod::where('value', $validated['payment_method_value'])->first();

        $patron = Patron::firstOrCreate(
            ['email' => $validated['email']],
            ['first_name' => $validated['first_name'], 'last_name' => $validated['last_name']]
        );
        $validated['patron_id'] = $patron->id;
        unset($validated['email']);

        $validated['payment_method_id'] = $paymentMethod->id;
        unset($validated['payment_method_value']);

        $validated['benefit'] = implode("\n", $level->benefits ?? []);
        $validated['season'] = ActiveSeason::get();
        // Founding-angel status is a permanent, patron-level fact granted
        // only by an admin — never self-declared on this public form, only
        // inherited if this patron already has it.
        $validated['founding_angel'] = $patron->founding_angel;

        $angel = Angel::create($validated);

        try {
            Mail::to(config('mail.admin_to.address'))
                ->send(new AngelDonationMailer($angel));
        } catch (Exception $e) {
            logger()->error('Failed to send Angel donation notification email', [
                'error' => $e->getMessage(),
                'angel_id' => $angel->id,
            ]);
        }

        try {
            Mail::to($angel->email)
                ->send(new AngelDonationConfirmationMailer($angel));
        } catch (Exception $e) {
            logger()->error('Failed to send Angel donation confirmation email', [
                'error' => $e->getMessage(),
                'angel_id' => $angel->id,
            ]);
        }

        return response()->json(['status' => 'success']);
    }

    /**
     * Admin manual add — unlike donate()/the Fixr webhook, this is how the
     * box office records a donation that came in by check, cash, or phone.
     * Every admin-added angel resolves to a patron (found-or-created by
     * email), same as the other two creation paths.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'recognition_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'angel_level_id' => 'required|exists:angel_levels,id',
            'donation_amount' => 'required|numeric|min:0',
            'payment_method_value' => 'required|string|exists:payment_methods,value',
            'season' => 'required|string',
            'founding_angel' => 'boolean',
        ]);

        $level = AngelLevel::findOrFail($validated['angel_level_id']);
        $paymentMethod = PaymentMethod::where('value', $validated['payment_method_value'])->first();

        $patron = Patron::firstOrCreate(
            ['email' => $validated['email']],
            ['first_name' => $validated['first_name'], 'last_name' => $validated['last_name']]
        );
        $validated['patron_id'] = $patron->id;
        unset($validated['email']);

        $validated['payment_method_id'] = $paymentMethod->id;
        unset($validated['payment_method_value']);

        $validated['benefit'] = implode("\n", $level->benefits ?? []);
        $validated['founding_angel'] = $this->resolveFoundingAngel($patron, $validated['founding_angel'] ?? false);

        $angel = Angel::create($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Angel created successfully',
            'data' => $angel
        ]);
    }

    public function update(Request $request, $id)
    {
        $angel = Angel::findOrFail($id);

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'recognition_name' => 'required|string|max:255',
            'angel_level_id' => 'required|exists:angel_levels,id',
            'donation_amount' => 'required|numeric|min:0',
            'payment_method_value' => 'required|string|exists:payment_methods,value',
            'season' => 'required|string',
            'founding_angel' => 'boolean',
        ]);

        $level = AngelLevel::findOrFail($validated['angel_level_id']);
        $paymentMethod = PaymentMethod::where('value', $validated['payment_method_value'])->first();

        $validated['payment_method_id'] = $paymentMethod->id;
        unset($validated['payment_method_value']);

        $validated['benefit'] = implode("\n", $level->benefits ?? []);
        // patron_id is deliberately not editable here — delete and re-add if
        // the wrong patron was linked, matching PatronController::updateFlexPackage.
        $validated['founding_angel'] = $this->resolveFoundingAngel($angel->patron, $validated['founding_angel'] ?? false);

        $angel->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Angel updated successfully',
            'data' => $angel
        ]);
    }

    /**
     * Founding-angel status is a permanent fact about the patron, granted
     * only by an admin manually checking the box (never inferred). Once a
     * patron has it, every current and future angel record for them shows
     * it locked on — the submitted value is ignored in that case. A null
     * $patron (legacy angels that predate patron_id) falls back to a plain,
     * non-sticky per-record boolean.
     */
    private function resolveFoundingAngel(?Patron $patron, bool $submitted): bool
    {
        if (! $patron) {
            return $submitted;
        }

        if ($patron->founding_angel) {
            return true;
        }

        if ($submitted) {
            $patron->update(['founding_angel' => true]);
            return true;
        }

        return false;
    }

    public function destroy($id)
    {
        $angel = Angel::findOrFail($id);
        $angel->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Angel deleted successfully'
        ]);
    }

    /**
     * Past seasons that have at least one angel on record, most recent first.
     */
    public function seasons()
    {
        $seasons = Angel::whereNotNull('season')
            ->distinct()
            ->orderByDesc('season')
            ->pluck('season');

        return response()->json($seasons);
    }

    /**
     * Angels for a given season, for the admin's by-season review table.
     */
    public function bySeason(string $season)
    {
        $angels = Angel::with(['angelLevel', 'paymentMethod'])
            ->where('season', $season)
            ->get()
            ->map(fn (Angel $angel) => [
                'id' => $angel->id,
                'recognition_name' => $angel->recognition_name,
                'angel_level' => $angel->angelLevel?->label,
                'donation_amount' => $angel->donation_amount,
                'payment_method' => $angel->paymentMethod?->label,
                'donated_at' => $angel->created_at?->toDateString(),
            ])
            ->sortByDesc('donation_amount')
            ->values();

        return response()->json($angels);
    }
}
