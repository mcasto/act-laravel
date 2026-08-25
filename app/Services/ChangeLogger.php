<?php

namespace App\Services;

use App\Models\ChangeLog;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Records admin changes for the change log. Two entry points:
 *
 *  - recordModelEvent() is called automatically by the wildcard Eloquent
 *    listeners registered in AppServiceProvider::boot() — any model's
 *    create/update/delete gets logged with zero per-feature wiring, as
 *    long as it goes through normal Eloquent calls.
 *
 *  - record() is for the handful of admin settings that live in flat files
 *    under storage/app/private (Flex/Support Us/Angels/Season config,
 *    Standard Button templates) rather than the database. Eloquent events
 *    can't see those, so the controller calls this explicitly right after
 *    the write.
 *
 * Both only log while there's an authenticated user — public ticket/donation
 * purchases, the Fixr webhook, and console/cron jobs never have one, so they
 * never end up in what's meant to be an admin accountability log.
 */
class ChangeLogger
{
    /** Field names never persisted into a log entry, regardless of model. */
    private const SENSITIVE_KEYS = ['password', 'remember_token'];

    /**
     * Models that mutate as a side effect of the framework itself rather
     * than a user taking an action — e.g. Sanctum bumps a token's
     * last_used_at on every authenticated request, which isn't a change
     * anyone made on purpose.
     */
    private const IGNORED_MODELS = [
        PersonalAccessToken::class,
    ];

    public static function recordModelEvent(string $action, Model $model): void
    {
        if (! auth()->check() || $model instanceof ChangeLog || self::isIgnored($model)) {
            return;
        }

        $changes = match ($action) {
            'created' => self::stripSensitive($model->getAttributes()),
            'updated' => self::diff($model),
            'deleted' => self::stripSensitive($model->getAttributes()),
            default => [],
        };

        ChangeLog::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'model_type' => class_basename($model),
            'model_id' => $model->getKey(),
            'changes' => $changes,
        ]);
    }

    /**
     * For non-Eloquent changes (flat-file config). $changes is whatever
     * shape is useful for that setting — typically ['old' => ..., 'new' => ...].
     */
    public static function record(string $description, array $changes = []): void
    {
        if (! auth()->check()) {
            return;
        }

        ChangeLog::create([
            'user_id' => auth()->id(),
            'action' => 'updated',
            'description' => $description,
            'changes' => $changes,
        ]);
    }

    /** Old/new pairs for only the fields that actually changed. */
    private static function diff(Model $model): array
    {
        $changed = self::stripSensitive($model->getChanges());
        unset($changed['updated_at']);

        $original = $model->getOriginal();

        $diff = [];
        foreach ($changed as $key => $newValue) {
            $diff[$key] = ['old' => $original[$key] ?? null, 'new' => $newValue];
        }

        return $diff;
    }

    private static function stripSensitive(array $attributes): array
    {
        return array_diff_key($attributes, array_flip(self::SENSITIVE_KEYS));
    }

    private static function isIgnored(Model $model): bool
    {
        foreach (self::IGNORED_MODELS as $ignored) {
            if ($model instanceof $ignored) {
                return true;
            }
        }

        return false;
    }
}
