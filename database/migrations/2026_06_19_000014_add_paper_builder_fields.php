<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            if (! Schema::hasColumn('exams', 'chapter_id')) {
                $table->foreignId('chapter_id')->nullable()->after('subject_id')->constrained()->nullOnDelete();
            }
            if (! Schema::hasColumn('exams', 'topic_id')) {
                $table->foreignId('topic_id')->nullable()->after('chapter_id')->constrained()->nullOnDelete();
            }
            if (! Schema::hasColumn('exams', 'generation_config')) {
                $table->json('generation_config')->nullable()->after('total_marks');
            }
        });

        Schema::table('homeworks', function (Blueprint $table) {
            if (! Schema::hasColumn('homeworks', 'topic_id')) {
                $table->foreignId('topic_id')->nullable()->after('chapter_id')->constrained()->nullOnDelete();
            }
            if (! Schema::hasColumn('homeworks', 'generation_config')) {
                $table->json('generation_config')->nullable()->after('status');
            }
        });

        Schema::table('exam_questions', function (Blueprint $table) {
            if (! Schema::hasColumn('exam_questions', 'chapter_question_id')) {
                $table->foreignId('chapter_question_id')->nullable()->after('exam_id')->constrained('chapter_questions')->nullOnDelete();
            }
        });

        Schema::table('homework_questions', function (Blueprint $table) {
            if (! Schema::hasColumn('homework_questions', 'chapter_question_id')) {
                $table->foreignId('chapter_question_id')->nullable()->after('homework_id')->constrained('chapter_questions')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('homework_questions', function (Blueprint $table) {
            if (Schema::hasColumn('homework_questions', 'chapter_question_id')) {
                $table->dropForeign(['chapter_question_id']);
                $table->dropColumn('chapter_question_id');
            }
        });

        Schema::table('exam_questions', function (Blueprint $table) {
            if (Schema::hasColumn('exam_questions', 'chapter_question_id')) {
                $table->dropForeign(['chapter_question_id']);
                $table->dropColumn('chapter_question_id');
            }
        });

        Schema::table('homeworks', function (Blueprint $table) {
            if (Schema::hasColumn('homeworks', 'topic_id')) {
                $table->dropForeign(['topic_id']);
                $table->dropColumn(['topic_id', 'generation_config']);
            }
        });

        Schema::table('exams', function (Blueprint $table) {
            if (Schema::hasColumn('exams', 'chapter_id')) {
                $table->dropForeign(['chapter_id']);
                $table->dropForeign(['topic_id']);
                $table->dropColumn(['chapter_id', 'topic_id', 'generation_config']);
            }
        });
    }
};
