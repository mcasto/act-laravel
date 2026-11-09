<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * One-time setup code for pairing the director page with a user.
 * Only the SHA-256 hash is stored; the plain code is returned once, when
 * issued. Valid for 15 minutes, single use, one live code per person.
 */
class AppPairingCode extends Model
{
    public const LIFETIME_MINUTES = 15;

    public const LENGTH = 8;

    /** No 0/O, 1/I/L — codes get read aloud and typed by hand. */
    private const ALPHABET = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

    protected $fillable = [
        'user_id',
        'code_hash',
        'expires_at',
        'used_at',
        'created_by',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at'    => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeUsable(Builder $query): Builder
    {
        return $query->whereNull('used_at')->where('expires_at', '>', now());
    }

    public static function hashCode(string $code): string
    {
        return hash('sha256', strtoupper(trim($code)));
    }

    /**
     * Issues a fresh code for $user, expiring any previous unused one.
     *
     * @return array{code: string, expires_at: \Illuminate\Support\Carbon}
     */
    public static function issueFor(User $user, ?User $issuedBy = null): array
    {
        $code = '';
        for ($i = 0; $i < self::LENGTH; $i++) {
            $code .= self::ALPHABET[random_int(0, strlen(self::ALPHABET) - 1)];
        }

        $expiresAt = now()->addMinutes(self::LIFETIME_MINUTES);

        static::where('user_id', $user->id)->usable()->update(['expires_at' => now()]);

        static::create([
            'user_id'    => $user->id,
            'code_hash'  => self::hashCode($code),
            'expires_at' => $expiresAt,
            'created_by' => $issuedBy?->id,
        ]);

        return ['code' => $code, 'expires_at' => $expiresAt];
    }

    /**
     * Atomically burns a usable code, returning its row, or null if the
     * code is unknown, expired, or already used (including by a request
     * racing this one).
     */
    public static function redeem(string $code): ?self
    {
        $row = static::where('code_hash', self::hashCode($code))->usable()->first();

        if (! $row) {
            return null;
        }

        $burned = static::where('id', $row->id)->usable()->update(['used_at' => now()]);

        return $burned === 1 ? $row->refresh() : null;
    }
}
