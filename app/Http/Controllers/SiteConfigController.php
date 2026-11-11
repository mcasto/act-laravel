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
        $buttons = StandardButton::forPatrons();

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
            'nearly_sold_out_threshold' => ['required', 'integer', 'min:0'],
        ]);

        $old = SiteConfig::latest()->first();
        $validated['dev_email'] = $old?->dev_email;

        $config = SiteConfig::create($validated);

        ChangeLogger::record('Updated site configuration', ['old' => $old?->toArray(), 'new' => $config->toArray()]);

        return response()->json(['status' => 'success', 'config' => $config]);
    }

    /**
     * The editable content sections of the admin Site Config page:
     * Support Us, Angels and the active season. (Patron purchase buttons
     * live on the Payment Methods page — see StandardButtonsController.)
     */
    public function content(): JsonResponse
    {
        return response()->json([
            'support' => json_decode(Storage::disk('local')->get('support-us.config.json')),
            'angels'  => json_decode(Storage::disk('local')->get('angels.config.json')),
            'season'  => ActiveSeason::get(),
        ]);
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
     * Override which season NEW Angel donation records and NEW Flex package
     * purchases get tagged with. Deliberately separate from TheaterSeason's
     * calendar calculation used by shows and Flex-ticket redemption — see
     * App\Helpers\ActiveSeason.
     */
    public function updateSeason(Request $request)
    {
        $validated = $request->validate([
            'season' => 'required|string|regex:/^\d{2}-\d{2}$/',
        ]);

        $old = ActiveSeason::get();
        ActiveSeason::set($validated['season']);

        ChangeLogger::record('Updated active season', ['old' => $old, 'new' => $validated['season']]);

        return response()->json(['status' => 'success']);
    }

    /**
     * The active season override, for admin forms that need to default a
     * new record to it (e.g. AdminFlexPurchases.vue's Add Purchase dialog) —
     * auth:sanctum only, not gated by 'site-config', since this is shared
     * reference data for other sections, not the Site Config screen itself.
     */
    public function activeSeason(): JsonResponse
    {
        return response()->json(['season' => ActiveSeason::get()]);
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

}
