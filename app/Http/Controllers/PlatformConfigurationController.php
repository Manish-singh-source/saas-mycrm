<?php

namespace App\Http\Controllers;

use App\Http\Requests\RunBackupRequest;
use App\Http\Requests\StoreNotificationTemplateRequest;
use App\Http\Requests\UpdateBackupSettingsRequest;
use App\Http\Requests\UpdateNotificationTemplateRequest;
use App\Http\Requests\UpdatePlatformSettingsRequest;
use App\Models\BackupRun;
use App\Jobs\ProcessPlatformBackup;
use App\Models\BackupSetting;
use App\Models\File;
use App\Models\NotificationTemplate;
use App\Models\PlatformSetting;
use App\Support\ActivityLogger;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class PlatformConfigurationController extends Controller
{
    public function platform(Request $request): mixed
    {
        $query = PlatformSetting::with('updatedBy')->orderBy('group')->orderBy('key');
        if ($request->filled('group')) $query->where('group', (string) $request->string('group'));
        $settings = $query->get()->map(fn (PlatformSetting $setting) => $this->presentSetting($setting));
        return ApiResponse::success($settings, 'Platform settings fetched.');
    }

    public function updatePlatform(UpdatePlatformSettingsRequest $request): mixed
    {
        $input = $request->validated()['settings'];
        $old = PlatformSetting::whereIn('group', collect($input)->pluck('group'))->whereIn('key', collect($input)->pluck('key'))->get()->mapWithKeys(fn ($s) => [$s->group.'.'.$s->key => $s->value])->all();
        DB::transaction(function () use ($input, $request): void {
            foreach ($input as $item) {
                PlatformSetting::updateOrCreate(
                    ['group' => $item['group'], 'key' => $item['key']],
                    ['value' => $item['value'] ?? null, 'value_type' => $item['value_type'] ?? 'json', 'is_encrypted' => (bool) ($item['is_encrypted'] ?? false), 'updated_by' => $request->user()->id]
                );
            }
        });
        $subject = PlatformSetting::where('group', $input[0]['group'])->where('key', $input[0]['key'])->first();
        if ($subject) ActivityLogger::record($request, 'platform_settings.updated', $subject, 'Platform settings updated.', ['old' => $old, 'new' => $input]);
        return $this->platform($request);
    }

    public function notificationTemplates(Request $request): mixed
    {
        $query = NotificationTemplate::whereNull('tenant_id')->latest('created_at');
        if ($request->filled('channel')) $query->where('channel', (string) $request->string('channel'));
        if ($request->filled('status')) $query->where('status', (string) $request->string('status'));
        $page = $query->paginate(min(max($request->integer('per_page', 25), 1), 100))->withQueryString();
        return ApiResponse::success($page->items(), 'Notification templates fetched.', 200, ['current_page' => $page->currentPage(), 'per_page' => $page->perPage(), 'total' => $page->total(), 'last_page' => $page->lastPage()]);
    }

    public function storeNotificationTemplate(StoreNotificationTemplateRequest $request): mixed
    {
        $data = $request->validated();
        $template = NotificationTemplate::create($data + ['uuid' => (string) Str::uuid(), 'tenant_id' => null, 'status' => $data['status'] ?? 'active']);
        ActivityLogger::record($request, 'notification_template.created', $template, 'Global notification template created.', $data);
        return ApiResponse::success(['template' => $template->fresh()], 'Notification template created successfully.', 201);
    }

    public function updateNotificationTemplate(UpdateNotificationTemplateRequest $request, string $uuid): mixed
    {
        $template = NotificationTemplate::whereNull('tenant_id')->where('uuid', $uuid)->first();
        if (! $template) return ApiResponse::error('Notification template not found.', 404, null, 'NOTIFICATION_TEMPLATE_NOT_FOUND');
        $old = $template->toArray(); $data = $request->validated(); $template->update($data);
        ActivityLogger::record($request, 'notification_template.updated', $template, 'Global notification template updated.', ['old' => $old, 'new' => $data]);
        return ApiResponse::success(['template' => $template->fresh()], 'Notification template updated successfully.');
    }

    public function backups(Request $request): mixed
    {
        $settings = BackupSetting::with('updatedBy')->orderBy('key')->get()->map(fn (BackupSetting $setting) => $this->presentBackupSetting($setting));
        $latest = BackupRun::with('file')->latest('started_at')->first();
        return ApiResponse::success(['settings' => $settings, 'latest_run' => $this->presentBackup($latest)], 'Backup settings fetched.');
    }

    public function updateBackups(UpdateBackupSettingsRequest $request): mixed
    {
        $items = $request->validated()['settings'];
        $old = BackupSetting::whereIn('key', collect($items)->pluck('key'))->get()->mapWithKeys(fn ($setting) => [$setting->key => $this->maskedValue($setting->key, $setting->value)])->all();
        foreach ($items as $item) BackupSetting::updateOrCreate(['key' => $item['key']], ['value' => $item['value'] ?? null, 'updated_by' => $request->user()->id]);
        $setting = BackupSetting::where('key', $items[0]['key'])->first();
        if ($setting) ActivityLogger::record($request, 'backup_settings.updated', $setting, 'Backup settings updated.', ['old' => $old, 'new' => collect($items)->mapWithKeys(fn ($item) => [$item['key'] => $this->maskedValue($item['key'], $item['value'] ?? null)])->all()]);
        return $this->backups($request);
    }

    public function runBackup(RunBackupRequest $request): mixed
    {
        $run = BackupRun::create(['uuid' => (string) Str::uuid(), 'backup_type' => $request->validated('backup_type', 'full'), 'status' => 'queued']);
        ProcessPlatformBackup::dispatch($run->id);
        ActivityLogger::record($request, 'backup_run.queued', $run, 'Manual backup run queued.', $request->validated());
        return ApiResponse::success(['run' => $this->presentBackup($run)], 'Backup run queued successfully.', 202);
    }

    public function backupRuns(Request $request): mixed
    {
        $query = BackupRun::with('file')->latest('started_at');
        if ($request->filled('status')) $query->where('status', (string) $request->string('status'));
        $page = $query->paginate(min(max($request->integer('per_page', 25), 1), 100))->withQueryString();
        return ApiResponse::success(collect($page->items())->map(fn ($run) => $this->presentBackup($run))->all(), 'Backup runs fetched.', 200, ['current_page' => $page->currentPage(), 'per_page' => $page->perPage(), 'total' => $page->total(), 'last_page' => $page->lastPage()]);
    }

    public function backupRun(string $uuid): mixed
    {
        $run = BackupRun::with('file')->where('uuid', $uuid)->first();
        if (! $run) return ApiResponse::error('Backup run not found.', 404, null, 'BACKUP_RUN_NOT_FOUND');
        return ApiResponse::success(['run' => $this->presentBackup($run)], 'Backup run fetched.');
    }

    public function downloadBackup(string $uuid): mixed
    {
        $run = BackupRun::with('file')->where('uuid', $uuid)->first();
        if (! $run) return ApiResponse::error('Backup run not found.', 404, null, 'BACKUP_RUN_NOT_FOUND');
        if ($run->status !== 'completed' || ! $run->file) return ApiResponse::error('Backup is not available for download.', 409, ['run' => $this->presentBackup($run)], 'BACKUP_NOT_AVAILABLE');
        return ApiResponse::success(['run' => $this->presentBackup($run), 'file' => ['uuid' => $run->file->uuid, 'name' => $run->file->original_name, 'mime_type' => $run->file->mime_type, 'size_bytes' => $run->file->size_bytes], 'download_url' => route('platform.backups.download', ['run_uuid' => $run->uuid])], 'Backup download reference generated.');
    }

    private function presentSetting(PlatformSetting $setting): array
    {
        return ['id' => $setting->id, 'group' => $setting->group, 'key' => $setting->key, 'value' => $setting->is_encrypted || $this->isSensitiveKey($setting->key) ? '[REDACTED]' : $setting->value, 'value_type' => $setting->value_type, 'is_encrypted' => (bool) $setting->is_encrypted, 'updated_by' => $setting->updatedBy];
    }

    private function presentBackup(?BackupRun $run): ?array
    {
        if (! $run) return null;
        return ['id' => $run->id, 'uuid' => $run->uuid, 'backup_type' => $run->backup_type, 'status' => $run->status, 'started_at' => $run->started_at, 'finished_at' => $run->finished_at, 'error_message' => $run->error_message, 'file' => $run->file ? ['uuid' => $run->file->uuid, 'name' => $run->file->original_name, 'mime_type' => $run->file->mime_type, 'size_bytes' => $run->file->size_bytes, 'checksum' => $run->file->checksum] : null];
    }
    private function presentBackupSetting(BackupSetting $setting): array
    {
        return ['id' => $setting->id, 'key' => $setting->key, 'value' => $this->maskedValue($setting->key, $setting->value), 'updated_by' => $setting->updatedBy];
    }

    private function maskedValue(string $key, mixed $value): mixed { return $this->isSensitiveKey($key) ? '[REDACTED]' : $value; }

    private function isSensitiveKey(string $key): bool { return (bool) preg_match('/(secret|password|token|credential|private[_-]?key|api[_-]?key)/i', $key); }
}
