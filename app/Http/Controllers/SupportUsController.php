<?php

namespace App\Http\Controllers;

use App\Models\StandardButton;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SupportUsController extends Controller
{
    public function index()
    {
        $config = json_decode(Storage::disk('local')
            ->get('support-us.config.json'));

        $config->buttons = StandardButton::orderBy('sort_order')
            ->get()
            ->map(function ($rec) use ($config) {
                $price = $config->price;
                $rec->popupText = view("standard-buttons.{$rec->key}", [
                    'param' => "{$price}",
                    'subject' => 'Support Us'
                ])->render();

                return $rec;
            });

        return $config;
    }
}
