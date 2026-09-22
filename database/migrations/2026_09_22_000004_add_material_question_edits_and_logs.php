<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('material_topics') && ! Schema::hasColumn('material_topics', 'question_edits')) {
            Schema::table('material_topics', function (Blueprint $table) {
                $table->json('question_edits')->nullable()->after('section_json');
            });
        }

        if (! Schema::hasTable('material_question_edit_logs')) {
            Schema::create('material_question_edit_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
                $table->unsignedBigInteger('material_topic_id');
                $table->unsignedBigInteger('material_id')->nullable();
                $table->string('question_key', 64);
                $table->string('question_type', 64)->nullable();
                $table->text('old_question_text')->nullable();
                $table->text('new_question_text')->nullable();
                $table->longText('old_answer')->nullable();
                $table->longText('new_answer')->nullable();
                $table->json('old_options')->nullable();
                $table->json('new_options')->nullable();
                $table->string('subject_name')->nullable();
                $table->string('chapter_name')->nullable();
                $table->string('topic_title')->nullable();
                $table->string('medium', 32)->nullable();
                $table->timestamps();

                $table->index(['material_topic_id']);
                $table->index(['teacher_id', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('material_question_edit_logs');

        if (Schema::hasTable('material_topics') && Schema::hasColumn('material_topics', 'question_edits')) {
            Schema::table('material_topics', function (Blueprint $table) {
                $table->dropColumn('question_edits');
            });
        }
    }
};
