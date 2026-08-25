<?php

namespace App\Http\Controllers;

use App\Models\SoldOutNotificationRecipient;
use Illuminate\Http\Request;

class SoldOutNotificationRecipientController extends Controller
{
    public function index()
    {
        return response()->json(SoldOutNotificationRecipient::orderBy('name')->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
        ]);

        $recipient = SoldOutNotificationRecipient::create($validated);

        return response()->json(['status' => 'success', 'data' => $recipient]);
    }

    public function update(Request $request, $id)
    {
        $recipient = SoldOutNotificationRecipient::find($id);
        if (! $recipient) {
            return response()->json(['status' => 'error', 'message' => 'Recipient not found']);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
        ]);

        $recipient->update($validated);

        return response()->json(['status' => 'success', 'data' => $recipient]);
    }

    public function destroy($id)
    {
        $recipient = SoldOutNotificationRecipient::find($id);
        if (! $recipient) {
            return response()->json(['status' => 'error', 'message' => 'Recipient not found']);
        }

        $recipient->delete();

        return response()->json(['status' => 'success']);
    }
}
