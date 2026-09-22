<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('student_work_attempts')) {
            return;
        }

        Schema::create('student_work_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('work_type', 32); // exam_objective, homework_objective, exam_sheet, homework_sheet
            $table->string('paper_type', 32)->nullable(); // exam | homework
            $table->unsignedBigInteger('paper_id')->nullable();
            $table->unsignedInteger('answered_count')->default(0);
            $table->unsignedInteger('correct_count')->default(0);
            $table->unsignedInteger('total_objective')->default(0);
            $table->decimal('earned_marks', 8, 2)->default(0);
            $table->decimal('max_marks', 8, 2)->default(0);
            $table->string('status', 32)->default('submitted'); // submitted / completed
            $table->timestamp('attempted_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'attempted_at']);
            $table->index(['work_type', 'attempted_at']);
            $table->index(['paper_type', 'paper_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_work_attempts');
    }
};
