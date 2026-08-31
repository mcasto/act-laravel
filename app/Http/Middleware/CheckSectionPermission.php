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
 */
class CheckSectionPermission
{
    public function handle(Request $request, Closure $next, string $section): Response
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
            ->where('section', $section)
            ->value('access') ?? 'none';

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
