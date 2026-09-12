<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PublicStorageController extends Controller
{
    public function show(string $path): BinaryFileResponse
    {
        $path = ltrim(str_replace(['..', '\\'], ['', '/'], $path), '/');

        // 1) public/uploads copy
        $publicCopy = public_path('uploads/'.$path);
        if (is_file($publicCopy)) {
            return response()->file($publicCopy);
        }

        // 2) storage/app/public
        if (Storage::disk('public')->exists($path)) {
            $absolute = Storage::disk('public')->path($path);

            // Best-effort publish for next request
            \App\Support\PublicMedia::publish($path);

            return response()->file($absolute);
        }

        // 3) broken symlink target
        $linked = public_path('storage/'.$path);
        if (is_file($linked)) {
            return response()->file($linked);
        }

        abort(404, 'Corrected image not found: '.$path);
    }
}
