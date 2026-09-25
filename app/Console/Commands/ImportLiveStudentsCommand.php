<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class ImportLiveStudentsCommand extends Command
{
    protected $signature = 'students:import-sheet';

    protected $description = 'Create approved students from the uploaded school sheets';

    public function handle(): int
    {
        $path = database_path('data/live-students-2026-09-25.json');
        if (! is_file($path)) {
            $this->error('Student list file is missing.');

            return self::FAILURE;
        }

        $rows = json_decode((string) file_get_contents($path), true);
        if (! is_array($rows)) {
            $this->error('Student list file is not valid.');

            return self::FAILURE;
        }

        $created = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            $name = trim((string) ($row['name'] ?? ''));
            $mobile = preg_replace('/\D+/', '', (string) ($row['mobile'] ?? '')) ?: '';
            $email = strtolower(trim((string) ($row['email'] ?? '')));
            $medium = (string) ($row['medium'] ?? '');
            $standard = (string) ($row['standard'] ?? '');

            if ($name === '' || strlen($mobile) < 10 || ! in_array($medium, ['english', 'gujarati'], true) || $standard === '') {
                $skipped++;
                $this->line("Skip {$name}: incomplete row");

                continue;
            }

            if (User::query()->where('mobile', $mobile)->exists()) {
                $skipped++;
                $this->line("Skip {$name}: mobile {$mobile} already registered");

                continue;
            }

            if ($email !== '' && User::query()->where('email', $email)->exists()) {
                $this->line("{$name}: email already used, saved without email");
                $email = '';
            }

            User::query()->create([
                'name' => $name,
                'mobile' => $mobile,
                'email' => $email !== '' ? $email : null,
                'medium' => $medium,
                'standard' => $standard,
                'password' => 'Student@123',
                'role' => 'student',
                'is_approved' => true,
            ]);
            $created++;
        }

        $this->info("Students imported: {$created} created, {$skipped} skipped.");

        return self::SUCCESS;
    }
}
