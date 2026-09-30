<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('mentor_students')) {
            return;
        }

        Schema::create('mentor_students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('principal_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('medium', 20)->nullable();
            $table->string('standard', 50)->nullable();
            $table->timestamps();

            // One student → one mentor teacher.
            $table->unique('student_id');
            $table->index(['teacher_id', 'standard', 'medium']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mentor_students');
    }
};
