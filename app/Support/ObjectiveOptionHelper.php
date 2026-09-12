<?php

namespace App\Support;

class ObjectiveOptionHelper
{
    /**
     * @return list<array{key: string, text: string}>
     */
    public static function entries(array $options, ?string $type = null): array
    {
        $entries = [];

        foreach (self::letterKeyed($options, $type) as $key => $text) {
            if (is_array($text)) {
                continue;
            }

            $entries[] = [
                'key' => (string) $key,
                'text' => (string) $text,
            ];
        }

        return $entries;
    }

    /**
     * @return array<string, string>
     */
    public static function letterKeyed(array $options, ?string $type = null): array
    {
        if ($options === []) {
            return [];
        }

        if (! self::shouldUseLetterKeys($type, $options)) {
            return $options;
        }

        $letters = range('A', 'Z');
        $result = [];
        $index = 0;

        foreach ($options as $option) {
            if (is_array($option)) {
                continue;
            }

            $result[$letters[$index] ?? (string) $index] = (string) $option;
            $index++;
        }

        return $result;
    }

    public static function shouldUseLetterKeys(?string $type, array $options): bool
    {
        if (in_array((string) $type, ['mcq', 'true_false', 'fill_blank', 'one_word'], true)) {
            return true;
        }

        return count($options) >= 2 && array_is_list($options);
    }
}
