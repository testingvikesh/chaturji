<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('teacher_timetables')) {
            return;
        }

        Schema::create('teacher_timetables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('weekday'); // 1=Mon … 7=Sun (ISO)
            $table->foreignId('period_id')->constrained('school_periods')->cascadeOnDelete();
            $table->string('medium', 32);
            $table->foreignId('standard_id')->constrained('standards')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->string('section', 32)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['teacher_id', 'weekday', 'period_id'], 'tt_teacher_day_period_unique');
            $table->index(['teacher_id', 'weekday', 'is_active']);
            $table->index(['weekday', 'period_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_timetables');
    }
};
