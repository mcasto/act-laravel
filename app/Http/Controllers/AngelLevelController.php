<?php

namespace App\Http\Controllers;

use App\Helpers\ActiveSeason;
use App\Models\Angel;
use App\Models\AngelLevel;
use App\Services\ChangeLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AngelLevelController extends Controller
{
    public function index()
    {
        $season = ActiveSeason::get();

        $mostRecentAngel = Angel::where('season', $season)
            ->orderBy('created_at', 'desc')
            ->first();

        $mostRecent = $mostRecentAngel
            ? $mostRecentAngel->created_at->format('F Y')
            : null;

        return [
            'header' => view('angel-header')->render(),
            'levels' => AngelLevel::orderBy('min_amount', 'desc')
                ->with(['angels' => fn ($query) => $query->where('season', $season)])
                ->get(),
            'config' => json_decode(Storage::disk('local')
                ->get('angels.config.json')),
            'mostRecent' => $mostRecent
        ];
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'label'      => 'required|string|max:255',
            'min_amount' => 'required|integer|min:0',
            'fixr_link'  => 'nullable|string',
            'benefits'   => 'nullable|array',
            'benefits.*' => 'string',
        ]);

        $benefits = $validated['benefits'] ?? [];
        unset($validated['benefits']);

        $level = AngelLevel::create($validated);
        $this->saveBenefits($level->id, $benefits);

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
            'label'      => 'required|string|max:255',
            'min_amount' => 'required|integer|min:0',
            'fixr_link'  => 'nullable|string',
            'benefits'   => 'nullable|array',
            'benefits.*' => 'string',
        ]);

        $benefits = $validated['benefits'] ?? [];
        unset($validated['benefits']);

        $level->update($validated);
        $this->saveBenefits($level->id, $benefits);

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

        Storage::disk('local')->delete("angel-config/{$id}.json");

        return response()->json([
            'status' => 'success',
            'message' => 'Angel level deleted successfully'
        ]);
    }

    /**
     * Persist an angel level's benefits list, filtering out blank entries
     * (the frontend list editor can leave an empty row while it's being
     * typed into). Stored as a plain JSON array — see AngelLevel::benefits().
     */
    private function saveBenefits(int $levelId, array $benefits): void
    {
        $benefits = array_values(array_filter(
            array_map('trim', $benefits),
            fn ($benefit) => $benefit !== ''
        ));

        $path = "angel-config/{$levelId}.json";
        $old = json_decode(Storage::disk('local')->get($path) ?? 'null', true);

        Storage::disk('local')->makeDirectory('angel-config');
        Storage::disk('local')->put($path, json_encode($benefits, JSON_PRETTY_PRINT));

        if ($old !== $benefits) {
            ChangeLogger::record("Updated benefits list for Angel level #{$levelId}", ['old' => $old, 'new' => $benefits]);
        }
    }
}
