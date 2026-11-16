<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One hard-to-guess preview link per show: /audition/preview/{uid} shows
     * that show's saved audition page regardless of its display dates. The
     * uid is uniqid() prefixed with the row's id ("12-6737a1f0c3b2e"), so it's
     * unique and unlikely to be stumbled on. Replaces the single global
     * "Preview" toggle on auditions.
     */
    public function up(): void
    {
        Schema::create('audition_previews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('show_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('uid')->nullable()->unique();
            $table->timestamps();
        });

        // Every existing show gets its link now; new shows get one when
        // they're created (Show::booted()).
        $now = now();
        foreach (DB::table('shows')->pluck('id') as $showId) {
            $id = DB::table('audition_previews')->insertGetId([
                'show_id' => $showId, 'created_at' => $now, 'updated_at' => $now,
            ]);
            DB::table('audition_previews')->where('id', $id)->update(['uid' => uniqid("{$id}-")]);
        }

        Schema::table('auditions', function (Blueprint $table) {
            $table->dropColumn('preview');
        });
    }

    public function down(): void
    {
        Schema::table('auditions', function (Blueprint $table) {
            $table->boolean('preview')->default(false)->after('end_display_date');
        });

        Schema::dropIfExists('audition_previews');
    }
};
