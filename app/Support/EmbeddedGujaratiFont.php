<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;

/**
 * GD-only Gujarati font for Hostinger (no Imagick).
 * Font ships at app/Support/fonts/NotoSansGujarati-Regular.ttf and is copied to writable dirs.
 */
final class EmbeddedGujaratiFont
{
    public static function path(): string
    {
        $bundled = __DIR__.DIRECTORY_SEPARATOR.'fonts'.DIRECTORY_SEPARATOR.'NotoSansGujarati-Regular.ttf';

        $candidates = array_values(array_filter([
            $bundled,
            dirname(__DIR__, 2).'/resources/fonts/NotoSansGujarati-Regular.ttf',
            dirname(__DIR__, 2).'/public/fonts/NotoSansGujarati-Regular.ttf',
        ]));

        try {
            $candidates[] = resource_path('fonts/NotoSansGujarati-Regular.ttf');
            $candidates[] = public_path('fonts/NotoSansGujarati-Regular.ttf');
            $candidates[] = storage_path('fonts/NotoSansGujarati-Regular.ttf');
        } catch (\Throwable) {
            // ignore
        }

        $source = null;
        foreach ($candidates as $path) {
            if (is_string($path) && is_file($path) && is_readable($path) && filesize($path) > 100000) {
                $source = $path;
                break;
            }
        }

        if ($source === null) {
            throw new \RuntimeException(
                'Gujarati font missing. Upload app/Support/fonts/NotoSansGujarati-Regular.ttf to Hostinger.'
            );
        }

        // Prefer using the bundled path directly (readable on Hostinger).
        if (IndicScript::fontRendersGujarati($source)) {
            self::mirrorToPublic($source);

            return $source;
        }

        // If probe failed but file is clearly our Noto Gujarati, still use it.
        if (preg_match('/gujarati/i', basename($source))) {
            self::mirrorToPublic($source);

            return $source;
        }

        throw new \RuntimeException('Gujarati font file is not usable by GD: '.$source);
    }

    private static function mirrorToPublic(string $source): void
    {
        $targets = [];
        try {
            $targets[] = public_path('fonts/NotoSansGujarati-Regular.ttf');
            $targets[] = storage_path('fonts/NotoSansGujarati-Regular.ttf');
        } catch (\Throwable) {
            $targets[] = dirname(__DIR__, 2).'/public/fonts/NotoSansGujarati-Regular.ttf';
            $targets[] = dirname(__DIR__, 2).'/storage/fonts/NotoSansGujarati-Regular.ttf';
        }

        $targets[] = sys_get_temp_dir().DIRECTORY_SEPARATOR.'NotoSansGujarati-Regular.ttf';

        foreach ($targets as $target) {
            try {
                $dir = dirname($target);
                if (! is_dir($dir)) {
                    @mkdir($dir, 0755, true);
                }
                if (! is_file($target) || filesize($target) < 100000) {
                    @copy($source, $target);
                }
            } catch (\Throwable $e) {
                Log::debug('Font mirror skip: '.$e->getMessage());
            }
        }
    }
}
