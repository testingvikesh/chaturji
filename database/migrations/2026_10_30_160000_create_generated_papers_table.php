<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('generated_papers')) {
            return;
        }

        Schema::create('generated_papers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('standard_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->string('medium', 32);
            $table->string('title');
            $table->text('instructions')->nullable();
            $table->unsignedSmallInteger('duration_minutes')->nullable();
            $table->text('chapter_label')->nullable();
            $table->json('chapter_ids')->nullable();
            $table->json('type_counts');
            $table->json('questions');
            $table->unsignedInteger('total_marks')->default(0);
            $table->unsignedInteger('total_questions')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('generated_papers');
    }
};
