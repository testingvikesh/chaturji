<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('principal_standards')) {
            return;
        }

        Schema::create('principal_standards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('principal_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('standard_id')->constrained('standards')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['principal_id', 'standard_id']);
            $table->index(['principal_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('principal_standards');
    }
};
