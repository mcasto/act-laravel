<?php

namespace App\Http\Controllers;

use App\Models\Patron;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PatronController extends Controller
{
    public function lookup(Request $request): JsonResponse
    {
        $request->validate(['email' => 'required|email']);

        $patron = Patron::where('email', $request->email)->first();

        if (! $patron) {
            return response()->json(null, 404);
        }

        return response()->json([
            'last_name' => $patron->last_name,
            'first_name' => $patron->first_name,
            'phone' => $patron->phone,
        ]);
    }
}
