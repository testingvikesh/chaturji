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
        try {
            foreach (['11', '12'] as $class) {
                self::separateSlug($class);
            }
        } catch (Throwable $e) {
            report($e);
        }

        if (Cache::get('stream-classes-ready') === '2026-10-06-v4') {
            return;
        }

        try {
            $slugs = [];
            foreach (['11', '12'] as $class) {
                $slugs[$class] = [
                    'science' => self::ensureStream($class, 'science'),
                    'commerce' => self::ensureStream($class, 'commerce'),
                ];
            }

            $scienceFile = database_path('data/science-students-2026-10-06.json');
            $commerceFile = database_path('data/commerce-students-2026-10-06.json');
            if (! is_file($scienceFile) || ! is_file($commerceFile)) {
                return;
            }

            self::assignFile($scienceFile, $slugs, 'science');
            self::assignFile($commerceFile, $slugs, 'commerce');
            Cache::forever('stream-classes-ready', '2026-10-06-v4');
        } catch (Throwable $e) {
            report($e);
        }
    }

    /**
     * The student dropdown is keyed by slug. Commerce must not keep Science's slug,
     * or the list shows only Science.
     */
    private static function separateSlug(string $class): void
    {
        $commerce = self::findStream($class, 'commerce');
        $science = self::findStream($class, 'science');
        if (! $commerce || ! $science) {
            return;
        }

        if ($commerce->slug !== $science->slug && str_contains(strtolower((string) $commerce->slug), 'commerce')) {
            return;
        }

        $slug = 'std-'.$class.'-commerce';
        if (Standard::query()->where('slug', $slug)->where('id', '!=', $commerce->id)->exists()) {
            $slug .= '-'.$commerce->id;
        }

        if ($commerce->slug !== $slug) {
            $commerce->slug = $slug;
            $commerce->save();
        }
    }

    private static function findStream(string $class, string $stream): ?Standard
    {
        $other = $stream === 'commerce' ? 'science' : 'commerce';

        return Standard::query()->orderBy('id')->get()->first(function (Standard $standard) use ($class, $stream, $other) {
            $haystack = strtolower($standard->name.' '.$standard->slug);

            return str_contains($haystack, $class)
                && str_contains($haystack, $stream)
                && ! str_contains($haystack, $other);
        });
    }

    private static function ensureStream(string $class, string $stream): string
    {
        $title = $stream === 'commerce' ? 'Commerce' : 'Science';
        $name = "Std {$class} {$title}";
        $preferred = "std-{$class}-{$stream}";

        $existing = self::findStream($class, $stream);

        if ($existing) {
            $existing->is_active = true;
            if (! str_contains(strtolower((string) $existing->name), $stream)) {
                $existing->name = $name;
            }
            $existing->save();

            return (string) $existing->slug;
        }

        Standard::query()->create([
            'name' => $name,
            'slug' => $preferred,
            'medium' => 'gujarati',
            'sort_order' => 0,
            'is_active' => true,
        ]);

        return $preferred;
    }

    /**
     * @param  array<string, array{science: string, commerce: string}>  $slugs
     */
    private static function assignFile(string $path, array $slugs, string $stream): void
    {
        $rows = json_decode((string) file_get_contents($path), true);
        if (! is_array($rows)) {
            return;
        }

        foreach ($rows as $row) {
            $class = (string) ($row['class'] ?? '');
            $slug = $slugs[$class][$stream] ?? null;
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
}
