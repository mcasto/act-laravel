<?php

namespace App\Models;

use App\Helpers\LocalTime;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Grants one user show-scoped access (via the director page) to one
 * show between starts_at and ends_at inclusive; a null bound is open-ended.
 * Days switch over at local (Cuenca) midnight — see LocalTime. All checks
 * compare Y-m-d strings, never Carbon instants (a date cast is midnight UTC).
 * The saved dates are the source of truth — defaultWindow() only supplies
 * UI pre-fill values and is never applied on save.
 */
class ShowAccessGrant extends Model
{
    protected $fillable = [
        'user_id',
        'show_id',
        'role',
        'starts_at',
        'ends_at',
    ];

    protected $casts = [
        'starts_at' => 'date:Y-m-d',
        'ends_at'   => 'date:Y-m-d',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function show()
    {
        return $this->belongsTo(Show::class);
    }

    /**
     * Grants in effect today: starts_at <= today <= ends_at, nulls open-ended.
     */
    public function scopeActive(Builder $query): Builder
    {
        $today = LocalTime::todayString();

        return $query
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', $today))
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', $today));
    }

    public function isActive(): bool
    {
        $today = LocalTime::todayString();

        return ($this->starts_at === null || $this->starts_at->toDateString() <= $today)
            && ($this->ends_at === null || $this->ends_at->toDateString() >= $today);
    }

    /** Starts on a future local date (has never been in effect). */
    public function isUpcoming(): bool
    {
        return $this->starts_at !== null && $this->starts_at->toDateString() > LocalTime::todayString();
    }

    /**
     * Suggested grant window for a show, for pre-filling the UI only:
     * start = the show's first audition session date (else its audition
     * notice's display_date, else the date the show was created), end = last performance + 7 days (null if the show
     * has no performances yet).
     *
     * @return array{starts_at: ?string, ends_at: ?string}
     */
    public static function defaultWindow(Show $show): array
    {
        $firstAudition = AuditionSession::query()
            ->join('auditions', 'auditions.id', '=', 'audition_sessions.audition_id')
            ->where('auditions.show_id', $show->id)
            ->whereNull('auditions.deleted_at')
            ->min('audition_sessions.session');

        // Audition sessions are optional, so fall back to the audition
        // notice's display_date before the show's creation date.
        // Session/display dates are already local; created_at is a UTC
        // timestamp, so convert it to the local calendar date.
        $start = $firstAudition
            ?? Audition::where('show_id', $show->id)->min('display_date')
            ?? $show->created_at?->copy()->setTimezone(LocalTime::zone())->toDateString();

        $lastPerformance = Performance::where('show_id', $show->id)->max('date');

        return [
            'starts_at' => $start ? CarbonImmutable::parse($start)->toDateString() : null,
            'ends_at'   => $lastPerformance ? CarbonImmutable::parse($lastPerformance)->addDays(7)->toDateString() : null,
        ];
    }
}
