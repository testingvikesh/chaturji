<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ticket extends Model
{
    public const STATUSES = [
        'open' => 'Open',
        'answered' => 'Answered',
        'closed' => 'Closed',
    ];

    public const CATEGORIES = [
        'exam' => 'Exam',
        'homework' => 'Homework',
        'account' => 'Account',
        'technical' => 'Technical',
        'other' => 'Other',
    ];

    protected $fillable = [
        'ticket_no',
        'user_id',
        'role',
        'category',
        'subject',
        'message',
        'status',
        'last_replied_at',
    ];

    protected $casts = [
        'last_replied_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function replies(): HasMany
    {
        return $this->hasMany(TicketReply::class)->oldest();
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(TicketAttachment::class)->oldest();
    }

    public function isClosed(): bool
    {
        return $this->status === 'closed';
    }

    public function categoryLabel(): string
    {
        return self::CATEGORIES[$this->category] ?? ucfirst((string) $this->category);
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst((string) $this->status);
    }

    public static function nextNumber(): string
    {
        $prefix = 'TKT-'.now()->format('ymd').'-';
        $last = static::query()
            ->where('ticket_no', 'like', $prefix.'%')
            ->orderByDesc('id')
            ->value('ticket_no');

        $seq = $last ? ((int) substr((string) $last, -4)) + 1 : 1;

        return $prefix.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }
}
