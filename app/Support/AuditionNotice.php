<?php

namespace App\Support;

use App\Models\Patron;
use App\Models\Show;
use App\Models\ShowAccessGrant;
use Carbon\Carbon;

/**
 * Turns the structured audition form into the ready-to-print values the
 * audition page needs (weekdays worked out from dates, times as ranges,
 * performance dates from the show), and supplies the form's defaults.
 *
 * Used by both HTML generators: the Blade template
 * (resources/views/auditions/notice.blade.php) and GeminiAuditionGenerator.
 */
class AuditionNotice
{
    private const NUMBER_WORDS = [
        1 => 'One', 'Two', 'Three', 'Four', 'Five', 'Six',
        'Seven', 'Eight', 'Nine', 'Ten', 'Eleven', 'Twelve',
    ];

    /**
     * Pre-fill values for the form. Never re-derived over a saved value:
     * null on the audition means "use these".
     */
    public static function defaults(Show $show): array
    {
        return [
            'contacts' => self::defaultContacts($show),
            'location' => config('act.address'),
            'registration_note' => config('act.audition_registration_note'),
            'performance_dates' => self::performanceDatesText($show),
        ];
    }

    /**
     * The show's director(s), from the production team's Director grants in
     * the order added: name + email from the user, phone from the patron
     * with that email (may be blank). Older shows entered by hand have no
     * grants, so only shows.director's name is known.
     *
     * @return array<int, array{name: string, title: string, phone: string, email: string}>
     */
    public static function defaultContacts(Show $show): array
    {
        $grants = ShowAccessGrant::where('show_id', $show->id)
            ->director()
            ->with('user:id,name,email')
            ->orderBy('id')
            ->get()
            ->filter(fn (ShowAccessGrant $g) => $g->user !== null);

        if ($grants->isNotEmpty()) {
            return $grants->map(fn (ShowAccessGrant $g) => [
                'name' => (string) $g->user->name,
                'title' => ShowAccessGrant::DIRECTOR_ROLE,
                'phone' => (string) (Patron::findByEmail($g->user->email)?->phone ?? ''),
                'email' => (string) ($g->user->email ?? ''),
            ])->unique('email')->values()->all();
        }

        if (filled($show->director)) {
            return [[
                'name' => $show->director,
                'title' => ShowAccessGrant::DIRECTOR_ROLE,
                'phone' => '',
                'email' => '',
            ]];
        }

        return [];
    }

    /**
     * "February 19, 20, 21, 26, 27, 28" — the month is named once per month
     * ("February 26, 27; March 5, 6"), the year never.
     */
    public static function performanceDatesText(Show $show): string
    {
        $dates = $show->performances()
            ->orderBy('date')
            ->pluck('date')
            ->map(fn ($d) => Carbon::parse($d))
            ->unique(fn (Carbon $d) => $d->toDateString());

        return $dates
            ->groupBy(fn (Carbon $d) => $d->format('Y-m'))
            ->map(fn ($month) => $month->first()->format('F') . ' ' . $month->map(fn (Carbon $d) => $d->format('j'))->join(', '))
            ->join('; ');
    }

    /** "Thursday, December 10" */
    public static function dateText(Carbon $date): string
    {
        return $date->format('l, F j');
    }

    /** "1:30–4:00 PM", "11:00 AM–1:00 PM", or "1:30 PM" with no end. */
    public static function timeRange(?string $start, ?string $end): string
    {
        if (blank($start)) {
            return '';
        }

        $s = Carbon::parse($start);

        if (blank($end)) {
            return $s->format('g:i A');
        }

        $e = Carbon::parse($end);

        return $s->format('A') === $e->format('A')
            ? $s->format('g:i') . '–' . $e->format('g:i A')
            : $s->format('g:i A') . '–' . $e->format('g:i A');
    }

    /** "Four Roles" / "One Role" / "14 Roles" */
    public static function rolesHeading(int $count): string
    {
        $n = self::NUMBER_WORDS[$count] ?? (string) $count;

        return $n . ' ' . ($count === 1 ? 'Role' : 'Roles');
    }

    /** The show's synopsis (shows.info, plain text or simple HTML) as paragraphs. */
    public static function paragraphs(?string $text): array
    {
        if (blank($text)) {
            return [];
        }

        $text = preg_replace('#<br\s*/?>|</(p|div|li|h[1-6])>#i', "\n", $text);
        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = str_replace("\u{00A0}", ' ', $text);

        return collect(preg_split('/\n\s*\n|\n/', $text))
            ->map(fn ($p) => trim(preg_replace('/[ \t]+/', ' ', $p)))
            ->filter()
            ->values()
            ->all();
    }

    /** The poster's filename without the "?t=…" cache-buster stored after a re-upload. */
    public static function posterFilename(Show $show): ?string
    {
        return filled($show->poster) ? strtok($show->poster, '?') : null;
    }

    /** The poster as the site serves it, root-relative. */
    public static function posterPath(Show $show): ?string
    {
        $file = self::posterFilename($show);

        return $file ? '/storage/posters/' . $file : null;
    }

    /** The poster on disk, or null when the show has none (or it's missing). */
    public static function posterFile(Show $show): ?string
    {
        $file = self::posterFilename($show);
        $path = $file ? storage_path('app/public/posters/' . basename($file)) : null;

        return $path && is_file($path) ? $path : null;
    }

    /**
     * Everything the page shows, formatted, from the form's (validated) input.
     *
     * @param array $input sessions/callbacks: [{date, start_time, end_time, note}],
     *                     roles: [{name, gender_age, notes}], contacts: null|[{…}], …
     */
    public static function data(array $input, Show $show): array
    {
        $sessions = fn (array $rows) => collect($rows)
            ->filter(fn ($r) => filled($r['date'] ?? null))
            ->sortBy(fn ($r) => ($r['date'] ?? '') . ' ' . ($r['start_time'] ?? ''))
            ->map(fn ($r) => [
                'date' => self::dateText(Carbon::parse($r['date'])),
                'time' => self::timeRange($r['start_time'] ?? null, $r['end_time'] ?? null),
                'note' => trim((string) ($r['note'] ?? '')),
            ])
            ->values()
            ->all();

        $roles = collect($input['roles'] ?? [])
            ->filter(fn ($r) => filled($r['name'] ?? null))
            ->map(fn ($r) => [
                'name' => trim($r['name']),
                'gender_age' => trim((string) ($r['gender_age'] ?? '')),
                'description' => trim((string) ($r['notes'] ?? '')),
            ])
            ->values()
            ->all();

        $contacts = collect($input['contacts'] ?? self::defaultContacts($show))
            ->map(fn ($c) => [
                'name' => trim((string) ($c['name'] ?? '')),
                'title' => trim((string) ($c['title'] ?? '')),
                'phone' => trim((string) ($c['phone'] ?? '')),
                'email' => trim((string) ($c['email'] ?? '')),
            ])
            ->filter(fn ($c) => $c['name'] !== '' || $c['phone'] !== '' || $c['email'] !== '')
            ->values()
            ->all();

        $rehearsalStart = filled($input['rehearsal_start'] ?? null) ? Carbon::parse($input['rehearsal_start']) : null;
        $location = trim((string) ($input['location'] ?? '')) ?: config('act.address');

        return [
            'title' => $show->name,
            'playwright' => $show->writer,
            'director' => filled($show->director) ? $show->director : 'TBA',
            'poster_path' => self::posterPath($show),
            'poster_alt' => $show->name . ' poster',
            'synopsis' => self::paragraphs($show->info),
            'what_to_expect' => self::paragraphs($input['what_to_expect'] ?? null),
            'sessions' => $sessions($input['sessions'] ?? []),
            'callbacks' => $sessions($input['callbacks'] ?? []),
            'location' => array_values(array_filter(array_map('trim', explode("\n", $location)))),
            'registration_url' => trim((string) ($input['registration_url'] ?? '')),
            'registration_note' => trim((string) ($input['registration_note'] ?? '')) ?: config('act.audition_registration_note'),
            'script_url' => trim((string) ($input['script_url'] ?? '')),
            'roles_heading' => $roles ? self::rolesHeading(count($roles)) : '',
            'roles' => $roles,
            'rehearsal_start' => $rehearsalStart
                ? self::dateText($rehearsalStart) . ' at ' . $rehearsalStart->format('g:i A')
                : '',
            'rehearsal_schedule' => self::paragraphs($input['rehearsal_schedule'] ?? null),
            'performance_dates' => self::performanceDatesText($show),
            'contacts' => $contacts,
        ];
    }
}
