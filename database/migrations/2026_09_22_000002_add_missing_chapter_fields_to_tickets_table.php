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

        Schema::table('tickets', function (Blueprint $table) {
            if (! Schema::hasColumn('tickets', 'medium')) {
                $table->string('medium', 32)->nullable()->after('category');
            }
            if (! Schema::hasColumn('tickets', 'standard_id')) {
                $table->foreignId('standard_id')->nullable()->after('medium')->constrained('standards')->nullOnDelete();
            }
            if (! Schema::hasColumn('tickets', 'subject_id')) {
                $table->foreignId('subject_id')->nullable()->after('standard_id')->constrained('subjects')->nullOnDelete();
            }
            if (! Schema::hasColumn('tickets', 'chapter_id')) {
                $table->unsignedBigInteger('chapter_id')->nullable()->after('subject_id');
            }
            if (! Schema::hasColumn('tickets', 'chapter_name')) {
                $table->string('chapter_name')->nullable()->after('chapter_id');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('tickets')) {
            return;
        }

        Schema::table('tickets', function (Blueprint $table) {
            if (Schema::hasColumn('tickets', 'standard_id')) {
                $table->dropConstrainedForeignId('standard_id');
            }
            if (Schema::hasColumn('tickets', 'subject_id')) {
                $table->dropConstrainedForeignId('subject_id');
            }
            foreach (['medium', 'chapter_id', 'chapter_name'] as $column) {
                if (Schema::hasColumn('tickets', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
