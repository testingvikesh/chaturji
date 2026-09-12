<?php

namespace App\Support;

use App\Models\Setting;

class AiPrompt
{
    public static function get(string $key): string
    {
        $default = (string) (config('prompts.catalog.'.$key.'.default') ?? '');
        $saved = trim((string) Setting::get('prompt_'.$key, ''));

        return $saved !== '' ? $saved : $default;
    }

    public static function settingKey(string $key): string
    {
        return 'prompt_'.$key;
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
