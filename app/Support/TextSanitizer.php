<?php

namespace App\Support;

class TextSanitizer
{
    public static function forDatabase(?string $text): ?string
    {
        if ($text === null || $text === '') {
            return $text;
        }

        if (! mb_check_encoding($text, 'UTF-8')) {
            $converted = @iconv('CP1252', 'UTF-8//IGNORE', $text)
                ?: @iconv('ISO-8859-1', 'UTF-8//IGNORE', $text);

            $text = $converted !== false ? $converted : '';
        }

        $cleaned = @iconv('UTF-8', 'UTF-8//IGNORE', $text);

        if ($cleaned !== false) {
            $text = $cleaned;
        }

        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $text) ?? '';
        $text = preg_replace('/\x{FFFD}/u', ' ', $text) ?? $text;

        return trim($text);
    }

    public static function isReadableText(string $text, float $minRatio = 0.5): bool
    {
        $text = trim($text);

        if ($text === '' || mb_strlen($text) < 8) {
            return false;
        }

        $readable = preg_match_all('/[\p{L}\p{N}\p{M}\p{P}\p{Z}\s]/u', $text);

        if ($readable === false || $readable === 0) {
            return false;
        }

        return ($readable / mb_strlen($text)) >= $minRatio;
    }

    public static function sanitizeArray(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $data[$key] = self::forDatabase($value);
            } elseif (is_array($value)) {
                $data[$key] = self::sanitizeArray($value);
            }
        }

        return $data;
    }

    /**
     * Keep valid Gujarati/UTF-8 and drop only broken bytes.
     */
    public static function utf8(?string $text): ?string
    {
        if ($text === null || $text === '') {
            return $text;
        }

        $cleaned = @iconv('UTF-8', 'UTF-8//IGNORE', $text);
        if ($cleaned !== false) {
            $text = $cleaned;
        }

        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $text) ?? $text;

        return trim($text);
    }

    /**
     * Drop invalid bytes so a JSON column can store Gujarati OCR text.
     *
     * @param  array<mixed>  $data
     * @return array<mixed>
     */
    public static function jsonSafe(array $data): array
    {
        $json = json_encode(self::utf8Array($data), JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        if ($json === false) {
            return [];
        }

        $decoded = json_decode($json, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param  array<mixed>  $data
     * @return array<mixed>
     */
    private static function utf8Array(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $data[$key] = self::utf8($value);
            } elseif (is_array($value)) {
                $data[$key] = self::utf8Array($value);
            }
        }

        return $data;
    }
}
