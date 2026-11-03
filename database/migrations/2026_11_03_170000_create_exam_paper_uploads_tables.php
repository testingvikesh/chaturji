<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('exam_paper_uploads')) {
            Schema::create('exam_paper_uploads', function (Blueprint $table) {
                $table->id();
                $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('standard_id')->constrained()->cascadeOnDelete();
                $table->string('subject_name');
                $table->string('title');
                $table->date('exam_date')->nullable();
                $table->unsignedSmallInteger('total_marks')->default(0);
                $table->string('paper_path');
                $table->string('paper_name')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('exam_answer_sheets')) {
            Schema::create('exam_answer_sheets', function (Blueprint $table) {
                $table->id();
                $table->foreignId('exam_paper_upload_id')->constrained()->cascadeOnDelete();
                $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
                $table->string('file_path');
                $table->string('file_name')->nullable();
                $table->decimal('marks_obtained', 6, 2)->nullable();
                $table->text('remarks')->nullable();
                $table->timestamp('checked_at')->nullable();
                $table->timestamps();

                $table->unique(['exam_paper_upload_id', 'student_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_answer_sheets');
        Schema::dropIfExists('exam_paper_uploads');
    }
};
