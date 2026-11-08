<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Rejects any Sanctum token that lacks the '*' ability — i.e. desktop
 * director-app tokens (abilities ['director']) — so those can only reach the
 * /api/director/* and /api/app/* routes, never the regular admin API. Web
 * admin logins (AuthController::login) issue ['*'] tokens and pass through
 * unchanged. Applied by CheckSectionPermission to every section-gated route,
 * and directly (alias 'admin-token') to auth:sanctum routes that have no
 * section gate.
 */
class EnsureAdminToken
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($rejection = self::reject($request)) {
            return $rejection;
        }

        return $next($request);
    }

    public static function reject(Request $request): ?Response
    {
        $user = $request->user();

        if ($user && ! $user->tokenCan('*')) {
            return response()->json([
                'status'  => 'error',
                'message' => 'This token can only be used by the director app',
            ], 403);
        }

        return null;
    }
}
