<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('teacher_subjects')) {
            return;
        }

        // Drop old unique(medium, subject_id) if present so Std 11/12 can share a subject across 2 teachers.
        try {
            Schema::table('teacher_subjects', function (Blueprint $table) {
                $table->dropForeign(['subject_id']);
            });
        } catch (\Throwable $e) {
            // Foreign key may already be absent.
        }

        try {
            Schema::table('teacher_subjects', function (Blueprint $table) {
                $table->dropUnique(['medium', 'subject_id']);
            });
        } catch (\Throwable $e) {
            // Unique may already be dropped.
        }

        try {
            Schema::table('teacher_subjects', function (Blueprint $table) {
                $table->unique(['teacher_id', 'medium', 'subject_id'], 'teacher_subjects_teacher_medium_subject_unique');
            });
        } catch (\Throwable $e) {
            // Unique may already exist.
        }

        try {
            Schema::table('teacher_subjects', function (Blueprint $table) {
                $table->foreign('subject_id')->references('id')->on('subjects')->cascadeOnDelete();
            });
        } catch (\Throwable $e) {
            // Foreign key may already exist.
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('teacher_subjects')) {
            return;
        }

        try {
            Schema::table('teacher_subjects', function (Blueprint $table) {
                $table->dropForeign(['subject_id']);
            });
        } catch (\Throwable $e) {
        }

        try {
            Schema::table('teacher_subjects', function (Blueprint $table) {
                $table->dropUnique('teacher_subjects_teacher_medium_subject_unique');
            });
        } catch (\Throwable $e) {
        }

        Schema::table('teacher_subjects', function (Blueprint $table) {
            $table->unique(['medium', 'subject_id']);
            $table->foreign('subject_id')->references('id')->on('subjects')->cascadeOnDelete();
        });
    }
};
