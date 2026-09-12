<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('notifications')) {
            Schema::create('notifications', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('type');
                $table->morphs('notifiable');
                $table->text('data');
                $table->timestamp('read_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('homework_questions')) {
            return;
        }

        $foreignKeyExists = DB::selectOne(
            'SELECT COUNT(*) AS total FROM information_schema.TABLE_CONSTRAINTS
             WHERE TABLE_SCHEMA = DATABASE()
             AND TABLE_NAME = ?
             AND CONSTRAINT_NAME = ?',
            ['homework_questions', 'homework_questions_homework_id_foreign']
        );

        if ((int) ($foreignKeyExists->total ?? 0) === 0) {
            Schema::table('homework_questions', function (Blueprint $table) {
                $table->foreign('homework_id')
                    ->references('id')
                    ->on('homeworks')
                    ->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('homework_questions')) {
            Schema::table('homework_questions', function (Blueprint $table) {
                $table->dropForeign(['homework_id']);
            });
        }

        Schema::dropIfExists('notifications');
    }
};
