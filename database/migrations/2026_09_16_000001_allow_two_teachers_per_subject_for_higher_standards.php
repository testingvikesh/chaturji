<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teacher_subjects', function (Blueprint $table) {
            $table->dropForeign(['subject_id']);
        });

        Schema::table('teacher_subjects', function (Blueprint $table) {
            $table->dropUnique(['medium', 'subject_id']);
            // Same teacher cannot duplicate the same subject+medium; Std 11/12 may share a subject across 2 teachers.
            $table->unique(['teacher_id', 'medium', 'subject_id'], 'teacher_subjects_teacher_medium_subject_unique');
            $table->foreign('subject_id')->references('id')->on('subjects')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('teacher_subjects', function (Blueprint $table) {
            $table->dropForeign(['subject_id']);
            $table->dropUnique('teacher_subjects_teacher_medium_subject_unique');
            $table->unique(['medium', 'subject_id']);
            $table->foreign('subject_id')->references('id')->on('subjects')->cascadeOnDelete();
        });
    }
};
