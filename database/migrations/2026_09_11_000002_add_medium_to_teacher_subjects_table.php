<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('teacher_subjects')) {
            return;
        }

        if (! Schema::hasColumn('teacher_subjects', 'medium')) {
            Schema::table('teacher_subjects', function (Blueprint $table) {
                $table->string('medium', 32)->default('english')->after('teacher_id');
            });
        }

        $this->dropForeignKeyIfExists('teacher_subjects', 'teacher_subjects_subject_id_foreign');
        $this->dropIndexIfExists('teacher_subjects', 'teacher_subjects_subject_id_unique');
        $this->dropIndexIfExists('teacher_subjects', 'teacher_subjects_medium_subject_id_unique');
        $this->dropIndexIfExists('teacher_subjects', 'teacher_subjects_teacher_id_medium_standard_id_index');

        if (! $this->indexExists('teacher_subjects', 'teacher_subjects_medium_subject_id_unique')) {
            Schema::table('teacher_subjects', function (Blueprint $table) {
                $table->unique(['medium', 'subject_id']);
            });
        }

        if (! $this->foreignKeyExists('teacher_subjects', 'teacher_subjects_subject_id_foreign')) {
            Schema::table('teacher_subjects', function (Blueprint $table) {
                $table->foreign('subject_id')->references('id')->on('subjects')->cascadeOnDelete();
            });
        }

        if (! $this->indexExists('teacher_subjects', 'teacher_subjects_teacher_id_medium_standard_id_index')) {
            Schema::table('teacher_subjects', function (Blueprint $table) {
                $table->index(['teacher_id', 'medium', 'standard_id']);
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('teacher_subjects')) {
            return;
        }

        $this->dropForeignKeyIfExists('teacher_subjects', 'teacher_subjects_subject_id_foreign');
        $this->dropIndexIfExists('teacher_subjects', 'teacher_subjects_medium_subject_id_unique');
        $this->dropIndexIfExists('teacher_subjects', 'teacher_subjects_teacher_id_medium_standard_id_index');

        Schema::table('teacher_subjects', function (Blueprint $table) {
            if (Schema::hasColumn('teacher_subjects', 'medium')) {
                $table->dropColumn('medium');
            }
        });

        if (! $this->indexExists('teacher_subjects', 'teacher_subjects_subject_id_unique')) {
            Schema::table('teacher_subjects', function (Blueprint $table) {
                $table->unique('subject_id');
            });
        }

        if (! $this->foreignKeyExists('teacher_subjects', 'teacher_subjects_subject_id_foreign')) {
            Schema::table('teacher_subjects', function (Blueprint $table) {
                $table->foreign('subject_id')->references('id')->on('subjects')->cascadeOnDelete();
            });
        }
    }

    private function indexExists(string $table, string $index): bool
    {
        return collect(DB::select('SHOW INDEX FROM `'.$table.'` WHERE Key_name = ?', [$index]))->isNotEmpty();
    }

    private function foreignKeyExists(string $table, string $key): bool
    {
        return collect(DB::select(
            "SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
               AND CONSTRAINT_NAME = ?
               AND CONSTRAINT_TYPE = 'FOREIGN KEY'",
            [$table, $key]
        ))->isNotEmpty();
    }

    private function dropForeignKeyIfExists(string $table, string $key): void
    {
        if ($this->foreignKeyExists($table, $key)) {
            Schema::table($table, function (Blueprint $blueprint) use ($key) {
                $blueprint->dropForeign($key);
            });
        }
    }

    private function dropIndexIfExists(string $table, string $index): void
    {
        if ($this->indexExists($table, $index)) {
            Schema::table($table, function (Blueprint $blueprint) use ($index) {
                $blueprint->dropIndex($index);
            });
        }
    }
};
