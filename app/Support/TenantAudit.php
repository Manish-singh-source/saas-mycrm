<?php

namespace App\Support;

use App\Models\PlatformUser;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class TenantAudit
{
    public static function record(Request $request, Tenant $tenant, string $event, array $data = [], bool $security = false): void
    {
        $actor = $request->user();
        $data = TenantPresenter::safe($data);
        DB::table('activity_logs')->insert([
            'tenant_id' => $tenant->id, 'actor_platform_user_id' => $actor instanceof PlatformUser ? $actor->id : null,
            'actor_user_id' => $actor && ! ($actor instanceof PlatformUser) ? $actor->id : null,
            'subject_type' => Tenant::class, 'subject_id' => $tenant->id, 'event' => $event,
            'description' => $data['reason'] ?? null, 'new_values' => json_encode($data),
            'ip_address' => $request->ip(), 'user_agent' => substr((string) $request->userAgent(), 0, 1000),
            'request_id' => $request->header('X-Request-ID'), 'created_at' => now(),
        ]);
        if ($security) {
            DB::table('security_events')->insert([
                'tenant_id' => $tenant->id, 'event' => $event, 'severity' => 'info', 'ip_address' => $request->ip(),
                'metadata' => json_encode($data + ['platform_user_id' => $actor instanceof PlatformUser ? $actor->id : null]), 'created_at' => now(),
            ]);
        }
    }
}
