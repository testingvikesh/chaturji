<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

class PublicMedia
{
    /**
     * Always use the Laravel media route on Hostinger.
     * Empty public/storage folders break /storage/... URLs with 404.
     */
    public static function url(?string $path): ?string
    {
        if (! filled($path)) {
            return null;
        }

        $path = ltrim(str_replace('\\', '/', (string) $path), '/');

        // Direct public copy (most reliable on shared hosting)
        $publicCopy = public_path('uploads/'.$path);
        if (is_file($publicCopy)) {
            return asset('uploads/'.$path);
        }

        // Fall back to media route (reads from storage/app/public)
        try {
            return url('/media/'.$path);
        } catch (\Throwable) {
            return asset('storage/'.$path);
        }
    }

    /**
     * Copy a storage/public file into public/uploads so browsers can load it
     * without needing storage:link.
     */
    public static function publish(?string $storageRelativePath): ?string
    {
        if (! filled($storageRelativePath)) {
            return null;
        }

        $storageRelativePath = ltrim(str_replace('\\', '/', $storageRelativePath), '/');

        if (! Storage::disk('public')->exists($storageRelativePath)) {
            return null;
        }

        $destination = public_path('uploads/'.$storageRelativePath);
        $directory = dirname($destination);

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $source = Storage::disk('public')->path($storageRelativePath);

        if (@copy($source, $destination)) {
            return $storageRelativePath;
        }

        return null;
    }
}
