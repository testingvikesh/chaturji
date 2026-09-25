<?php

namespace App\Services;

use App\Support\EmbeddedGujaratiFont;
use App\Support\IndicFontResolver;
use App\Support\IndicLabelSprites;
use App\Support\IndicScript;
use App\Support\IndicTextRasterizer;
use App\Support\PaperLanguage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Annotates the student's uploaded sheet (image-1 flow) with ChatGPT red-pen style (image-2).
 */
class CorrectedAnswerSheetRenderer
{
    private ?string $latinFont = null;

    private ?string $gujaratiFont = null;

    private ?string $penFont = null;

    private string $sheetLang = 'en';

    private int $lastDrawWidth = 0;

    /**
     * @param  string|list<string>  $sourceImagePath  One page, or all PDF/photo pages to stack
     * @param  array<int, array<string, mixed>>  $questionRows
     * @param  array<string, mixed>  $summary
     */
    public function render(string|array $sourceImagePath, array $questionRows, array $summary, string $storageFolder): ?string
    {
        if (! extension_loaded('gd')) {
            return null;
        }

        $this->latinFont = IndicFontResolver::latin() ?? $this->resolveLatinFont();
        // GD-only Hostinger: always materialize embedded Noto Sans Gujarati (no Imagick).
        try {
            $this->gujaratiFont = EmbeddedGujaratiFont::path();
        } catch (\Throwable $e) {
            Log::error('Embedded Gujarati font: '.$e->getMessage());
            $this->gujaratiFont = IndicFontResolver::indic() ?? $this->resolveGujaratiFont();
        }
        if ($this->gujaratiFont && ! IndicScript::fontRendersGujarati($this->gujaratiFont)) {
            Log::error('Gujarati font path invalid for GD', ['path' => $this->gujaratiFont]);
            try {
                $this->gujaratiFont = EmbeddedGujaratiFont::path();
            } catch (\Throwable) {
                $this->gujaratiFont = null;
            }
        }
        $this->penFont = $this->resolvePenFont() ?? $this->latinFont;

        $langHint = PaperLanguage::normalize((string) ($summary['language'] ?? ''));
        if (in_array($langHint, ['gu', 'hi'], true) && ! $this->gujaratiFont) {
            Log::error('Corrected sheet: Indic font missing on server — upload EmbeddedGujaratiFont.php + run homework:fix-answer-sheet');
        }

        $paths = is_array($sourceImagePath) ? $sourceImagePath : [$sourceImagePath];
        $paths = array_values(array_filter($paths, fn ($path) => is_string($path) && $path !== '' && is_file($path)));

        if ($paths === []) {
            return null;
        }

        $pageHeights = [];
        $stackedPath = null;
        $sourceForAnnotate = $paths[0];

        if (count($paths) > 1) {
            $stackedPath = $this->stackPagesToTempJpeg($paths, $pageHeights);
            if ($stackedPath) {
                $sourceForAnnotate = $stackedPath;
            }
        } else {
            $probe = $this->loadImage($paths[0]);
            if ($probe !== null) {
                $pageHeights[] = imagesy($probe);
                imagedestroy($probe);
            }
        }

        $page = $this->annotateUploadLikeNotebook($sourceForAnnotate, $questionRows, $summary, $pageHeights);

        if ($stackedPath && is_file($stackedPath)) {
            @unlink($stackedPath);
        }

        if ($page === null) {
            return null;
        }

        $relativePath = trim($storageFolder, '/').'/corrected_'.time().'_'.bin2hex(random_bytes(4)).'.jpg';
        $absolutePath = Storage::disk('public')->path($relativePath);
        $dir = dirname($absolutePath);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        imagejpeg($page, $absolutePath, 94);
        imagedestroy($page);

        return $relativePath;
    }

    /**
     * @param  list<string>  $paths
     * @param  list<int>  $outHeights  Scaled page heights matching stacked output
     */
    private function stackPagesToTempJpeg(array $paths, array &$outHeights = []): ?string
    {
        $images = [];
        $heights = [];
        $maxW = 0;
        $totalH = 0;
        $targetMaxW = 1200; // Hostinger memory-safe width for tall 7–12 page stacks

        foreach ($paths as $path) {
            $image = $this->loadImage($path);
            if ($image === null) {
                continue;
            }

            $w = imagesx($image);
            $h = imagesy($image);
            if ($w > $targetMaxW) {
                $nw = $targetMaxW;
                $nh = max(1, (int) round($h * ($nw / $w)));
                $scaled = imagecreatetruecolor($nw, $nh);
                if ($scaled !== false) {
                    $white = imagecolorallocate($scaled, 255, 255, 255);
                    imagefill($scaled, 0, 0, $white);
                    imagecopyresampled($scaled, $image, 0, 0, 0, 0, $nw, $nh, $w, $h);
                    imagedestroy($image);
                    $image = $scaled;
                }
            }

            $maxW = max($maxW, imagesx($image));
            $ph = imagesy($image);
            $totalH += $ph;
            $heights[] = $ph;
            $images[] = $image;
        }

        if ($images === [] || $maxW < 1 || $totalH < 1) {
            return null;
        }

        // Hard cap extreme height to avoid OOM on shared hosting.
        if ($totalH > 14000) {
            $scale = 14000 / $totalH;
            $scaledImages = [];
            $scaledHeights = [];
            $totalH = 0;
            foreach ($images as $image) {
                $w = imagesx($image);
                $h = imagesy($image);
                $nw = max(1, (int) round($w * $scale));
                $nh = max(1, (int) round($h * $scale));
                $scaled = imagecreatetruecolor($nw, $nh);
                if ($scaled === false) {
                    imagedestroy($image);
                    continue;
                }
                imagecopyresampled($scaled, $image, 0, 0, 0, 0, $nw, $nh, $w, $h);
                imagedestroy($image);
                $totalH += $nh;
                $scaledHeights[] = $nh;
                $scaledImages[] = $scaled;
            }
            $images = $scaledImages;
            $heights = $scaledHeights;
            $maxW = 0;
            foreach ($images as $image) {
                $maxW = max($maxW, imagesx($image));
            }
        }

        $canvas = imagecreatetruecolor($maxW, $totalH);
        if ($canvas === false) {
            foreach ($images as $image) {
                imagedestroy($image);
            }

            return null;
        }

        $white = imagecolorallocate($canvas, 255, 255, 255);
        imagefill($canvas, 0, 0, $white);

        $y = 0;
        foreach ($images as $image) {
            $w = imagesx($image);
            $h = imagesy($image);
            $x = (int) max(0, ($maxW - $w) / 2);
            imagecopy($canvas, $image, $x, $y, 0, 0, $w, $h);
            $y += $h;
            imagedestroy($image);
        }

        $temp = tempnam(sys_get_temp_dir(), 'sheet_stack_');
        if ($temp === false) {
            imagedestroy($canvas);

            return null;
        }

        $temp .= '.jpg';
        imagejpeg($canvas, $temp, 88);
        imagedestroy($canvas);
        $outHeights = $heights;

        return is_file($temp) ? $temp : null;
    }

    /**
     * Top  = uploaded student page with Incorrect/Correct + X/✓ + marks (image 1)
     * Bottom = notebook-style titled feedback like ChatGPT sample (image 2)
     *
     * @param  array<int, array<string, mixed>>  $questionRows
     * @param  array<string, mixed>  $summary
     * @param  list<int>  $pageHeights
     */
    private function annotateUploadLikeNotebook(string $sourceImagePath, array $questionRows, array $summary, array $pageHeights = []): ?\GdImage
    {
        $source = $this->loadImage($sourceImagePath);
        if ($source === null) {
            return null;
        }

        $srcW = imagesx($source);
        $srcH = imagesy($source);

        $lang = PaperLanguage::normalize((string) ($summary['language'] ?? $this->detectLanguageFromRows($questionRows)));
        $this->sheetLang = $lang;
        $L = PaperLanguage::labels($lang);
        $indic = in_array($lang, ['gu', 'hi'], true);

        $count = max(count($questionRows), 1);
        $canvasW = max($srcW, 1100);
        $marksColW = 120;
        $contentLeft = 86;
        $marksDividerX = $canvasW - 24 - $marksColW;
        $commentWidth = max(280, $marksDividerX - $contentLeft - 48);
        $wrapChars = max(42, (int) floor($commentWidth / 7.4));
        $wrappedComments = [];
        foreach (array_values($questionRows) as $row) {
            $wrappedComments[] = array_slice($this->wrapSmart($this->teacherComment($row, 'en'), $wrapChars), 0, 4);
        }
        $feedbackH = 90;
        foreach ($wrappedComments as $lines) {
            $feedbackH += 36 + (max(1, count($lines)) * 26) + 22;
        }
        $feedbackH += 40 + ($count * 28) + 220;
        $canvasH = $srcH + $feedbackH;

        $canvas = imagecreatetruecolor($canvasW, $canvasH);
        if ($canvas === false) {
            imagedestroy($source);

            return null;
        }

        $paper = imagecolorallocate($canvas, 255, 255, 253);
        $lineBlue = imagecolorallocate($canvas, 185, 215, 235);
        $marginBlue = imagecolorallocate($canvas, 140, 175, 210);
        $red = imagecolorallocate($canvas, 205, 28, 28);
        $darkRed = imagecolorallocate($canvas, 155, 18, 18);
        $soft = imagecolorallocate($canvas, 255, 250, 250);
        $stamp = imagecolorallocate($canvas, 220, 235, 250);

        imagefill($canvas, 0, 0, $paper);

        // Notebook lines under feedback area (image-2 feel)
        for ($ly = $srcH + 8; $ly < $canvasH - 10; $ly += 34) {
            imageline($canvas, 20, $ly, $canvasW - 20, $ly, $lineBlue);
        }
        imageline($canvas, 70, $srcH + 8, 70, $canvasH - 20, $marginBlue);
        imageline($canvas, $marksDividerX, $srcH + 8, $marksDividerX, $canvasH - 20, $marginBlue);

        // Place uploaded sheet on top (full width if possible)
        $pasteX = (int) max(0, ($canvasW - $srcW) / 2);
        $markSlots = $this->answerMarkSlots($source, $count);
        imagecopy($canvas, $source, $pasteX, 0, 0, 0, $srcW, $srcH);
        imagedestroy($source);

        // Date + stamp (image-2 cues)
        $this->drawPen($canvas, max(13, (int) ($srcW * 0.022)), $pasteX + max(12, $srcW - 140), 30, now()->format('d/m/Y'), $red);
        imagefilledellipse($canvas, $pasteX + (int) ($srcW / 2), 36, 48, 48, $stamp);
        imageellipse($canvas, $pasteX + (int) ($srcW / 2), 36, 48, 48, $marginBlue);

        // Marks in the blank space beside each answer, in one aligned column.
        $markYs = $this->resolveMarkYPositions($questionRows, $srcH, $pageHeights);
        $tick = max(26, (int) ($srcW * 0.042));
        $markX = $pasteX + (int) round($srcW * 0.62);
        $markX = min($markX, $pasteX + $srcW - $tick - 72);

        foreach (array_values($questionRows) as $index => $row) {
            $awarded = (int) ($row['score_awarded'] ?? 0);
            $max = max(1, (int) ($row['max_score'] ?? 1));
            $slot = $markSlots[$index] ?? null;
            $markY = $slot['y'] ?? $markYs[$index] ?? (int) ($srcH * (0.12 + ($index / max(1, $count)) * 0.78));
            $rowX = $slot['x'] ?? $markX;
            $markY = max(40, min($srcH - 40, $markY - (int) round($tick * 0.35)));
            $rowX = max($pasteX + 24, min($pasteX + $srcW - $tick - 64, $rowX));

            // Cross only for 0 marks; any awarded marks get a tick
            if ($awarded > 0) {
                $this->drawTick($canvas, $rowX, $markY, $tick, $red);
            } else {
                $this->drawCross($canvas, $rowX, $markY, (int) ($tick * 0.92), $red);
            }
            $sx = $rowX + $tick + 6;
            $this->drawAscii($canvas, max(14, (int) ($srcW * 0.026)), $sx, $markY + (int) ($tick * 0.55), $awarded.'/'.$max, $red);
            imageline($canvas, $sx, $markY + (int) ($tick * 0.62), $sx + 52, $markY + (int) ($tick * 0.62), $red);
        }

        // ===== Teacher Checked Sheet: Q + marks + teacher comment only =====
        $y = $srcH + 28;
        imageline($canvas, 20, $srcH + 4, $canvasW - 20, $srcH + 4, $red);

        $titleSize = $indic ? 17 : 18;
        $this->drawUiLabel($canvas, 'teacher_checked_sheet', 86, $y, $titleSize, $darkRed, $L['teacher_checked_sheet']);
        $this->drawUiLabel($canvas, 'marks', $canvasW - 260, $y, 12, $red, $L['marks']);
        $y += $indic ? 38 : 34;

        $marksColX = $marksDividerX + 18;

        foreach (array_values($questionRows) as $index => $row) {
            $number = (int) ($row['question_number'] ?? 0);
            $awarded = (int) ($row['score_awarded'] ?? 0);
            $max = max(1, (int) ($row['max_score'] ?? 1));
            $lines = $wrappedComments[$index] ?? [];
            $rowTop = $y;
            $earnedAny = $awarded > 0;

            if ($earnedAny) {
                $this->drawTick($canvas, 28, $y - 16, 18, $red);
            } else {
                $this->drawCross($canvas, 28, $y - 14, 16, $red);
            }
            $this->drawAscii($canvas, 16, $contentLeft, $y, 'Q.'.$number, $darkRed);
            $scoreX = $contentLeft + max(52, $this->lastDrawWidth + 14);
            $this->drawAscii($canvas, 18, $scoreX, $y, $awarded.'/'.$max, $red);
            imageline($canvas, $scoreX, $y + 6, $scoreX + 48, $y + 6, $red);
            $y += 30;

            foreach ($lines as $line) {
                $this->drawPen($canvas, 14, $contentLeft, $y, $line, $red);
                $y += 26;
            }

            $y += 10;
            imageline($canvas, 20, $y, $canvasW - 20, $y, $lineBlue);
            $y = max($y + 16, $rowTop + 36 + (max(1, count($lines)) * 26) + 8);
        }

        // Compact marks list like sample bottom (Q.1 0/1, Q.2 1/1, ...)
        $y += 10;
        imageline($canvas, 20, $y, $canvasW - 20, $y, $red);
        $y += 28;
        $this->drawAscii($canvas, 15, $contentLeft, $y, 'Marks summary', $darkRed);
        $y += 26;
        foreach (array_values($questionRows) as $row) {
            $number = (int) ($row['question_number'] ?? 0);
            $awarded = (int) ($row['score_awarded'] ?? 0);
            $max = max(1, (int) ($row['max_score'] ?? 1));
            if ($awarded > 0) {
                $this->drawTick($canvas, $contentLeft - 22, $y - 12, 14, $red);
            } else {
                $this->drawCross($canvas, $contentLeft - 22, $y - 10, 13, $red);
            }
            $this->drawAscii($canvas, 14, $contentLeft, $y, 'Q.'.$number, $darkRed);
            $scoreX = $contentLeft + max(48, $this->lastDrawWidth + 12);
            $this->drawAscii($canvas, 16, $scoreX, $y, $awarded.'/'.$max, $red);
            imageline($canvas, $scoreX, $y + 4, $scoreX + 46, $y + 4, $red);
            $y += 24;
        }
        $y += 8;

        // Summary box + Checked by (image-2)
        $totalScore = (int) ($summary['total_score'] ?? 0);
        $maxScore = (int) ($summary['max_score'] ?? 0);
        $percentage = (int) ($summary['percentage'] ?? 0);
        $grade = (string) ($summary['grade'] ?? $this->gradeLabel($percentage));

        $sumW = 280;
        $sumH = 140;
        $sumX = $canvasW - 20 - $sumW;
        // Keep total box below the Q.1..Q.n marks list so nothing is covered.
        $sumY = $y;
        if ($sumY + $sumH + 80 > $canvasH) {
            $sumY = max($srcH + 40, $canvasH - $sumH - 80);
        }
        imagefilledrectangle($canvas, $sumX, $sumY, $sumX + $sumW, $sumY + $sumH, $soft);
        imagerectangle($canvas, $sumX, $sumY, $sumX + $sumW, $sumY + $sumH, $red);
        $by = $sumY + 28;

        $this->drawUiLabel($canvas, 'total_marks', $sumX + 16, $by, 14, $red, $L['total_marks']);
        $this->drawAscii($canvas, 14, $sumX + 16 + max(8, $this->lastDrawWidth + 4), $by, ' : '.$totalScore.' / '.$maxScore, $red);
        $by += 28;
        $this->drawUiLabel($canvas, 'percentage', $sumX + 16, $by, 14, $red, $L['percentage']);
        $this->drawAscii($canvas, 14, $sumX + 16 + max(8, $this->lastDrawWidth + 4), $by, ' : '.$percentage.'%', $red);
        $by += 28;
        $this->drawUiLabel($canvas, 'grade', $sumX + 16, $by, 14, $red, $L['grade']);
        // Always draw A+/B/C with Latin/builtin font — never Gujarati TTF (fixes ☐+)
        $this->drawAscii($canvas, 14, $sumX + 16 + max(8, $this->lastDrawWidth + 4), $by, ' : ('.$grade.')', $red);
        $by += 28;
        imageellipse($canvas, $sumX + 170, $by - 34, 36, 30, $red);

        $sigY = max($y + 8, $sumY + $sumH + 22);
        $this->drawUiLabel($canvas, 'checked_by', $contentLeft, $sigY, 14, $darkRed, $L['checked_by']);
        $this->drawAscii($canvas, 14, $contentLeft + max(70, $this->lastDrawWidth + 8), $sigY, ' :', $darkRed);
        $this->drawAscii($canvas, 16, $contentLeft + max(90, $this->lastDrawWidth + 28), $sigY, 'GSES Teacher', $red);
        imageline($canvas, $contentLeft + 110, $sigY + 6, $contentLeft + 240, $sigY + 6, $red);
        imageline($canvas, $contentLeft + 120, $sigY + 12, $contentLeft + 220, $sigY + 2, $red);
        $this->drawUiLabel($canvas, 'date', $canvasW - 250, $sigY, 13, $red, $L['date']);
        $this->drawAscii($canvas, 13, $canvasW - 250 + max(8, $this->lastDrawWidth + 4), $sigY, ' : '.now()->format('d / m / Y'), $red);

        $used = min($canvasH, $sigY + 40);
        if ($used < $canvasH - 30) {
            $cropped = imagecreatetruecolor($canvasW, $used);
            if ($cropped !== false) {
                imagecopy($cropped, $canvas, 0, 0, 0, 0, $canvasW, $used);
                imagedestroy($canvas);

                return $cropped;
            }
        }

        return $canvas;
    }

    private function loadImage(string $path): ?\GdImage
    {
        if (! is_file($path) || ! is_readable($path)) {
            return null;
        }

        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $image = match ($ext) {
            'jpg', 'jpeg' => @imagecreatefromjpeg($path) ?: null,
            'png' => @imagecreatefrompng($path) ?: null,
            'gif' => @imagecreatefromgif($path) ?: null,
            'webp' => function_exists('imagecreatefromwebp') ? (@imagecreatefromwebp($path) ?: null) : null,
            default => null,
        };

        if ($image instanceof \GdImage) {
            return $image;
        }

        $info = @getimagesize($path);
        if (! is_array($info)) {
            return null;
        }

        return match ($info[2] ?? 0) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($path) ?: null,
            IMAGETYPE_PNG => @imagecreatefrompng($path) ?: null,
            IMAGETYPE_GIF => @imagecreatefromgif($path) ?: null,
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? (@imagecreatefromwebp($path) ?: null) : null,
            default => null,
        };
    }

    private function growCanvas(
        \GdImage $canvas,
        int $canvasW,
        int $canvasH,
        int $extra,
        int $marksDividerX,
        int $srcH,
    ): ?\GdImage {
        if ($extra < 1) {
            return null;
        }

        $newH = $canvasH + $extra;
        $grown = imagecreatetruecolor($canvasW, $newH);
        if ($grown === false) {
            return null;
        }

        $paper = imagecolorallocate($grown, 255, 255, 253);
        $lineBlue = imagecolorallocate($grown, 185, 215, 235);
        $marginBlue = imagecolorallocate($grown, 140, 175, 210);
        imagefill($grown, 0, 0, $paper);
        imagecopy($grown, $canvas, 0, 0, 0, 0, $canvasW, $canvasH);

        for ($ly = max($srcH + 8, $canvasH); $ly < $newH - 10; $ly += 34) {
            imageline($grown, 20, $ly, $canvasW - 20, $ly, $lineBlue);
        }
        imageline($grown, 70, $srcH + 8, 70, $newH - 20, $marginBlue);
        imageline($grown, $marksDividerX, $srcH + 8, $marksDividerX, $newH - 20, $marginBlue);

        return $grown;
    }

    /**
     * Blank space beside the handwriting, one column, each score on its answer.
     *
     * @return list<array{x: int, y: int}>
     */
    private function answerMarkSlots(\GdImage $image, int $count): array
    {
        $count = max(1, $count);
        $w = imagesx($image);
        $h = imagesy($image);
        $pageRight = $this->paperRightEdge($image);
        $xStart = (int) round($w * 0.05);
        $xEnd = max($xStart + 20, $pageRight - 4);
        $step = 2;
        $rows = [];

        for ($y = (int) round($h * 0.12); $y < (int) round($h * 0.96); $y += $step) {
            $ink = 0;
            $right = 0;
            for ($x = $xStart; $x < $xEnd; $x += $step) {
                if ($this->isHandwriting($image, $x, $y)) {
                    $ink++;
                    $right = $x;
                }
            }
            if ($ink >= 4) {
                $rows[] = ['y' => $y, 'right' => $right];
            }
        }

        if (count($rows) < 6) {
            return [];
        }

        $bands = $this->splitInkBands($rows, $count, $h);
        $rights = [];
        $centers = [];
        foreach ($bands as $i => $band) {
            $rights[$i] = 0;
            $sum = 0;
            foreach ($band as $row) {
                $rights[$i] = max($rights[$i], $row['right']);
                $sum += $row['y'];
            }
            $centers[$i] = (int) round($sum / max(1, count($band)));
        }

        $gap = (int) max(16, round($w * 0.018));
        $column = max($rights) + $gap;
        $tick = max(26, (int) round($w * 0.042));
        $column = min($column, $pageRight - $tick - 68);
        $column = max($xStart + 40, $column);

        $slots = [];
        for ($i = 0; $i < $count; $i++) {
            $slots[$i] = [
                'x' => $column,
                'y' => $centers[$i] ?? (int) round($h * (0.22 + ($i / $count) * 0.5)),
            ];
        }

        return $slots;
    }

    /**
     * @param  list<array{y: int, right: int}>  $rows
     * @return list<list<array{y: int, right: int}>>
     */
    private function splitInkBands(array $rows, int $count, int $height): array
    {
        $clusters = [];
        $current = [$rows[0]];
        $gap = (int) max(14, round($height * 0.025));
        $previous = $rows[0]['y'];
        foreach (array_slice($rows, 1) as $row) {
            if ($row['y'] - $previous > $gap) {
                $clusters[] = $current;
                $current = [];
            }
            $current[] = $row;
            $previous = $row['y'];
        }
        $clusters[] = $current;

        while (count($clusters) > $count) {
            $smallest = 0;
            $smallestGap = PHP_INT_MAX;
            for ($i = 0; $i < count($clusters) - 1; $i++) {
                $left = $clusters[$i];
                $right = $clusters[$i + 1];
                $between = $right[0]['y'] - $left[count($left) - 1]['y'];
                if ($between < $smallestGap) {
                    $smallestGap = $between;
                    $smallest = $i;
                }
            }
            $clusters[$smallest] = array_merge($clusters[$smallest], $clusters[$smallest + 1]);
            array_splice($clusters, $smallest + 1, 1);
        }

        if (count($clusters) === $count) {
            return $clusters;
        }

        $top = $rows[0]['y'];
        $bottom = $rows[count($rows) - 1]['y'];
        $span = max(1, $bottom - $top);
        $bands = array_fill(0, $count, []);
        foreach ($rows as $row) {
            $index = (int) min($count - 1, floor((($row['y'] - $top) / $span) * $count));
            $bands[$index][] = $row;
        }
        foreach ($bands as $i => $band) {
            if ($band === []) {
                $y = (int) round($top + (($i + 0.5) / $count) * $span);
                $bands[$i] = [['y' => $y, 'right' => $rows[0]['right']]];
            }
        }

        return $bands;
    }

    private function paperRightEdge(\GdImage $image): int
    {
        $w = imagesx($image);
        $h = imagesy($image);
        $y0 = (int) round($h * 0.15);
        $y1 = (int) round($h * 0.9);
        for ($x = $w - 2; $x > (int) round($w * 0.45); $x -= 3) {
            $dark = 0;
            $n = 0;
            for ($y = $y0; $y < $y1; $y += 4) {
                $n++;
                if ($this->isDarkPixel($image, $x, $y)) {
                    $dark++;
                }
            }
            if ($n > 0 && ($dark / $n) < 0.28) {
                return $x;
            }
        }

        return (int) round($w * 0.9);
    }

    private function isHandwriting(\GdImage $image, int $x, int $y): bool
    {
        $rgb = imagecolorat($image, $x, $y);
        $r = ($rgb >> 16) & 0xFF;
        $g = ($rgb >> 8) & 0xFF;
        $b = $rgb & 0xFF;
        if ($b > 150 && $b > $r + 18 && $g > 130) {
            return false;
        }
        $lum = (int) ((0.3 * $r) + (0.59 * $g) + (0.11 * $b));

        return $lum < 130;
    }

    private function isDarkPixel(\GdImage $image, int $x, int $y): bool
    {
        $rgb = imagecolorat($image, $x, $y);
        $r = ($rgb >> 16) & 0xFF;
        $g = ($rgb >> 8) & 0xFF;
        $b = $rgb & 0xFF;
        $lum = (int) ((0.3 * $r) + (0.59 * $g) + (0.11 * $b));

        return $lum < 90;
    }

    /**
     * @param  array<int, array<string, mixed>>  $questionRows
     * @param  list<int>  $pageHeights
     * @return array<int, int>
     */
    private function resolveMarkYPositions(array $questionRows, int $srcH, array $pageHeights): array
    {
        $count = max(count(array_values($questionRows)), 1);
        // Place each mark on its own answer block, in question order.
        // Vision y positions drift (first mark lands on the date), so the order is fixed.
        $top = (int) round($srcH * 0.18);
        $bottomRatio = min(0.86, 0.28 + ($count * 0.12));
        $bottom = (int) round($srcH * $bottomRatio);
        $bottom = max($top + 40, $bottom);
        $step = $count <= 1 ? 0 : ($bottom - $top) / ($count - 1);
        $positions = [];
        for ($i = 0; $i < $count; $i++) {
            $positions[$i] = (int) round($top + ($i * $step));
        }

        return $positions;
    }

    /**
     * @param  array<int, array<string, mixed>>  $questionRows
     */
    private function detectLanguageFromRows(array $questionRows): string
    {
        $bits = [];
        foreach ($questionRows as $row) {
            $bits[] = (string) ($row['question_text'] ?? '');
            $bits[] = (string) ($row['student_answer'] ?? '');
            $bits[] = (string) ($row['correct_answer'] ?? '');
            $bits[] = (string) ($row['teacher_comment'] ?? '');
        }

        return PaperLanguage::detect(...$bits);
    }

    private function isGenericFeedbackText(string $text): bool
    {
        $t = mb_strtolower(trim($text));
        $fragments = [
            'write the complete meaning',
            'full sentences',
            'important keyword from the chapter',
            'suitable real-life example',
            'explain the answer with more detail',
            'main idea of the lesson',
            'add key points, keywords',
            'add more key points',
            'include an important keyword',
            'answer is incomplete',
            'good attempt, but the idea is incomplete',
            'next, polish:',
            'well covered:',
            'need:',
        ];
        foreach ($fragments as $fragment) {
            if (str_contains($t, $fragment)) {
                return true;
            }
        }

        return false;
    }

    private function looksLikeBrokenOcr(string $text): bool
    {
        $t = strtolower(trim($text));
        if (strlen($t) < 8) {
            return true;
        }
        if (preg_match('/\b(body cow|teh |adn |teh\b|wnat|becouse|becaus)\b/i', $t)) {
            return true;
        }
        $letters = preg_replace('/[^a-z]/i', '', $t) ?? '';
        if ($letters !== '' && preg_match_all('/[aeiou]/i', $letters) < max(2, (int) (strlen($letters) * 0.15))) {
            return true;
        }

        return false;
    }

    /**
     * One proper teacher comment — used on both the uploaded paper margin and the checked sheet.
     *
     * @param  array<string, mixed>  $row
     */
    private function teacherComment(array $row, string $lang): string
    {
        $awarded = (int) ($row['score_awarded'] ?? 0);
        $max = max(1, (int) ($row['max_score'] ?? 1));
        $question = trim((string) ($row['paper_question_text'] ?? $row['question_text'] ?? ''));
        $topic = $this->clip(
            preg_replace('/^(what|why|how|explain|analyze|analyse|describe|write|discuss)\b[\s,:]*/iu', '', $question) ?: 'this topic',
            42
        );
        $topic = trim($topic, " \t\n\r\0\x0B.,;:");
        if ($topic === '' || IndicScript::containsIndic($topic)) {
            $topic = 'this answer';
        }

        $comment = trim((string) ($row['teacher_comment'] ?? ''));
        if (
            $comment !== ''
            && strlen($comment) >= 24
            && ! IndicScript::containsIndic($comment)
            && ! $this->isGenericFeedbackText($comment)
            && ! $this->looksLikeBrokenOcr($comment)
        ) {
            $text = preg_replace('/\s+/u', ' ', $comment) ?? $comment;
        } else {
            $text = match (true) {
                $awarded >= $max => 'Excellent work on '.$topic.'! Your answer is clear and complete. :)',
                $awarded > 0 => 'Nice effort on '.$topic.'! You are close — add a little more detail for full marks. :)',
                default => 'Good try on '.$topic.'! Keep practising — you will improve soon. :)',
            };
        }

        $text = trim($text);
        if (! str_contains($text, ':)')) {
            $text .= ' :)';
        }

        return $this->clip($text, 160);
    }

    private function drawLightbulb(\GdImage $canvas, int $x, int $y, int $color): void
    {
        imageellipse($canvas, $x + 10, $y + 8, 16, 16, $color);
        imageline($canvas, $x + 5, $y + 17, $x + 15, $y + 17, $color);
        imageline($canvas, $x + 7, $y + 21, $x + 13, $y + 21, $color);
    }

    private function drawTick(\GdImage $canvas, int $x, int $y, int $size, int $color): void
    {
        $thickness = max(3, (int) round($size / 9));
        imagesetthickness($canvas, $thickness);
        imageline($canvas, $x, $y + (int) ($size * 0.45), $x + (int) ($size * 0.35), $y + (int) ($size * 0.85), $color);
        imageline($canvas, $x + (int) ($size * 0.32), $y + (int) ($size * 0.85), $x + (int) ($size * 0.95), $y + (int) ($size * 0.1), $color);
        imagesetthickness($canvas, 1);
    }

    private function drawCross(\GdImage $canvas, int $x, int $y, int $size, int $color): void
    {
        $thickness = max(3, (int) round($size / 9));
        imagesetthickness($canvas, $thickness);
        imageline($canvas, $x, $y, $x + $size, $y + $size, $color);
        imageline($canvas, $x + $size, $y, $x, $y + $size, $color);
        imagesetthickness($canvas, 1);
    }

    private function drawStar(\GdImage $canvas, int $x, int $y, int $size, int $color): void
    {
        $r = max(4, (int) ($size / 2));
        $cx = $x + $r;
        $cy = $y + $r;
        $points = [];
        for ($i = 0; $i < 10; $i++) {
            $angle = deg2rad(-90 + ($i * 36));
            $radius = ($i % 2 === 0) ? $r : (int) ($r * 0.4);
            $points[] = (int) ($cx + cos($angle) * $radius);
            $points[] = (int) ($cy + sin($angle) * $radius);
        }
        imagefilledpolygon($canvas, $points, $color);
    }

    /**
     * Blit pre-shaped Gujarati label PNG (Hostinger-safe). Falls back to drawPen.
     */
    private function drawUiLabel(\GdImage $canvas, string $key, int $x, int $y, int $size, int $color, string $fallbackText): void
    {
        $sprite = IndicLabelSprites::find($this->sheetLang, $key);
        if ($sprite !== null) {
            $img = @imagecreatefrompng($sprite['path']);
            if ($img instanceof \GdImage) {
                imagesavealpha($img, true);
                $sw = imagesx($img);
                $sh = imagesy($img);
                // Sprites rendered at ~28px; scale to requested size.
                $targetH = max(14, (int) round($size * 1.55));
                $targetW = max(10, (int) round($sw * ($targetH / max(1, $sh))));
                $destY = max(0, $y - (int) round($targetH * 0.78));
                imagecopyresampled($canvas, $img, $x, $destY, 0, 0, $targetW, $targetH, $sw, $sh);
                imagedestroy($img);
                $this->lastDrawWidth = $targetW;

                return;
            }
        }

        $this->drawPen($canvas, $size, $x, $y, $fallbackText, $color);
    }

    private function drawAnswerHeading(\GdImage $canvas, int $size, int $x, int $y, int $number, int $color, array $L): void
    {
        $this->drawAscii($canvas, $size, $x, $y, '[ ', $color);
        $x2 = $x + max(10, $this->lastDrawWidth);
        $this->drawUiLabel($canvas, 'answer_no_label', $x2, $y, $size, $color, $L['answer_no_label']);
        $x3 = $x2 + max(10, $this->lastDrawWidth);
        $this->drawAscii($canvas, $size, $x3, $y, ' '.$number.' ]', $color);
        $this->lastDrawWidth = ($x3 + $this->lastDrawWidth) - $x;
    }

    /**
     * ASCII / digits / punctuation only — never use Gujarati TTF (fixes A+ → ☐).
     */
    private function drawAscii(\GdImage $canvas, int $size, int $x, int $y, string $text, int $color): void
    {
        $text = $this->sanitizeForDraw($text);
        if ($text === '') {
            $this->lastDrawWidth = 0;

            return;
        }

        $font = $this->latinFont ?? IndicFontResolver::latin();
        if ($font && function_exists('imagettftext')) {
            $box = imagettfbbox($size, 0, $font, $text);
            imagettftext($canvas, $size, 0, $x, $y, $color, $font, $text);
            $this->lastDrawWidth = is_array($box) ? (int) abs($box[2] - $box[0]) + 1 : strlen($text) * (int) ($size * 0.55);

            return;
        }

        // Builtin GD font always has A–Z / 0–9
        imagestring($canvas, 5, $x, max(0, $y - 12), $text, $color);
        $this->lastDrawWidth = strlen($text) * 9;
    }

    private function drawPen(\GdImage $canvas, int $size, int $x, int $y, string $text, int $color): void
    {
        $text = $this->sanitizeForDraw($text);
        if ($text === '') {
            $this->lastDrawWidth = 0;

            return;
        }

        // Pure ASCII → Latin path only
        if (! IndicScript::containsIndic($text)) {
            $this->drawAscii($canvas, $size, $x, $y, $text, $color);

            return;
        }

        // Ensure we have a real Gujarati font before drawing dynamic text.
        if (! $this->gujaratiFont || ! IndicScript::fontRendersGujarati($this->gujaratiFont)) {
            IndicFontResolver::diagnose(); // clears cache + re-find/download
            $this->gujaratiFont = IndicFontResolver::indic();
        }

        if (! $this->gujaratiFont) {
            Log::error('Dynamic Gujarati text skipped: NotoSansGujarati-Regular.ttf missing on live server');
            $this->drawAscii($canvas, $size, $x, $y, '[Gujarati font missing]', $color);

            return;
        }

        if (! function_exists('imagettftext')) {
            imagestring($canvas, 3, $x, max(0, $y - 12), $text, $color);
            $this->lastDrawWidth = strlen($text) * 8;

            return;
        }

        $rgb = imagecolorsforindex($canvas, $color) ?: ['red' => 200, 'green' => 30, 'blue' => 30];
        $r = (int) ($rgb['red'] ?? 200);
        $g = (int) ($rgb['green'] ?? 30);
        $b = (int) ($rgb['blue'] ?? 30);

        $cursorX = $x;
        foreach ($this->scriptRuns($text) as $run) {
            $chunk = $run['text'];
            if ($chunk === '') {
                continue;
            }

            if (! $run['indic']) {
                $before = $cursorX;
                $this->drawAscii($canvas, $size, $cursorX, $y, $chunk, $color);
                $cursorX = $before + max(1, $this->lastDrawWidth);
                continue;
            }

            $indicFont = $this->gujaratiFont;
            $raster = IndicTextRasterizer::rasterize($chunk, $size, $indicFont, $r, $g, $b);
            if (is_array($raster)) {
                $layer = @imagecreatefromstring($raster['png']);
                if ($layer instanceof \GdImage) {
                    $lw = imagesx($layer);
                    $lh = imagesy($layer);
                    $destY = max(0, $y - (int) round($lh * 0.78));
                    imagecopy($canvas, $layer, $cursorX, $destY, 0, 0, $lw, $lh);
                    imagedestroy($layer);
                    $cursorX += $lw + 1;
                    continue;
                }
            }

            $box = imagettfbbox($size, 0, $indicFont, $chunk);
            imagettftext($canvas, $size, 0, $cursorX, $y, $color, $indicFont, $chunk);
            $cursorX += is_array($box)
                ? (int) abs($box[2] - $box[0]) + 1
                : $this->graphemeLen($chunk) * (int) ($size * 0.7);
        }

        $this->lastDrawWidth = max(1, $cursorX - $x);
    }

    /**
     * @return array<int, array{text: string, indic: bool}>
     */
    private function scriptRuns(string $text): array
    {
        $runs = [];
        $current = '';
        $currentIsIndic = null;
        $chars = preg_match_all('/\X/u', $text, $m) ? ($m[0] ?? []) : (preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: []);

        foreach ($chars as $char) {
            $isIndic = IndicScript::isIndicChar($char);

            if ($current !== '' && preg_match('/^\s+$/u', $char)) {
                $current .= $char;
                continue;
            }

            if ($currentIsIndic === null) {
                $currentIsIndic = $isIndic;
                $current = $char;
                continue;
            }

            if ($isIndic === $currentIsIndic) {
                $current .= $char;
            } else {
                $runs[] = ['text' => $current, 'indic' => (bool) $currentIsIndic];
                $current = $char;
                $currentIsIndic = $isIndic;
            }
        }

        if ($current !== '') {
            $runs[] = ['text' => $current, 'indic' => (bool) $currentIsIndic];
        }

        return $runs !== [] ? $runs : [['text' => $text, 'indic' => false]];
    }

    /**
     * @return array<int, string>
     */
    private function wrapSmart(string $text, int $width): array
    {
        $text = $this->sanitizeForDraw($text);
        if ($text === '') {
            return [];
        }

        if (IndicScript::containsIndic($text)) {
            $graphemes = $this->indicClusters($text);
            if ($graphemes === []) {
                return [$text];
            }

            $lines = [];
            $line = '';
            $count = 0;
            foreach ($graphemes as $g) {
                $isSpace = preg_match('/^\s+$/u', $g) === 1;
                if ($count >= $width && $isSpace) {
                    $lines[] = rtrim($line);
                    $line = '';
                    $count = 0;
                    continue;
                }
                if ($count >= $width && ! $isSpace) {
                    $spacePos = mb_strrpos($line, ' ', 0, 'UTF-8');
                    if ($spacePos !== false && $spacePos > (int) ($width * 0.35)) {
                        $lines[] = rtrim(mb_substr($line, 0, $spacePos, 'UTF-8'));
                        $line = ltrim(mb_substr($line, $spacePos + 1, null, 'UTF-8')).$g;
                        $count = count($this->indicClusters($line));
                        continue;
                    }
                    $lines[] = $line;
                    $line = $g;
                    $count = 1;
                    continue;
                }
                $line .= $g;
                $count++;
            }
            if (trim($line) !== '') {
                $lines[] = rtrim($line);
            }

            return array_values(array_filter($lines, fn ($l) => trim($l) !== ''));
        }

        return array_values(array_filter(explode("\n", wordwrap($text, $width, "\n", true)), fn ($l) => $l !== ''));
    }

    private function graphemeLen(string $text): int
    {
        if (IndicScript::containsIndic($text)) {
            return max(1, count($this->indicClusters($text)));
        }

        if (function_exists('grapheme_strlen')) {
            $len = grapheme_strlen($text);
            if (is_int($len)) {
                return max(1, $len);
            }
        }
        if (preg_match_all('/\X/u', $text, $m)) {
            return max(1, count($m[0]));
        }

        return max(1, mb_strlen($text, 'UTF-8'));
    }

    /**
     * Keep matras and virama+conjuncts with their base letter so wrap never breaks ઉદાહરણો / પ્રયત્ન.
     *
     * @return list<string>
     */
    private function indicClusters(string $text): array
    {
        $chars = preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $clusters = [];
        $n = count($chars);
        $i = 0;

        while ($i < $n) {
            $ch = $chars[$i];

            if (preg_match('/^[\s\.,;:!\?\-\/\(\)0-9A-Za-z\+\*=\'\"%]+$/u', $ch)) {
                $clusters[] = $ch;
                $i++;
                continue;
            }

            $buf = $ch;
            $i++;

            while ($i < $n) {
                $next = $chars[$i];

                // Combining marks: matras, anusvara, visarga, nukta (Gujarati + Devanagari)
                if (preg_match('/[\x{0A81}-\x{0A83}\x{0ABC}\x{0ABE}-\x{0ACC}\x{0901}-\x{0903}\x{093C}\x{093A}-\x{094C}\x{094E}\x{094F}]/u', $next)) {
                    $buf .= $next;
                    $i++;
                    continue;
                }

                // Virama + following consonant (+ its matras) → one conjunct cluster
                if (preg_match('/[\x{0ACD}\x{094D}]/u', $next)) {
                    $buf .= $next;
                    $i++;
                    if ($i < $n && IndicScript::isIndicChar($chars[$i])
                        && ! preg_match('/[\x{0A81}-\x{0A83}\x{0ABC}\x{0ABE}-\x{0ACC}\x{0ACD}\x{0901}-\x{0903}\x{093C}\x{093A}-\x{094C}\x{094D}]/u', $chars[$i])) {
                        $buf .= $chars[$i];
                        $i++;
                        while ($i < $n && preg_match('/[\x{0A81}-\x{0A83}\x{0ABC}\x{0ABE}-\x{0ACC}\x{0901}-\x{0903}\x{093C}\x{093A}-\x{094C}]/u', $chars[$i])) {
                            $buf .= $chars[$i];
                            $i++;
                        }
                    }
                    continue;
                }

                break;
            }

            $clusters[] = $buf;
        }

        return $clusters;
    }

    private function clip(string $text, int $chars): string
    {
        $text = $this->sanitizeForDraw($text);
        if ($this->graphemeLen($text) <= $chars) {
            return $text;
        }

        if (IndicScript::containsIndic($text)) {
            $parts = $this->indicClusters($text);

            return implode('', array_slice($parts, 0, max(1, $chars - 1))).'...';
        }

        if (function_exists('grapheme_substr')) {
            return rtrim((string) grapheme_substr($text, 0, max(1, $chars - 1))).'...';
        }

        return mb_substr($text, 0, $chars - 1, 'UTF-8').'...';
    }

    private function sanitizeForDraw(string $text): string
    {
        $text = str_replace(
            ['✓', '✔', '✗', '✘', '⭐', '🌟', '🏆', '🎉', '👍', '💪', '😊', '🙂', '💡'],
            ['', '', '', '', '*', '*', '*', '*', '', '', ':)', ':)', ''],
            $text
        );

        return trim(preg_replace("/\s+/u", ' ', $text) ?? $text);
    }

    private function gradeLabel(int $percentage): string
    {
        return match (true) {
            $percentage >= 90 => 'A+',
            $percentage >= 75 => 'A',
            $percentage >= 60 => 'B',
            $percentage >= 45 => 'C',
            $percentage >= 30 => 'D',
            default => 'E',
        };
    }

    private function resolveLatinFont(): ?string
    {
        return IndicFontResolver::latin() ?? $this->firstExistingFont([
            resource_path('fonts/NotoSans-Regular.ttf'),
            public_path('fonts/NotoSans-Regular.ttf'),
            'C:\\Windows\\Fonts\\comic.ttf',
            'C:\\Windows\\Fonts\\arial.ttf',
            '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
        ]);
    }

    private function resolvePenFont(): ?string
    {
        return $this->firstExistingFont([
            resource_path('fonts/PatrickHand-Regular.ttf'),
            resource_path('fonts/Inkfree.ttf'),
            'C:\\Windows\\Fonts\\Inkfree.ttf',
            'C:\\Windows\\Fonts\\segoesc.ttf',
            'C:\\Windows\\Fonts\\comic.ttf',
            public_path('fonts/NotoSans-Regular.ttf'),
            resource_path('fonts/NotoSans-Regular.ttf'),
        ]);
    }

    private function resolveGujaratiFont(): ?string
    {
        return IndicFontResolver::indic();
    }

    /**
     * @param  array<int, string>  $candidates
     */
    private function firstExistingFont(array $candidates): ?string
    {
        foreach ($candidates as $font) {
            if (is_file($font) && is_readable($font) && filesize($font) > 1000) {
                return $font;
            }
        }

        return null;
    }
}
