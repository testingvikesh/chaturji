<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chapter_contents', function (Blueprint $table) {
            $table->string('extraction_method')->nullable()->after('language');
            $table->unsignedSmallInteger('page_count')->default(0)->after('extraction_method');
        });
    }

    public function down(): void
    {
        Schema::table('chapter_contents', function (Blueprint $table) {
            $table->dropColumn(['extraction_method', 'page_count']);
        });
    }
};
