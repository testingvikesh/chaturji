<?php

namespace App\Support;

/**
 * Detect Gujarati/Devanagari without relying on \p{Gujarati} (broken on some Hostinger PCRE builds).
 */
final class IndicScript
{
    public static function isIndicChar(string $char): bool
    {
        return (bool) preg_match('/[\x{0A80}-\x{0AFF}\x{0900}-\x{097F}]/u', $char);
    }

    public static function containsIndic(string $text): bool
    {
        return (bool) preg_match('/[\x{0A80}-\x{0AFF}\x{0900}-\x{097F}]/u', $text);
    }

    /**
     * True if TTF can draw a Gujarati letter (rejects Latin-only fonts that cause □□□).
     */
    public static function fontRendersGujarati(string $fontPath): bool
    {
        if (! is_file($fontPath) || ! function_exists('imagettfbbox')) {
            return false;
        }

        $base = strtolower(basename($fontPath));

        // Trust known Indic font filenames.
        if (preg_match('/gujarati|devanagari|lohit|nirmala|mangal|shruti/i', $base)) {
            return true;
        }

        // Hard-reject common Latin fonts (they “load” but draw □□□ for Gujarati).
        if (preg_match('/notosans-regular|notoSans-regular|dejavu|freesans|arial|comic|roboto|opensans/i', $base)) {
            return false;
        }

        $gu = @imagettfbbox(24, 0, $fontPath, 'કાઈ');
        $en = @imagettfbbox(24, 0, $fontPath, 'ABC');
        if (! is_array($gu) || ! is_array($en)) {
            return false;
        }

        $guW = abs(($gu[2] ?? 0) - ($gu[0] ?? 0));
        $enW = abs(($en[2] ?? 0) - ($en[0] ?? 0));

        // Real Gujarati glyphs are typically similar width to Latin; .notdef boxes are tiny or oddly narrow.
        return $guW >= 18 && $guW >= (int) ($enW * 0.55);
    }
}
