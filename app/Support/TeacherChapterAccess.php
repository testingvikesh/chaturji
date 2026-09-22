<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class TeacherChapterAccess
{
    /**
     * @param  Collection<int, mixed>|iterable<int, mixed>  $sections
     * @param  array<string, mixed>  $questionGroups
     * @return array{0: Collection<int, mixed>, 1: array<string, mixed>}
     */
    public static function filterMaterial(iterable $sections, array|Collection $questionGroups, ?Request $request = null): array
    {
        $sections = collect($sections)->values();
        $questionGroups = $questionGroups instanceof Collection
            ? $questionGroups->all()
            : $questionGroups;

        // IP restriction removed — show all chapter detail blocks.
        return [$sections, $questionGroups];
    }
}
