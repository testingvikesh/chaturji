<?php

namespace App\Console\Commands;

use App\Jobs\ProcessExamAnswerSheet;
use App\Jobs\ProcessHomeworkAnswerSheet;
use App\Models\ExamSubmission;
use App\Models\HomeworkSubmission;
use Illuminate\Console\Command;

class ProcessPendingAnswerSheetsCommand extends Command
{
    protected $signature = 'homework:process-pending-sheets {--limit=5}';

    protected $description = 'Finish stuck/processing answer-sheet checks (Hostinger cron fallback)';

    public function handle(): int
    {
        $limit = max(1, (int) $this->option('limit'));

        $examIds = ExamSubmission::query()
            ->where('status', 'processing')
            ->where('submitted_at', '<=', now()->subSeconds(5))
            ->orderBy('id')
            ->limit($limit)
            ->pluck('id');

        foreach ($examIds as $id) {
            $this->info("Processing exam submission #{$id}");
            ProcessExamAnswerSheet::dispatchSync((int) $id);
        }

        $homeworkIds = HomeworkSubmission::query()
            ->where('status', 'processing')
            ->where('submitted_at', '<=', now()->subSeconds(5))
            ->orderBy('id')
            ->limit($limit)
            ->pluck('id');

        foreach ($homeworkIds as $id) {
            $this->info("Processing homework submission #{$id}");
            ProcessHomeworkAnswerSheet::dispatchSync((int) $id);
        }

        $this->info('Done. Exam: '.$examIds->count().' · Homework: '.$homeworkIds->count());

        return self::SUCCESS;
    }
}
