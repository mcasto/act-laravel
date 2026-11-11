<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

/**
 * A show's own revenue multiplier for a payment method — frozen when the
 * row is added, so later changes to the method's global multiplier don't
 * rewrite that show's revenue. A show with no row for a method uses the
 * global multiplier.
 */
class ShowPaymentRate extends Model
{
    protected $fillable = ['show_id', 'payment_method_id', 'revenue_multiplier'];

    protected $casts = ['revenue_multiplier' => 'float'];

    public function show(): BelongsTo
    {
        return $this->belongsTo(Show::class);
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    /**
     * Adds a row at the method's current global multiplier for every
     * per-show method / show pair that doesn't have one yet. Only ever
     * adds: an existing row (including one an admin has overridden) is
     * never changed or removed. Narrow it with $showIds and/or $methodIds.
     *
     * @return int rows added
     */
    public static function addMissing(?array $showIds = null, ?array $methodIds = null): int
    {
        $methods = PaymentMethod::where('per_show', true)
            ->when($methodIds !== null, fn ($q) => $q->whereIn('id', $methodIds))
            ->get(['id', 'revenue_multiplier']);

        if ($methods->isEmpty()) {
            return 0;
        }

        $shows = Show::query()
            ->when($showIds !== null, fn ($q) => $q->whereIn('id', $showIds))
            ->pluck('id');

        $now = now();
        $rows = [];
        foreach ($shows as $showId) {
            foreach ($methods as $method) {
                $rows[] = [
                    'show_id'            => $showId,
                    'payment_method_id'  => $method->id,
                    'revenue_multiplier' => $method->revenue_multiplier,
                    'created_at'         => $now,
                    'updated_at'         => $now,
                ];
            }
        }

        // insertOrIgnore skips pairs that already exist (unique index), so
        // nothing set is ever overwritten.
        return collect($rows)->chunk(500)
            ->sum(fn ($chunk) => DB::table('show_payment_rates')->insertOrIgnore($chunk->all()));
    }
}
