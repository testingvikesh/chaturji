<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('standards', 'medium')) {
            return;
        }

        Schema::table('standards', function (Blueprint $table) {
            $table->string('medium', 32)->default('english')->after('slug');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('standards', 'medium')) {
            return;
        }

        Schema::table('standards', function (Blueprint $table) {
            $table->dropColumn('medium');
        });
    }
};
