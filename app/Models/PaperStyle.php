<?php

namespace App\Models;

use App\Support\ExamPaperWeightage;
use App\Support\PaperTypeHelper;
use App\Support\SubjectiveQuestionTypes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaperStyle extends Model
{
    protected $fillable = [
        'standard_id',
        'subject_id',
        'type_counts',
    ];

    protected $casts = [
        'type_counts' => 'array',
    ];

    public function standard(): BelongsTo
    {
        return $this->belongsTo(Standard::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    /**
     * @return list<array{label: string, quantity: int}>
     */
    public function quantityRows(bool $subjective): array
    {
        $rows = [];
        foreach (PaperTypeHelper::types() as $type => $label) {
            if (in_array($type, ['short_answer', 'long_answer'], true)) {
                continue;
            }
            if (SubjectiveQuestionTypes::isSubjective($type) !== $subjective) {
                continue;
            }
            $quantity = (int) ($this->type_counts[$type] ?? 0);
            if ($quantity > 0) {
                $rows[] = ['label' => $label, 'quantity' => $quantity];
            }
        }

        return $rows;
    }

    public function totalMarks(): int
    {
        $total = 0;
        foreach ((array) $this->type_counts as $type => $count) {
            if (in_array($type, ['short_answer', 'long_answer'], true)) {
                continue;
            }
            $each = (int) (ExamPaperWeightage::MARKS_PER_QUESTION[$type] ?? 1);
            $total += max(0, (int) $count) * max(1, $each);
        }

        return $total;
    }
}
