<?php

namespace App\Services;

use App\Exceptions\AuditionAiException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Restyles the plain audition page (resources/views/auditions/notice.blade.php)
 * with Google Gemini, using the show's poster for the theme. The plain page
 * already holds every fact, so Gemini only changes the look.
 *
 * Gemini often answers 503 "high demand" (also 429/500/504). Those are
 * retried with a short backoff, then tried once on the fallback model, and
 * finally reported as AuditionAiException('busy') so the admin can try again
 * later or use the plain page.
 */
class GeminiAuditionGenerator
{
    private const ENDPOINT = 'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent';

    /** Statuses that mean "overloaded / try again later", not "your request is wrong". */
    private const BUSY_STATUSES = [429, 500, 502, 503, 504];

    public static function configured(): bool
    {
        return filled(config('services.gemini.key'));
    }

    /**
     * @param string      $plainHtml  The Blade-rendered page (all content, plain styling)
     * @param array       $data       AuditionNotice::data() — sent too, for context
     * @param string|null $posterFile Poster on disk, attached for theming
     *
     * @throws AuditionAiException
     */
    public function generate(string $plainHtml, array $data, ?string $posterFile): string
    {
        if (! self::configured()) {
            throw new AuditionAiException('config', 'GEMINI_API_KEY isn\'t set in .env, so the AI version can\'t be generated.');
        }

        $body = $this->requestBody($plainHtml, $data, $posterFile);

        $models = array_values(array_unique(array_filter([
            config('services.gemini.model'),
            config('services.gemini.fallback_model'),
        ])));

        $lastBusy = null;

        foreach ($models as $model) {
            try {
                $response = $this->send($model, $body);
            } catch (ConnectionException $e) {
                // Timed out or unreachable — treated like "busy".
                logger()->warning("Gemini ({$model}) connection failed: {$e->getMessage()}");
                $lastBusy = $e->getMessage();
                continue;
            }

            if (in_array($response->status(), self::BUSY_STATUSES, true)) {
                logger()->warning("Gemini ({$model}) still busy after retries: HTTP {$response->status()} " . $this->errorMessage($response));
                $lastBusy = $this->errorMessage($response);
                continue;
            }

            if ($response->failed()) {
                logger()->error("Gemini ({$model}) error: HTTP {$response->status()} " . $response->body());

                $kind = in_array($response->status(), [400, 401, 403, 404], true) ? 'config' : 'failed';

                throw new AuditionAiException(
                    $kind,
                    "Gemini rejected the request (HTTP {$response->status()}): " . $this->errorMessage($response),
                );
            }

            return $this->extractHtml($response);
        }

        throw new AuditionAiException(
            'busy',
            'Google\'s AI is overloaded right now (that\'s on their end, not ours). '
            . 'Wait a minute or two and try again, or use "Build without AI" for the plain version.',
            $lastBusy,
        );
    }

    /**
     * One model, retrying a busy answer once after 5s. Gemini's 503s
     * themselves take up to a minute to arrive, so more retries mostly add
     * waiting; the fallback model is the better second chance.
     */
    private function send(string $model, array $body): Response
    {
        return Http::withHeaders(['x-goog-api-key' => config('services.gemini.key')])
            ->acceptJson()
            ->timeout(config('services.gemini.timeout', 120))
            ->retry(
                2,
                5000,
                fn ($e) => $e instanceof RequestException
                    && in_array($e->response->status(), self::BUSY_STATUSES, true),
                throw: false,
            )
            ->post(sprintf(self::ENDPOINT, $model), $body);
    }

    private function requestBody(string $plainHtml, array $data, ?string $posterFile): array
    {
        $parts = [];

        if ($posterFile) {
            $parts[] = [
                'inline_data' => [
                    'mime_type' => mime_content_type($posterFile) ?: 'image/jpeg',
                    'data' => base64_encode(file_get_contents($posterFile)),
                ],
            ];
        }

        $parts[] = ['text' => $this->userPrompt($plainHtml, $data, (bool) $posterFile)];

        return [
            'system_instruction' => ['parts' => [['text' => $this->systemPrompt($data)]]],
            'contents' => [['role' => 'user', 'parts' => $parts]],
            'generationConfig' => [
                'temperature' => 0.8,
                'maxOutputTokens' => 32768,
            ],
        ];
    }

    private function userPrompt(string $plainHtml, array $data, bool $hasPoster): string
    {
        $poster = $hasPoster
            ? 'The attached image is the show\'s poster. Theme the page from it: background, text color, one or two accent colors, and the overall mood and typography feel.'
            : 'There is no poster image available, so choose a palette that suits the show\'s title and tone.';

        return <<<TXT
        This is the audition announcement for "{$data['title']}", a production of Azuay Community Theater (ACT) in Cuenca, Ecuador.

        {$poster}

        Below is a plain version of the page that already contains every fact, correctly worded and formatted. Redesign it into an attractive, themed audition page. You may restructure the layout, regroup sections, change headings' styling and add decorative CSS, but keep all of the text content exactly as given — every date, time, name, role, address, link, phone number and email — without adding, changing or leaving out any facts.

        The same facts as JSON, for reference:
        {$this->json($data)}

        Plain page:
        {$plainHtml}
        TXT;
    }

    private function systemPrompt(array $data): string
    {
        $img = $data['poster_path']
            ? '<img src="' . $data['poster_path'] . '" alt="' . htmlspecialchars($data['poster_alt'], ENT_QUOTES) . '">'
            : null;
        $posterRule = $img
            ? "Feature the poster near the top using exactly this tag (keep the src as this root-relative path): {$img}"
            : 'Do not include any images.';

        return <<<TXT
        You design audition announcement pages for a community theater website. You output an HTML fragment that is inserted into an existing page, and nothing else.

        Rules (the page will break otherwise):
        1. Output ONLY the fragment: one <style> block followed by one <div class="act-audition"> containing all the content. No <html>, <head>, <body>, <!DOCTYPE>, markdown code fences or explanations.
        2. Every CSS selector must start with .act-audition (for example ".act-audition h2"). Never style html, body, :root or * on their own. Invent class names prefixed with "act-"; never use text-*, bg-* or q-* class names.
        3. No JavaScript: no <script>, no on… attributes, no javascript: links.
        4. No external resources other than the poster image and normal links: no @import, web fonts, <link>, <iframe>, <svg>, other images or forms. Use common system fonts with serif/sans-serif fallbacks.
        5. Must look good on phones and desktops: max-width about 900px, centered; images max-width: 100% and height: auto; no fixed widths wider than a phone; no position: fixed.
        6. Use simple standard tags (div, section, h1–h4, p, ul/ol/li, strong/em, a, img). Keep CSS in the <style> block.
        7. Do not indent anything: start every line of CSS and HTML at the left margin, and never use &nbsp; for spacing.
        8. Keep strong text/background contrast everywhere so it's easy to read.
        9. Links: email addresses as mailto: links; web addresses open in a new tab (target="_blank" rel="noopener").
        10. {$posterRule}
        TXT;
    }

    private function json(array $data): string
    {
        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    private function errorMessage(Response $response): string
    {
        return (string) ($response->json('error.message') ?? str($response->body())->limit(200));
    }

    /** The text of the first candidate, cleaned up and made safe for the editor. */
    private function extractHtml(Response $response): string
    {
        $candidate = $response->json('candidates.0');

        if (! $candidate) {
            $reason = $response->json('promptFeedback.blockReason');
            throw new AuditionAiException('failed', 'Gemini returned no result' . ($reason ? " (blocked: {$reason})" : '') . '. Try again.');
        }

        if (($candidate['finishReason'] ?? null) === 'MAX_TOKENS') {
            throw new AuditionAiException('failed', 'Gemini\'s answer was cut off before it finished. Try again.');
        }

        $html = collect($candidate['content']['parts'] ?? [])
            ->reject(fn ($p) => $p['thought'] ?? false)
            ->pluck('text')
            ->join('');

        $html = self::clean($html);

        if (! str_contains($html, 'act-audition')) {
            throw new AuditionAiException('failed', 'Gemini\'s answer wasn\'t a usable audition page. Try again.');
        }

        return $html;
    }

    /**
     * Strips code fences and anything outside the fragment, removes script
     * and event-handler attributes, and un-indents every line (indentation
     * becomes non-breaking spaces in the admin editor).
     */
    public static function clean(string $html): string
    {
        $html = trim($html);
        $html = preg_replace('/^```[a-z]*\s*|\s*```$/i', '', $html);

        // Keep from the first <style or <div to the end.
        if (preg_match('/<(style|div)\b/i', $html, $m, PREG_OFFSET_CAPTURE)) {
            $html = substr($html, $m[0][1]);
        }

        $html = preg_replace('#<script\b.*?</script\s*>#is', '', $html);
        $html = preg_replace('#<(iframe|object|embed|link|meta|form)\b[^>]*>(.*?</\1\s*>)?#is', '', $html);
        $html = preg_replace('/\s+on[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html);
        $html = preg_replace('/(href|src)\s*=\s*(["\'])\s*javascript:[^"\']*\2/i', '$1="#"', $html);
        $html = preg_replace('/@import[^;]*;/i', '', $html);
        $html = preg_replace('/^[ \t]+/m', '', $html);

        return trim($html);
    }
}
