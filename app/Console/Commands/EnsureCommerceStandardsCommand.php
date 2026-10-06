<?php

namespace App\Console\Commands;

use App\Models\Standard;
use App\Models\User;
use Illuminate\Console\Command;

class EnsureCommerceStandardsCommand extends Command
{
    protected $signature = 'students:ensure-commerce-standards
        {file=commerce-students-2026-10-06.json : JSON file inside database/data}';

    protected $description = 'Show Std 11 Commerce and Std 12 Commerce in the student standard list';

    public function handle(): int
    {
        $moved = 0;
        foreach (['11', '12'] as $class) {
            $slug = $this->ensureStandard($class);
            $moved += $this->attachCommerceStudents($class, $slug);
            $this->info("Std {$class} Commerce is available ({$slug}).");
        }

        if ($moved > 0) {
            $this->info("Moved {$moved} commerce student(s) onto the commerce class.");
        }

        return self::SUCCESS;
    }

    private function ensureStandard(string $class): string
    {
        $name = "Std {$class} Commerce";
        $legacy = "std-{$class}";
        $preferred = "std-{$class}-commerce";

        $named = Standard::query()
            ->where(function ($query) use ($class, $preferred, $name) {
                $query->where('slug', $preferred)
                    ->orWhereRaw('LOWER(name) = ?', [strtolower($name)])
                    ->orWhere('name', 'like', '%'.$class.'%Commerce%')
                    ->orWhere('name', 'like', '%'.$class.'%commerce%');
            })
            ->get()
            ->first(function (Standard $standard) use ($class) {
                $haystack = strtolower($standard->name.' '.$standard->slug);

                return str_contains($haystack, $class) && str_contains($haystack, 'commerce');
            });

        if ($named) {
            $named->name = $name;
            $named->is_active = true;
            $named->sort_order = $this->sortOrder($class);
            $named->save();

            return (string) $named->slug;
        }

        $legacyRow = Standard::query()->where('slug', $legacy)->first();
        if ($legacyRow && ! str_contains(strtolower($legacyRow->name.' '.$legacyRow->slug), 'science')) {
            $legacyRow->name = $name;
            $legacyRow->is_active = true;
            $legacyRow->sort_order = $this->sortOrder($class);
            $legacyRow->save();

            return (string) $legacyRow->slug;
        }

        $slug = $legacyRow ? $preferred : $legacy;
        Standard::query()->create([
            'name' => $name,
            'slug' => $slug,
            'medium' => 'gujarati',
            'sort_order' => $this->sortOrder($class),
            'is_active' => true,
        ]);

        return $slug;
    }

    private function sortOrder(string $class): int
    {
        $science = Standard::query()
            ->get()
            ->first(function (Standard $standard) use ($class) {
                $haystack = strtolower($standard->name.' '.$standard->slug);

                return str_contains($haystack, $class) && str_contains($haystack, 'science');
            });

        return (int) ($science->sort_order ?? $class);
    }

    private function attachCommerceStudents(string $class, string $slug): int
    {
        $file = (string) $this->argument('file');
        $path = database_path('data/'.$file);
        if (! is_file($path)) {
            return 0;
        }

        $rows = json_decode((string) file_get_contents($path), true);
        if (! is_array($rows)) {
            return 0;
        }

        $mobiles = [];
        foreach ($rows as $row) {
            if ((string) ($row['class'] ?? '') !== $class) {
                continue;
            }
            $mobile = preg_replace('/\D+/', '', (string) ($row['mobile'] ?? '')) ?: '';
            if ($mobile !== '') {
                $mobiles[] = $mobile;
            }
        }

        if ($mobiles === []) {
            return 0;
        }

        return User::students()
            ->whereIn('mobile', $mobiles)
            ->where('standard', '!=', $slug)
            ->update(['standard' => $slug]);
    }
}
