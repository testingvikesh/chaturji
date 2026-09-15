<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class TeacherChapterAccess
{
    /**
     * Chapter content sections only visible on allowed IPs (teacher + student panels).
     *
     * @var list<string>
     */
    public const RESTRICTED_SECTION_TYPES = [
        'introduction',
        'trailer',
        'importance_of_this_topic',
        'what_i_like',
        'gun',
        'kala',
        'sankar',
    ];

    /**
     * Question groups only visible on allowed IPs (teacher + student panels).
     *
     * @var list<string>
     */
    public const RESTRICTED_QUESTION_TYPES = [
        'knowledge_ladder',
        'line_to_line',
    ];

    public static function isTeacherRequest(?Request $request = null): bool
    {
        $request ??= request();

        return $request->routeIs('teacher.*');
    }

    public static function isStudentRequest(?Request $request = null): bool
    {
        $request ??= request();

        return $request->routeIs('student.*');
    }

    /**
     * Teacher and student material readers both apply the IP gate.
     */
    public static function isRestrictedPanelRequest(?Request $request = null): bool
    {
        return self::isTeacherRequest($request) || self::isStudentRequest($request);
    }

    public static function allowsCurrentIp(?Request $request = null): bool
    {
        $allowed = self::allowedIps();

        if ($allowed === []) {
            return false;
        }

        foreach (self::clientIps($request) as $ip) {
            if (in_array($ip, $allowed, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    public static function clientIps(?Request $request = null): array
    {
        $request ??= request();
        $ips = [];

        $ip = trim((string) $request->ip());
        if ($ip !== '') {
            $ips[] = $ip;
        }

        $forwarded = $request->header('X-Forwarded-For');
        if (is_string($forwarded) && $forwarded !== '') {
            foreach (explode(',', $forwarded) as $part) {
                $part = trim($part);
                if ($part !== '') {
                    $ips[] = $part;
                }
            }
        }

        return array_values(array_unique($ips));
    }

    /**
     * @return list<string>
     */
    public static function allowedIps(): array
    {
        $configured = config('materials.teacher_chapter_detail_ips', ['103.81.116.121']);

        if (is_string($configured)) {
            $configured = explode(',', $configured);
        }

        return array_values(array_filter(array_map(
            static fn ($ip) => trim((string) $ip),
            is_array($configured) ? $configured : []
        )));
    }

    /**
     * On teacher/student panels, strip restricted blocks unless client IP is allowed.
     *
     * @param  Collection<int, mixed>|iterable<int, mixed>  $sections
     * @param  array<string, mixed>  $questionGroups
     * @return array{0: Collection<int, mixed>, 1: array<string, mixed>}
     */
    public static function filterMaterial(iterable $sections, array|Collection $questionGroups, ?Request $request = null): array
    {
        $sections = collect($sections);
        $questionGroups = $questionGroups instanceof Collection
            ? $questionGroups->all()
            : $questionGroups;

        if (! self::isRestrictedPanelRequest($request) || self::allowsCurrentIp($request)) {
            return [$sections->values(), $questionGroups];
        }

        $sections = $sections
            ->reject(function ($section) {
                $type = is_object($section)
                    ? ($section->section_type ?? null)
                    : ($section['section_type'] ?? null);

                return in_array($type, self::RESTRICTED_SECTION_TYPES, true);
            })
            ->values();

        foreach (self::RESTRICTED_QUESTION_TYPES as $type) {
            unset($questionGroups[$type]);
        }

        return [$sections, $questionGroups];
    }
}
