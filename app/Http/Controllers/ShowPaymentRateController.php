<?php

namespace App\Http\Controllers;

use App\Models\Show;
use App\Models\ShowPaymentRate;
use App\Services\ChangeLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * A show's own revenue multipliers (Shows → gear menu → Revenue
 * Multipliers). Rows are added automatically for per-show payment methods
 * (ShowPaymentRate::addMissing()); here an admin can override a show's rate.
 */
class ShowPaymentRateController extends Controller
{
    public function index(int $showId): JsonResponse
    {
        $show = Show::findOrFail($showId);

        $rates = $show->paymentRates()
            ->with('paymentMethod:id,label,value,revenue_multiplier,per_show')
            ->get()
            ->filter(fn (ShowPaymentRate $r) => $r->paymentMethod)
            ->sortBy(fn (ShowPaymentRate $r) => strtolower($r->paymentMethod->label))
            ->values()
            ->map(fn (ShowPaymentRate $r) => [
                'payment_method_id'  => $r->payment_method_id,
                'label'              => $r->paymentMethod->label,
                'revenue_multiplier' => $r->revenue_multiplier,
                'global_multiplier'  => $r->paymentMethod->revenue_multiplier !== null
                    ? (float) $r->paymentMethod->revenue_multiplier
                    : null,
            ]);

        return response()->json($rates);
    }

    /** Overrides this show's multiplier for one payment method. */
    public function update(Request $request, int $showId, int $methodId): JsonResponse
    {
        $validated = $request->validate([
            'revenue_multiplier' => 'required|numeric|min:0|max:2',
        ]);

        $rate = ShowPaymentRate::where('show_id', $showId)
            ->where('payment_method_id', $methodId)
            ->firstOrFail();

        $old = $rate->revenue_multiplier;
        $rate->update(['revenue_multiplier' => $validated['revenue_multiplier']]);

        ChangeLogger::record(
            "Changed {$rate->paymentMethod?->label} revenue multiplier for {$rate->show?->name}",
            ['old' => $old, 'new' => $rate->revenue_multiplier]
        );

        return response()->json(['status' => 'success', 'revenue_multiplier' => $rate->revenue_multiplier]);
    }
}
