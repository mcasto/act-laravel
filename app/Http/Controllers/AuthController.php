<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    /**
     * Handle user login and return API token.
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        $user = User::with('permissions.permissionLevel')->where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json(
                ['status' => 'error', 'message' => 'Invalid Authorization']
            );
        }

        $token = $user->createToken('api-token')->plainTextToken;

        $user['token'] = $token;

        return response()->json($user);
    }

    /**
     * Handle user logout and revoke tokens.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->tokens()->delete();
        return response()->json(['status' => 'success', 'message' => 'Logged out']);
    }

    /**
     * Return logged-in user
     */
    public function getUser(Request $request): JsonResponse
    {
        return $request->user();
    }
}
