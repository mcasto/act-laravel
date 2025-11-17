<?php

namespace App\Http\Controllers;

use App\Models\Angel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AngelController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'angel_level_id' => 'required|exists:angel_levels,id',
            'founding_angel' => 'boolean'
        ]);

        // Ensure founding_angel defaults to false if not provided
        $validated['founding_angel'] = $validated['founding_angel'] ?? false;

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
            'name' => 'required|string|max:255',
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
}
