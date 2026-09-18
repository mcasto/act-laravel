<?php

namespace App\Http\Middleware;

use App\Models\UserPermission;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gates an admin API route by the authenticated user's access level for a
 * given section — usage: ->middleware('permission:patrons'). Mirrors
 * frontend/src/assets/get-permission-level.js exactly, so a section that's
 * hidden/read-only in the UI is also actually blocked here, not just
 * hidden. Not used for the "users" section, which has its own bespoke
 * self-edit/hasFullUsersAccess() logic in UserController — that doesn't
 * fit this generic none/read-only/full shape.
 *
 * $sections may list more than one section, e.g.
 * 'permission:shows,auditions' — used by routes shared across several
 * independently-gated sections (e.g. a show lookup used by both the Shows
 * and Auditions admin areas). Laravel splits a comma-separated middleware
 * parameter into separate positional arguments (same mechanism as
 * 'throttle:60,1'), which is why this is variadic rather than doing its own
 * comma-splitting. The user's highest access level across the listed
 * sections wins (full > read-only > none), then the usual
 * full/read-only/none logic applies. A single section behaves exactly as
 * before.
 */
class CheckSectionPermission
{
    private const RANK = ['none' => 0, 'read-only' => 1, 'full' => 2];

    public function handle(Request $request, Closure $next, string ...$sections): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['status' => 'error', 'message' => 'Unauthenticated'], 401);
        }

        // Owner always has full access everywhere, regardless of stored
        // rows — never even looked up, same as the frontend's bypass.
        if ($user->email === config('auth.owner_email')) {
            return $next($request);
        }

        $access = UserPermission::where('user_id', $user->id)
            ->whereIn('section', $sections)
            ->pluck('access')
            ->reduce(
                fn ($best, $current) => self::RANK[$current] > self::RANK[$best] ? $current : $best,
                'none',
            );

        if ($access === 'none') {
            return response()->json([
                'status' => 'error',
                'message' => 'You do not have permission to access this section',
            ], 403);
        }

        if ($access === 'read-only' && ! $request->isMethod('get') && ! $request->isMethod('head')) {
            return response()->json([
                'status' => 'error',
                'message' => 'You only have read-only access to this section',
            ], 403);
        }

        return $next($request);
    }
}
