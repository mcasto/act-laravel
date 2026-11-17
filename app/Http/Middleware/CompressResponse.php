<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Gzips JSON/text API responses. The host (LiteSpeed) compresses static files
 * but not PHP output, so without this large responses like the admin ticket
 * sales list (~1.5 MB of JSON) cross the wire uncompressed. Files and streams
 * (images, downloads, exports) are left alone.
 */
class CompressResponse
{
    /** Below this, compressing isn't worth the bytes it saves. */
    private const MIN_BYTES = 1024;

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (
            $response instanceof BinaryFileResponse
            || $response instanceof StreamedResponse
            || $response->headers->has('Content-Encoding')
            || ! str_contains(strtolower($request->header('Accept-Encoding', '')), 'gzip')
            || ! preg_match('#^(application/(json|.*\+json|javascript)|text/)#i', (string) $response->headers->get('Content-Type'))
        ) {
            return $response;
        }

        $content = $response->getContent();

        if ($content === false || strlen($content) < self::MIN_BYTES) {
            return $response;
        }

        $compressed = gzencode($content, 5);

        if ($compressed === false) {
            return $response;
        }

        $response->setContent($compressed);
        $response->headers->set('Content-Encoding', 'gzip');
        $response->headers->set('Content-Length', (string) strlen($compressed));
        $response->setVary('Accept-Encoding', false);

        return $response;
    }
}
