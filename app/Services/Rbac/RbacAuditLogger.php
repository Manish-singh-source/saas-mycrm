<?php

namespace App\Services\Rbac;

use App\Models\ActivityLog;
use App\Models\PlatformUser;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class RbacAuditLogger
{
    public function log(Request $request, string $event, Model $subject, ?array $oldValues = null, ?array $newValues = null, ?string $description = null): void
    {
        $actor = $request->user();

        ActivityLog::query()->create([
            'tenant_id' => $subject->getAttribute('tenant_id') ?: $request->attributes->get('tenant_id'),
            'actor_user_id' => $actor instanceof User ? $actor->id : null,
            'actor_platform_user_id' => $actor instanceof PlatformUser ? $actor->id : null,
            'subject_type' => $subject::class,
            'subject_id' => $subject->getKey(),
            'event' => $event,
            'description' => $description,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
        ]);
    }
}
