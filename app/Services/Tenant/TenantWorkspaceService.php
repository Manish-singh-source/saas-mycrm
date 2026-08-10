<?php

namespace App\Services\Tenant;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class TenantWorkspaceService extends BaseTenantService
{
    public function audit(Request $request, string $event, string $subjectType, int $subjectId, ?array $old = null, ?array $new = null, ?string $description = null): void
    {
        DB::table('activity_logs')->insert([
            'tenant_id' => $this->tenantId(),
            'actor_user_id' => $request->user() instanceof User ? $request->user()->id : null,
            'actor_platform_user_id' => null,
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

    public function byUuid(string $table, ?string $uuid, bool $nullable = true): ?object
    {
        if ($uuid === null || $uuid === '') {
            return $nullable ? null : abort(422, 'Required UUID is missing.');
        }

        if (! Schema::hasTable($table)) {
            abort(422, "Unsupported table {$table}.");
        }

        $query = DB::table($table)->where('uuid', $uuid);
        if (Schema::hasColumn($table, 'tenant_id')) {
            $query->where('tenant_id', $this->tenantId());
        }
        if (Schema::hasColumn($table, 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        return $query->first() ?: abort(404, 'Resource not found.');
    }

    public function uuidToId(string $table, ?string $uuid, bool $nullable = true): ?int
    {
        return ($record = $this->byUuid($table, $uuid, $nullable)) ? (int) $record->id : null;
    }

    public function count(string $table, array $where = []): int
    {
        if (! Schema::hasTable($table)) {
            return 0;
        }

        $query = DB::table($table);
        if (Schema::hasColumn($table, 'tenant_id')) {
            $query->where('tenant_id', $this->tenantId());
        }
        if (Schema::hasColumn($table, 'deleted_at')) {
            $query->whereNull('deleted_at');
        }
        foreach ($where as $column => $value) {
            if (Schema::hasColumn($table, $column)) {
                is_array($value) ? $query->whereIn($column, $value) : $query->where($column, $value);
            }
        }

        return (int) $query->count();
    }

    public function createJob(Request $request, string $type, string $module, array $payload = []): object
    {
        $id = DB::table('tenant_import_export_jobs')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'tenant_id' => $this->tenantId(),
            'user_id' => $request->user()?->id,
            'type' => $type,
            'module' => $module,
            'status' => 'queued',
            'payload' => json_encode($payload),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->audit($request, 'tenant_'.$module.'_'.$type.'_queued', 'tenant_import_export_job', $id, null, $payload);

        return DB::table('tenant_import_export_jobs')->where('id', $id)->first();
    }

    public function bankPayload(object $row): array
    {
        $account = null;
        try {
            $account = Crypt::decryptString((string) $row->account_number_encrypted);
        } catch (\Throwable) {
            $account = null;
        }

        $data = (array) $row;
        unset($data['account_number_encrypted']);
        $data['account_number_masked'] = $account ? str_repeat('*', max(strlen($account) - 4, 0)).substr($account, -4) : null;

        return $data;
    }
}
