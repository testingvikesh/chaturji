<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chapter_contents', function (Blueprint $table) {
            if (! Schema::hasColumn('chapter_contents', 'original_pdf_path')) {
                $table->string('original_pdf_path')->nullable()->after('source_path');
            }
            if (! Schema::hasColumn('chapter_contents', 'original_pdf_filename')) {
                $table->string('original_pdf_filename')->nullable()->after('original_pdf_path');
            }
        });
    }

    public function down(): void
    {
        Schema::table('chapter_contents', function (Blueprint $table) {
            if (Schema::hasColumn('chapter_contents', 'original_pdf_filename')) {
                $table->dropColumn('original_pdf_filename');
            }
            if (Schema::hasColumn('chapter_contents', 'original_pdf_path')) {
                $table->dropColumn('original_pdf_path');
            }
        });
    }
};
