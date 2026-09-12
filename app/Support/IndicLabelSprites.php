<?php

namespace App\Support;

/**
 * Pre-shaped Gujarati UI label PNGs (Hostinger GD cannot shape ક્ષ/matras correctly).
 */
final class IndicLabelSprites
{
    /**
     * @return array{path: string, width: int, height: int}|null
     */
    public static function find(string $lang, string $key): ?array
    {
        $lang = PaperLanguage::normalize($lang);
        if ($lang !== 'gu') {
            return null;
        }

        $candidates = [
            resource_path('fonts/labels/gu/'.$key.'.png'),
            public_path('fonts/labels/gu/'.$key.'.png'),
            base_path('resources/fonts/labels/gu/'.$key.'.png'),
            base_path('public/fonts/labels/gu/'.$key.'.png'),
            dirname(__DIR__, 2).'/resources/fonts/labels/gu/'.$key.'.png',
            dirname(__DIR__, 2).'/public/fonts/labels/gu/'.$key.'.png',
        ];

        foreach ($candidates as $path) {
            if (! is_file($path) || ! is_readable($path)) {
                continue;
            }
            $info = @getimagesize($path);
            if (! is_array($info) || ($info[0] ?? 0) < 2) {
                continue;
            }

            return [
                'path' => $path,
                'width' => (int) $info[0],
                'height' => (int) $info[1],
            ];
        }

        return null;
    }
}
