<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class ActivityLogger
{
    public static function record(Request $request, string $event, object $subject, ?string $description = null, array $newValues = []): void
    {
        $user = $request->user();
        DB::table('activity_logs')->insert([
            'tenant_id' => $user?->tenant_id,
            'actor_user_id' => $user && $user->getTable() === 'users' ? $user->getKey() : null,
            'actor_platform_user_id' => $user && $user->getTable() === 'platform_users' ? $user->getKey() : null,
            'subject_type' => $subject::class,
            'subject_id' => $subject->getKey(),
            'event' => $event,
            'description' => $description,
            'new_values' => $newValues === [] ? null : json_encode($newValues),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'request_id' => $request->header('X-Request-ID'),
            'created_at' => now(),
        ]);
    }
}
