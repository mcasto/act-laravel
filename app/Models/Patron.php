<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

class Patron extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'last_name',
        'first_name',
        'phone',
        'email',
        'founding_angel',
        'front_row',
        'comments',
    ];

    protected $casts = [
        'founding_angel' => 'boolean',
        'front_row' => 'integer',
    ];

    /**
     * Keeps the main address (patrons.email) listed in patron_emails, so
     * lookups by any address find this patron. Skipped when the value
     * isn't an email address, or the address already belongs to another
     * patron (that's a merge for an admin to decide, not something to
     * silently move).
     */
    protected static function booted(): void
    {
        static::saved(function (Patron $patron) {
            if (! $patron->wasRecentlyCreated && ! $patron->wasChanged('email')) {
                return;
            }

            $email = self::normalizeEmail($patron->email);

            if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return;
            }

            $existing = PatronEmail::where('email', $email)->first();

            if (! $existing) {
                $patron->emails()->create(['email' => $email]);
            }
        });
    }

    public function flexPackages(): HasMany
    {
        return $this->hasMany(PatronFlexPackage::class);
    }

    public function ticketSales(): HasMany
    {
        return $this->hasMany(TicketSale::class);
    }

    /** Every address this patron uses, the main one included. */
    public function emails(): HasMany
    {
        return $this->hasMany(PatronEmail::class)->orderBy('email');
    }

    /**
     * Lowercased and trimmed, with stray wrapping removed — "mailto:",
     * angle brackets and quotes pasted in from an email client (e.g.
     * "name@example.com>").
     */
    public static function normalizeEmail(?string $email): string
    {
        $email = strtolower(trim((string) $email));
        $email = preg_replace('/^mailto:/', '', $email);

        return trim($email, " \t\n\r\0\x0B<>\"'");
    }

    /**
     * A real address someone can be emailed at — not blank, not a name
     * typed into the field, and not a "no-email+…@address.com" stand-in
     * used for patrons who gave no email.
     */
    public static function isRealEmail(?string $email): bool
    {
        $email = self::normalizeEmail($email);

        return (bool) filter_var($email, FILTER_VALIDATE_EMAIL)
            && ! str_starts_with($email, 'no-email+');
    }

    /**
     * The patron who uses this address — as their main email or any other.
     * Use this (never where('email', …)) to find a patron by email.
     */
    public static function findByEmail(?string $email): ?self
    {
        $email = self::normalizeEmail($email);

        if ($email === '') {
            return null;
        }

        return self::whereHas('emails', fn ($q) => $q->where('email', $email))->first()
            // A main address that couldn't be listed (not a valid email) is
            // still matched directly.
            ?? self::whereRaw('LOWER(TRIM(email)) = ?', [$email])->first();
    }

    /**
     * findByEmail(), or a new patron with this email and $attributes —
     * the replacement for firstOrCreate(['email' => …], …).
     */
    public static function firstOrCreateByEmail(string $email, array $attributes = []): self
    {
        return self::findByEmail($email)
            ?? self::create(['email' => trim($email)] + $attributes);
    }

    /** Every address, main first — for matching records keyed by email (e.g. comps). */
    public function allEmails(): Collection
    {
        return collect([self::normalizeEmail($this->email)])
            ->merge($this->emails()->pluck('email'))
            ->filter()
            ->unique()
            ->values();
    }
}
