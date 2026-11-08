<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AnnouncementBannerController extends Controller
{
    /**
     * Retrieve announcement banner configuration
     *
     * Checks if the announcement banner configuration file exists and returns it,
     * otherwise returns a status indicating no banner is configured.
     *
     * @return string|array JSON content from file or status array
     *
     * @source File: storage/app/announcement-banner.json
     */
    public function show()
    {
        $banner = json_decode(
            Storage::disk('local')->get('announcement-banner.json') ?? '',
            true
        );

        // Missing, empty, or unparseable file all mean "no banner" — always
        // answer with real JSON so the frontend's parse can't choke on it.
        return is_array($banner) ? $banner : ['status' => false];
    }

    /**
     * Update announcement banner configuration
     *
     * Saves the entire request payload as JSON to the announcement banner file.
     *
     * @param Request $request The HTTP request containing banner configuration
     * @return array Status array indicating success
     *
     * @source File: storage/app/announcement-banner.json (writes)
     */
    public function update(Request $request)
    {
        Storage::disk('local')
            ->put('announcement-banner.json', json_encode($request->all()));

        return ['status' => 'success'];
    }
}
