<?php

namespace App\Support;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ActivityLogger
{
    public static function log(
        string $action,
        string $description,
        ?Model $subject = null,
        array $properties = [],
        ?User $actor = null
    ): ?ActivityLog {
        try {
            $actor = $actor ?? (auth()->user() instanceof User ? auth()->user() : null);
            $request = request();

            return ActivityLog::query()->create([
                'user_id' => $actor?->id,
                'role' => $actor?->role ?? ($properties['role'] ?? null),
                'action' => $action,
                'subject_type' => $subject ? $subject->getMorphClass() : null,
                'subject_id' => $subject?->getKey(),
                'description' => Str::limit($description, 255),
                'properties' => $properties !== [] ? $properties : null,
                'ip_address' => $request?->ip(),
                'user_agent' => $request ? Str::limit((string) $request->userAgent(), 500) : null,
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            report($e);

            return null;
        }
    }
}
