<?php

namespace App\Http\Middleware;

use App\Models\Show;
use App\Models\ShowAccessGrant;
use App\Models\User;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gates /api/director/shows/{show}/* — usage: ->middleware('director-show').
 * Requires a director-app token (the 'director' ability) and an active
 * ShowAccessGrant for the {show} route parameter. Web-login ('*') tokens are
 * refused too, so this API is reachable only through the desktop app and
 * never widens what a regular admin login can see. On success the Show and
 * grant are stashed on the request as attributes 'director_show' and
 * 'director_grant'.
 */
class EnsureDirectorShowAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($rejection = self::rejectNonAppToken($request)) {
            return $rejection;
        }

        $showId = (int) $request->route('show');
        $show = Show::find($showId);
        $grant = $show
            ? ShowAccessGrant::where('user_id', $request->user()->id)->where('show_id', $showId)->first()
            : null;

        if (! $grant) {
            return self::forbidden('no_access', "You don't have access to this show");
        }

        if (! $grant->isActive()) {
            $today = Carbon::today();

            if ($grant->starts_at && $grant->starts_at->gt($today)) {
                return self::forbidden('not_started', "Your access to {$show->name} starts on {$grant->starts_at->format('M j, Y')}", [
                    'starts_at' => $grant->starts_at->toDateString(),
                ]);
            }

            return self::forbidden('ended', "Your access to {$show->name} ended on {$grant->ends_at->format('M j, Y')}", [
                'ended_at' => $grant->ends_at->toDateString(),
            ]);
        }

        $request->attributes->set('director_show', $show);
        $request->attributes->set('director_grant', $grant);

        return $next($request);
    }

    /**
     * 403 unless the caller authenticated with a director-app token. Note
     * tokenCan('director') is also true for '*' tokens, hence the extra
     * '*' check.
     */
    public static function rejectNonAppToken(Request $request): ?Response
    {
        $user = $request->user();

        if (! $user || $user->tokenCan('*') || ! $user->tokenCan(User::APP_TOKEN_ABILITY)) {
            return self::forbidden('not_app_token', 'This endpoint is only available to the director app');
        }

        return null;
    }

    private static function forbidden(string $reason, string $message, array $extra = []): Response
    {
        return response()->json([
            'status'  => 'error',
            'reason'  => $reason,
            'message' => $message,
        ] + $extra, 403);
    }
}
