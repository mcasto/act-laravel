<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * A Gemini audition-page generation that didn't produce HTML.
 * kind: 'busy' (overloaded / timed out — try again later),
 *       'config' (key missing or rejected, bad model name),
 *       'failed' (anything else, e.g. an unusable answer).
 * The message is meant for the admin.
 */
class AuditionAiException extends RuntimeException
{
    public function __construct(
        public readonly string $kind,
        string $message,
        public readonly ?string $detail = null,
    ) {
        parent::__construct($message);
    }
}
