<?php

namespace App\Models;

use App\Helpers\LocalTime;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class Course extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'slug',
        'instructor_name',
        'instructor_email',
        'enrollment_start',
        'enrollment_end',
        'cost',
        'max_participants',
        'poster',
        'tagline',
        'location',
        'fixr',
        'instructor_photo',
        'instructor_info',
    ];

    /**
     * Append attributes to the model's array form
     */
    protected $appends = [];

    /**
     * Boot the model and register event listeners
     */
    protected static function booted()
    {
        static::deleting(function ($course) {
            // Soft deletes don't trigger the DB's ON DELETE CASCADE, so
            // cascade to sessions and enrollments here.
            $course->sessions()->get()->each->delete();
            $course->contacts()->get()->each->delete();

            // Delete the blade template
            $templatePath = resource_path("views/courses/{$course->slug}.blade.php");
            if (file_exists($templatePath)) {
                unlink($templatePath);
            }

            // Delete the HTML snippet
            $snippetPath = storage_path("app/public/snippets/courses/{$course->slug}.html");
            if (file_exists($snippetPath)) {
                unlink($snippetPath);
            }
        });
    }

    /**
     * Get the rendered view for this course
     */
    public function getMessageAttribute()
    {
        $cacheKey = "course-message-{$this->slug}";

        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        // The view file backing this is written directly to disk by
        // CourseController::store()/update() and unlinked by the deleting
        // hook above — it can go missing independently of this DB row (e.g.
        // a course created directly on one environment whose view file was
        // since removed from the codebase without that row ever being
        // deleted there too). Don't let that 500 every admin/public request
        // that touches this course — surface it as empty content instead so
        // the course can still be found and edited (which recreates the
        // file) or deleted. Deliberately NOT cached, so a fix takes effect
        // on the next request rather than waiting out the 1-hour TTL below.
        try {
            $rendered = view("courses.{$this->slug}")->render();
        } catch (\InvalidArgumentException $e) {
            Log::warning("Course view file missing for slug '{$this->slug}'", ['course_id' => $this->id]);
            return null;
        }

        Cache::put($cacheKey, $rendered, 3600);

        return $rendered;
    }

    /**
     * Relationship to sessions
     */
    public function sessions()
    {
        return $this->hasMany(CourseSession::class)->orderBy('date', 'asc');
    }

    /**
     * Relationship to contacts
     */
    public function contacts()
    {
        return $this->hasMany(CourseContact::class);
    }

    /**
     * Inclusive of both enrollment dates — enrollment runs through the end
     * of enrollment_end, not up to its first moment. (Comparing the date
     * column straight against now() did the latter, closing a class the
     * evening before its last enrollment day.)
     */
    public function enrollmentOpen(): bool
    {
        $today = LocalTime::todayString();

        return $this->enrollment_start <= $today && $this->enrollment_end >= $today;
    }

    /**
     * When the class drops out of the public nav: once its final session
     * has ended, so enrollees can keep looking up times after enrollment
     * closes. Falls back to the end of enrollment_end if it has no sessions.
     */
    public function visibleUntil(): Carbon
    {
        $lastSession = $this->sessions->sortBy(fn ($session) => $session->date . ' ' . $session->end)->last();

        if ($lastSession) {
            // Enrollment dates and session times are local Cuenca time.
            return LocalTime::parse(
                Carbon::parse($lastSession->date)->toDateString() . ' ' . $lastSession->end
            );
        }

        return LocalTime::parse($this->enrollment_end)->endOfDay();
    }

    /** Shown publicly from the start of enrollment until visibleUntil(). */
    public function isPubliclyVisible(): bool
    {
        return $this->enrollment_start <= LocalTime::todayString()
            && now()->lt($this->visibleUntil());
    }

    /**
     * Whether the class has hit max_participants. Every (non-deleted)
     * enrollment counts, confirmed or not — an unconfirmed PayPal/Transfer
     * enrollment is still holding a seat until an admin removes it. A null
     * max_participants means unlimited.
     */
    public function isFull(): bool
    {
        return $this->max_participants !== null
            && $this->contacts()->count() >= $this->max_participants;
    }

    /**
     * Alias so FixrWebhooksController::findByFixrLink() — which reads
     * ->fixr_link on every record type (Performance, AngelLevel, ...) — can
     * accept a Course unmodified, without touching that shared method.
     */
    protected function fixrLink(): Attribute
    {
        return Attribute::make(get: fn () => $this->fixr);
    }
}
