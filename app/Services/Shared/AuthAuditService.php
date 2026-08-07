<?php

namespace App\Services\Shared;

use App\Models\PlatformUser;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AuthAuditService
{
    public function log(Request $request, string $event, string $severity = 'info', ?User $tenantUser = null, ?PlatformUser $platformUser = null, array $metadata = []): void
    {
        $tenantId = $tenantUser?->tenant_id ?? $request->attributes->get('tenant_id');

        if (DB::getSchemaBuilder()->hasTable('security_events')) {
            DB::table('security_events')->insert([
                'tenant_id' => $tenantId,
                'user_id' => $tenantUser?->id,
                'event' => $event,
                'severity' => $severity,
                'ip_address' => $request->ip(),
                'metadata' => json_encode(array_filter([
                    'platform_user_id' => $platformUser?->id,
                    'request_id' => $request->attributes->get('request_id'),
                    'user_agent' => $request->userAgent(),
                    ...$metadata,
                ], static fn (mixed $value): bool => $value !== null)),
                'created_at' => now(),
            ]);
        }

        if (DB::getSchemaBuilder()->hasTable('activity_logs')) {
            DB::table('activity_logs')->insert([
                'tenant_id' => $tenantId,
                'actor_user_id' => $tenantUser?->id,
                'actor_platform_user_id' => $platformUser?->id,
                'subject_type' => $platformUser !== null ? PlatformUser::class : User::class,
                'subject_id' => $platformUser?->id ?? $tenantUser?->id ?? 0,
                'event' => $event,
                'description' => str_replace('_', ' ', $event),
                'old_values' => null,
                'new_values' => $metadata === [] ? null : json_encode($metadata),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'created_at' => now(),
            ]);
        }
    }
}