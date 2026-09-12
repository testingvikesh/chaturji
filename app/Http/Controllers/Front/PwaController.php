<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class PwaController extends Controller
{
    public function install(): View
    {
        return view('front.install');
    }

    public function manifest(): JsonResponse
    {
        $name = config('app.name', 'Gses Chaturji');
        $root = rtrim(request()->root(), '/');

        return response()->json([
            'id' => $root.'/',
            'name' => $name,
            'short_name' => 'Chaturji',
            'description' => 'Gujarat School of Excellence System — homework, exams, and progress in one app.',
            'start_url' => $root.'/?source=pwa',
            'scope' => $root.'/',
            'display' => 'standalone',
            'display_override' => ['standalone', 'minimal-ui', 'browser'],
            'orientation' => 'any',
            'background_color' => '#0d1f4a',
            'theme_color' => '#1a367c',
            'lang' => 'en',
            'dir' => 'ltr',
            'categories' => ['education'],
            'prefer_related_applications' => false,
            'icons' => [
                [
                    'src' => $root.'/images/pwa/icon-192.png',
                    'sizes' => '192x192',
                    'type' => 'image/png',
                    'purpose' => 'any',
                ],
                [
                    'src' => $root.'/images/pwa/icon-512.png',
                    'sizes' => '512x512',
                    'type' => 'image/png',
                    'purpose' => 'any',
                ],
                [
                    'src' => $root.'/images/pwa/icon-192-maskable.png',
                    'sizes' => '192x192',
                    'type' => 'image/png',
                    'purpose' => 'maskable',
                ],
                [
                    'src' => $root.'/images/pwa/icon-512-maskable.png',
                    'sizes' => '512x512',
                    'type' => 'image/png',
                    'purpose' => 'maskable',
                ],
            ],
        ], 200, [
            'Content-Type' => 'application/manifest+json',
            'Cache-Control' => 'no-cache',
        ]);
    }
}
