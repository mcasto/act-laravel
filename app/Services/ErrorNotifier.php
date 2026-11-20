<?php

namespace App\Services;

use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Emails ERROR_NOTIFICATION_EMAIL whenever something is logged at error level
 * or worse. That covers both unhandled exceptions (Laravel reports them by
 * logging at "error") and the places we catch a failure and call
 * logger()->error() ourselves.
 *
 * Registered in AppServiceProvider::boot(). Notifying must never cause a
 * failure of its own, so everything here swallows its errors, and a guard
 * stops an error raised while notifying from triggering another notification.
 */
class ErrorNotifier
{
    private const LEVELS = ['error', 'critical', 'alert', 'emergency'];

    /** The same error is emailed at most once per this many minutes. */
    private const DEDUPE_MINUTES = 15;

    private static bool $sending = false;

    public function handle(MessageLogged $event): void
    {
        $to = config('mail.error_notification.address');

        if (! $to || self::$sending || ! in_array($event->level, self::LEVELS, true)) {
            return;
        }

        self::$sending = true;

        try {
            $exception = $event->context['exception'] ?? null;
            $exception = $exception instanceof Throwable ? $exception : null;

            if ($this->recentlySent($event, $exception)) {
                return;
            }

            $summary = $exception
                ? class_basename($exception) . ': ' . $exception->getMessage()
                : $event->message;

            Mail::raw($this->body($event, $exception), function ($mail) use ($to, $summary) {
                $mail->to($to)->subject(
                    '[' . config('app.name') . ' ' . app()->environment() . '] '
                    . mb_strimwidth(preg_replace('/\s+/', ' ', $summary), 0, 150, '…')
                );
            });
        } catch (Throwable $e) {
            // Can't use the logger here without looping back into this
            // listener, so fall back to PHP's own error log.
            error_log('ErrorNotifier failed: ' . $e->getMessage());
        } finally {
            self::$sending = false;
        }
    }

    /**
     * Throttles repeats so a failure hit on every request doesn't flood the
     * inbox. Uses the file cache so it still works when the database (the
     * default cache store) is what's broken.
     */
    private function recentlySent(MessageLogged $event, ?Throwable $exception): bool
    {
        $signature = $exception
            ? get_class($exception) . '|' . $exception->getFile() . '|' . $exception->getLine()
            : $event->level . '|' . $event->message;

        try {
            return ! Cache::store('file')->add(
                'error-notifier:' . md5($signature),
                true,
                now()->addMinutes(self::DEDUPE_MINUTES)
            );
        } catch (Throwable) {
            return false;
        }
    }

    private function body(MessageLogged $event, ?Throwable $exception): string
    {
        $lines = [
            'Time:        ' . now()->toDateTimeString(),
            'Environment: ' . app()->environment(),
            'Level:       ' . $event->level,
        ];

        if (app()->runningInConsole()) {
            $lines[] = 'Command:     ' . implode(' ', $_SERVER['argv'] ?? []);
        } else {
            try {
                $request = request();
                $lines[] = 'Request:     ' . $request->method() . ' ' . $request->fullUrl();
                $lines[] = 'IP:          ' . $request->ip();
                if ($user = $request->user()) {
                    $lines[] = 'User:        #' . $user->getAuthIdentifier()
                        . (isset($user->email) ? ' (' . $user->email . ')' : '');
                }
            } catch (Throwable) {
                // No request info available.
            }
        }

        $lines[] = '';
        $lines[] = 'Message:';
        $lines[] = $event->message;

        if ($exception) {
            $lines[] = '';
            $lines[] = 'Exception: ' . get_class($exception);
            $lines[] = 'At:        ' . $exception->getFile() . ':' . $exception->getLine();
            $lines[] = '';
            $lines[] = 'Trace:';
            $lines[] = mb_substr($exception->getTraceAsString(), 0, 8000);
        }

        $context = array_diff_key($event->context, ['exception' => true]);
        if ($context) {
            $lines[] = '';
            $lines[] = 'Context:';
            $lines[] = mb_substr(
                (string) json_encode($context, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR),
                0,
                8000
            );
        }

        $lines[] = '';
        $lines[] = 'Full details are in storage/logs/laravel.log on the server.';

        return implode("\n", $lines);
    }
}
