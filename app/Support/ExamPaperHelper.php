<?php

namespace App\Support;

use App\Models\Setting;

class ExamPaperHelper
{
    public static function instructions(?string $examInstructions = null): ?string
    {
        $groups = self::instructionGroups($examInstructions);

        if ($groups === []) {
            return null;
        }

        return collect($groups)
            ->flatMap(fn (array $group) => $group['points'])
            ->implode("\n");
    }

    /**
     * @return array<int, array{label: string, points: array<int, string>}>
     */
    public static function instructionGroups(?string $examInstructions = null): array
    {
        $groups = [];

        $defaultPoints = self::parseInstructionLines(Setting::get('exam_default_instructions', ''));
        if ($defaultPoints !== []) {
            $groups[] = [
                'label' => 'General Instructions',
                'points' => $defaultPoints,
            ];
        }

        $examPoints = self::parseInstructionLines($examInstructions);
        if ($examPoints !== []) {
            $groups[] = [
                'label' => 'Exam Instructions',
                'points' => $examPoints,
            ];
        }

        return $groups;
    }

    /**
     * @return array<int, string>
     */
    public static function parseInstructionLines(?string $text): array
    {
        if (! filled($text)) {
            return [];
        }

        return collect(preg_split('/\R+/', (string) $text))
            ->map(fn ($line) => trim((string) $line))
            ->map(fn (string $line) => (string) preg_replace('/^(\d+[\.\):\-]\s*|[-*•●▪]\s*)/u', '', $line))
            ->filter(fn (string $line) => $line !== '')
            ->values()
            ->all();
    }
}
