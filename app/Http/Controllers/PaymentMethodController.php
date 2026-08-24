<?php

namespace App\Http\Controllers;

use App\Models\PaymentMethod;
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
            'user_option'        => 'required|boolean',
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
            'user_option'        => 'required|boolean',
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

        $method->delete();

        return response()->json(['status' => 'success']);
    }
}
