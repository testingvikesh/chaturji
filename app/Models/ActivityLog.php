<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ActivityLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'role',
        'action',
        'subject_type',
        'subject_id',
        'description',
        'properties',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected $casts = [
        'properties' => 'array',
        'created_at' => 'datetime',
    ];

    public const ACTION_LABELS = [
        'auth.login' => 'Login',
        'auth.login_failed' => 'Login failed',
        'auth.logout' => 'Logout',
        'teacher.settings.update' => 'Teacher subjects saved',
        'teacher.settings.remove_subject' => 'Teacher subject removed',
        'teacher.settings.clear_group' => 'Teacher subject group cleared',
        'teacher.question.update' => 'Material question edited',
        'teacher.exam.create' => 'Exam created',
        'teacher.homework.create' => 'Homework created',
        'ticket.create' => 'Ticket created',
        'ticket.reply' => 'Ticket reply',
        'ticket.status' => 'Ticket status changed',
        'admin.user.approve' => 'User approved',
        'admin.user.pending' => 'User set pending',
        'admin.user.update' => 'User updated',
        'admin.user.delete' => 'User deleted',
        'profile.update' => 'Profile updated',
        'profile.password' => 'Password changed',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function actionLabel(): string
    {
        return self::ACTION_LABELS[$this->action] ?? str_replace(['_', '.'], [' ', ' · '], (string) $this->action);
    }
}
