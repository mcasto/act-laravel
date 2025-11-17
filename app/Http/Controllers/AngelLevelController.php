<?php

namespace App\Http\Controllers;

use App\Models\Angel;
use App\Models\AngelLevel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AngelLevelController extends Controller
{
    public function index()
    {
        $mostRecentAngel = Angel::orderBy('created_at', 'desc')->first();

        $mostRecent = $mostRecentAngel
            ? $mostRecentAngel->created_at->format('F Y')
            : null;

        return [
            'levels' => AngelLevel::orderBy('min_amount', 'desc')
                ->with('angels')
                ->get(),
            'config' => json_decode(Storage::disk('local')
                ->get('angels.config.json')),
            'mostRecent' => $mostRecent
        ];
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'label' => 'required|string|max:255',
            'min_amount' => 'required|integer|min:0'
        ]);

        $level = AngelLevel::create($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Angel level created successfully',
            'data' => $level
        ]);
    }

    public function update(Request $request, $id)
    {
        $level = AngelLevel::findOrFail($id);

        $validated = $request->validate([
            'label' => 'required|string|max:255',
            'min_amount' => 'required|integer|min:0'
        ]);

        $level->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Angel level updated successfully',
            'data' => $level
        ]);
    }

    public function destroy($id)
    {
        $level = AngelLevel::findOrFail($id);

        // The cascade delete in the migration will handle deleting associated angels
        $level->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Angel level deleted successfully'
        ]);
    }
}
