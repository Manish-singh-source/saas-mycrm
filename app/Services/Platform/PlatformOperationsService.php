<?php

namespace App\Services\Platform;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PlatformOperationsService
{
    public function __construct(private readonly PlatformAdminService $admin) {}

    public function byUuid(string $table, string $uuid, bool $withTrashed = false): object
    {
        $q = DB::table($table)->where('uuid', $uuid);
        if (! $withTrashed && $this->softDeletes($table)) {
            $q->whereNull('deleted_at');
        }
        $row = $q->first();
        abort_if(! $row, 404);
        return $row;
    }

    public function tenantId(?string $uuid): ?int
    {
        if (! $uuid) return null;
        $id = DB::table('tenants')->where('uuid', $uuid)->value('id');
        abort_if(! $id, 404);
        return (int) $id;
    }

    public function platformUserId(?string $uuid): ?int
    {
        if (! $uuid) return null;
        $id = DB::table('platform_users')->where('uuid', $uuid)->value('id');
        abort_if(! $id, 404);
        return (int) $id;
    }

    public function encryptCredentials(array $credentials): array
    {
        $rows = [];
        foreach ($credentials as $key => $value) {
            $rows[] = ['key' => (string) $key, 'encrypted_value' => Crypt::encryptString((string) $value), 'expires_at' => null];
        }
        return $rows;
    }

    public function mask(mixed $value): mixed
    {
        $sensitive = ['password', 'secret', 'token', 'key', 'authorization', 'signature', 'credential', 'api_key'];
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            return json_last_error() === JSON_ERROR_NONE ? $this->mask($decoded) : $value;
        }
        if (! is_array($value)) return $value;
        foreach ($value as $key => $child) {
            $value[$key] = Str::contains(Str::lower((string) $key), $sensitive) ? '[masked]' : $this->mask($child);
        }
        return $value;
    }

    public function retryOnce(Request $request, string $table, int $id, string $event): bool
    {
        $key = $request->header('Idempotency-Key');
        abort_if(! $key, 409, 'Idempotency-Key header is required.');
        $row = DB::table($table)->where('id', $id)->first();
        if (($row->last_retry_idempotency_key ?? null) === $key) return false;
        DB::table($table)->where('id', $id)->update(['status' => 'retry_queued', 'retry_count' => DB::raw('retry_count + 1'), 'queued_at' => now(), 'last_retry_idempotency_key' => $key]);
        $this->audit($request, $event, $table, $id, (array) $row, ['status' => 'retry_queued']);
        return true;
    }

    public function audit(Request $request, string $event, string $subjectType, int $subjectId, ?array $old = null, ?array $new = null, ?string $description = null): void
    {
        $this->admin->audit($request, $event, $subjectType, $subjectId, $old, $new, $description);
    }

    private function softDeletes(string $table): bool
    {
        return in_array($table, ['platform_tickets', 'knowledge_base_articles', 'legal_documents', 'platform_announcements', 'platform_webhook_endpoints'], true);
    }
}
