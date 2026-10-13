<?php

namespace App\Support;

class MathMarkup
{
    /**
     * Keep subscript and superscript tags. Everything else becomes plain text.
     */
    public static function sanitize(?string $html): string
    {
        $html = TextSanitizer::forDatabase($html) ?? '';
        if ($html === '') {
            return '';
        }

        // Leave comparisons such as a < b alone. strip_tags() would eat them.
        if (! preg_match('/<\s*\/?\s*(?:sub|sup|br|span|div|p|font|b|i|strong|em)\b/i', $html)) {
            return $html;
        }

        $tokens = [];
        $html = preg_replace_callback(
            '/<\s*(\/)?\s*(sub|sup)\s*>|<\s*br\s*\/?\s*>/i',
            function (array $match) use (&$tokens): string {
                $token = '%%MATH'.count($tokens).'%%';
                $tokens[$token] = isset($match[2]) && $match[2] !== ''
                    ? '<'.(! empty($match[1]) ? '/' : '').strtolower($match[2]).'>'
                    : "\n";

                return $token;
            },
            $html
        ) ?? $html;

        $html = preg_replace('/<\s*\/?\s*[a-zA-Z][^>]*>/', '', $html) ?? $html;
        $html = strtr($html, $tokens);
        $html = preg_replace("/[ \t]+\n/", "\n", $html) ?? $html;

        return trim($html);
    }

    /**
     * Safe HTML for the page and the editor. Only sub, sup, and line breaks remain.
     */
    public static function render(?string $text): string
    {
        $text = self::sanitize($text);
        if ($text === '') {
            return '';
        }

        $escaped = e($text);

        return nl2br(str_replace(
            ['&lt;sub&gt;', '&lt;/sub&gt;', '&lt;sup&gt;', '&lt;/sup&gt;'],
            ['<sub>', '</sub>', '<sup>', '</sup>'],
            $escaped
        ), false);
    }
}
