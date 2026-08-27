<?php

namespace App\Http\Controllers;

use App\Helpers\ActiveSeason;
use App\Mail\AngelDonationMailer;
use App\Models\Angel;
use App\Models\AngelLevel;
use App\Models\PaymentMethod;
use App\Models\Patron;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

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
        // Founding-angel status is permanent for a given donor and never
        // self-declared on this public form — only inherited from a past
        // record under this name, if one exists.
        $validated['founding_angel'] = Angel::wasFoundingAngel($validated['first_name'], $validated['last_name']);

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

        return response()->json(['status' => 'success']);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'recognition_name' => 'required|string|max:255',
            'angel_level_id' => 'required|exists:angel_levels,id',
            'founding_angel' => 'boolean'
        ]);

        // Founding-angel status is permanent for a given donor — if any past
        // record under this name has it, this new one inherits it too,
        // regardless of what was submitted.
        $validated['founding_angel'] = Angel::wasFoundingAngel($validated['first_name'], $validated['last_name'])
            || ($validated['founding_angel'] ?? false);

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
            'founding_angel' => 'boolean'
        ]);

        $angel->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Angel updated successfully',
            'data' => $angel
        ]);
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
