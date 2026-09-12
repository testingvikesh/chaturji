<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeacherDailyOtp extends Model
{
    protected $fillable = [
        'user_id',
        'otp',
        'otp_date',
    ];

    protected $casts = [
        'otp_date' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
