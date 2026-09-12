<?php

namespace App\Jobs;

use App\Models\HomeworkSubmission;
use App\Services\AnswerSheetProcessor;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessHomeworkAnswerSheet
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public int $submissionId
    ) {}

    public function handle(AnswerSheetProcessor $processor): void
    {
        $submission = HomeworkSubmission::query()->find($this->submissionId);

        if (! $submission || $submission->status !== 'processing') {
            return;
        }

        try {
            @ignore_user_abort(true);
            @set_time_limit(300);
            @ini_set('max_execution_time', '300');
            $processor->finishHomeworkSubmission($submission);
        } catch (Throwable $exception) {
            Log::error('Background homework sheet check failed: '.$exception->getMessage(), [
                'submission_id' => $this->submissionId,
            ]);

            $submission->update([
                'status' => 'failed',
                'error_message' => $exception->getMessage(),
            ]);
        }
    }
}
