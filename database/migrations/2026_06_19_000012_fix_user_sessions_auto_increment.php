<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('user_sessions')) {
            return;
        }

        $this->ensurePrimaryKey('user_sessions');

        if ($this->hasAutoIncrement('user_sessions')) {
            return;
        }

        $this->enableAutoIncrement('user_sessions');
    }

    public function down(): void
    {
        if (! Schema::hasTable('user_sessions') || ! $this->hasAutoIncrement('user_sessions')) {
            return;
        }

        DB::statement(
            'ALTER TABLE `user_sessions` MODIFY `id` BIGINT UNSIGNED NOT NULL'
        );
    }

    private function ensurePrimaryKey(string $table): void
    {
        $hasPrimaryKey = DB::selectOne(
            'SELECT COUNT(*) AS total FROM information_schema.TABLE_CONSTRAINTS
             WHERE TABLE_SCHEMA = DATABASE()
             AND TABLE_NAME = ?
             AND CONSTRAINT_TYPE = ?',
            [$table, 'PRIMARY KEY']
        );

        if ((int) ($hasPrimaryKey->total ?? 0) === 0) {
            DB::statement("ALTER TABLE `{$table}` ADD PRIMARY KEY (`id`)");
        }
    }

    private function hasAutoIncrement(string $table): bool
    {
        $column = DB::selectOne(
            'SELECT EXTRA FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
             AND TABLE_NAME = ?
             AND COLUMN_NAME = ?',
            [$table, 'id']
        );

        return str_contains((string) ($column->EXTRA ?? ''), 'auto_increment');
    }

    private function enableAutoIncrement(string $table): void
    {
        $maxId = DB::table($table)->max('id') ?? 0;
        $nextId = max(1, (int) $maxId + 1);

        DB::statement(
            "ALTER TABLE `{$table}` MODIFY `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT={$nextId}"
        );
    }
};
