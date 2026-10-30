<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('paper_styles')) {
            return;
        }

        Schema::create('paper_styles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('standard_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->json('type_counts');
            $table->timestamps();

            $table->unique(['standard_id', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paper_styles');
    }
};
