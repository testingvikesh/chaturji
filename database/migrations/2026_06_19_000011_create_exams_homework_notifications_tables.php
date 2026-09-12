<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('exams')) {
            Schema::create('exams', function (Blueprint $table) {
                $table->id();
                $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
                $table->string('standard', 50);
                $table->foreignId('subject_id')->nullable()->constrained()->nullOnDelete();
                $table->string('title');
                $table->text('description')->nullable();
                $table->text('instructions')->nullable();
                $table->unsignedSmallInteger('duration_minutes')->nullable();
                $table->timestamp('starts_at')->nullable();
                $table->timestamp('ends_at')->nullable();
                $table->string('status', 20)->default('draft');
                $table->unsignedInteger('total_marks')->default(0);
                $table->timestamps();

                $table->index(['standard', 'status']);
            });
        }

        if (! Schema::hasTable('exam_questions')) {
            Schema::create('exam_questions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('exam_id')->constrained()->cascadeOnDelete();
                $table->string('question_type', 30);
                $table->text('question_text');
                $table->json('options')->nullable();
                $table->text('answer')->nullable();
                $table->unsignedSmallInteger('marks')->default(1);
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('homeworks')) {
            Schema::create('homeworks', function (Blueprint $table) {
                $table->id();
                $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
                $table->string('standard', 50);
                $table->foreignId('subject_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('chapter_id')->nullable()->constrained()->nullOnDelete();
                $table->string('title');
                $table->text('description')->nullable();
                $table->timestamp('due_at')->nullable();
                $table->string('status', 20)->default('draft');
                $table->timestamps();

                $table->index(['standard', 'status']);
            });
        }

        if (! Schema::hasTable('homework_questions')) {
            Schema::create('homework_questions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('homework_id')->constrained('homeworks')->cascadeOnDelete();
                $table->string('question_type', 30);
                $table->text('question_text');
                $table->json('options')->nullable();
                $table->text('answer')->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('notifications')) {
            Schema::create('notifications', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('type');
                $table->morphs('notifiable');
                $table->text('data');
                $table->timestamp('read_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('homework_questions');
        Schema::dropIfExists('homeworks');
        Schema::dropIfExists('exam_questions');
        Schema::dropIfExists('exams');
    }
};
