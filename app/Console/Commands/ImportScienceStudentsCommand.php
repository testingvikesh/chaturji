<?php

namespace App\Console\Commands;

use App\Models\Standard;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportScienceStudentsCommand extends Command
{
    protected $signature = 'students:import-science
        {file=science-students-2026-10-06.json : JSON file inside database/data}
        {--password=Student@123 : Password for new science students}';

    protected $description = 'Create 11 Science and 12 Science standards if needed, then add or update those students';

    public function handle(): int
    {
        $file = (string) $this->argument('file');
        $path = database_path('data/'.$file);
        if (! is_file($path)) {
            $this->error('Science student file missing: '.$file);

            return self::FAILURE;
        }

        $rows = json_decode((string) file_get_contents($path), true);
        if (! is_array($rows) || $rows === []) {
            $this->error('Science student file is empty or invalid.');

            return self::FAILURE;
        }

        $password = (string) $this->option('password');
        $created = 0;
        $updated = 0;
        $skipped = 0;

        DB::transaction(function () use ($rows, $password, &$created, &$updated, &$skipped) {
            $slugs = [];
            foreach ($rows as $row) {
                $name = trim(preg_replace('/\s+/u', ' ', (string) ($row['name'] ?? '')) ?? '');
                $mobile = preg_replace('/\D+/', '', (string) ($row['mobile'] ?? '')) ?: '';
                $email = strtolower(trim((string) ($row['email'] ?? '')));
                $medium = strtolower(trim((string) ($row['medium'] ?? '')));
                $class = (string) ($row['class'] ?? '');

                if ($name === '' || strlen($mobile) < 8 || ! in_array($medium, ['english', 'gujarati'], true) || ! in_array($class, ['11', '12'], true)) {
                    $skipped++;
                    $this->line("Skip incomplete: {$name}");

                    continue;
                }

                if (strlen($mobile) < 10) {
                    $this->warn("{$name}: mobile {$mobile} is shorter than 10 digits. Saved as written in the sheet.");
                }

                $standard = $slugs[$class] ??= $this->ensureStandard($class);
                if ($email === '' || ! str_contains($email, '@')) {
                    $email = '';
                }

                $existing = User::query()->where('mobile', $mobile)->first();
                if ($existing && $existing->role !== 'student') {
                    $skipped++;
                    $this->line("Skip {$name}: mobile {$mobile} belongs to a non-student");

                    continue;
                }

                if ($existing && $existing->standard && $existing->standard !== $standard && ! str_contains((string) $existing->standard, 'science')) {
                    $skipped++;
                    $this->line("Skip {$name}: mobile {$mobile} is already used by {$existing->name} ({$existing->standard}).");

                    continue;
                }

                if (! $existing) {
                    $existing = User::students()
                        ->where('standard', $standard)
                        ->where('medium', $medium)
                        ->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($name)])
                        ->orderBy('id')
                        ->first();
                }

                if ($email !== '') {
                    $emailTaken = User::query()
                        ->where('email', $email)
                        ->when($existing, fn ($q) => $q->where('id', '!=', $existing->id))
                        ->exists();
                    if ($emailTaken) {
                        $this->line("{$name}: email already used, saved without email.");
                        $email = '';
                    }
                }

                if ($existing) {
                    $existing->name = $name;
                    $existing->medium = $medium;
                    $existing->standard = $standard;
                    $existing->is_approved = true;
                    if ($existing->mobile === null || $existing->mobile === '') {
                        $existing->mobile = $mobile;
                    }
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

        $this->info("Science students: {$created} created, {$updated} updated, {$skipped} skipped.");

        return self::SUCCESS;
    }

    private function ensureStandard(string $class): string
    {
        $name = $class.' Science';
        $preferred = 'std-'.$class.'-science';

        $matches = Standard::query()
            ->where('slug', $preferred)
            ->orWhereRaw('LOWER(name) = ?', [strtolower($name)])
            ->orWhere('name', 'like', '%'.$class.'%Science%')
            ->orWhere('name', 'like', '%'.$class.'%science%')
            ->get()
            ->filter(function (Standard $standard) use ($class) {
                $haystack = strtolower($standard->name.' '.$standard->slug);

                return str_contains($haystack, $class) && str_contains($haystack, 'science');
            })
            ->values();

        $standard = $matches->firstWhere('slug', $preferred)
            ?: $matches->first(fn (Standard $standard) => strcasecmp($standard->name, $name) === 0)
            ?: $matches->first();

        if (! $standard) {
            $standard = Standard::query()->create([
                'name' => $name,
                'slug' => $preferred,
                'medium' => 'gujarati',
                'sort_order' => (int) $class,
                'is_active' => true,
            ]);
            $this->info("Created standard {$standard->name} ({$standard->slug}).");
        } else {
            $this->info("Using standard {$standard->name} ({$standard->slug}).");
        }

        if (strlen((string) $standard->slug) > 20) {
            $this->error("Standard slug {$standard->slug} is longer than the student class field allows.");
        }

        return (string) $standard->slug;
    }
}
