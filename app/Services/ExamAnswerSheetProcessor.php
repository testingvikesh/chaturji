<?php

namespace App\Services;

use App\Models\Exam;
use App\Models\ExamSubmission;
use App\Models\User;
use Illuminate\Http\UploadedFile;

/**
 * @deprecated Use AnswerSheetProcessor::processExam() instead.
 */
class ExamAnswerSheetProcessor extends AnswerSheetProcessor
{
    public function process(Exam $exam, User $user, UploadedFile $file): ExamSubmission
    {
        return $this->processExam($exam, $user, $file);
    }
}
