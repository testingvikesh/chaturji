<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('exam_answer_sheets')) {
            return;
        }

        Schema::table('exam_answer_sheets', function (Blueprint $table) {
            if (! Schema::hasColumn('exam_answer_sheets', 'ai_result')) {
                $table->json('ai_result')->nullable()->after('remarks');
            }
            if (! Schema::hasColumn('exam_answer_sheets', 'ai_error')) {
                $table->text('ai_error')->nullable()->after('ai_result');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('exam_answer_sheets')) {
            return;
        }

        Schema::table('exam_answer_sheets', function (Blueprint $table) {
            if (Schema::hasColumn('exam_answer_sheets', 'ai_error')) {
                $table->dropColumn('ai_error');
            }
            if (Schema::hasColumn('exam_answer_sheets', 'ai_result')) {
                $table->dropColumn('ai_result');
            }
        });
    }
};
