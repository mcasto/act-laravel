<?php

namespace App\Http\Controllers;

use App\Models\SiteConfig;
use App\Models\StandardButton;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class StandardButtonsController extends Controller
{
    /**
     * Get standard buttons by type
     *
     * Retrieves all standard buttons of a specific type (e.g., 'show', 'flex')
     * ordered by their sort_order field. Standard buttons are reusable
     * call-to-action buttons used throughout the application.
     *
     * @param string $type The button type to filter by
     * @return \Illuminate\Database\Eloquent\Collection Buttons of specified type
     *
     * @source Database Model: StandardButton (reads filtered by type)
     */
    public function index()
    {
        // need flex config
        // need suport us config
        // need standard buttons views

        $siteConfig = SiteConfig::latest()->first();

        return response()->json([
            'support' => json_decode(Storage::disk('local')
                ->get('support-us.config.json')),
            'flex' => json_decode(Storage::disk('local')
                ->get('flex-purchase-config.json')),
            'buttons' => StandardButton::orderBy('sort_order')
                ->get()
                ->map(function ($rec) {
                    $rec->template =  file_get_contents(resource_path("views/standard-buttons/{$rec->key}.blade.php"));

                    return $rec;
                })
        ]);
    }
}
