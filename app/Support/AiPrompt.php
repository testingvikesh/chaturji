<?php

namespace App\Support;

use App\Models\Setting;

class AiPrompt
{
    /**
     * @param  array<string, scalar|null>  $replacements
     */
    public static function get(string $key, array $replacements = []): string
    {
        $default = (string) (config('prompts.catalog.'.$key.'.default') ?? '');
        $saved = trim((string) Setting::get('prompt_'.$key, ''));

        $text = $saved !== '' ? $saved : $default;

        return self::applyReplacements($text, $replacements);
    }

    public static function settingKey(string $key): string
    {
        return 'prompt_'.$key;
    }

    /**
     * @param  array<string, scalar|null>  $replacements
     */
    public static function applyReplacements(string $text, array $replacements): string
    {
        foreach ($replacements as $key => $value) {
            $value = (string) ($value ?? '');
            $text = str_replace(
                ['{{'.$key.'}}', '{'.$key.'}'],
                $value,
                $text
            );
        }

        return $text;
    }

    /**
     * @return array<string, array{key:string,label:string,hint:string,default:string,value:string,custom:bool}>
     */
    public static function all(): array
    {
        $rows = [];

        foreach (config('prompts.catalog', []) as $key => $item) {
            $default = (string) ($item['default'] ?? '');
            $saved = trim((string) Setting::get(self::settingKey($key), ''));

            $rows[$key] = [
                'key' => $key,
                'label' => (string) ($item['label'] ?? $key),
                'hint' => (string) ($item['hint'] ?? ''),
                'default' => $default,
                'value' => $saved !== '' ? $saved : $default,
                'custom' => $saved !== '' && $saved !== $default,
            ];
        }

        return $rows;
    }
}
