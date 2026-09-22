<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('school_periods')) {
            Schema::create('school_periods', function (Blueprint $table) {
                $table->id();
                $table->unsignedTinyInteger('period_no');
                $table->string('name', 64);
                $table->time('start_time')->nullable();
                $table->time('end_time')->nullable();
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->unique('period_no');
            });
        }

        if (Schema::hasTable('school_periods') && DB::table('school_periods')->count() === 0) {
            $now = now();
            $rows = [];
            for ($i = 1; $i <= 8; $i++) {
                $rows[] = [
                    'period_no' => $i,
                    'name' => 'Period '.$i,
                    'start_time' => null,
                    'end_time' => null,
                    'sort_order' => $i,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            DB::table('school_periods')->insert($rows);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('school_periods');
    }
};
