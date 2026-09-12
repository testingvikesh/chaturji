<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chapter_contents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chapter_id')->constrained()->cascadeOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->text('overview')->nullable();
            $table->unsignedSmallInteger('total_questions')->default(0);
            $table->string('source_filename');
            $table->string('source_path');
            $table->longText('raw_text')->nullable();
            $table->timestamps();

            $table->unique('chapter_id');
        });

        Schema::create('chapter_content_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chapter_content_id')->constrained()->cascadeOnDelete();
            $table->string('section_type');
            $table->string('title')->nullable();
            $table->longText('content');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['chapter_content_id', 'section_type']);
        });

        Schema::create('chapter_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chapter_content_id')->constrained()->cascadeOnDelete();
            $table->string('question_type');
            $table->text('question_text');
            $table->json('options')->nullable();
            $table->text('answer')->nullable();
            $table->json('metadata')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['chapter_content_id', 'question_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chapter_questions');
        Schema::dropIfExists('chapter_content_sections');
        Schema::dropIfExists('chapter_contents');
    }
};
