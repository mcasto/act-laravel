<?php

namespace App\Http\Controllers;

use App\Models\Angel;
use App\Models\CourseContact;
use App\Models\PatronFlexPackage;
use App\Models\PaymentMethod;
use App\Models\ShowPaymentRate;
use App\Models\TicketSale;
use Illuminate\Http\Request;

class PaymentMethodController extends Controller
{
    public function index()
    {
        return response()->json(PaymentMethod::orderBy('label')
            ->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'label'              => 'required|string|max:255',
            'value'              => 'required|string|max:255|unique:payment_methods,value',
            'color'              => 'nullable|string|max:20',
            'revenue_multiplier' => 'nullable|numeric|min:0|max:2',
            'per_show'           => 'sometimes|boolean',
        ]);

        $method = PaymentMethod::create($validated);
        $added = $method->per_show ? ShowPaymentRate::addMissing(null, [$method->id]) : 0;

        return response()->json(['status' => 'success', 'data' => $method, 'shows_added' => $added]);
    }

    public function update(Request $request, $id)
    {
        $method = PaymentMethod::find($id);
        if (! $method) {
            return response()->json(['status' => 'error', 'message' => 'Payment method not found']);
        }

        $validated = $request->validate([
            'label'              => 'required|string|max:255',
            'value'              => 'required|string|max:255|unique:payment_methods,value,' . $method->id,
            'color'              => 'nullable|string|max:20',
            'revenue_multiplier' => 'nullable|numeric|min:0|max:2',
            'per_show'           => 'sometimes|boolean',
        ]);

        $method->update($validated);
        $added = $method->per_show ? ShowPaymentRate::addMissing(null, [$method->id]) : 0;

        return response()->json(['status' => 'success', 'data' => $method, 'shows_added' => $added]);
    }

    /**
     * The table's Per Show toggle. Turning it on gives every show that
     * doesn't have one its own copy of the current multiplier; turning it
     * off stops new shows getting one. Either way, rates shows already have
     * are kept as they are (see ShowPaymentRate::addMissing()).
     */
    public function updatePerShow(Request $request, $id)
    {
        $method = PaymentMethod::findOrFail($id);
        $validated = $request->validate(['per_show' => 'required|boolean']);

        $method->update(['per_show' => $validated['per_show']]);
        $added = $method->per_show ? ShowPaymentRate::addMissing(null, [$method->id]) : 0;

        return response()->json(['status' => 'success', 'per_show' => $method->per_show, 'shows_added' => $added]);
    }

    public function destroy($id)
    {
        $method = PaymentMethod::find($id);
        if (! $method) {
            return response()->json(['status' => 'error', 'message' => 'Payment method not found']);
        }

        // Soft-deleting a method in use hides it from every record that
        // references it — e.g. the "flex" method going away would silently
        // zero out every patron's flex usage, since that's matched through
        // this relationship.
        $inUse = TicketSale::where('payment_method_id', $method->id)->exists()
            || Angel::where('payment_method_id', $method->id)->exists()
            || PatronFlexPackage::where('payment_method_id', $method->id)->exists()
            || CourseContact::where('payment_method_id', $method->id)->exists();
        if ($inUse) {
            return response()->json([
                'status' => 'error',
                'message' => "Can't delete \"{$method->label}\" — it's recorded on existing ticket sales, Angels, Flex purchases or class enrollments.",
            ]);
        }

        $method->delete();

        return response()->json(['status' => 'success']);
    }
}
