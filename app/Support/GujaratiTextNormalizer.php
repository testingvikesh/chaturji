<?php

namespace App\Support;

class GujaratiTextNormalizer
{
    public static function normalize(string $text): string
    {
        $text = TextSanitizer::forDatabase($text) ?? '';
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = preg_replace('/[ \t]+/u', ' ', $text) ?? $text;
        $text = preg_replace("/\n{3,}/", "\n\n", $text) ?? $text;

        return trim($text);
    }

    public static function cleanOcr(string $text): string
    {
        $text = self::normalize($text);
        $text = self::normalizeQuestionMarkers($text);

        // Only strip English OCR noise from Gujarati-majority sheets.
        // English answer sheets (Q10 / Ans - ...) must keep full words.
        if (self::isMostlyGujarati($text)) {
            $text = preg_replace('/\b(?!Q\d?\b|Ans(?:wer)?\b)[a-zA-Z]{2,}\b/u', ' ', $text) ?? $text;
        }

        $text = preg_replace('/yogidham|gurukul|rajkot|યોગીધામ/iu', '', $text) ?? $text;
        $text = preg_replace("/\n{2,}/", "\n", $text) ?? $text;

        return trim($text);
    }

    public static function normalizeQuestionMarkers(string $text): string
    {
        $text = preg_replace('/\bQ(\d{1,2})\s*-/iu', 'Q$1 -', $text) ?? $text;
        $text = preg_replace('/\bQ(\d{1,2})-(?!\s)/iu', 'Q$1 - ', $text) ?? $text;
        $text = preg_replace('/^[QO0]\s*[lI|](?=\s*[\.\)\:\-])/miu', 'Q1', $text) ?? $text;
        $text = preg_replace('/\b[QO0]\s*(\d{1,2})\s*([\.\)\:\-])?/iu', 'Q$1$2', $text) ?? $text;

        return $text;
    }

    public static function normalizeForMatch(string $text): string
    {
        $text = self::normalize($text);
        $text = self::normalizeQuestionMarkers($text);
        // Collapse Gujarati word spaces only for fuzzy matching
        $text = preg_replace('/(?<=\p{Gujarati})\s+(?=\p{Gujarati})/u', '', $text) ?? $text;
        $text = preg_replace('/\s+/u', '', $text) ?? $text;
        $text = preg_replace('/[^\p{L}\p{N}]/u', '', $text) ?? $text;

        return mb_strtolower($text, 'UTF-8');
    }

    public static function answersMatch(?string $studentAnswer, ?string $correctAnswer): bool
    {
        $student = self::normalizeForMatch((string) $studentAnswer);
        $correct = self::normalizeForMatch((string) $correctAnswer);

        if ($student === '' || $correct === '') {
            return false;
        }

        if ($student === $correct) {
            return true;
        }

        $studentLength = mb_strlen($student);
        $correctLength = mb_strlen($correct);
        $lengthRatio = min($studentLength, $correctLength) / max($studentLength, $correctLength);

        if ($lengthRatio < 0.65) {
            return false;
        }

        if ($lengthRatio >= 0.85) {
            if (str_contains($student, $correct) || str_contains($correct, $student)) {
                return true;
            }
        }

        similar_text($student, $correct, $percent);

        $threshold = max($correctLength, $studentLength) <= 12 ? 92.0 : 88.0;

        return $percent >= $threshold;
    }

    public static function isMostlyGujarati(string $text): bool
    {
        $gujarati = preg_match_all('/\p{Gujarati}/u', $text) ?: 0;
        $letters = preg_match_all('/\p{L}/u', $text) ?: 0;

        return $letters > 0 && ($gujarati / $letters) >= 0.6;
    }
}
