<?php

namespace App\Http\Controllers;

use App\Models\StandardButton;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class SupportUsController extends Controller
{
    public function index()
    {
        $config = json_decode(Storage::disk('local')
            ->get('support-us.config.json'));

        $price = $config->price;
        $config->buttons = Cache::remember('standard-buttons', 3600, fn() => StandardButton::orderBy('sort_order')->get())
            ->whereIn('key', ['paypal', 'transfer'])
            ->map(function ($rec) use ($price) {
                $rec->popupText = Cache::remember(
                    "standard-button-{$rec->key}-{$price}-support",
                    3600,
                    fn() => view("standard-buttons.{$rec->key}", [
                        'param' => "{$price}",
                        'subject' => 'Support Us'
                    ])->render()
                );
                return $rec;
            });

        return $config;
    }
}
