<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('teaching_logs')) {
            return;
        }

        Schema::table('teaching_logs', function (Blueprint $table) {
            if (! Schema::hasColumn('teaching_logs', 'concept_covered')) {
                $table->string('concept_covered', 500)->nullable()->after('notes');
            }
            if (! Schema::hasColumn('teaching_logs', 'homework_given')) {
                $table->boolean('homework_given')->default(false)->after('concept_covered');
            }
            if (! Schema::hasColumn('teaching_logs', 'material_shared')) {
                $table->boolean('material_shared')->default(false)->after('homework_given');
            }
            if (! Schema::hasColumn('teaching_logs', 'self_test_given')) {
                $table->boolean('self_test_given')->default(false)->after('material_shared');
            }
            if (! Schema::hasColumn('teaching_logs', 'period_label')) {
                $table->string('period_label', 100)->nullable()->after('self_test_given');
            }
            if (! Schema::hasColumn('teaching_logs', 'section')) {
                $table->string('section', 50)->nullable()->after('period_label');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('teaching_logs')) {
            return;
        }

        Schema::table('teaching_logs', function (Blueprint $table) {
            $columns = collect([
                'concept_covered',
                'homework_given',
                'material_shared',
                'self_test_given',
                'period_label',
                'section',
            ])->filter(fn (string $column) => Schema::hasColumn('teaching_logs', $column))->values()->all();

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
