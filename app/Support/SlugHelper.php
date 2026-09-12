<?php

namespace App\Support;

use Illuminate\Support\Str;

class SlugHelper
{
    public static function unique(string $name, callable $exists): string
    {
        $base = Str::slug($name);

        if ($base === '') {
            $base = 'item-'.substr(hash('sha256', $name), 0, 10);
        }

        $slug = $base;
        $counter = 1;

        while ($exists($slug)) {
            $slug = $base.'-'.$counter;
            $counter++;
        }

        return $slug;
    }
}
