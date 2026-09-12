<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;

class FixAnswerSheetLiveCommand extends Command
{
    protected $signature = 'homework:fix-answer-sheet';

    protected $description = 'Fix live-server setup for answer sheet upload + teacher corrected images';

    public function handle(): int
    {
        $this->info('Checking answer-sheet live setup...');
        $ok = true;

        if (! extension_loaded('gd')) {
            $this->error('PHP GD extension is missing (required to generate corrected images).');
            $ok = false;
        } else {
            $this->info('GD: OK');
        }

        if (extension_loaded('imagick')) {
            $this->info('Imagick: OK (better Gujarati shaping)');
        } else {
            $this->warn('Imagick: not enabled — enable in hPanel PHP Extensions for cleaner Gujarati matras.');
        }

        if (! filled(config('services.openai.key'))) {
            $this->error('OPENAI_API_KEY is empty in .env (required for OCR/check).');
            $ok = false;
        } else {
            $this->info('OPENAI_API_KEY: OK');
        }

        $fontReport = \App\Support\IndicFontResolver::diagnose();
        try {
            $embedded = \App\Support\EmbeddedGujaratiFont::path();
            $this->info('Gujarati font (GD bundled): '.$embedded);
        } catch (\Throwable $e) {
            $this->error('Gujarati font BUNDLED FILE MISSING — comments will be □□□');
            $this->line('Upload folder: app/Support/fonts/NotoSansGujarati-Regular.ttf');
            $ok = false;
        }
        if ($fontReport['indic_ok']) {
            $this->info('Gujarati font resolver OK: '.$fontReport['indic']);
        }
        foreach ([
            resource_path('fonts/NotoSans-Regular.ttf'),
            public_path('fonts/NotoSans-Regular.ttf'),
        ] as $font) {
            if (is_file($font)) {
                $this->info('Latin font OK: '.$font);
                break;
            }
        }

        // Mirror fonts + Gujarati label sprites into easy Hostinger paths
        $srcGuj = $fontReport['indic'] ?? null;
        if (is_string($srcGuj) && is_file($srcGuj)) {
            foreach ([public_path('fonts'), resource_path('fonts'), storage_path('fonts')] as $dir) {
                if (! is_dir($dir)) {
                    File::makeDirectory($dir, 0755, true);
                }
                $dest = rtrim($dir, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'NotoSansGujarati-Regular.ttf';
                if (! is_file($dest) || filesize($dest) < 10000) {
                    @copy($srcGuj, $dest);
                }
            }
        }

        $spriteSrc = resource_path('fonts/labels/gu');
        $spriteDest = public_path('fonts/labels/gu');
        if (is_dir($spriteSrc)) {
            if (! is_dir($spriteDest)) {
                File::makeDirectory($spriteDest, 0755, true);
            }
            foreach (File::files($spriteSrc) as $file) {
                $target = $spriteDest.DIRECTORY_SEPARATOR.$file->getFilename();
                if (! is_file($target) || filesize($target) < 100) {
                    @copy($file->getPathname(), $target);
                }
            }
            $this->info('Gujarati label sprites: OK ('.$spriteDest.')');
        } else {
            $this->warn('Missing Gujarati label sprites at resources/fonts/labels/gu — upload that folder for proper GD text.');
        }

        $publicStorage = public_path('storage');
        if (! File::exists($publicStorage)) {
            $this->warn('public/storage link missing — creating...');
            $this->call('storage:link');
        } else {
            $this->info('storage link: OK');
        }

        $storageRoot = storage_path('app/public');
        if (! is_dir($storageRoot)) {
            mkdir($storageRoot, 0755, true);
        }
        @chmod($storageRoot, 0755);
        foreach (['homework-submissions', 'exam-submissions'] as $dir) {
            $path = $storageRoot.DIRECTORY_SEPARATOR.$dir;
            if (! is_dir($path)) {
                mkdir($path, 0755, true);
            }
            @chmod($path, 0755);
        }
        $this->info('Storage folders: OK');

        try {
            $this->ensureColumn('homework_submissions');
            $this->ensureColumn('exam_submissions');
        } catch (\Throwable $e) {
            $this->warn('DB column check skipped: '.$e->getMessage());
        }

        $this->call('view:clear');
        $this->call('config:clear');
        $this->call('cache:clear');
        $this->call('route:clear');

        foreach (['packages.php', 'services.php', 'config.php', 'routes-v7.php'] as $cacheFile) {
            $path = base_path('bootstrap/cache/'.$cacheFile);
            if (is_file($path)) {
                @unlink($path);
                $this->info('Removed stale cache: bootstrap/cache/'.$cacheFile);
            }
        }

        $uploads = public_path('uploads');
        if (! is_dir($uploads)) {
            mkdir($uploads, 0755, true);
            $this->info('Created public/uploads');
        }

        // Re-publish existing corrected sheets so old submissions stop 404-ing
        $published = 0;
        try {
            foreach (['homework_submissions', 'exam_submissions'] as $table) {
                if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'corrected_sheet_path')) {
                    continue;
                }

                $rows = DB::table($table)
                    ->whereNotNull('corrected_sheet_path')
                    ->pluck('corrected_sheet_path');

                foreach ($rows as $relative) {
                    if (\App\Support\PublicMedia::publish($relative)) {
                        $published++;
                    }
                }
            }
            $this->info("Published corrected sheets to public/uploads: {$published}");
        } catch (\Throwable $e) {
            $this->warn('Publish corrected sheets skipped: '.$e->getMessage());
        }

        if (class_exists(\Illuminate\Foundation\ComposerScripts::class)) {
            try {
                $this->call('package:discover');
            } catch (\Throwable $e) {
                $this->warn('package:discover skipped: '.$e->getMessage());
            }
        }

        if ($ok) {
            $this->info('Answer sheet live fix completed.');
            $this->comment('Corrected images URL example: /public/uploads/homework-submissions/2/corrected_xxx.jpg');
            $this->comment('Or media route: /public/media/homework-submissions/2/corrected_xxx.jpg');

            return self::SUCCESS;
        }

        $this->error('Fix completed with issues. Resolve errors above and re-run.');

        return self::FAILURE;
    }

    private function ensureColumn(string $table): void
    {
        if (! Schema::hasTable($table)) {
            $this->warn("Table {$table} not found — skip.");

            return;
        }

        if (Schema::hasColumn($table, 'corrected_sheet_path')) {
            $this->info("{$table}.corrected_sheet_path: OK");

            return;
        }

        Schema::table($table, function (Blueprint $blueprint) {
            $blueprint->string('corrected_sheet_path')->nullable()->after('pdf_path');
        });

        $this->info("Added {$table}.corrected_sheet_path");
    }
}
