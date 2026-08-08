<?php

namespace App\Services\Shared;

use App\Models\PlatformUser;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SharedPrimitiveService
{
    private const FIELD_TYPES = ['text', 'textarea', 'number', 'date', 'datetime', 'boolean', 'select', 'multiselect', 'email', 'url', 'phone', 'json'];

    public function scope(Request $request): array
    {
        $tenant = $request->attributes->get('tenant');
        $user = $request->user();

        return [
            'surface' => $tenant instanceof Tenant ? 'tenant' : 'platform',
            'tenant_id' => $tenant instanceof Tenant ? $tenant->id : null,
            'tenant_uuid' => $tenant instanceof Tenant ? $tenant->uuid : null,
            'user_id' => $user instanceof User ? $user->id : null,
            'platform_user_id' => $user instanceof PlatformUser ? $user->id : null,
        ];
    }

    public function filePayload(object $file): array
    {
        return [
            'uuid' => $file->uuid,
            'tenant_id' => $file->tenant_id,
            'disk' => $file->disk,
            'original_name' => $file->original_name,
            'mime_type' => $file->mime_type,
            'extension' => $file->extension,
            'size_bytes' => (int) $file->size_bytes,
            'checksum' => $file->checksum,
            'visibility' => $file->visibility,
            'uploaded_by' => $file->uploaded_by,
            'platform_uploaded_by' => $file->platform_uploaded_by,
            'created_at' => $file->created_at,
        ];
    }

    public function fileQuery(Request $request)
    {
        $scope = $this->scope($request);
        $query = DB::table('files')->whereNull('deleted_at');

        return $scope['surface'] === 'tenant'
            ? $query->where('tenant_id', $scope['tenant_id'])
            : $query->whereNull('tenant_id');
    }

    public function findFile(Request $request, string $uuid): object
    {
        $file = $this->fileQuery($request)->where('uuid', $uuid)->first();
        abort_if(! $file, 404);

        return $file;
    }

    public function signedDownloadUrl(string $uuid): string
    {
        return URL::temporarySignedRoute('api.files.signed-download', now()->addMinutes(10), ['file_uuid' => $uuid]);
    }

    public function resolveEntity(Request $request, string $type, string $uuid): array
    {
        $scope = $this->scope($request);
        $type = Str::snake($type);

        if ($scope['surface'] === 'tenant' && $type === 'tenant' && $uuid !== $scope['tenant_uuid']) {
            abort(404);
        }

        $map = [
            'tenant' => ['table' => 'tenants', 'subject' => Tenant::class],
            'platform_user' => ['table' => 'platform_users', 'subject' => PlatformUser::class],
            'user' => ['table' => 'users', 'subject' => User::class],
            'file' => ['table' => 'files', 'subject' => 'file'],
        ];

        $table = $map[$type]['table'] ?? Str::plural($type);
        abort_if(! Schema::hasTable($table) || ! Schema::hasColumn($table, 'uuid'), 422, 'Unsupported entity type.');

        $query = DB::table($table)->where('uuid', $uuid);
        if ($scope['surface'] === 'tenant') {
            if ($type === 'platform_user') {
                abort(404);
            }
            if (Schema::hasColumn($table, 'tenant_id')) {
                $query->where('tenant_id', $scope['tenant_id']);
            }
        } elseif (Schema::hasColumn($table, 'tenant_id') && $type !== 'tenant') {
            $query->whereNull('tenant_id');
        }

        $record = $query->first();
        abort_if(! $record, 404);

        return ['type' => $map[$type]['subject'] ?? $type, 'table' => $table, 'id' => $record->id, 'uuid' => $uuid, 'record' => $record];
    }

    public function audit(Request $request, string $event, string $subjectType, int $subjectId, ?array $old = null, ?array $new = null, ?string $description = null): void
    {
        $scope = $this->scope($request);

        DB::table('activity_logs')->insert([
            'tenant_id' => $scope['tenant_id'],
            'actor_user_id' => $scope['user_id'],
            'actor_platform_user_id' => $scope['platform_user_id'],
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

    public function validateCustomField(array $data): void
    {
        if (! in_array($data['field_type'], self::FIELD_TYPES, true)) {
            throw ValidationException::withMessages(['field_type' => ['Unsupported custom field type.']]);
        }

        if (in_array($data['field_type'], ['select', 'multiselect'], true) && empty($data['options'])) {
            throw ValidationException::withMessages(['options' => ['Options are required for select fields.']]);
        }
    }

    public function normalizeCustomValue(object $field, mixed $value): array
    {
        if ($field->is_required && ($value === null || $value === '')) {
            throw ValidationException::withMessages([$field->code => ['This custom field is required.']]);
        }

        if ($value === null || $value === '') {
            return ['value_text' => null, 'value_number' => null, 'value_date' => null, 'value_json' => null];
        }

        $options = is_string($field->options) ? json_decode($field->options, true) : ($field->options ?? []);

        return match ($field->field_type) {
            'number' => is_numeric($value) ? ['value_text' => null, 'value_number' => $value, 'value_date' => null, 'value_json' => null] : throw ValidationException::withMessages([$field->code => ['This custom field must be numeric.']]),
            'date', 'datetime' => ['value_text' => null, 'value_number' => null, 'value_date' => $value, 'value_json' => null],
            'boolean' => ['value_text' => null, 'value_number' => null, 'value_date' => null, 'value_json' => json_encode((bool) $value)],
            'select' => in_array($value, Arr::wrap($options), true) ? ['value_text' => (string) $value, 'value_number' => null, 'value_date' => null, 'value_json' => null] : throw ValidationException::withMessages([$field->code => ['Selected value is not allowed.']]),
            'multiselect' => empty(array_diff(Arr::wrap($value), Arr::wrap($options))) ? ['value_text' => null, 'value_number' => null, 'value_date' => null, 'value_json' => json_encode(Arr::wrap($value))] : throw ValidationException::withMessages([$field->code => ['One or more selected values are not allowed.']]),
            'json' => ['value_text' => null, 'value_number' => null, 'value_date' => null, 'value_json' => json_encode($value)],
            default => ['value_text' => (string) $value, 'value_number' => null, 'value_date' => null, 'value_json' => null],
        };
    }

    public function storeUploadedFile(Request $request, array $data): object
    {
        $scope = $this->scope($request);
        $upload = $data['file'];
        $disk = $data['disk'] ?? config('filesystems.default', 'local');
        $visibility = $data['visibility'] ?? 'private';
        $root = $scope['surface'] === 'tenant' ? 'tenants/'.$scope['tenant_id'] : 'platform';
        $path = $upload->store($root.'/'.now()->format('Y/m'), $disk);

        $id = DB::table('files')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'tenant_id' => $scope['tenant_id'],
            'disk' => $disk,
            'path' => $path,
            'original_name' => $upload->getClientOriginalName(),
            'mime_type' => $upload->getClientMimeType(),
            'extension' => $upload->getClientOriginalExtension(),
            'size_bytes' => $upload->getSize() ?: 0,
            'checksum' => hash_file('sha256', $upload->getRealPath()),
            'visibility' => $visibility,
            'uploaded_by' => $scope['user_id'],
            'platform_uploaded_by' => $scope['platform_user_id'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $file = DB::table('files')->where('id', $id)->first();
        $this->audit($request, $scope['surface'].'.file_uploaded', 'file', $id, null, $this->filePayload($file));

        return $file;
    }

    public function deletePhysicalFile(object $file): void
    {
        Storage::disk($file->disk)->delete($file->path);
    }
}
