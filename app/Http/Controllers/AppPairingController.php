<?php

namespace App\Http\Controllers;

use App\Models\AppPairingCode;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Endpoints the desktop director app itself calls to pair / unpair. Setup
 * codes are issued by an admin via UserController::createAppPairingCode().
 */
class AppPairingController extends Controller
{
    /**
     * Pair a computer: burn a setup code, return a director-only token
     *
     * Public (the app has no credentials yet) and throttled at the route.
     * The token carries only the 'director' ability, so EnsureAdminToken /
     * CheckSectionPermission keep it off every regular admin route, and is
     * named after the device so it can be listed and revoked individually.
     *
     * @return JsonResponse {token, user: {id, name}} or 422 for a bad code
     *
     * @source Database Model: AppPairingCode (burns), personal_access_tokens (creates)
     */
    public function pair(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code'        => ['required', 'string', 'max:32'],
            'device_name' => ['required', 'string', 'max:100'],
        ]);

        $pairing = AppPairingCode::redeem($validated['code']);
        $user    = $pairing ? User::find($pairing->user_id) : null;

        if (! $user) {
            return response()->json([
                'status'  => 'error',
                'message' => 'That setup code is invalid, expired, or already used',
            ], 422);
        }

        $token = $user->createToken(
            trim($validated['device_name']),
            [User::APP_TOKEN_ABILITY],
        )->plainTextToken;

        return response()->json([
            'token' => $token,
            'user'  => ['id' => $user->id, 'name' => $user->name],
        ]);
    }

    /**
     * Unpair this computer: delete the calling app token only
     *
     * @source Database: personal_access_tokens (deletes current token)
     */
    public function unpair(Request $request): JsonResponse
    {
        $token = $request->user()->currentAccessToken();

        // tokenCan('director') is also true for '*' tokens, so test for
        // the absence of '*' instead — web logins must use /logout.
        if ($request->user()->tokenCan('*') || ! method_exists($token, 'delete')) {
            return response()->json(['status' => 'error', 'message' => 'Not a director-app token'], 403);
        }

        $token->delete();

        return response()->json(['status' => 'success']);
    }
}
