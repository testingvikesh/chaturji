<?php

namespace App\Support;

use App\Models\Standard;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Throwable;

class CommerceStandardSetup
{
    public static function ensure(): void
    {
        if (Cache::get('commerce-standards-ready') === '2026-10-06-v2') {
            return;
        }

        try {
            $slugs = [];
            foreach (['11', '12'] as $class) {
                $slugs[$class] = self::ensureStandard($class);
            }

            $path = database_path('data/commerce-students-2026-10-06.json');
            if (! is_file($path)) {
                return;
            }

            self::assignStudents($slugs);
            Cache::forever('commerce-standards-ready', '2026-10-06-v2');
        } catch (Throwable $e) {
            report($e);
        }
    }

    /**
     * @param  array<string, string>  $slugs
     */
    private static function assignStudents(array $slugs): void
    {
        $path = database_path('data/commerce-students-2026-10-06.json');
        if (! is_file($path)) {
            return;
        }

        $rows = json_decode((string) file_get_contents($path), true);
        if (! is_array($rows)) {
            return;
        }

        foreach ($rows as $row) {
            $class = (string) ($row['class'] ?? '');
            $slug = $slugs[$class] ?? null;
            $name = trim(preg_replace('/\s+/u', ' ', (string) ($row['name'] ?? '')) ?? '');
            $mobile = preg_replace('/\D+/', '', (string) ($row['mobile'] ?? '')) ?: '';
            $medium = strtolower(trim((string) ($row['medium'] ?? '')));
            $email = strtolower(trim((string) ($row['email'] ?? '')));

            if ($slug === null || $name === '' || strlen($mobile) !== 10 || ! in_array($medium, ['english', 'gujarati'], true)) {
                continue;
            }

            $user = User::query()->where('mobile', $mobile)->first();
            if ($user && $user->role !== 'student') {
                continue;
            }

            if ($email === '' || ! str_contains($email, '@')) {
                $email = null;
            } elseif (User::query()->where('email', $email)->when($user, fn ($query) => $query->where('id', '!=', $user->id))->exists()) {
                $email = null;
            }

            if ($user) {
                $user->name = $name;
                $user->medium = $medium;
                $user->standard = $slug;
                $user->is_approved = true;
                if ($email) {
                    $user->email = $email;
                }
                $user->save();

                continue;
            }

            User::query()->create([
                'name' => $name,
                'mobile' => $mobile,
                'email' => $email,
                'medium' => $medium,
                'standard' => $slug,
                'password' => 'Student@123',
                'role' => 'student',
                'is_approved' => true,
            ]);
        }
    }

    private static function ensureStandard(string $class): string
    {
        $name = "Std {$class} Commerce";
        $slug = "std-{$class}-commerce";

        $existing = Standard::query()
            ->where('slug', $slug)
            ->orWhere('name', $name)
            ->first();

        $science = Standard::query()
            ->where('name', 'like', '%'.$class.'%Science%')
            ->orWhere('name', 'like', '%'.$class.'%science%')
            ->first();
        $sort = (int) ($science->sort_order ?? 0);

        if ($existing) {
            $existing->name = $name;
            $existing->is_active = true;
            $existing->sort_order = $sort;
            $existing->save();

            return (string) $existing->slug;
        }

        Standard::query()->create([
            'name' => $name,
            'slug' => $slug,
            'medium' => 'gujarati',
            'sort_order' => $sort,
            'is_active' => true,
        ]);

        return $slug;
    }
}
