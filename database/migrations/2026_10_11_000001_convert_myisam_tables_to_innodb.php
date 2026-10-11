<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * These were created while config/database.php's engine was null, so
     * they picked up the production host's default engine (MyISAM) — which
     * silently ignores transactions, row locks and foreign keys. A rolled-
     * back ticket sale could leave its tickets rows behind, and tickets'
     * foreign keys were never actually created. The config now pins InnoDB
     * for new tables; this converts the existing ones.
     */
    private array $tables = [
        'tickets',
        'sold_out_notification_recipients',
        'message_us_submissions',
        'change_logs',
        'project_charters',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check before converting anything (MySQL DDL isn't transactional),
        // so a failure here leaves the tables untouched.
        $orphans = DB::table('tickets')
            ->whereNotIn('ticket_sale_id', DB::table('ticket_sales')->select('id'))
            ->orWhereNotIn('show_id', DB::table('shows')->select('id'))
            ->count();

        if ($orphans > 0) {
            throw new RuntimeException(
                "{$orphans} tickets rows reference a missing ticket_sale or show — clean those up before running this migration."
            );
        }

        foreach ($this->tables as $table) {
            if (Schema::hasTable($table) && $this->engine($table) !== 'InnoDB') {
                DB::statement("ALTER TABLE `{$table}` ENGINE = InnoDB");
            }
        }

        // Same constraints as create_tickets_table declared — MyISAM
        // discarded them.
        $existing = collect(Schema::getForeignKeys('tickets'))->pluck('name');

        Schema::table('tickets', function (Blueprint $table) use ($existing) {
            if (! $existing->contains('tickets_ticket_sale_id_foreign')) {
                $table->foreign('ticket_sale_id')->references('id')->on('ticket_sales')->onDelete('cascade');
            }

            if (! $existing->contains('tickets_show_id_foreign')) {
                $table->foreign('show_id')->references('id')->on('shows');
            }
        });
    }

    /**
     * Reverse the migrations. Drops the restored foreign keys but leaves
     * the tables on InnoDB — going back to MyISAM would only reintroduce
     * the problem.
     */
    public function down(): void
    {
        $existing = collect(Schema::getForeignKeys('tickets'))->pluck('name');

        Schema::table('tickets', function (Blueprint $table) use ($existing) {
            if ($existing->contains('tickets_ticket_sale_id_foreign')) {
                $table->dropForeign('tickets_ticket_sale_id_foreign');
            }

            if ($existing->contains('tickets_show_id_foreign')) {
                $table->dropForeign('tickets_show_id_foreign');
            }
        });
    }

    private function engine(string $table): ?string
    {
        return DB::selectOne(
            'SELECT engine FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?',
            [$table]
        )?->engine;
    }
};
