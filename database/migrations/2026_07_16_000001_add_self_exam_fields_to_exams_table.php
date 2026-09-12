<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->foreignId('student_id')->nullable()->after('teacher_id')->constrained('users')->nullOnDelete();
            $table->boolean('is_self_exam')->default(false)->after('student_id');
            $table->index(['student_id', 'is_self_exam']);
        });
    }

    public function down(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->dropIndex(['student_id', 'is_self_exam']);
            $table->dropConstrainedForeignId('student_id');
            $table->dropColumn('is_self_exam');
        });
    }
};
