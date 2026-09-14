<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Show extends Model
{
    /** @use HasFactory<\Database\Factories\ShowFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'writer',
        'tagline',
        'director',
        'info',
        'poster',
        'ticket_sales_start',
        'slug',
        'tentative',
        'ticket_price'
    ];

    protected $casts = [
        'tentative' => 'integer',
    ];

    public static function validate($data, $id = null)
    {
        $rules = [
            'name'               => ['required', 'string', 'max:255'],
            'writer'             => ['required', 'string', 'max:255'],
            'tagline'            => ['required', 'string', 'max:255'],
            'director'           => ['required', 'string', 'max:255'],
            'info'               => ['nullable', 'string', 'max:65535'],
            'poster'             => ['required', 'string', 'max:255'],
            'ticket_sales_start' => ['required', 'date'],
            'slug'               => ['required', 'string', 'max:255'],
            'ticket_price'      => ['sometimes', 'integer']
        ];

        $validator = validator($data, $rules);

        if ($validator->fails()) {
            return ['errors' => $validator->errors()->toArray()];
        }

        $validated = $validator->validated();
        if (is_null($validated['info'])) {
            $validated['info'] = "";
        }

        return $validated;
    }

    /**
     * Relationship to gallery_images
     */
    public function galleryImages()
    {
        return $this->hasMany(GalleryImage::class);
    }

    /**
     * Relationship to performances
     */
    public function performances()
    {
        return $this->hasMany(Performance::class)->orderBy('date');
    }

    /**
     * Relationship to comp tickets
     */
    public function compTickets()
    {
        return $this->hasMany(CompTicket::class);
    }

    /**
     * Relationship to audition
     */
    public function audition()
    {
        return $this->hasOne(Audition::class)
            ->where('display_date', '<=', now()->toDateString())
            ->where('end_display_date', '>=', now()->toDateString());
    }

    /**
     * Atomically reserves the next $count sequential internal ticket numbers
     * for this show, shared across its whole run of performances (never
     * per-performance). The lockForUpdate() inside a transaction is what
     * keeps two concurrent purchases for the same show (e.g. PayPal and
     * Transfer arriving seconds apart) from racing onto the same numbers.
     */
    public function reserveTicketNumbers(int $count): array
    {
        return DB::transaction(function () use ($count) {
            $show = static::where('id', $this->id)->lockForUpdate()->first();
            $start = $show->next_ticket_number;
            $show->next_ticket_number = $start + $count;
            $show->save();

            return range($start, $start + $count - 1);
        });
    }

    /**
     * Whether the show's gallery (if any) should be visible yet — hidden
     * until the day after the show's final performance. Requires
     * `performances` to already be loaded.
     */
    public function galleryVisible(): bool
    {
        $lastPerformance = $this->performances->max('date');

        return $lastPerformance && $lastPerformance < now()->toDateString();
    }
}
