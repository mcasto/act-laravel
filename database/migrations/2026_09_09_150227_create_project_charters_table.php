<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_charters', function (Blueprint $table) {
            $table->id();

            $table->string('title');
            $table->string('sponsor');

            // 1. Project Overview
            $table->text('description')->nullable();

            // 2. Impact & Beneficiaries
            $table->text('importance')->nullable();

            // 3. Project Goals & Success Measures
            $table->text('goal')->nullable();
            $table->json('success_measures')->nullable();
            $table->json('measurement_methods')->nullable();
            $table->text('measurement_approach')->nullable();

            // 4. Project Plan & Timeline
            $table->date('start_date')->nullable();
            $table->date('completion_date')->nullable();
            $table->json('milestones')->nullable();

            // 5. Resources & Partnerships
            $table->text('team_members')->nullable();
            $table->text('community_partners')->nullable();
            $table->json('resources_needed')->nullable();
            $table->string('resources_other')->nullable();

            // 6. Documentation & Storytelling
            $table->json('documentation_methods')->nullable();
            $table->text('documentation_plan')->nullable();

            // 7. Risks & Challenges
            $table->json('risks')->nullable();

            // 8. Leadership Reflection
            $table->text('leadership_reflection')->nullable();

            // Sponsor Commitment
            $table->string('commitment_signature')->nullable();
            $table->date('commitment_date')->nullable();

            // Admin-only feasibility scoring (1-5, 5 = best) and private notes
            $table->unsignedTinyInteger('price_score')->nullable();
            $table->unsignedTinyInteger('complexity_score')->nullable();
            $table->unsignedTinyInteger('time_score')->nullable();
            $table->unsignedTinyInteger('risk_score')->nullable();
            $table->text('admin_notes')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_charters');
    }
};
