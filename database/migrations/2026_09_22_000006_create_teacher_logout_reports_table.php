<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('teacher_logout_reports')) {
            return;
        }

        Schema::create('teacher_logout_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
            $table->date('report_date');
            $table->string('employee_code', 32)->nullable();
            $table->string('medium', 32)->nullable();
            $table->string('standard', 50)->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('subject_name')->nullable();
            $table->unsignedBigInteger('chapter_id')->nullable();
            $table->string('chapter_name')->nullable();
            $table->unsignedBigInteger('topic_id')->nullable();
            $table->string('topic_name')->nullable();
            $table->boolean('chk_medium')->default(false);
            $table->boolean('chk_standard')->default(false);
            $table->boolean('chk_subject')->default(false);
            $table->boolean('chk_chapter')->default(false);
            $table->boolean('chk_topic')->default(false);
            $table->boolean('chk_complete')->default(false);
            $table->boolean('chk_remain')->default(false);
            $table->string('status', 32)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('mail_sent')->default(false);
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->index(['teacher_id', 'report_date']);
            $table->index(['report_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_logout_reports');
    }
};
