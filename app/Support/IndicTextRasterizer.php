<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;

/**
 * Rasterize Gujarati/Hindi with proper shaping when Imagick/ImageMagick is available.
 * Falls back to null so callers can use GD.
 */
final class IndicTextRasterizer
{
    /**
     * @return array{png: string, width: int, height: int}|null
     */
    public static function rasterize(string $text, int $fontSize, string $fontPath, int $r, int $g, int $b): ?array
    {
        $text = trim($text);
        if ($text === '' || ! is_file($fontPath)) {
            return null;
        }

        $viaImagick = self::viaImagick($text, $fontSize, $fontPath, $r, $g, $b);
        if ($viaImagick !== null) {
            return $viaImagick;
        }

        return self::viaMagickCli($text, $fontSize, $fontPath, $r, $g, $b);
    }

    /**
     * @return array{png: string, width: int, height: int}|null
     */
    private static function viaImagick(string $text, int $fontSize, string $fontPath, int $r, int $g, int $b): ?array
    {
        if (! extension_loaded('imagick') || ! class_exists(\Imagick::class)) {
            return null;
        }

        try {
            // SVG + embedded font + rsvg/pango = proper Indic shaping on many Hostinger builds
            $approxChars = max(1, grapheme_strlen($text) ?: mb_strlen($text, 'UTF-8'));
            $width = (int) max(40, $approxChars * $fontSize * 0.95 + 16);
            $height = (int) max(24, $fontSize * 2.2);
            $baseline = (int) ($fontSize * 1.35);
            $fill = sprintf('rgb(%d,%d,%d)', $r, $g, $b);
            $fontData = base64_encode((string) file_get_contents($fontPath));
            $escaped = htmlspecialchars($text, ENT_XML1 | ENT_QUOTES, 'UTF-8');

            $svg = <<<SVG
<?xml version="1.0" encoding="UTF-8"?>
<svg xmlns="http://www.w3.org/2000/svg" width="{$width}" height="{$height}">
  <defs>
    <style type="text/css"><![CDATA[
      @font-face {
        font-family: "IndicSheet";
        src: url("data:font/truetype;charset=utf-8;base64,{$fontData}") format("truetype");
      }
    ]]></style>
  </defs>
  <text x="2" y="{$baseline}" font-family="IndicSheet" font-size="{$fontSize}px" fill="{$fill}">{$escaped}</text>
</svg>
SVG;

            $im = new \Imagick;
            $im->setBackgroundColor(new \ImagickPixel('transparent'));
            $im->readImageBlob($svg);
            $im->setImageFormat('png32');
            $im->trimImage(0);
            $im->setImagePage(0, 0, 0, 0);
            $blob = $im->getImageBlob();
            $w = $im->getImageWidth();
            $h = $im->getImageHeight();
            $im->clear();
            $im->destroy();

            if ($blob === '' || $w < 2 || $h < 2) {
                return null;
            }

            return ['png' => $blob, 'width' => $w, 'height' => $h];
        } catch (\Throwable $e) {
            Log::debug('Indic Imagick SVG render failed: '.$e->getMessage());
        }

        // Fallback: annotateImage (works when Imagick was built with pango/harfbuzz)
        try {
            $approxChars = max(1, grapheme_strlen($text) ?: mb_strlen($text, 'UTF-8'));
            $width = (int) max(40, $approxChars * $fontSize * 1.1 + 20);
            $height = (int) max(28, $fontSize * 2.4);

            $im = new \Imagick;
            $im->newImage($width, $height, new \ImagickPixel('transparent'));
            $im->setImageFormat('png32');

            $draw = new \ImagickDraw;
            $draw->setFont($fontPath);
            $draw->setFontSize($fontSize);
            $draw->setFillColor(new \ImagickPixel(sprintf('rgb(%d,%d,%d)', $r, $g, $b)));
            $draw->setTextEncoding('UTF-8');
            $im->annotateImage($draw, 2, (int) ($fontSize * 1.3), 0, $text);
            $im->trimImage(0);
            $im->setImagePage(0, 0, 0, 0);

            $blob = $im->getImageBlob();
            $w = $im->getImageWidth();
            $h = $im->getImageHeight();
            $im->clear();
            $im->destroy();

            if ($blob === '' || $w < 2) {
                return null;
            }

            return ['png' => $blob, 'width' => $w, 'height' => $h];
        } catch (\Throwable $e) {
            Log::debug('Indic Imagick annotate failed: '.$e->getMessage());

            return null;
        }
    }

    /**
     * @return array{png: string, width: int, height: int}|null
     */
    private static function viaMagickCli(string $text, int $fontSize, string $fontPath, int $r, int $g, int $b): ?array
    {
        if (! function_exists('exec') || ! is_writable(sys_get_temp_dir())) {
            return null;
        }

        $bin = self::findMagickBinary();
        if ($bin === null) {
            return null;
        }

        $tmpTxt = tempnam(sys_get_temp_dir(), 'indtxt');
        $tmpPng = tempnam(sys_get_temp_dir(), 'indpng');
        if ($tmpTxt === false || $tmpPng === false) {
            return null;
        }
        $tmpPngOut = $tmpPng.'.png';
        @unlink($tmpPng);

        try {
            file_put_contents($tmpTxt, $text);
            $fill = sprintf('rgb(%d,%d,%d)', $r, $g, $b);
            $cmd = sprintf(
                '%s -background none -fill %s -font %s -pointsize %d label:@%s %s 2>&1',
                escapeshellcmd($bin),
                escapeshellarg($fill),
                escapeshellarg($fontPath),
                $fontSize,
                escapeshellarg($tmpTxt),
                escapeshellarg($tmpPngOut)
            );
            \exec($cmd, $output, $code);
            if ($code !== 0 || ! is_file($tmpPngOut) || filesize($tmpPngOut) < 20) {
                return null;
            }
            $blob = (string) file_get_contents($tmpPngOut);
            $info = @getimagesizefromstring($blob);
            if (! is_array($info)) {
                return null;
            }

            return ['png' => $blob, 'width' => (int) $info[0], 'height' => (int) $info[1]];
        } catch (\Throwable $e) {
            Log::debug('Indic magick CLI failed: '.$e->getMessage());

            return null;
        } finally {
            @unlink($tmpTxt);
            @unlink($tmpPngOut);
        }
    }

    private static function findMagickBinary(): ?string
    {
        foreach (['magick', 'convert'] as $name) {
            $out = [];
            $code = 1;
            @\exec('command -v '.escapeshellarg($name).' 2>/dev/null', $out, $code);
            if ($code === 0 && isset($out[0]) && is_string($out[0]) && $out[0] !== '') {
                // Avoid Windows System32\convert.exe
                if ($name === 'convert' && str_contains(strtolower($out[0]), 'system32')) {
                    continue;
                }

                return $out[0];
            }
        }

        return null;
    }
}
