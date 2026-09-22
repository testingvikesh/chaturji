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
            if (! Schema::hasColumn('teacher_logout_reports', 'topic_ids')) {
                $table->json('topic_ids')->nullable()->after('topic_name');
            }
            if (! Schema::hasColumn('teacher_logout_reports', 'topic_names')) {
                $table->text('topic_names')->nullable()->after('topic_ids');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('teacher_logout_reports')) {
            return;
        }

        Schema::table('teacher_logout_reports', function (Blueprint $table) {
            if (Schema::hasColumn('teacher_logout_reports', 'topic_names')) {
                $table->dropColumn('topic_names');
            }
            if (Schema::hasColumn('teacher_logout_reports', 'topic_ids')) {
                $table->dropColumn('topic_ids');
            }
        });
    }
};
