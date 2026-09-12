<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('teacher_subjects', 'medium')) {
            Schema::table('teacher_subjects', function (Blueprint $table) {
                $table->string('medium', 32)->default('english')->after('teacher_id');
            });
        }

        Schema::table('teacher_subjects', function (Blueprint $table) {
            $table->dropForeign(['subject_id']);
        });

        Schema::table('teacher_subjects', function (Blueprint $table) {
            $table->dropUnique(['subject_id']);
            $table->unique(['medium', 'subject_id']);
            $table->foreign('subject_id')->references('id')->on('subjects')->cascadeOnDelete();
            $table->index(['teacher_id', 'medium', 'standard_id']);
        });
    }

    public function down(): void
    {
        Schema::table('teacher_subjects', function (Blueprint $table) {
            $table->dropForeign(['subject_id']);
            $table->dropUnique(['medium', 'subject_id']);
            $table->dropIndex(['teacher_id', 'medium', 'standard_id']);
            $table->dropColumn('medium');
            $table->unique('subject_id');
            $table->foreign('subject_id')->references('id')->on('subjects')->cascadeOnDelete();
        });
    }
};
