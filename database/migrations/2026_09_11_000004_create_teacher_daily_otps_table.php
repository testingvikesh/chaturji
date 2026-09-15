<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('teacher_daily_otps')) {
            return;
        }

        Schema::create('teacher_daily_otps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('otp', 4);
            $table->date('otp_date');
            $table->timestamps();

            $table->unique(['user_id', 'otp_date']);
            $table->index('otp_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_daily_otps');
    }
};
