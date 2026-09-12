<?php

namespace App\Services;

class AnswerSheetImageEnhancer
{
    /**
     * Fast Hostinger-safe enhance: mild contrast, cap width, smaller JPEG.
     *
     * @return array<int, string>
     */
    public function enhanceAll(array $imagePaths): array
    {
        $enhanced = [];

        foreach ($imagePaths as $path) {
            $enhanced[] = $this->enhance($path);
        }

        return $enhanced;
    }

    public function enhance(string $imagePath): string
    {
        if (! extension_loaded('gd') || ! is_file($imagePath)) {
            return $imagePath;
        }

        $image = $this->loadImage($imagePath);

        if ($image === null) {
            return $imagePath;
        }

        $width = imagesx($image);
        $height = imagesy($image);
        $targetWidth = 1280;

        // Downscale large photos; only mild upscale for tiny images (avoid 2x CPU cost).
        if ($width > $targetWidth) {
            $scale = $targetWidth / $width;
            $newW = $targetWidth;
            $newH = max(1, (int) round($height * $scale));
            $resized = imagecreatetruecolor($newW, $newH);
            imagecopyresampled($resized, $image, 0, 0, 0, 0, $newW, $newH, $width, $height);
            imagedestroy($image);
            $image = $resized;
        } elseif ($width > 0 && $width < 900) {
            $scale = 1.4;
            $newW = (int) round($width * $scale);
            $newH = (int) round($height * $scale);
            $resized = imagecreatetruecolor($newW, $newH);
            imagecopyresampled($resized, $image, 0, 0, 0, 0, $newW, $newH, $width, $height);
            imagedestroy($image);
            $image = $resized;
        }

        imagefilter($image, IMG_FILTER_GRAYSCALE);
        imagefilter($image, IMG_FILTER_CONTRAST, -35);
        imagefilter($image, IMG_FILTER_BRIGHTNESS, 12);

        $output = tempnam(sys_get_temp_dir(), 'sheet_enhanced_');

        if ($output === false) {
            imagedestroy($image);

            return $imagePath;
        }

        $output .= '.jpg';
        imagejpeg($image, $output, 82);
        imagedestroy($image);

        return $output;
    }

    /**
     * @param  array<int, string>  $paths
     * @param  array<int, string>  $originalPaths
     */
    public function cleanup(array $paths, array $originalPaths): void
    {
        foreach ($paths as $path) {
            if (! in_array($path, $originalPaths, true) && str_starts_with($path, sys_get_temp_dir())) {
                @unlink($path);
            }
        }
    }

    private function loadImage(string $path): ?\GdImage
    {
        return match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'jpg', 'jpeg' => @imagecreatefromjpeg($path) ?: null,
            'png' => @imagecreatefrompng($path) ?: null,
            'webp' => function_exists('imagecreatefromwebp') ? (@imagecreatefromwebp($path) ?: null) : null,
            default => null,
        };
    }
}
