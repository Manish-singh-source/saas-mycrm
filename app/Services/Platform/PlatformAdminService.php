<?php

namespace App\Services\Platform;

use App\Models\PlatformUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PlatformAdminService
{
    public function audit(Request $request, string $event, string $subjectType, int $subjectId, ?array $old = null, ?array $new = null, ?string $description = null): void
    {
        $actor = $request->user();

        DB::table('activity_logs')->insert([
            'tenant_id' => null,
            'actor_user_id' => null,
            'actor_platform_user_id' => $actor instanceof PlatformUser ? $actor->id : null,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'event' => $event,
            'description' => $description,
            'old_values' => $old ? json_encode($old) : null,
            'new_values' => $new ? json_encode($new) : null,
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 1000),
            'request_id' => $request->attributes->get('request_id') ?? $request->header('X-Request-Id'),
            'created_at' => now(),
        ]);
    }

    public function security(Request $request, string $event, string $severity, ?int $tenantId = null, array $metadata = []): void
    {
        DB::table('security_events')->insert([
            'tenant_id' => $tenantId,
            'user_id' => null,
            'event' => $event,
            'severity' => $severity,
            'ip_address' => $request->ip(),
            'metadata' => json_encode([
                ...$metadata,
                'platform_user_id' => $request->user()?->id,
                'request_id' => $request->attributes->get('request_id') ?? $request->header('X-Request-Id'),
            ]),
            'created_at' => now(),
        ]);
    }

    public function createDefaultTenantSettings(int $tenantId): void
    {
        foreach ([
            ['general', 'locale', 'en', 'string'],
            ['security', 'require_2fa', false, 'boolean'],
            ['modules', 'overrides', [], 'json'],
        ] as [$group, $key, $value, $type]) {
            DB::table('tenant_settings')->updateOrInsert(
                ['tenant_id' => $tenantId, 'group' => $group, 'key' => $key],
                ['value' => json_encode($value), 'value_type' => $type, 'is_encrypted' => false, 'updated_at' => now(), 'created_at' => now()]
            );
        }
    }
}
