<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VolunteerController extends Controller
{
    public function create(Request $request): JsonResponse
    {
        return response()->json($request);
    }
}
