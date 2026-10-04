<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class ImportLiveStudentsCommand extends Command
{
    protected $signature = 'students:import-sheet {file? : JSON file name inside database/data}';

    protected $description = 'Create/update approved students from the uploaded school sheets';

    public function handle(): int
    {
        $file = (string) ($this->argument('file') ?: 'live-students-class-6-10-2026-10-04.json');
        $path = database_path('data/'.$file);
        if (! is_file($path)) {
            $this->error('Student list file is missing: '.$file);

            return self::FAILURE;
        }

        $rows = json_decode((string) file_get_contents($path), true);
        if (! is_array($rows)) {
            $this->error('Student list file is not valid.');

            return self::FAILURE;
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            $name = trim((string) ($row['name'] ?? ''));
            $mobile = preg_replace('/\D+/', '', (string) ($row['mobile'] ?? '')) ?: '';
            $email = strtolower(trim((string) ($row['email'] ?? '')));
            $medium = (string) ($row['medium'] ?? '');
            $standard = (string) ($row['standard'] ?? '');

            if ($name === '' || $mobile === '' || ! in_array($medium, ['english', 'gujarati'], true) || $standard === '') {
                $skipped++;
                $this->line("Skip {$name}: incomplete row");

                continue;
            }

            // Email is optional. Prefer the JSON email only when free (or already on this student).
            if ($email === '' || ! str_contains($email, '@')) {
                $email = null;
            }

            $existing = User::query()->where('mobile', $mobile)->first();
            if ($existing) {
                if ($existing->role !== 'student') {
                    $skipped++;
                    $this->line("Skip {$name}: mobile {$mobile} belongs to a non-student");

                    continue;
                }

                $dirty = false;
                if ($existing->name !== $name) {
                    $existing->name = $name;
                    $dirty = true;
                }
                if ($existing->medium !== $medium) {
                    $existing->medium = $medium;
                    $dirty = true;
                }
                if ($existing->standard !== $standard) {
                    $existing->standard = $standard;
                    $dirty = true;
                }
                if (! $existing->is_approved) {
                    $existing->is_approved = true;
                    $dirty = true;
                }
                if ($email) {
                    $taken = User::query()
                        ->where('email', $email)
                        ->where('id', '!=', $existing->id)
                        ->exists();
                    if (! $taken && (string) $existing->email !== $email) {
                        $existing->email = $email;
                        $dirty = true;
                    }
                }

                if ($dirty) {
                    $existing->save();
                    $updated++;
                } else {
                    $skipped++;
                }

                continue;
            }

            if ($email && User::query()->where('email', $email)->exists()) {
                $email = null;
            }

            User::query()->create([
                'name' => $name,
                'mobile' => $mobile,
                'email' => $email,
                'medium' => $medium,
                'standard' => $standard,
                'password' => 'Student@123',
                'role' => 'student',
                'is_approved' => true,
            ]);
            $created++;
        }

        $this->info("Students imported: {$created} created, {$updated} updated, {$skipped} skipped.");

        return self::SUCCESS;
    }
}
