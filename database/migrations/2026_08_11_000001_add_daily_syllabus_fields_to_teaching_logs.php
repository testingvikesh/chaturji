<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teaching_logs', function (Blueprint $table) {
            $table->string('concept_covered', 500)->nullable()->after('notes');
            $table->boolean('homework_given')->default(false)->after('concept_covered');
            $table->boolean('material_shared')->default(false)->after('homework_given');
            $table->boolean('self_test_given')->default(false)->after('material_shared');
            $table->string('period_label', 100)->nullable()->after('self_test_given');
            $table->string('section', 50)->nullable()->after('period_label');
        });
    }

    public function down(): void
    {
        Schema::table('teaching_logs', function (Blueprint $table) {
            $table->dropColumn([
                'concept_covered',
                'homework_given',
                'material_shared',
                'self_test_given',
                'period_label',
                'section',
            ]);
        });
    }
};
