<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('homeworks', function (Blueprint $table) {
            if (! Schema::hasColumn('homeworks', 'student_id')) {
                $table->foreignId('student_id')->nullable()->after('teacher_id')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('homeworks', 'is_self_homework')) {
                $table->boolean('is_self_homework')->default(false)->after('student_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('homeworks', function (Blueprint $table) {
            if (Schema::hasColumn('homeworks', 'student_id')) {
                $table->dropConstrainedForeignId('student_id');
            }
            if (Schema::hasColumn('homeworks', 'is_self_homework')) {
                $table->dropColumn('is_self_homework');
            }
        });
    }
};
