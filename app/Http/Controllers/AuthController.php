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
     * Handle user login and return API token
     *
     * Validates user credentials, generates a Sanctum API token,
     * and returns the user data with permissions and token.
     *
     * @param Request $request Contains email and password
     * @return JsonResponse User data with token or error message
     *
     * @source Database Model: User (reads with permissions.permissionLevel relationship)
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
     * Refresh user permissions
     *
     * Retrieves the latest permission data for the authenticated user.
     * Useful when permissions have been updated and need to be reloaded.
     *
     * @param Request $request The authenticated request
     * @return \Illuminate\Database\Eloquent\Collection User's current permissions
     *
     * @source Database Model: User (reads with permissions.permissionLevel relationship)
     */
    public function refreshPermissions(Request $request)
    {
        $user = User::with('permissions.permissionLevel')->find($request->user()->id);
        return $user->permissions;
    }

    /**
     * Handle user logout and revoke tokens
     *
     * Deletes all API tokens for the authenticated user, effectively
     * logging them out from all sessions.
     *
     * @param Request $request The authenticated request
     * @return JsonResponse Success message
     *
     * @source Database: User tokens (deletes)
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->tokens()->delete();
        return response()->json(['status' => 'success', 'message' => 'Logged out']);
    }

    /**
     * Return the authenticated user
     *
     * Simple passthrough that returns the currently authenticated user
     * from the request object.
     *
     * @param Request $request The authenticated request
     * @return JsonResponse The authenticated user
     *
     * @source None (returns authenticated user from request)
     */
    public function getUser(Request $request): JsonResponse
    {
        return $request->user();
    }
}
