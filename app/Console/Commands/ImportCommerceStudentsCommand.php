<?php

namespace App\Console\Commands;

use App\Models\Standard;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportCommerceStudentsCommand extends Command
{
    protected $signature = 'students:import-commerce
        {file=commerce-students-2026-10-06.json : JSON file inside database/data}
        {--password=Student@123 : Password for new commerce students}';

    protected $description = 'Add or update 11 Commerce and 12 Commerce students from the commerce email sheet';

    public function handle(): int
    {
        $file = (string) $this->argument('file');
        $path = database_path('data/'.$file);
        if (! is_file($path)) {
            $this->error('Commerce student file missing: '.$file);

            return self::FAILURE;
        }

        $rows = json_decode((string) file_get_contents($path), true);
        if (! is_array($rows) || $rows === []) {
            $this->error('Commerce student file is empty or invalid.');

            return self::FAILURE;
        }

        $this->call('students:ensure-commerce-standards', ['file' => $file]);

        $password = (string) $this->option('password');
        $created = 0;
        $updated = 0;
        $skipped = 0;

        DB::transaction(function () use ($rows, $password, &$created, &$updated, &$skipped) {
            $slugs = [];
            foreach ($rows as $row) {
                $name = trim(preg_replace('/\s+/u', ' ', (string) ($row['name'] ?? '')) ?? '');
                $mobile = preg_replace('/\D+/', '', (string) ($row['mobile'] ?? '')) ?: '';
                $mother = preg_replace('/\D+/', '', (string) ($row['mother_mobile'] ?? '')) ?: '';
                $email = strtolower(trim((string) ($row['email'] ?? '')));
                $medium = strtolower(trim((string) ($row['medium'] ?? '')));
                $class = (string) ($row['class'] ?? '');

                if ($name === '' || strlen($mobile) !== 10 || ! in_array($medium, ['english', 'gujarati'], true) || ! in_array($class, ['11', '12'], true)) {
                    $skipped++;
                    $this->line("Skip incomplete: {$name}");

                    continue;
                }

                $standard = $slugs[$class] ??= $this->commerceSlug($class);
                if ($standard === null) {
                    $skipped++;
                    $this->line("Skip {$name}: Std {$class} Commerce is missing.");

                    continue;
                }

                $existing = $this->studentForMobile($mobile);
                if (! $existing && $this->mobileBlocked($mobile) && strlen($mother) === 10 && ! $this->mobileBlocked($mother)) {
                    $mobile = $mother;
                    $existing = $this->studentForMobile($mobile);
                }

                if (! $existing && $this->mobileBlocked($mobile)) {
                    $skipped++;
                    $this->line("Skip {$name}: mobile {$mobile} is already used.");

                    continue;
                }

                if ($email === '' || ! str_contains($email, '@')) {
                    $email = '';
                }
                if ($email !== '') {
                    $taken = User::query()
                        ->where('email', $email)
                        ->when($existing, fn ($query) => $query->where('id', '!=', $existing->id))
                        ->exists();
                    if ($taken) {
                        $this->line("{$name}: email already used, saved without email.");
                        $email = '';
                    }
                }

                if ($existing) {
                    $existing->name = $name;
                    $existing->medium = $medium;
                    $existing->standard = $standard;
                    $existing->is_approved = true;
                    if ($email !== '') {
                        $existing->email = $email;
                    }
                    $existing->save();
                    $updated++;

                    continue;
                }

                User::query()->create([
                    'name' => $name,
                    'mobile' => $mobile,
                    'email' => $email !== '' ? $email : null,
                    'medium' => $medium,
                    'standard' => $standard,
                    'password' => $password,
                    'role' => 'student',
                    'is_approved' => true,
                ]);
                $created++;
            }
        });

        $this->info("Commerce students: {$created} created, {$updated} updated, {$skipped} skipped.");

        return self::SUCCESS;
    }

    private function commerceSlug(string $class): ?string
    {
        $slug = Standard::query()->where('name', "Std {$class} Commerce")->value('slug');

        return $slug ? (string) $slug : null;
    }

    private function studentForMobile(string $mobile): ?User
    {
        $user = User::query()->where('mobile', $mobile)->first();
        if (! $user || $user->role !== 'student') {
            return null;
        }

        if (str_contains(strtolower((string) $user->standard), 'science')) {
            return null;
        }

        return $user;
    }

    private function mobileBlocked(string $mobile): bool
    {
        $user = User::query()->where('mobile', $mobile)->first();
        if (! $user) {
            return false;
        }

        return $user->role !== 'student' || str_contains(strtolower((string) $user->standard), 'science');
    }
}
