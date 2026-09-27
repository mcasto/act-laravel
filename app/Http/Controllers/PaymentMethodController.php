<?php

namespace App\Http\Controllers;

use App\Models\Angel;
use App\Models\CourseContact;
use App\Models\PatronFlexPackage;
use App\Models\PaymentMethod;
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
        ]);

        $method = PaymentMethod::create($validated);

        return response()->json(['status' => 'success', 'data' => $method]);
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
        ]);

        $method->update($validated);

        return response()->json(['status' => 'success', 'data' => $method]);
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
