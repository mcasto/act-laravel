<?php

namespace App\Http\Controllers;

use App\Models\StandardButton;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FlexPurchaseController extends Controller
{
    public function show()
    {
        $config = json_decode(Storage::disk('local')
            ->get('flex-purchase-config.json'), true);

        $config['buttons'] = StandardButton::orderBy('sort_order')->get();

        return $config;
    }

    public function update(Request $request)
    {
        logger()->info('update flex purchase config', $request->all());
    }
}
