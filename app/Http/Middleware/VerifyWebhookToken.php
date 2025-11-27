<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyWebhookToken
{
    // app/Http/Middleware/VerifyWebhookToken.php
    public function handle(Request $request, Closure $next): Response
    {
        logger()->info('Webhook middleware checking token');

        $expectedToken = config('services.payment_processor.webhook_token');
        $providedToken = $request->bearerToken();

        logger()->info('Token check', [
            'expected' => $expectedToken ? 'SET' : 'NOT SET',
            'provided' => $providedToken ? 'PROVIDED' : 'NOT PROVIDED'
        ]);

        if (!$providedToken || $providedToken !== $expectedToken) {
            logger()->error('Webhook token mismatch');
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        logger()->info('Webhook token verified');
        return $next($request);
    }
}
