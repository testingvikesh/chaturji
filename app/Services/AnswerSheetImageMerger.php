<?php

namespace App\Services;

use RuntimeException;

class AnswerSheetImageMerger
{
    /**
     * @param  array<int, string>  $absolutePaths
     */
    public function merge(array $absolutePaths): string
    {
        $paths = array_values(array_filter($absolutePaths, fn (string $path) => is_file($path)));

        if ($paths === []) {
            throw new RuntimeException('No valid files to merge.');
        }

        if (count($paths) === 1) {
            return $paths[0];
        }

        if (! extension_loaded('gd')) {
            return $paths[0];
        }

        $images = [];

        foreach ($paths as $path) {
            $image = $this->loadImage($path);

            if ($image !== null) {
                $images[] = $image;
            }
        }

        if ($images === []) {
            throw new RuntimeException('Could not read uploaded images for merging.');
        }

        $width = max(array_map(fn (\GdImage $image) => imagesx($image), $images));
        $height = array_sum(array_map(fn (\GdImage $image) => imagesy($image), $images));
        $canvas = imagecreatetruecolor($width, $height);
        $white = imagecolorallocate($canvas, 255, 255, 255);
        imagefill($canvas, 0, 0, $white);

        $offsetY = 0;

        foreach ($images as $image) {
            $imageWidth = imagesx($image);
            $imageHeight = imagesy($image);
            $offsetX = (int) max(0, floor(($width - $imageWidth) / 2));
            imagecopy($canvas, $image, $offsetX, $offsetY, 0, 0, $imageWidth, $imageHeight);
            $offsetY += $imageHeight;
            imagedestroy($image);
        }

        $output = tempnam(sys_get_temp_dir(), 'merged_sheet_');

        if ($output === false) {
            imagedestroy($canvas);

            throw new RuntimeException('Could not create merged image.');
        }

        $output .= '.jpg';
        imagejpeg($canvas, $output, 92);
        imagedestroy($canvas);

        return $output;
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
