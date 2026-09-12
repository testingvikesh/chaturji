<?php

namespace App\Services;

use RuntimeException;

class PdfPageRasterizer
{
    /**
     * @return array<int, string>
     */
    public function toImagePaths(string $path, int $dpi = 140): array
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if (in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
            return [$path];
        }

        return $this->rasterize($path, $dpi);
    }

    /**
     * @return array{images: array<int, string>, errors: array<int, string>}
     */
    public function toImagePathsWithDiagnostics(string $path, int $dpi = 140): array
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if (in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
            return ['images' => [$path], 'errors' => []];
        }

        $errors = [];

        $embedded = $this->rasterizeWithEmbeddedJpeg($path);

        if ($embedded !== []) {
            return ['images' => $embedded, 'errors' => []];
        }

        $errors[] = 'Embedded JPEG extraction from PDF failed.';

        if (extension_loaded('imagick')) {
            try {
                $images = $this->rasterizeWithImagick($path, $dpi);

                if ($images !== []) {
                    return ['images' => $images, 'errors' => []];
                }

                $errors[] = 'Imagick is enabled but returned no pages (PDF policy may block reading).';
            } catch (\Throwable $exception) {
                $errors[] = 'Imagick error: '.$exception->getMessage();
            }
        } else {
            $errors[] = 'PHP Imagick extension is not loaded.';
        }

        $images = $this->rasterizeWithPdftoppm($path, $dpi);

        if ($images !== []) {
            return ['images' => $images, 'errors' => []];
        }

        $errors[] = 'Poppler pdftoppm is not available or could not convert the PDF.';

        return ['images' => [], 'errors' => $errors];
    }

    /**
     * @return array<int, string>
     */
    public function rasterize(string $pdfPath, int $dpi = 200): array
    {
        $result = $this->toImagePathsWithDiagnostics($pdfPath, $dpi);

        if ($result['images'] !== []) {
            return $result['images'];
        }

        throw new RuntimeException(
            'Could not convert PDF to image. '.implode(' ', $result['errors'])
        );
    }

    /**
     * WhatsApp / phone camera PDFs often embed a single JPEG stream
     * (sometimes also a small thumbnail — ignore thumbnails).
     *
     * @return array<int, string>
     */
    private function rasterizeWithEmbeddedJpeg(string $pdfPath): array
    {
        $pdf = @file_get_contents($pdfPath);

        if ($pdf === false || ! preg_match_all('/\/Subtype\s*\/Image.*?stream\r?\n(.*?)\r?\nendstream/s', $pdf, $matches)) {
            return [];
        }

        $candidates = [];

        foreach ($matches[1] as $raw) {
            $decoded = $this->decodePdfImageStream($raw);

            if ($decoded === null) {
                continue;
            }

            $tempPath = tempnam(sys_get_temp_dir(), 'pdf_embed_');

            if ($tempPath === false) {
                continue;
            }

            $tempPath .= '.jpg';

            if (@file_put_contents($tempPath, $decoded) === false) {
                continue;
            }

            $info = @getimagesize($tempPath);
            if (! is_array($info)) {
                @unlink($tempPath);
                continue;
            }

            $width = (int) ($info[0] ?? 0);
            $height = (int) ($info[1] ?? 0);
            $pixels = $width * $height;

            // Skip tiny embedded thumbnails (common in phone camera PDFs)
            if ($pixels < 180000) {
                @unlink($tempPath);
                continue;
            }

            $hash = md5($decoded);
            if (isset($candidates[$hash])) {
                @unlink($tempPath);
                continue;
            }

            $candidates[$hash] = [
                'path' => $tempPath,
                'pixels' => $pixels,
            ];
        }

        if ($candidates === []) {
            return [];
        }

        // Prefer larger pages first; one-page uploads should stay one page
        uasort($candidates, fn (array $a, array $b) => $b['pixels'] <=> $a['pixels']);

        return array_values(array_map(fn (array $row) => $row['path'], $candidates));
    }

    private function decodePdfImageStream(string $raw): ?string
    {
        $attempts = [
            $raw,
            @gzuncompress($raw) ?: null,
            @gzinflate($raw) ?: null,
            @gzinflate(substr($raw, 2)) ?: null,
        ];

        foreach ($attempts as $data) {
            if (! is_string($data) || $data === '') {
                continue;
            }

            $start = strpos($data, "\xFF\xD8\xFF");

            if ($start === false) {
                continue;
            }

            $end = strpos($data, "\xFF\xD9", $start);

            if ($end === false) {
                continue;
            }

            return substr($data, $start, $end - $start + 2);
        }

        return null;
    }

    /**
     * @return array<int, string>
     */
    private function rasterizeWithImagick(string $pdfPath, int $dpi): array
    {
        $images = [];
        $imagick = new \Imagick();
        $imagick->setResolution($dpi, $dpi);
        $imagick->readImage($pdfPath);

        foreach ($imagick as $index => $page) {
            $page->setImageFormat('png');
            $tempPath = tempnam(sys_get_temp_dir(), 'pdf_page_');

            if ($tempPath === false) {
                continue;
            }

            $tempPath .= '.png';
            $page->writeImage($tempPath);
            $images[$index] = $tempPath;
        }

        $imagick->clear();
        $imagick->destroy();

        return array_values($images);
    }

    /**
     * @return array<int, string>
     */
    private function rasterizeWithPdftoppm(string $pdfPath, int $dpi): array
    {
        $outputDir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'pdf_ocr_'.uniqid();

        if (! @mkdir($outputDir) && ! is_dir($outputDir)) {
            return [];
        }

        $prefix = $outputDir.DIRECTORY_SEPARATOR.'page';
        $binaries = [
            'pdftoppm',
            '/usr/bin/pdftoppm',
            '/usr/local/bin/pdftoppm',
            'C:\\Program Files\\poppler\\Library\\bin\\pdftoppm.exe',
            'C:\\poppler\\Library\\bin\\pdftoppm.exe',
        ];

        foreach ($binaries as $binary) {
            if (! in_array($binary, ['pdftoppm', '/usr/bin/pdftoppm', '/usr/local/bin/pdftoppm'], true) && ! file_exists($binary)) {
                continue;
            }

            $command = sprintf(
                '%s -png -r %d %s %s',
                escapeshellarg($binary),
                $dpi,
                escapeshellarg($pdfPath),
                escapeshellarg($prefix)
            );

            @\exec($command.' 2>&1', $output, $code);

            $files = glob($prefix.'-*.png') ?: glob($prefix.'*.png') ?: [];

            if ($code === 0 && $files !== []) {
                sort($files);

                return array_values($files);
            }
        }

        return [];
    }
}
