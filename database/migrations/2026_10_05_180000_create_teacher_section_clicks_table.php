<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('teacher_section_clicks')) {
            return;
        }

        Schema::create('teacher_section_clicks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
            $table->date('click_date');
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('subject_name')->nullable();
            $table->unsignedBigInteger('chapter_id')->nullable();
            $table->string('chapter_name')->nullable();
            $table->unsignedBigInteger('material_topic_id')->nullable();
            $table->string('topic_name')->nullable();
            $table->string('section_key', 80);
            $table->string('section_label')->nullable();
            $table->unsignedInteger('points')->default(0);
            $table->unsignedInteger('topic_points')->default(0);
            $table->unsignedInteger('clicks')->default(0);
            $table->timestamps();

            $table->unique(
                ['teacher_id', 'click_date', 'material_topic_id', 'section_key'],
                'teacher_section_clicks_unique'
            );
            $table->index(['click_date', 'teacher_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_section_clicks');
    }
};
