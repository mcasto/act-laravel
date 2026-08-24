<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Fixr is a payment processor, not a payment method in its own right —
     * consolidating it back into "Credit Card" so the label stays accurate
     * if the processor ever changes. Existing ticket_sales recorded against
     * "fixr" move to "credit_card" before the row is retired.
     */
    public function up(): void
    {
        $fixrId = DB::table('payment_methods')->where('value', 'fixr')->value('id');
        $creditCardId = DB::table('payment_methods')->where('value', 'credit_card')->value('id');

        if ($fixrId && $creditCardId) {
            DB::table('ticket_sales')
                ->where('payment_method_id', $fixrId)
                ->update(['payment_method_id' => $creditCardId]);
        }

        if ($fixrId) {
            DB::table('payment_methods')->where('id', $fixrId)->update(['deleted_at' => now()]);
        }
    }

    public function down(): void
    {
        DB::table('payment_methods')->where('value', 'fixr')->update(['deleted_at' => null]);
        // Ticket sales reassigned to credit_card above aren't reverted here —
        // there's no reliable way to tell which ones were originally fixr.
    }
};
