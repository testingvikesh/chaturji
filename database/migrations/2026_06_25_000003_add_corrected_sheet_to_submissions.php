<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('homework_submissions', function (Blueprint $table) {
            if (! Schema::hasColumn('homework_submissions', 'corrected_sheet_path')) {
                $table->string('corrected_sheet_path')->nullable()->after('pdf_path');
            }
        });

        Schema::table('exam_submissions', function (Blueprint $table) {
            if (! Schema::hasColumn('exam_submissions', 'corrected_sheet_path')) {
                $table->string('corrected_sheet_path')->nullable()->after('pdf_path');
            }
        });
    }

    public function down(): void
    {
        Schema::table('homework_submissions', function (Blueprint $table) {
            if (Schema::hasColumn('homework_submissions', 'corrected_sheet_path')) {
                $table->dropColumn('corrected_sheet_path');
            }
        });

        Schema::table('exam_submissions', function (Blueprint $table) {
            if (Schema::hasColumn('exam_submissions', 'corrected_sheet_path')) {
                $table->dropColumn('corrected_sheet_path');
            }
        });
    }
};
