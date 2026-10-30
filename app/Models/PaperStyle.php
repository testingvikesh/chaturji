<?php

namespace App\Models;

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
}
