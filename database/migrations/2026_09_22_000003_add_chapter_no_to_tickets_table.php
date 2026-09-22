<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tickets')) {
            return;
        }

        if (! Schema::hasColumn('tickets', 'chapter_no')) {
            Schema::table('tickets', function (Blueprint $table) {
                $table->string('chapter_no', 64)->nullable()->after('chapter_name');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('tickets') && Schema::hasColumn('tickets', 'chapter_no')) {
            Schema::table('tickets', function (Blueprint $table) {
                $table->dropColumn('chapter_no');
            });
        }
    }
};
