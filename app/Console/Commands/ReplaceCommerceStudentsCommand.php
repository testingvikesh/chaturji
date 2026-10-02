<?php

namespace App\Console\Commands;

use App\Models\Standard;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ReplaceCommerceStudentsCommand extends Command
{
    protected $signature = 'students:replace-commerce
        {file=commerce-students-2026-10-02.json : JSON file inside database/data}
        {--password=Student@123 : Password for new commerce students}
        {--dry-run : Show what would happen without deleting/creating}';

    protected $description = 'Remove old Std 11/12 commerce students and import the new commerce Excel list';

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

        $standardSlugs = $this->resolveCommerceStandardSlugs();
        if ($standardSlugs === []) {
            $this->error('Could not find Std 11 / Std 12 standards in the database.');

            return self::FAILURE;
        }

        $this->info('Commerce standards: '.implode(', ', $standardSlugs));

        $oldQuery = User::students()->where(function ($q) use ($standardSlugs) {
            $q->whereIn('standard', $standardSlugs)
                ->orWhereIn('standard', ['std-11', 'std-12', 'standard_11', 'standard_12', '11', '12'])
                ->orWhere('standard', 'like', '%std-11%')
                ->orWhere('standard', 'like', '%std-12%')
                ->orWhere('standard', 'like', '%standard_11%')
                ->orWhere('standard', 'like', '%standard_12%');
        });
        $oldCount = (clone $oldQuery)->count();
        $this->warn("Old commerce students (Std 11/12) to remove: {$oldCount}");

        $password = (string) $this->option('password');
        $dryRun = (bool) $this->option('dry-run');

        if ($dryRun) {
            $this->line('Dry run — would import '.count($rows).' students.');
            foreach ($rows as $row) {
                $this->line(($row['standard'] ?? '').' | '.($row['medium'] ?? '').' | '.($row['mobile'] ?? '').' | '.($row['name'] ?? ''));
            }

            return self::SUCCESS;
        }

        $created = 0;
        $skipped = 0;
        $deleted = 0;

        DB::transaction(function () use ($oldQuery, $rows, $password, $standardSlugs, &$created, &$skipped, &$deleted) {
            $deleted = $oldQuery->delete();

            $seenMobiles = [];
            foreach ($rows as $row) {
                $name = trim((string) ($row['name'] ?? ''));
                $mobile = preg_replace('/\D+/', '', (string) ($row['mobile'] ?? '')) ?: '';
                $email = strtolower(trim((string) ($row['email'] ?? '')));
                $medium = strtolower(trim((string) ($row['medium'] ?? '')));
                $standard = trim((string) ($row['standard'] ?? ''));

                if ($name === '' || $mobile === '' || ! in_array($medium, ['english', 'gujarati'], true)) {
                    $skipped++;
                    $this->line("Skip incomplete: {$name}");

                    continue;
                }

                if (! in_array($standard, $standardSlugs, true)) {
                    // Allow std-11 / std-12 from file even if DB uses same slugs list.
                    if (! preg_match('/^(std-|standard_)?(11|12)$/i', $standard)) {
                        $skipped++;
                        $this->line("Skip bad standard {$standard}: {$name}");

                        continue;
                    }
                }

                if (isset($seenMobiles[$mobile]) || User::query()->where('mobile', $mobile)->exists()) {
                    // Prefer mother mobile if father is taken.
                    $alt = preg_replace('/\D+/', '', (string) ($row['mother_mobile'] ?? '')) ?: '';
                    if ($alt !== '' && $alt !== $mobile && ! isset($seenMobiles[$alt]) && ! User::query()->where('mobile', $alt)->exists()) {
                        $mobile = $alt;
                    } else {
                        $skipped++;
                        $this->line("Skip mobile taken: {$name} ({$mobile})");

                        continue;
                    }
                }

                if ($email === '' || ! str_contains($email, '@') || User::query()->where('email', $email)->exists()) {
                    $email = '';
                }

                $seenMobiles[$mobile] = true;

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

        $this->info("Commerce replace done: deleted {$deleted}, created {$created}, skipped {$skipped}.");

        return self::SUCCESS;
    }

    /**
     * @return list<string>
     */
    private function resolveCommerceStandardSlugs(): array
    {
        $candidates = ['std-11', 'std-12', 'standard_11', 'standard_12', '11', '12'];

        $fromDb = Standard::query()
            ->where(function ($q) {
                $q->whereIn('slug', ['std-11', 'std-12', 'standard_11', 'standard_12', '11', '12'])
                    ->orWhere('name', 'like', '%11%')
                    ->orWhere('name', 'like', '%12%')
                    ->orWhere('slug', 'like', '%11%')
                    ->orWhere('slug', 'like', '%12%');
            })
            ->pluck('slug')
            ->map(fn ($s) => (string) $s)
            ->filter(function (string $slug) {
                return (bool) preg_match('/(^|[^0-9])(11|12)([^0-9]|$)/', $slug)
                    || in_array($slug, ['11', '12'], true);
            })
            ->unique()
            ->values()
            ->all();

        if ($fromDb !== []) {
            return $fromDb;
        }

        // Fallback to live sheet style if standards table empty/mismatched.
        return array_values(array_filter($candidates, fn ($s) => str_contains($s, '11') || str_contains($s, '12') || in_array($s, ['std-11', 'std-12'], true)));
    }
}
