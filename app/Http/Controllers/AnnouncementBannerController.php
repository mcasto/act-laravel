<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AnnouncementBannerController extends Controller
{
    public function show()
    {
        if (Storage::disk('local')
            ->exists('announcement-banner.json')
        ) {
            return  Storage::disk('local')
                ->get('announcement-banner.json');
        } else {
            return ['status' => false];
        }
    }

    public function update(Request $request)
    {
        Storage::disk('local')
            ->put('announcement-banner.json', json_encode($request->all()));
    }
}
