<?php

namespace App\Jobs;

use App\Models\ExamSubmission;
use App\Services\AnswerSheetProcessor;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessExamAnswerSheet
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public int $submissionId
    ) {}

    public function handle(AnswerSheetProcessor $processor): void
    {
        $submission = ExamSubmission::query()->find($this->submissionId);

        if (! $submission || $submission->status !== 'processing') {
            return;
        }

        try {
            @ignore_user_abort(true);
            @set_time_limit(300);
            @ini_set('max_execution_time', '300');
            $processor->finishExamSubmission($submission);
        } catch (Throwable $exception) {
            Log::error('Background exam sheet check failed: '.$exception->getMessage(), [
                'submission_id' => $this->submissionId,
            ]);

            $submission->update([
                'status' => 'failed',
                'error_message' => $exception->getMessage(),
            ]);
        }
    }
}
