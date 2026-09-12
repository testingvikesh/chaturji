<?php

namespace App\Console\Commands;

use App\Services\GoogleVisionService;
use App\Services\OpenAiVisionOcrService;
use App\Services\PdfPageRasterizer;
use Illuminate\Console\Command;

class CheckAnswerSheetOcrCommand extends Command
{
    protected $signature = 'homework:check-ocr {file? : Optional PDF/image path to test conversion}';

    protected $description = 'Check OCR configuration for answer sheet uploads';

    public function handle(
        OpenAiVisionOcrService $openAi,
        GoogleVisionService $googleVision,
        PdfPageRasterizer $rasterizer,
    ): int {
        $this->info('Answer sheet OCR diagnostics');
        $this->newLine();

        $this->line('OpenAI key loaded: '.($openAi->isConfigured() ? 'YES' : 'NO'));
        $this->line('Google Vision key loaded: '.($googleVision->isConfigured() ? 'YES' : 'NO'));
        $this->line('PHP Imagick: '.(extension_loaded('imagick') ? 'YES' : 'NO'));
        $this->line('PHP GD: '.(extension_loaded('gd') ? 'YES' : 'NO'));
        $this->line('Temp dir writable: '.(is_writable(sys_get_temp_dir()) ? 'YES' : 'NO'));

        $file = $this->argument('file');

        if ($file && is_file($file)) {
            $this->newLine();
            $this->info('Testing file: '.$file);

            $result = $rasterizer->toImagePathsWithDiagnostics($file);
            $this->line('Images extracted: '.count($result['images']));

            foreach ($result['errors'] as $error) {
                $this->warn($error);
            }
        } elseif ($file) {
            $this->error('File not found: '.$file);
        } else {
            $this->newLine();
            $this->comment('Run with a file path to test PDF conversion, e.g. php artisan homework:check-ocr storage/app/test.pdf');
        }

        if (! $openAi->isConfigured()) {
            $this->newLine();
            $this->warn('If you updated .env recently, run: php artisan config:clear');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
