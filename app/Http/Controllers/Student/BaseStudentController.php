<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Standard;

abstract class BaseStudentController extends Controller
{
    protected function resolveStandard(?string $slug): ?Standard
    {
        if (! $slug) {
            return null;
        }

        return Standard::where('slug', $slug)->where('is_active', true)->first();
    }

    protected function standardName(?string $slug): string
    {
        $standard = $this->resolveStandard($slug);

        return $standard?->name ?? str_replace('_', ' ', ucwords(str_replace('_', ' ', $slug ?? ''), '_'));
    }
}
