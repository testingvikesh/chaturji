<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            if (! Schema::hasColumn('exams', 'student_id')) {
                $table->foreignId('student_id')->nullable()->after('teacher_id')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('exams', 'is_self_exam')) {
                $table->boolean('is_self_exam')->default(false)->after('student_id');
            }
        });

        $indexExists = collect(DB::select(
            'SHOW INDEX FROM `exams` WHERE Key_name = ?',
            ['exams_student_id_is_self_exam_index']
        ))->isNotEmpty();

        if (! $indexExists && Schema::hasColumn('exams', 'student_id') && Schema::hasColumn('exams', 'is_self_exam')) {
            Schema::table('exams', function (Blueprint $table) {
                $table->index(['student_id', 'is_self_exam']);
            });
        }
    }

    public function down(): void
    {
        $indexExists = collect(DB::select(
            'SHOW INDEX FROM `exams` WHERE Key_name = ?',
            ['exams_student_id_is_self_exam_index']
        ))->isNotEmpty();

        Schema::table('exams', function (Blueprint $table) use ($indexExists) {
            if ($indexExists) {
                $table->dropIndex(['student_id', 'is_self_exam']);
            }
            if (Schema::hasColumn('exams', 'student_id')) {
                $table->dropConstrainedForeignId('student_id');
            }
            if (Schema::hasColumn('exams', 'is_self_exam')) {
                $table->dropColumn('is_self_exam');
            }
        });
    }
};
