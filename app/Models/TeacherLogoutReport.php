<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeacherLogoutReport extends Model
{
    protected $fillable = [
        'teacher_id',
        'report_date',
        'employee_code',
        'medium',
        'standard',
        'subject_id',
        'subject_name',
        'chapter_id',
        'chapter_name',
        'topic_id',
        'topic_name',
        'topic_ids',
        'topic_names',
        'chk_medium',
        'chk_standard',
        'chk_subject',
        'chk_chapter',
        'chk_topic',
        'chk_complete',
        'chk_remain',
        'status',
        'notes',
        'mail_sent',
        'submitted_at',
    ];

    protected $casts = [
        'report_date' => 'date',
        'topic_ids' => 'array',
        'chk_medium' => 'boolean',
        'chk_standard' => 'boolean',
        'chk_subject' => 'boolean',
        'chk_chapter' => 'boolean',
        'chk_topic' => 'boolean',
        'chk_complete' => 'boolean',
        'chk_remain' => 'boolean',
        'mail_sent' => 'boolean',
        'submitted_at' => 'datetime',
    ];

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function chapter(): BelongsTo
    {
        return $this->belongsTo(Chapter::class);
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    public function statusLabel(): string
    {
        if ($this->chk_complete) {
            return 'Complete';
        }
        if ($this->chk_remain) {
            return 'Remain';
        }

        return $this->status ? ucfirst((string) $this->status) : '—';
    }

    public function checkedLabels(): array
    {
        $labels = [];
        if ($this->chk_medium) {
            $labels[] = 'Medium';
        }
        if ($this->chk_standard) {
            $labels[] = 'Standard';
        }
        if ($this->chk_subject) {
            $labels[] = 'Subject';
        }
        if ($this->chk_chapter) {
            $labels[] = 'Chapter';
        }
        if ($this->chk_topic) {
            $labels[] = 'Topic';
        }
        if ($this->chk_complete) {
            $labels[] = 'Complete';
        }
        if ($this->chk_remain) {
            $labels[] = 'Remain';
        }

        return $labels;
    }
}
