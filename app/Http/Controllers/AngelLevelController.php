<?php

namespace App\Http\Controllers;

use App\Models\Angel;
use App\Models\AngelLevel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AngelLevelController extends Controller
{
    public function index()
    {
        $mostRecent = Angel::orderBy('created_at', 'desc')
            ->first()
            ->created_at;

        return [
            'levels' => AngelLevel::orderBy('min_amount', 'desc')
                ->with('angels')
                ->get(),
            'config' => json_decode(Storage::disk('local')
                ->get('angels.config.json')),
            'mostRecent' => $mostRecent->format('F Y')
        ];
    }
}
