<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('instructor_name');
            $table->string('instructor_photo');
            $table->text('instructor_email');
            $table->longText('instructor_info');
            $table->date('enrollment_start');
            $table->date('enrollment_end');
            $table->integer('cost');
            $table->string('poster');
            $table->longText('tagline');
            $table->longText('location')->default("<address>>Azuay Community Theater<br />Antonio Vega Muñoz 14-46 between Estevez de Toral and Coronel Talbot</address>");
            $table->string('slug');
            $table->string('fixr_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
