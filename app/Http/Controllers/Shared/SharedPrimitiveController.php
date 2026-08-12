<?php

namespace App\Http\Controllers\Shared;

use App\Services\Shared\SharedPrimitiveService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SharedPrimitiveController extends BaseApiController
{
    public function __construct(private readonly SharedPrimitiveService $shared) {}

    public function files(Request $request)
    {
        $query = $this->shared->fileQuery($request);
        if ($request->filled('search')) {
            $query->where('original_name', 'like', '%'.$request->string('search').'%');
        }
        foreach (['visibility', 'mime_type'] as $field) {
            if ($request->filled('filter.'.$field)) {
                $query->where($field, $request->input('filter.'.$field));
            }
        }

        $paginator = $query->latest('id')->paginate((int) $request->integer('per_page', 25));

        return $this->list(collect($paginator->items())->map(fn ($file) => $this->shared->filePayload($file))->all(), $paginator);
    }

    public function upload(Request $request)
    {
        $data = $request->validate([
            'file' => ['required', 'file', 'max:51200'],
            'disk' => ['nullable', 'string', 'max:80'],
            'visibility' => ['nullable', Rule::in(['private', 'public', 'tenant'])],
            'purpose' => ['nullable', 'string', 'max:80'],
        ]);

        abort_if(! array_key_exists($data['disk'] ?? config('filesystems.default', 'local'), config('filesystems.disks', [])), 422, 'Invalid storage disk.');

        $file = $this->shared->storeUploadedFile($request, $data);

        return $this->success(['file' => $this->shared->filePayload($file)], 'File uploaded.', 201);
    }

    public function file(Request $request, string $file_uuid)
    {
        return $this->success(['file' => $this->shared->filePayload($this->shared->findFile($request, $file_uuid))]);
    }

    public function download(Request $request, string $file_uuid)
    {
        $file = $this->shared->findFile($request, $file_uuid);

        if ($file->visibility === 'public') {
            return $this->success(['url' => Storage::disk($file->disk)->url($file->path), 'expires_at' => null]);
        }

        return $this->success(['url' => $this->shared->signedDownloadUrl($file->uuid), 'expires_at' => now()->addMinutes(10)->toISOString()]);
    }

    public function deleteFile(Request $request, string $file_uuid)
    {
        $file = $this->shared->findFile($request, $file_uuid);
        abort_if(DB::table('attachments')->where('file_id', $file->id)->exists(), 409, 'Attached files cannot be deleted.');

        DB::table('files')->where('id', $file->id)->update(['deleted_at' => now(), 'updated_at' => now()]);
        $this->shared->deletePhysicalFile($file);
        $this->shared->audit($request, $this->shared->scope($request)['surface'].'.file_deleted', 'file', $file->id, $this->shared->filePayload($file));

        return $this->success(null, 'File deleted.');
    }

    public function attachments(Request $request)
    {
        $entity = $this->entityFromQuery($request, 'attachable');
        $scope = $this->shared->scope($request);
        $items = DB::table('attachments')
            ->join('files', 'files.id', '=', 'attachments.file_id')
            ->where('attachments.tenant_id', $scope['tenant_id'])
            ->where('attachable_type', $entity['type'])
            ->where('attachable_id', $entity['id'])
            ->whereNull('files.deleted_at')
            ->select('attachments.id', 'attachments.label', 'attachments.created_at', 'files.uuid', 'files.original_name', 'files.mime_type', 'files.size_bytes', 'files.visibility')
            ->latest('attachments.id')
            ->get();

        return $this->success(['attachments' => $items]);
    }

    public function attach(Request $request)
    {
        $data = $request->validate(['file_uuid' => ['required', 'uuid'], 'attachable_type' => ['required', 'string'], 'attachable_uuid' => ['required', 'uuid'], 'label' => ['nullable', 'string', 'max:150']]);
        $file = $this->shared->findFile($request, $data['file_uuid']);
        $entity = $this->shared->resolveEntity($request, $data['attachable_type'], $data['attachable_uuid']);
        $scope = $this->shared->scope($request);

        $id = DB::table('attachments')->insertGetId(['tenant_id' => $scope['tenant_id'], 'file_id' => $file->id, 'attachable_type' => $entity['type'], 'attachable_id' => $entity['id'], 'label' => $data['label'] ?? null, 'created_by' => $scope['user_id'], 'created_at' => now()]);
        $this->shared->audit($request, $scope['surface'].'.attachment_created', $entity['type'], $entity['id'], null, ['attachment_id' => $id, 'file_uuid' => $file->uuid]);

        return $this->success(['attachment' => DB::table('attachments')->where('id', $id)->first()], 'Attachment created.', 201);
    }

    public function detach(Request $request, int $attachment_id)
    {
        $scope = $this->shared->scope($request);
        $attachment = DB::table('attachments')->where('tenant_id', $scope['tenant_id'])->where('id', $attachment_id)->first();
        abort_if(! $attachment, 404);
        DB::table('attachments')->where('id', $attachment_id)->delete();
        $this->shared->audit($request, $scope['surface'].'.attachment_deleted', $attachment->attachable_type, $attachment->attachable_id, (array) $attachment);

        return $this->success(null, 'Attachment removed.');
    }

    public function notes(Request $request)
    {
        $entity = $this->entityFromQuery($request, 'notable');
        $scope = $this->shared->scope($request);
        $notes = DB::table('notes')->where('tenant_id', $scope['tenant_id'])->where('notable_type', $entity['type'])->where('notable_id', $entity['id'])->whereNull('deleted_at')->latest('id')->get();

        return $this->success(['notes' => $notes]);
    }

    public function createNote(Request $request)
    {
        $data = $request->validate(['notable_type' => ['required', 'string'], 'notable_uuid' => ['required', 'uuid'], 'note' => ['required_without:body', 'string'], 'body' => ['required_without:note', 'string'], 'visibility' => ['nullable', Rule::in(['private', 'team', 'tenant', 'client'])]]);
        $entity = $this->shared->resolveEntity($request, $data['notable_type'], $data['notable_uuid']);
        $scope = $this->shared->scope($request);
        $id = DB::table('notes')->insertGetId(['uuid' => (string) Str::uuid(), 'tenant_id' => $scope['tenant_id'], 'notable_type' => $entity['type'], 'notable_id' => $entity['id'], 'note' => $data['note'] ?? $data['body'], 'visibility' => $data['visibility'] ?? 'tenant', 'created_by' => $scope['user_id'], 'updated_by' => $scope['user_id'], 'platform_created_by' => $scope['platform_user_id'], 'platform_updated_by' => $scope['platform_user_id'], 'created_at' => now(), 'updated_at' => now()]);
        $note = DB::table('notes')->where('id', $id)->first();
        $this->shared->audit($request, $scope['surface'].'.note_created', $entity['type'], $entity['id'], null, (array) $note);

        return $this->success(['note' => $note], 'Note created.', 201);
    }

    public function updateNote(Request $request, string $note_uuid)
    {
        $data = $request->validate(['note' => ['sometimes', 'string'], 'body' => ['sometimes', 'string'], 'visibility' => ['sometimes', Rule::in(['private', 'team', 'tenant', 'client'])]]);
        $scope = $this->shared->scope($request);
        $note = DB::table('notes')->where('tenant_id', $scope['tenant_id'])->where('uuid', $note_uuid)->whereNull('deleted_at')->first();
        abort_if(! $note, 404);
        $update = array_filter(['note' => $data['note'] ?? $data['body'] ?? null, 'visibility' => $data['visibility'] ?? null], fn ($value) => $value !== null);
        $update['updated_by'] = $scope['user_id']; $update['platform_updated_by'] = $scope['platform_user_id']; $update['updated_at'] = now();
        DB::table('notes')->where('id', $note->id)->update($update);
        $fresh = DB::table('notes')->where('id', $note->id)->first();
        $this->shared->audit($request, $scope['surface'].'.note_updated', $note->notable_type, $note->notable_id, (array) $note, (array) $fresh);

        return $this->success(['note' => $fresh], 'Note updated.');
    }

    public function deleteNote(Request $request, string $note_uuid)
    {
        $scope = $this->shared->scope($request);
        $note = DB::table('notes')->where('tenant_id', $scope['tenant_id'])->where('uuid', $note_uuid)->whereNull('deleted_at')->first();
        abort_if(! $note, 404);
        DB::table('notes')->where('id', $note->id)->update(['deleted_at' => now(), 'updated_at' => now()]);
        $this->shared->audit($request, $scope['surface'].'.note_deleted', $note->notable_type, $note->notable_id, (array) $note);

        return $this->success(null, 'Note deleted.');
    }

    public function activityLogs(Request $request)
    {
        $scope = $this->shared->scope($request);
        $query = DB::table('activity_logs')->where('tenant_id', $scope['tenant_id']);
        if ($request->filled('subject_type')) $query->where('subject_type', $request->input('subject_type'));
        if ($request->filled('subject_id')) $query->where('subject_id', $request->integer('subject_id'));
        if ($request->filled('event')) $query->where('event', $request->input('event'));
        $paginator = $query->latest('id')->paginate((int) $request->integer('per_page', 25));

        return $this->list($paginator->items(), $paginator);
    }

    public function activityCompare(Request $request, int $activity_id)
    {
        $scope = $this->shared->scope($request);
        $log = DB::table('activity_logs')->where('tenant_id', $scope['tenant_id'])->where('id', $activity_id)->first();
        abort_if(! $log, 404);
        $old = json_decode($log->old_values ?: '[]', true) ?: [];
        $new = json_decode($log->new_values ?: '[]', true) ?: [];
        $changed = collect(array_unique([...array_keys($old), ...array_keys($new)]))->filter(fn ($key) => ($old[$key] ?? null) !== ($new[$key] ?? null))->values()->all();

        return $this->success(['activity' => $log, 'compare' => ['old_values' => $old, 'new_values' => $new, 'changed_fields' => $changed]]);
    }

    public function tags(Request $request)
    {
        $scope = $this->shared->scope($request);
        abort_if($scope['surface'] !== 'tenant', 404);
        $query = DB::table('tags')->where('tenant_id', $scope['tenant_id']);
        if ($request->filled('search')) {
            $query->where('name', 'like', '%'.$request->string('search').'%');
        }

        return $this->success(['tags' => $query->orderBy('name')->get()]);
    }

    public function lookups(Request $request)
    {
        $scope = $this->shared->scope($request);
        abort_if($scope['surface'] !== 'tenant', 404);
        $query = DB::table('tenant_lookups')
            ->where(fn ($q) => $q->whereNull('tenant_id')->orWhere('tenant_id', $scope['tenant_id']))
            ->where('status', 'active');

        if ($request->filled('group')) {
            $query->where('group', $request->input('group'));
        }
        if ($request->filled('groups')) {
            $groups = array_filter(explode(',', (string) $request->input('groups')));
            if ($groups !== []) {
                $query->whereIn('group', $groups);
            }
        }
        if ($search = $request->input('search')) {
            $query->where(fn ($q) => $q->where('name', 'like', '%'.$search.'%')->orWhere('code', 'like', '%'.$search.'%'));
        }

        return $this->success(['lookups' => $query->orderBy('group')->orderBy('sort_order')->orderBy('name')->get()]);
    }

    public function createTag(Request $request)
    {
        $scope = $this->shared->scope($request);
        abort_if($scope['surface'] !== 'tenant', 404);
        $data = $request->validate(['name' => ['required', 'string', 'max:120'], 'slug' => ['nullable', 'string', 'max:150'], 'color' => ['nullable', 'string', 'max:30'], 'icon' => ['nullable', 'string', 'max:80'], 'status' => ['nullable', Rule::in(['active', 'inactive'])]]);
        $slug = $data['slug'] ?? Str::slug($data['name']);
        abort_if(DB::table('tags')->where('tenant_id', $scope['tenant_id'])->where('slug', $slug)->exists(), 422, 'Tag slug already exists.');
        $id = DB::table('tags')->insertGetId(['uuid' => (string) Str::uuid(), 'tenant_id' => $scope['tenant_id'], 'name' => $data['name'], 'slug' => $slug, 'color' => $data['color'] ?? null, 'icon' => $data['icon'] ?? null, 'status' => $data['status'] ?? 'active', 'created_at' => now(), 'updated_at' => now()]);
        $tag = DB::table('tags')->where('id', $id)->first();
        $this->shared->audit($request, 'tenant.tag_created', 'tag', $id, null, (array) $tag);

        return $this->success(['tag' => $tag], 'Tag created.', 201);
    }

    public function updateTag(Request $request, string $tag_uuid)
    {
        $scope = $this->shared->scope($request);
        abort_if($scope['surface'] !== 'tenant', 404);
        $tag = DB::table('tags')->where('tenant_id', $scope['tenant_id'])->where('uuid', $tag_uuid)->first();
        abort_if(! $tag, 404);
        $data = $request->validate(['name' => ['sometimes', 'string', 'max:120'], 'slug' => ['nullable', 'string', 'max:150'], 'color' => ['nullable', 'string', 'max:30'], 'icon' => ['nullable', 'string', 'max:80'], 'status' => ['nullable', Rule::in(['active', 'inactive'])]]);
        if (isset($data['slug']) && DB::table('tags')->where('tenant_id', $scope['tenant_id'])->where('slug', $data['slug'])->where('id', '!=', $tag->id)->exists()) {
            abort(422, 'Tag slug already exists.');
        }
        $data['updated_at'] = now();
        DB::table('tags')->where('id', $tag->id)->update($data);
        $fresh = DB::table('tags')->where('id', $tag->id)->first();
        $this->shared->audit($request, 'tenant.tag_updated', 'tag', $tag->id, (array) $tag, (array) $fresh);

        return $this->success(['tag' => $fresh], 'Tag updated.');
    }

    public function deleteTag(Request $request, string $tag_uuid)
    {
        $scope = $this->shared->scope($request);
        abort_if($scope['surface'] !== 'tenant', 404);
        $tag = DB::table('tags')->where('tenant_id', $scope['tenant_id'])->where('uuid', $tag_uuid)->first();
        abort_if(! $tag, 404);
        abort_if(DB::table('taggables')->where('tenant_id', $scope['tenant_id'])->where('tag_id', $tag->id)->exists(), 409, 'Used tags cannot be deleted.');
        DB::table('tags')->where('id', $tag->id)->delete();
        $this->shared->audit($request, 'tenant.tag_deleted', 'tag', $tag->id, (array) $tag);

        return $this->success(null, 'Tag deleted.');
    }

    public function tagRecord(Request $request)
    {
        $scope = $this->shared->scope($request);
        abort_if($scope['surface'] !== 'tenant', 404);
        $data = $request->validate(['tag_uuid' => ['required', 'uuid'], 'taggable_type' => ['required', 'string'], 'taggable_uuid' => ['required', 'uuid']]);
        $tag = DB::table('tags')->where('tenant_id', $scope['tenant_id'])->where('uuid', $data['tag_uuid'])->where('status', 'active')->first();
        abort_if(! $tag, 404);
        $entity = $this->shared->resolveEntity($request, $data['taggable_type'], $data['taggable_uuid']);
        DB::table('taggables')->updateOrInsert(['tenant_id' => $scope['tenant_id'], 'tag_id' => $tag->id, 'taggable_type' => $entity['type'], 'taggable_id' => $entity['id']], ['created_at' => now()]);
        $this->shared->audit($request, 'tenant.record_tagged', $entity['type'], $entity['id'], null, ['tag_uuid' => $tag->uuid]);

        return $this->success(['tagged' => true]);
    }

    public function untagRecord(Request $request)
    {
        $scope = $this->shared->scope($request);
        abort_if($scope['surface'] !== 'tenant', 404);
        $data = $request->validate(['tag_uuid' => ['required', 'uuid'], 'taggable_type' => ['required', 'string'], 'taggable_uuid' => ['required', 'uuid']]);
        $tag = DB::table('tags')->where('tenant_id', $scope['tenant_id'])->where('uuid', $data['tag_uuid'])->first();
        abort_if(! $tag, 404);
        $entity = $this->shared->resolveEntity($request, $data['taggable_type'], $data['taggable_uuid']);
        DB::table('taggables')->where(['tenant_id' => $scope['tenant_id'], 'tag_id' => $tag->id, 'taggable_type' => $entity['type'], 'taggable_id' => $entity['id']])->delete();
        $this->shared->audit($request, 'tenant.record_untagged', $entity['type'], $entity['id'], ['tag_uuid' => $tag->uuid]);

        return $this->success(['tagged' => false]);
    }
    public function customFields(Request $request)
    {
        $scope = $this->shared->scope($request);
        abort_if($scope['surface'] !== 'tenant', 404);
        $query = DB::table('custom_fields')->where('tenant_id', $scope['tenant_id']);
        if ($request->filled('entity_type')) {
            $query->where('entity_type', $request->input('entity_type'));
        }

        return $this->success(['custom_fields' => $query->orderBy('sort_order')->orderBy('name')->get()]);
    }

    public function createCustomField(Request $request)
    {
        $scope = $this->shared->scope($request);
        abort_if($scope['surface'] !== 'tenant', 404);
        $data = $request->validate(['entity_type' => ['required', 'string', 'max:100'], 'name' => ['required', 'string', 'max:150'], 'code' => ['required', 'string', 'max:100'], 'field_type' => ['required', 'string', 'max:50'], 'options' => ['nullable', 'array'], 'validation_rules' => ['nullable', 'array'], 'is_required' => ['nullable', 'boolean'], 'sort_order' => ['nullable', 'integer'], 'status' => ['nullable', Rule::in(['active', 'inactive'])]]);
        $this->shared->validateCustomField($data);
        abort_if(DB::table('custom_fields')->where('tenant_id', $scope['tenant_id'])->where('entity_type', $data['entity_type'])->where('code', $data['code'])->exists(), 422, 'Custom field code already exists.');
        $id = DB::table('custom_fields')->insertGetId(['uuid' => (string) Str::uuid(), 'tenant_id' => $scope['tenant_id'], 'entity_type' => $data['entity_type'], 'name' => $data['name'], 'code' => $data['code'], 'field_type' => $data['field_type'], 'options' => isset($data['options']) ? json_encode($data['options']) : null, 'validation_rules' => isset($data['validation_rules']) ? json_encode($data['validation_rules']) : null, 'is_required' => (bool) ($data['is_required'] ?? false), 'sort_order' => $data['sort_order'] ?? 0, 'status' => $data['status'] ?? 'active', 'created_at' => now(), 'updated_at' => now()]);
        $field = DB::table('custom_fields')->where('id', $id)->first();
        $this->shared->audit($request, 'tenant.custom_field_created', 'custom_field', $id, null, (array) $field);

        return $this->success(['custom_field' => $field], 'Custom field created.', 201);
    }

    public function updateCustomField(Request $request, string $field_uuid)
    {
        $scope = $this->shared->scope($request);
        abort_if($scope['surface'] !== 'tenant', 404);
        $field = DB::table('custom_fields')->where('tenant_id', $scope['tenant_id'])->where('uuid', $field_uuid)->first();
        abort_if(! $field, 404);
        $data = $request->validate(['name' => ['sometimes', 'string', 'max:150'], 'field_type' => ['sometimes', 'string', 'max:50'], 'options' => ['nullable', 'array'], 'validation_rules' => ['nullable', 'array'], 'is_required' => ['nullable', 'boolean'], 'sort_order' => ['nullable', 'integer'], 'status' => ['nullable', Rule::in(['active', 'inactive'])]]);
        $this->shared->validateCustomField(['field_type' => $data['field_type'] ?? $field->field_type, 'options' => $data['options'] ?? json_decode($field->options ?: '[]', true)]);
        if (isset($data['options'])) $data['options'] = json_encode($data['options']);
        if (isset($data['validation_rules'])) $data['validation_rules'] = json_encode($data['validation_rules']);
        $data['updated_at'] = now();
        DB::table('custom_fields')->where('id', $field->id)->update($data);
        $fresh = DB::table('custom_fields')->where('id', $field->id)->first();
        $this->shared->audit($request, 'tenant.custom_field_updated', 'custom_field', $field->id, (array) $field, (array) $fresh);

        return $this->success(['custom_field' => $fresh], 'Custom field updated.');
    }

    public function deleteCustomField(Request $request, string $field_uuid)
    {
        $scope = $this->shared->scope($request);
        abort_if($scope['surface'] !== 'tenant', 404);
        $field = DB::table('custom_fields')->where('tenant_id', $scope['tenant_id'])->where('uuid', $field_uuid)->first();
        abort_if(! $field, 404);
        DB::table('custom_fields')->where('id', $field->id)->delete();
        $this->shared->audit($request, 'tenant.custom_field_deleted', 'custom_field', $field->id, (array) $field);

        return $this->success(null, 'Custom field deleted.');
    }

    public function customFieldValues(Request $request)
    {
        $entity = $this->entityFromQuery($request, 'entity');
        $scope = $this->shared->scope($request);
        abort_if($scope['surface'] !== 'tenant', 404);
        $values = DB::table('custom_field_values')->join('custom_fields', 'custom_fields.id', '=', 'custom_field_values.custom_field_id')->where('custom_field_values.tenant_id', $scope['tenant_id'])->where('custom_field_values.entity_type', $entity['type'])->where('custom_field_values.entity_id', $entity['id'])->select('custom_fields.uuid as field_uuid', 'custom_fields.code', 'custom_fields.name', 'custom_fields.field_type', 'custom_field_values.*')->get();

        return $this->success(['values' => $values]);
    }

    public function replaceCustomFieldValues(Request $request)
    {
        $data = $request->validate(['entity_type' => ['required', 'string'], 'entity_uuid' => ['required', 'uuid'], 'values' => ['required', 'array']]);
        $entity = $this->shared->resolveEntity($request, $data['entity_type'], $data['entity_uuid']);
        $scope = $this->shared->scope($request);
        abort_if($scope['surface'] !== 'tenant', 404);
        $old = DB::table('custom_field_values')->where('tenant_id', $scope['tenant_id'])->where('entity_type', $entity['type'])->where('entity_id', $entity['id'])->get()->toArray();
        foreach ($data['values'] as $code => $value) {
            $field = DB::table('custom_fields')->where('tenant_id', $scope['tenant_id'])->where('entity_type', $data['entity_type'])->where('code', $code)->where('status', 'active')->first();
            abort_if(! $field, 422, 'Unknown custom field: '.$code);
            DB::table('custom_field_values')->updateOrInsert(['tenant_id' => $scope['tenant_id'], 'custom_field_id' => $field->id, 'entity_type' => $entity['type'], 'entity_id' => $entity['id']], [...$this->shared->normalizeCustomValue($field, $value), 'updated_at' => now(), 'created_at' => now()]);
        }
        $new = DB::table('custom_field_values')->where('tenant_id', $scope['tenant_id'])->where('entity_type', $entity['type'])->where('entity_id', $entity['id'])->get()->toArray();
        $this->shared->audit($request, 'tenant.custom_field_values_replaced', $entity['type'], $entity['id'], json_decode(json_encode($old), true), json_decode(json_encode($new), true));

        return $this->success(['values' => $new], 'Custom field values saved.');
    }

    public function reminders(Request $request)
    {
        $entity = $this->entityFromQuery($request, 'remindable');
        $scope = $this->shared->scope($request);
        abort_if($scope['surface'] !== 'tenant', 404);
        return $this->success(['reminders' => DB::table('reminders')->where('tenant_id', $scope['tenant_id'])->where('remindable_type', $entity['type'])->where('remindable_id', $entity['id'])->orderBy('remind_at')->get()]);
    }

    public function createReminder(Request $request)
    {
        $data = $request->validate(['remindable_type' => ['required', 'string'], 'remindable_uuid' => ['required', 'uuid'], 'user_uuid' => ['nullable', 'uuid'], 'channel' => ['required', 'string', 'max:50'], 'remind_at' => ['required', 'date'], 'metadata' => ['nullable', 'array']]);
        $entity = $this->shared->resolveEntity($request, $data['remindable_type'], $data['remindable_uuid']);
        $scope = $this->shared->scope($request);
        abort_if($scope['surface'] !== 'tenant', 404);
        $userId = $scope['user_id'];
        if (! empty($data['user_uuid'])) {
            $userId = DB::table('users')->where('tenant_id', $scope['tenant_id'])->where('uuid', $data['user_uuid'])->value('id');
            abort_if(! $userId, 404);
        }
        $id = DB::table('reminders')->insertGetId(['uuid' => (string) Str::uuid(), 'tenant_id' => $scope['tenant_id'], 'remindable_type' => $entity['type'], 'remindable_id' => $entity['id'], 'user_id' => $userId, 'channel' => $data['channel'], 'remind_at' => $data['remind_at'], 'status' => 'pending', 'metadata' => isset($data['metadata']) ? json_encode($data['metadata']) : null, 'created_at' => now(), 'updated_at' => now()]);
        $reminder = DB::table('reminders')->where('id', $id)->first();
        $this->shared->audit($request, 'tenant.reminder_created', $entity['type'], $entity['id'], null, (array) $reminder);

        return $this->success(['reminder' => $reminder], 'Reminder created.', 201);
    }

    public function updateReminder(Request $request, string $reminder_uuid)
    {
        $scope = $this->shared->scope($request);
        abort_if($scope['surface'] !== 'tenant', 404);
        $reminder = DB::table('reminders')->where('tenant_id', $scope['tenant_id'])->where('uuid', $reminder_uuid)->first();
        abort_if(! $reminder, 404);
        $data = $request->validate(['channel' => ['sometimes', 'string', 'max:50'], 'remind_at' => ['sometimes', 'date'], 'status' => ['sometimes', 'string', 'max:50'], 'metadata' => ['nullable', 'array']]);
        if (isset($data['metadata'])) $data['metadata'] = json_encode($data['metadata']);
        $data['updated_at'] = now();
        DB::table('reminders')->where('id', $reminder->id)->update($data);
        $fresh = DB::table('reminders')->where('id', $reminder->id)->first();
        $this->shared->audit($request, 'tenant.reminder_updated', $reminder->remindable_type, $reminder->remindable_id, (array) $reminder, (array) $fresh);

        return $this->success(['reminder' => $fresh], 'Reminder updated.');
    }

    public function deleteReminder(Request $request, string $reminder_uuid)
    {
        $scope = $this->shared->scope($request);
        abort_if($scope['surface'] !== 'tenant', 404);
        $reminder = DB::table('reminders')->where('tenant_id', $scope['tenant_id'])->where('uuid', $reminder_uuid)->first();
        abort_if(! $reminder, 404);
        DB::table('reminders')->where('id', $reminder->id)->delete();
        $this->shared->audit($request, 'tenant.reminder_deleted', $reminder->remindable_type, $reminder->remindable_id, (array) $reminder);

        return $this->success(null, 'Reminder deleted.');
    }
    private function entityFromQuery(Request $request, string $prefix): array
    {
        $data = $request->validate([$prefix.'_type' => ['required', 'string'], $prefix.'_uuid' => ['required', 'uuid']]);
        return $this->shared->resolveEntity($request, $data[$prefix.'_type'], $data[$prefix.'_uuid']);
    }
}
