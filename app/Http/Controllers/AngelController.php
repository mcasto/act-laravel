<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AngelController extends Controller
{
    /**
     * Retrieve angel configuration from file storage
     *
     * @return string JSON content from angel.config.json
     *
     * @source File: storage/app/angel.config.json
     */
    public function index()
    {
        return Storage::disk('local')
            ->get('angel.config.json');
    }
}
