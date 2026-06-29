<?php

namespace App\Http\Controllers;

use App\Models\SiteConfig;
use App\Models\StandardButton;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class SiteConfigController extends Controller
{
    /**
     * Display the most recent site configuration
     *
     * Retrieves the latest site configuration record along with all
     * standard buttons ordered by their sort order. The configuration
     * table is insert-only, so historical configurations are retained.
     *
     * @return JsonResponse Latest config and all buttons
     *
     * @source Database Models:
     *   - SiteConfig (reads latest)
     *   - StandardButton (reads all ordered by sort_order)
     */
    public function show(): JsonResponse
    {
        $config = Cache::remember('site-config', 3600, fn() => SiteConfig::latest()->first());
        $buttons = Cache::remember('standard-buttons', 3600, fn() => StandardButton::orderBy('sort_order')->get());

        return response()->json(['config' => $config, 'buttons' => $buttons]);
    }

    public function updateButtons(Request $request)
    {
        $validated = $request->validate([
            'id'         => 'required|integer|exists:standard_buttons,id',
            'label'      => 'required|string',
            'key'        => 'required|string',
            'sort_order' => 'required|integer',
            'template'   => 'required|string',
        ]);

        StandardButton::find($validated['id'])->update([
            'label'      => $validated['label'],
            'key'        => $validated['key'],
            'sort_order' => $validated['sort_order'],
        ]);

        file_put_contents(
            resource_path("views/standard-buttons/{$validated['key']}.blade.php"),
            $validated['template']
        );

        return response()->json(['status' => 'success']);
    }

    public function updateSupport(Request $request)
    {
        $request->validate([
            'price' => 'required|string',
            'fixr_label' => 'required|string',
            'fixr_link' => 'required|string',
        ]);

        Storage::disk('local')
            ->put('support-us.config.json', json_encode($request->all()));

        return response()->json(['status' => 'success']);
    }

    public function updateFlex(Request $request)
    {
        $request->validate([
            'title'        => 'required|string',
            'image'        => 'required|string',
            'price'        => 'required|string',
            'num_tickets'  => 'required|integer|min:1',
            'subtitle'     => 'required|string',
            'body'         => 'required|string',
            'fixr'         => 'required|array',
            'fixr.link'    => 'required|url',
            'fixr.label'   => 'required|string',
            'start_date'   => 'required|date',
            'end_date'     => 'required|date|after:start_date',
        ]);

        Storage::disk('local')
            ->put('flex-purchase-config.json', json_encode($request->all()));

        return response()->json(['status' => 'success']);
    }
}
