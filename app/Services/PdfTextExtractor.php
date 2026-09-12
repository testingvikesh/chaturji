<?php

namespace App\Services;

use App\Support\TextSanitizer;
use RuntimeException;

class PdfTextExtractor
{
    public function extract(string $path): string
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if ($extension === 'txt') {
            return $this->normalize(trim((string) file_get_contents($path)));
        }

        if ($extension !== 'pdf') {
            throw new RuntimeException('Only PDF or TXT files are supported.');
        }

        $attempts = [
            fn () => $this->extractWithSmalot($path),
            fn () => $this->extractWithPdftotext($path),
            fn () => $this->extractFromPdfBinary($path),
        ];

        foreach ($attempts as $extract) {
            $text = trim($extract());

            if ($text !== '' && TextSanitizer::isReadableText($text)) {
                return $this->normalize($text);
            }
        }

        throw new RuntimeException(
            'Could not extract text from this PDF. It may be a scanned/image PDF. '.
            'Please upload a text-based PDF, a .txt file, or paste the chapter text in the form.'
        );
    }

    private function extractWithSmalot(string $path): string
    {
        if (! class_exists(\Smalot\PdfParser\Parser::class)) {
            return '';
        }

        $parser = new \Smalot\PdfParser\Parser();

        return $parser->parseFile($path)->getText();
    }

    private function extractWithPdftotext(string $path): string
    {
        $output = $path.'.txt';
        $candidates = [
            'pdftotext',
            'C:\\Program Files\\poppler\\Library\\bin\\pdftotext.exe',
            'C:\\poppler\\Library\\bin\\pdftotext.exe',
        ];

        foreach ($candidates as $binary) {
            if ($binary !== 'pdftotext' && ! file_exists($binary)) {
                continue;
            }

            @unlink($output);
            $command = sprintf('"%s" %s %s 2>NUL', $binary, escapeshellarg($path), escapeshellarg($output));
            @\exec($command, $lines, $code);

            if ($code === 0 && file_exists($output)) {
                $text = (string) file_get_contents($output);
                @unlink($output);

                return $text;
            }
        }

        return '';
    }

    private function extractFromPdfBinary(string $path): string
    {
        $content = file_get_contents($path);

        if ($content === false) {
            return '';
        }

        $parts = [];

        if (preg_match_all('/\((?:[^\\\\()]|\\\\.)*\)/s', $content, $matches)) {
            foreach ($matches[0] as $match) {
                $text = stripcslashes(trim($match, '()'));
                $text = preg_replace('/[^\P{C}\n\r\t]+/u', '', $text) ?? $text;

                if (strlen(trim($text)) >= 2) {
                    $parts[] = $text;
                }
            }
        }

        return implode("\n", $parts);
    }

    private function normalize(string $text): string
    {
        $text = TextSanitizer::forDatabase($text) ?? '';

        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = preg_replace('/[ \t]+/u', ' ', $text) ?? $text;
        $text = preg_replace("/\n{3,}/", "\n\n", $text) ?? $text;

        return trim($text);
    }
}
