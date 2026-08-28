<?php

namespace App\Http\Controllers;

use App\Helpers\ActiveSeason;
use App\Models\SiteConfig;
use App\Models\StandardButton;
use App\Services\ChangeLogger;
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

    /**
     * Save the core site config fields (ticket price, sold-out target,
     * ticket/contact emails) shown at the top of the admin Site Config
     * page. The table is insert-only — this creates a new row rather than
     * updating in place, carrying forward dev_email from the latest row
     * since it isn't editable from this form.
     */
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ticket_price'    => ['required', 'integer', 'min:0'],
            'ticket_email'    => ['required', 'email'],
            'contact_email'   => ['required', 'email'],
            'sold_out_target' => ['required', 'integer', 'min:0'],
        ]);

        $old = SiteConfig::latest()->first();
        $validated['dev_email'] = $old?->dev_email;

        $config = SiteConfig::create($validated);

        ChangeLogger::record('Updated site configuration', ['old' => $old?->toArray(), 'new' => $config->toArray()]);

        return response()->json(['status' => 'success', 'config' => $config]);
    }

    public function updateButtons(Request $request)
    {
        $validated = $request->validate([
            'id'                     => 'required|integer|exists:standard_buttons,id',
            'label'                  => 'required|string',
            'key'                    => 'required|string',
            'sort_order'             => 'required|integer',
            'template'               => 'required|string',
            'confirmation_template'  => 'sometimes|nullable|string',
        ]);

        StandardButton::find($validated['id'])->update([
            'label'      => $validated['label'],
            'key'        => $validated['key'],
            'sort_order' => $validated['sort_order'],
        ]);

        $templatePath = resource_path("views/standard-buttons/{$validated['key']}.blade.php");
        $confirmationPath = resource_path("views/standard-buttons/{$validated['key']}-confirmation.blade.php");
        $oldTemplate = file_exists($templatePath) ? file_get_contents($templatePath) : null;
        $oldConfirmation = file_exists($confirmationPath) ? file_get_contents($confirmationPath) : null;

        file_put_contents($templatePath, $validated['template']);
        file_put_contents($confirmationPath, $validated['confirmation_template'] ?? '');

        ChangeLogger::record("Updated \"{$validated['label']}\" payment button template", [
            'template' => ['old' => $oldTemplate, 'new' => $validated['template']],
            'confirmation_template' => ['old' => $oldConfirmation, 'new' => $validated['confirmation_template'] ?? ''],
        ]);

        return response()->json(['status' => 'success']);
    }

    public function updateSupport(Request $request)
    {
        $request->validate([
            'price' => 'required|string',
            'fixr_label' => 'required|string',
            'fixr_link' => 'required|string',
        ]);

        $old = json_decode(Storage::disk('local')->get('support-us.config.json') ?? 'null', true);

        Storage::disk('local')
            ->put('support-us.config.json', json_encode($request->all()));

        ChangeLogger::record('Updated Support Us page config', ['old' => $old, 'new' => $request->all()]);

        return response()->json(['status' => 'success']);
    }

    /**
     * Override which season NEW Angel donation records get tagged with.
     * Deliberately separate from TheaterSeason's calendar calculation used
     * by shows and Flex-ticket redemption — see App\Helpers\ActiveSeason.
     */
    public function updateSeason(Request $request)
    {
        $validated = $request->validate([
            'season' => 'required|string|regex:/^\d{2}-\d{2}$/',
        ]);

        $old = ActiveSeason::get();
        ActiveSeason::set($validated['season']);

        ChangeLogger::record('Updated active Angel season', ['old' => $old, 'new' => $validated['season']]);

        return response()->json(['status' => 'success']);
    }

    public function updateAngels(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string',
            'note' => 'required|string',
            'fixr_label' => 'required|string',
        ]);

        $old = json_decode(Storage::disk('local')->get('angels.config.json') ?? 'null', true);

        Storage::disk('local')
            ->put('angels.config.json', json_encode($validated));

        ChangeLogger::record('Updated Angels page config', ['old' => $old, 'new' => $validated]);

        return response()->json(['status' => 'success']);
    }

    public function updateFlex(Request $request)
    {
        $request->validate([
            'title'              => 'required|string',
            'image'              => 'required|string',
            'price'              => 'required|string',
            'num_tickets'        => 'required|integer|min:1',
            'subtitle'           => 'required|string',
            'body'               => 'required|string',
            'confirmation_body'  => 'sometimes|nullable|string',
            'fixr'               => 'required|array',
            'fixr.link'          => 'required|url',
            'fixr.label'         => 'required|string',
            'start_date'         => 'required|date',
            'end_date'           => 'required|date|after:start_date',
        ]);

        $old = json_decode(Storage::disk('local')->get('flex-purchase-config.json') ?? 'null', true);

        Storage::disk('local')
            ->put('flex-purchase-config.json', json_encode($request->all()));

        ChangeLogger::record('Updated Flex Tickets page config', ['old' => $old, 'new' => $request->all()]);

        return response()->json(['status' => 'success']);
    }
}
