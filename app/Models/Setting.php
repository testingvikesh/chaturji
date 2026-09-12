<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = [
        'key',
        'value',
    ];

    public static function allCached(): array
    {
        return Cache::rememberForever('settings.all', function () {
            $stored = static::pluck('value', 'key')->toArray();

            return array_merge(config('settings.defaults', []), $stored);
        });
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        $settings = static::allCached();

        return $settings[$key] ?? $default;
    }

    public static function setMany(array $values): void
    {
        foreach ($values as $key => $value) {
            static::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }

        static::clearCache();
    }

    public static function clearCache(): void
    {
        Cache::forget('settings.all');
    }

    public static function keys(): array
    {
        return array_keys(config('settings.defaults', []));
    }
}
