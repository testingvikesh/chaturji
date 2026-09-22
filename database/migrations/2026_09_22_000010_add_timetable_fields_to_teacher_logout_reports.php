<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('teacher_logout_reports')) {
            return;
        }

        Schema::table('teacher_logout_reports', function (Blueprint $table) {
            if (! Schema::hasColumn('teacher_logout_reports', 'timetable_id')) {
                $table->unsignedBigInteger('timetable_id')->nullable()->after('subject_name');
            }
            if (! Schema::hasColumn('teacher_logout_reports', 'period_id')) {
                $table->unsignedBigInteger('period_id')->nullable()->after('timetable_id');
            }
            if (! Schema::hasColumn('teacher_logout_reports', 'period_label')) {
                $table->string('period_label', 64)->nullable()->after('period_id');
            }
            if (! Schema::hasColumn('teacher_logout_reports', 'section')) {
                $table->string('section', 32)->nullable()->after('period_label');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('teacher_logout_reports')) {
            return;
        }

        Schema::table('teacher_logout_reports', function (Blueprint $table) {
            foreach (['section', 'period_label', 'period_id', 'timetable_id'] as $col) {
                if (Schema::hasColumn('teacher_logout_reports', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
