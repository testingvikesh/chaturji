<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_logs', function (Blueprint $table) {
            $table->id();
            $table->string('to_email')->nullable();
            $table->string('cc_email')->nullable();
            $table->string('bcc_email')->nullable();
            $table->string('from_email')->nullable();
            $table->string('subject')->nullable();
            $table->string('mailable')->nullable();
            $table->string('mailer', 50)->nullable();
            $table->string('status', 20)->default('sent');
            $table->longText('body_html')->nullable();
            $table->longText('body_text')->nullable();
            $table->text('error')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'sent_at']);
            $table->index('to_email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_logs');
    }
};
