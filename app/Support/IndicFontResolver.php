<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Finds Gujarati/Devanagari TTF fonts on local XAMPP and Hostinger layouts.
 */
final class IndicFontResolver
{
    private static ?string $cachedIndic = null;

    private static ?string $cachedLatin = null;

    public static function indic(): ?string
    {
        if (self::$cachedIndic !== null) {
            return self::$cachedIndic !== '' ? self::$cachedIndic : null;
        }

        $found = self::firstExisting(self::indicCandidates());
        if ($found === null) {
            $found = self::ensureBundledFontCopied();
        }
        // Hostinger-safe: font is embedded in PHP — no File Manager / Imagick needed.
        if ($found === null) {
            try {
                $found = EmbeddedGujaratiFont::path();
            } catch (\Throwable $e) {
                Log::error('Embedded Gujarati font failed: '.$e->getMessage());
            }
        }
        if ($found === null) {
            $found = self::tryDownloadNotoGujarati();
        }

        self::$cachedIndic = $found ?? '';

        if ($found === null) {
            Log::error('Indic font missing: upload NotoSansGujarati-Regular.ttf to resources/fonts or public/fonts', [
                'checked' => self::indicCandidates(),
            ]);
        }

        return $found;
    }

    public static function latin(): ?string
    {
        if (self::$cachedLatin !== null) {
            return self::$cachedLatin !== '' ? self::$cachedLatin : null;
        }

        $found = self::firstExisting(self::latinCandidates());
        self::$cachedLatin = $found ?? '';

        return $found;
    }

    /**
     * @return array{indic: ?string, latin: ?string, indic_ok: bool, candidates_indic: list<string>}
     */
    public static function diagnose(): array
    {
        self::$cachedIndic = null;
        self::$cachedLatin = null;

        $indic = self::indic();
        $latin = self::latin();

        return [
            'indic' => $indic,
            'latin' => $latin,
            'indic_ok' => is_string($indic) && is_file($indic) && filesize($indic) > 10000,
            'candidates_indic' => self::indicCandidates(),
        ];
    }

    /**
     * @return list<string>
     */
    public static function indicCandidates(): array
    {
        $names = [
            'NotoSansGujarati-Regular.ttf',
            'NotoSansGujarati.ttf',
            'NotoSansDevanagari-Regular.ttf',
            'Lohit-Gujarati.ttf',
        ];

        $dirs = self::fontDirectories();
        $paths = [];
        // Bundled with the app — primary Hostinger GD path (no Imagick).
        $paths[] = __DIR__.DIRECTORY_SEPARATOR.'fonts'.DIRECTORY_SEPARATOR.'NotoSansGujarati-Regular.ttf';
        foreach ($dirs as $dir) {
            foreach ($names as $name) {
                $paths[] = rtrim($dir, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$name;
            }
        }

        $paths[] = '/usr/share/fonts/truetype/noto/NotoSansGujarati-Regular.ttf';
        $paths[] = '/usr/share/fonts/truetype/noto/NotoSansDevanagari-Regular.ttf';
        $paths[] = '/usr/share/fonts/truetype/lohit-gujarati/Lohit-Gujarati.ttf';
        // Do NOT use FreeSans/DejaVu here — they load but draw Gujarati as □□□.
        $paths[] = 'C:\\Windows\\Fonts\\Nirmala.ttf';
        $paths[] = 'C:\\Windows\\Fonts\\mangal.ttf';
        $paths[] = 'C:\\Windows\\Fonts\\shruti.ttf';

        return array_values(array_unique($paths));
    }

    /**
     * @return list<string>
     */
    public static function latinCandidates(): array
    {
        $names = ['NotoSans-Regular.ttf', 'DejaVuSans.ttf', 'arial.ttf'];
        $paths = [];
        $paths[] = __DIR__.DIRECTORY_SEPARATOR.'fonts'.DIRECTORY_SEPARATOR.'NotoSans-Regular.ttf';
        foreach (self::fontDirectories() as $dir) {
            foreach ($names as $name) {
                $paths[] = rtrim($dir, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$name;
            }
        }
        $paths[] = '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf';
        $paths[] = 'C:\\Windows\\Fonts\\arial.ttf';
        $paths[] = 'C:\\Windows\\Fonts\\comic.ttf';

        return array_values(array_unique($paths));
    }

    /**
     * @return list<string>
     */
    private static function fontDirectories(): array
    {
        $dirs = [];

        try {
            $dirs[] = resource_path('fonts');
            $dirs[] = public_path('fonts');
            $dirs[] = storage_path('fonts');
            $dirs[] = storage_path('app/fonts');
            $dirs[] = base_path('resources/fonts');
            $dirs[] = base_path('public/fonts');
            $dirs[] = base_path('fonts');
            // Hostinger often uses public_html as base, with nested public/
            $dirs[] = base_path('../resources/fonts');
            $dirs[] = base_path('../public/fonts');
            $dirs[] = dirname(base_path()).'/resources/fonts';
            $dirs[] = dirname(base_path()).'/public/fonts';
        } catch (\Throwable) {
            // ignore when running outside Laravel bootstrap
        }

        // Absolute path relative to this file: app/Support -> ../../resources/fonts
        $dirs[] = dirname(__DIR__, 2).DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'fonts';
        $dirs[] = dirname(__DIR__, 2).DIRECTORY_SEPARATOR.'public'.DIRECTORY_SEPARATOR.'fonts';
        $dirs[] = dirname(__DIR__, 2).DIRECTORY_SEPARATOR.'storage'.DIRECTORY_SEPARATOR.'fonts';

        return array_values(array_unique(array_filter($dirs)));
    }

    /**
     * @param  list<string>  $candidates
     */
    private static function firstExisting(array $candidates): ?string
    {
        foreach ($candidates as $font) {
            if (! is_string($font) || ! is_file($font) || ! is_readable($font) || filesize($font) < 10000) {
                continue;
            }

            $base = strtolower(basename($font));

            // Never pick Latin-only fonts for Indic (they produce □□□ for Gujarati).
            if (preg_match('/(dejavu|freesans|arial|comic|notosans-regular|notoSans-regular)/i', $base)
                && ! preg_match('/gujarati|devanagari/i', $base)) {
                continue;
            }

            if (IndicScript::fontRendersGujarati($font)) {
                return $font;
            }

            // Known Indic font filenames — accept even if glyph probe is flaky on some GD builds.
            if (preg_match('/gujarati|devanagari|lohit|nirmala|mangal|shruti/i', $base)) {
                return $font;
            }
        }

        return null;
    }

    /**
     * Copy font into storage + public locations Hostinger File Manager can see.
     */
    private static function ensureBundledFontCopied(): ?string
    {
        $source = self::firstExisting([
            dirname(__DIR__, 2).'/resources/fonts/NotoSansGujarati-Regular.ttf',
            dirname(__DIR__, 2).'/public/fonts/NotoSansGujarati-Regular.ttf',
        ]);

        if ($source === null) {
            return null;
        }

        $targets = [];
        try {
            $targets[] = storage_path('fonts/NotoSansGujarati-Regular.ttf');
            $targets[] = public_path('fonts/NotoSansGujarati-Regular.ttf');
            $targets[] = resource_path('fonts/NotoSansGujarati-Regular.ttf');
        } catch (\Throwable) {
            $targets[] = dirname(__DIR__, 2).'/storage/fonts/NotoSansGujarati-Regular.ttf';
            $targets[] = dirname(__DIR__, 2).'/public/fonts/NotoSansGujarati-Regular.ttf';
        }

        foreach ($targets as $target) {
            $dir = dirname($target);
            if (! is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }
            if (! is_file($target) || filesize($target) < 10000) {
                @copy($source, $target);
            }
            if (is_file($target) && is_readable($target) && filesize($target) > 10000) {
                return $target;
            }
        }

        return is_file($source) ? $source : null;
    }

    /**
     * Last resort for Hostinger: download a free Noto Sans Gujarati TTF.
     */
    private static function tryDownloadNotoGujarati(): ?string
    {
        $targets = [];
        try {
            $targets[] = storage_path('fonts/NotoSansGujarati-Regular.ttf');
            $targets[] = public_path('fonts/NotoSansGujarati-Regular.ttf');
        } catch (\Throwable) {
            return null;
        }

        $urls = [
            // jsDelivr mirror of google/fonts Noto Sans Gujarati
            'https://cdn.jsdelivr.net/gh/googlefonts/noto-fonts@main/hinted/ttf/NotoSansGujarati/NotoSansGujarati-Regular.ttf',
            'https://github.com/googlefonts/noto-fonts/raw/main/hinted/ttf/NotoSansGujarati/NotoSansGujarati-Regular.ttf',
        ];

        foreach ($targets as $target) {
            $dir = dirname($target);
            if (! is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }
        }

        foreach ($urls as $url) {
            try {
                $response = Http::timeout(45)->withOptions(['allow_redirects' => true])->get($url);
                if (! $response->successful()) {
                    continue;
                }
                $body = $response->body();
                if (strlen($body) < 10000) {
                    continue;
                }
                foreach ($targets as $target) {
                    if (@file_put_contents($target, $body) && is_file($target) && filesize($target) > 10000) {
                        Log::info('Downloaded NotoSansGujarati font for corrected sheets', ['path' => $target]);

                        return $target;
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('Indic font download failed: '.$e->getMessage(), ['url' => $url]);
            }
        }

        return null;
    }
}
