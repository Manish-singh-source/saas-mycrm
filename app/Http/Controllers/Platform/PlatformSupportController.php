<?php

namespace App\Http\Controllers\Platform;

use App\Services\Platform\PlatformOperationsService;
use App\Services\Shared\SharedPrimitiveService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PlatformSupportController extends BasePlatformController
{
    public function __construct(private readonly PlatformOperationsService $ops, private readonly SharedPrimitiveService $shared) {}

    public function tickets(Request $request)
    {
        $q = $this->ticketQuery()->whereNull('platform_tickets.deleted_at');
        foreach (['status', 'priority', 'category'] as $f) if ($request->filled($f)) $q->where("platform_tickets.$f", $request->input($f));
        if ($request->filled('tenant_uuid')) $q->where('platform_tickets.tenant_id', $this->ops->tenantId($request->input('tenant_uuid')));
        if ($request->filled('assigned_to_uuid')) $q->where('platform_tickets.assigned_to', $this->ops->platformUserId($request->input('assigned_to_uuid')));
        $p = $q->latest('platform_tickets.id')->paginate((int) $request->integer('per_page', 25));
        return $this->list($p->items(), $p);
    }

    public function storeTicket(Request $request)
    {
        $d = $request->validate(['tenant_uuid' => ['nullable', 'uuid'], 'subject' => ['required', 'string'], 'description' => ['nullable', 'string'], 'priority' => ['nullable', 'string'], 'category' => ['nullable', 'string'], 'source' => ['nullable', 'string'], 'assigned_to_uuid' => ['nullable', 'uuid']]);
        $id = DB::table('platform_tickets')->insertGetId(['uuid' => (string) Str::uuid(), 'ticket_number' => 'TCK-' . Str::upper(Str::random(8)), 'tenant_id' => $this->ops->tenantId($d['tenant_uuid'] ?? null), 'subject' => $d['subject'], 'description' => $d['description'] ?? null, 'priority' => $d['priority'] ?? 'medium', 'category' => $d['category'] ?? null, 'source' => $d['source'] ?? 'platform', 'status' => 'open', 'assigned_to' => $this->ops->platformUserId($d['assigned_to_uuid'] ?? null), 'opened_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
        $ticket = $this->ticketRecord($id);
        $this->ops->audit($request, 'support_ticket_created', 'platform_tickets', $id, null, (array) $ticket);
        return $this->success(['ticket' => $ticket], 'Ticket created.', 201);
    }

    public function showTicket(string $ticket_uuid)
    {
        $rawTicket = $this->ops->byUuid('platform_tickets', $ticket_uuid);
        $ticket = $this->ticketRecord($rawTicket->id);
        return $this->success(['ticket' => $ticket, 'comments' => $this->ticketComments($ticket->id), 'attachments' => $this->ticketAttachments($ticket->id), 'audit' => DB::table('activity_logs')->where('subject_type', 'platform_tickets')->where('subject_id', $ticket->id)->latest('id')->get()]);
    }

    public function updateTicket(Request $request, string $ticket_uuid)
    {
        $ticket = $this->ops->byUuid('platform_tickets', $ticket_uuid);
        $d = $request->validate(['tenant_uuid' => ['nullable', 'uuid'], 'subject' => ['sometimes', 'string'], 'description' => ['nullable', 'string'], 'priority' => ['nullable', 'string'], 'category' => ['nullable', 'string'], 'source' => ['nullable', 'string'], 'assigned_to_uuid' => ['nullable', 'uuid'], 'status' => ['nullable', 'string']]);
        $updates = collect($d)->except(['tenant_uuid', 'assigned_to_uuid'])->all();
        if (array_key_exists('tenant_uuid', $d)) $updates['tenant_id'] = $this->ops->tenantId($d['tenant_uuid'] ?? null);
        if (array_key_exists('assigned_to_uuid', $d)) $updates['assigned_to'] = $this->ops->platformUserId($d['assigned_to_uuid'] ?? null);
        DB::table('platform_tickets')->where('id', $ticket->id)->update([...$updates, 'updated_at' => now()]);
        $fresh = $this->ticketRecord($ticket->id);
        $this->ops->audit($request, 'support_ticket_updated', 'platform_tickets', $ticket->id, (array) $ticket, (array) $fresh);
        return $this->success(['ticket' => $fresh], 'Ticket updated.');
    }

    public function assignTicket(Request $request, string $ticket_uuid)
    {
        $ticket = $this->ops->byUuid('platform_tickets', $ticket_uuid);
        $d = $request->validate(['assigned_to_uuid' => ['nullable', 'uuid'], 'audit_reason' => ['nullable', 'string']]);
        DB::table('platform_tickets')->where('id', $ticket->id)->update(['assigned_to' => $this->ops->platformUserId($d['assigned_to_uuid'] ?? null), 'updated_at' => now()]);
        $this->ops->audit($request, 'support_ticket_assigned', 'platform_tickets', $ticket->id, (array) $ticket, ['assigned_to_uuid' => $d['assigned_to_uuid'] ?? null], $d['audit_reason'] ?? null);
        return $this->success(['ticket' => $this->ticketRecord($ticket->id)], 'Ticket assigned.');
    }

    public function comment(Request $request, string $ticket_uuid)
    {
        $ticket = $this->ops->byUuid('platform_tickets', $ticket_uuid);
        $d = $request->validate(['comment' => ['required', 'string'], 'is_internal' => ['nullable', 'boolean']]);
        $id = DB::table('platform_ticket_comments')->insertGetId(['platform_ticket_id' => $ticket->id, 'platform_user_id' => $request->user()?->id, 'comment' => $d['comment'], 'is_internal' => (bool) ($d['is_internal'] ?? false), 'created_at' => now(), 'updated_at' => now()]);
        $comment = $this->ticketComment($id);
        $this->ops->audit($request, $comment->is_internal ? 'support_ticket_internal_note_added' : 'support_ticket_comment_added', 'platform_tickets', $ticket->id, null, (array) $comment);
        return $this->success(['comment' => $comment], 'Comment added.', 201);
    }

    public function attach(Request $request, string $ticket_uuid)
    {
        $ticket = $this->ops->byUuid('platform_tickets', $ticket_uuid);
        $d = $request->validate(['file_uuid' => ['nullable', 'required_without:file', 'uuid'], 'file' => ['nullable', 'required_without:file_uuid', 'file', 'max:51200'], 'disk' => ['nullable', 'string', 'max:80'], 'visibility' => ['nullable', Rule::in(['private', 'public', 'tenant'])], 'purpose' => ['nullable', 'string', 'max:80']]);
        $file = $request->hasFile('file')
            ? $this->shared->storeUploadedFile($request, $d)
            : $this->shared->findFile($request, $d['file_uuid']);
        DB::table('platform_ticket_attachments')->updateOrInsert(['platform_ticket_id' => $ticket->id, 'file_id' => $file->id], ['created_by' => $request->user()?->id, 'created_at' => now(), 'updated_at' => now()]);
        $this->ops->audit($request, 'support_ticket_attachment_added', 'platform_tickets', $ticket->id, null, ['file_uuid' => $file->uuid]);
        return $this->success(['attachments' => $this->ticketAttachments($ticket->id)], 'Attachment added.');
    }

    public function close(Request $request, string $uuid)
    {
        return $this->ticketStatus($request, $uuid, 'closed', 'Ticket closed.');
    }
    public function reopen(Request $request, string $uuid)
    {
        return $this->ticketStatus($request, $uuid, 'open', 'Ticket reopened.');
    }
    public function exportTickets()
    {
        return $this->success(['export' => ['status' => 'queued', 'format' => 'csv']], 'Ticket export queued.');
    }

    public function kbCategories(Request $request)
    {
        $p = DB::table('knowledge_base_categories')->latest('id')->paginate((int) $request->integer('per_page', 25));
        return $this->list($p->items(), $p);
    }
    public function storeKbCategory(Request $request)
    {
        $d = $request->validate(['parent_uuid' => ['nullable', 'uuid'], 'name' => ['required', 'string'], 'slug' => ['nullable', 'string'], 'audience' => ['nullable', 'string'], 'status' => ['nullable', 'string']]);
        $parent = isset($d['parent_uuid']) ? $this->ops->byUuid('knowledge_base_categories', $d['parent_uuid']) : null;
        $id = DB::table('knowledge_base_categories')->insertGetId(['uuid' => (string) Str::uuid(), 'parent_id' => $parent->id ?? null, 'name' => $d['name'], 'slug' => $d['slug'] ?? Str::slug($d['name']), 'audience' => $d['audience'] ?? 'all', 'status' => $d['status'] ?? 'active', 'created_at' => now(), 'updated_at' => now()]);
        return $this->success(['category' => DB::table('knowledge_base_categories')->where('id', $id)->first()], 'Category created.', 201);
    }
    public function updateKbCategory(Request $request, string $category_uuid)
    {
        $cat = $this->ops->byUuid('knowledge_base_categories', $category_uuid);
        $d = $request->validate(['name' => ['sometimes', 'string'], 'slug' => ['nullable', 'string'], 'audience' => ['nullable', 'string'], 'status' => ['nullable', 'string']]);
        DB::table('knowledge_base_categories')->where('id', $cat->id)->update([...$d, 'updated_at' => now()]);
        return $this->success(['category' => DB::table('knowledge_base_categories')->where('id', $cat->id)->first()], 'Category updated.');
    }

    public function articles(Request $request)
    {
        $q = DB::table('knowledge_base_articles')->whereNull('deleted_at');
        if ($request->filled('status')) $q->where('status', $request->input('status'));
        $p = $q->latest('id')->paginate((int) $request->integer('per_page', 25));
        return $this->list($p->items(), $p);
    }
    public function storeArticle(Request $request)
    {
        $d = $this->articleData($request);
        $cat = ! empty($d['category_uuid']) ? $this->ops->byUuid('knowledge_base_categories', $d['category_uuid']) : null;
        $id = DB::table('knowledge_base_articles')->insertGetId(['uuid' => (string) Str::uuid(), 'category_id' => $cat->id ?? null, 'title' => $d['title'], 'slug' => $d['slug'] ?? Str::slug($d['title']), 'body' => $d['body'], 'audience' => $d['audience'] ?? 'all', 'status' => $d['status'] ?? 'draft', 'created_by' => $request->user()?->id, 'created_at' => now(), 'updated_at' => now()]);
        return $this->success(['article' => DB::table('knowledge_base_articles')->where('id', $id)->first()], 'Article created.', 201);
    }
    public function showArticle(string $article_uuid)
    {
        return $this->success(['article' => $this->ops->byUuid('knowledge_base_articles', $article_uuid)]);
    }
    public function updateArticle(Request $request, string $article_uuid)
    {
        $a = $this->ops->byUuid('knowledge_base_articles', $article_uuid);
        $d = $this->articleData($request, true);
        DB::table('knowledge_base_articles')->where('id', $a->id)->update([...collect($d)->except('category_uuid')->all(), 'updated_at' => now()]);
        return $this->success(['article' => DB::table('knowledge_base_articles')->where('id', $a->id)->first()], 'Article updated.');
    }
    public function publishArticle(Request $request, string $uuid)
    {
        return $this->articleStatus($request, $uuid, 'published', ['published_at' => now()]);
    }
    public function unpublishArticle(Request $request, string $uuid)
    {
        return $this->articleStatus($request, $uuid, 'draft', ['published_at' => null]);
    }
    public function archiveArticle(Request $request, string $uuid)
    {
        return $this->articleStatus($request, $uuid, 'archived', ['deleted_at' => now()]);
    }

    public function remoteSessions(Request $request)
    {
        $p = DB::table('remote_login_sessions')->latest('id')->paginate((int) $request->integer('per_page', 25));
        return $this->list($p->items(), $p);
    }
    public function remoteSession(string $session_uuid)
    {
        return $this->success(['session' => $this->ops->byUuid('remote_login_sessions', $session_uuid)]);
    }
    public function endRemoteSession(Request $request, string $session_uuid)
    {
        $s = $this->ops->byUuid('remote_login_sessions', $session_uuid);
        DB::table('remote_login_sessions')->where('id', $s->id)->update(['status' => 'ended', 'ended_at' => now(), 'updated_at' => now()]);
        $this->ops->audit($request, 'remote_login_session_ended', 'remote_login_sessions', $s->id, (array) $s, ['status' => 'ended']);
        return $this->success(['session' => DB::table('remote_login_sessions')->where('id', $s->id)->first()], 'Remote login session ended.');
    }

    private function ticketStatus(Request $request, string $uuid, string $status, string $message)
    {
        $ticket = $this->ops->byUuid('platform_tickets', $uuid);
        $extra = $status === 'closed' ? ['closed_at' => now()] : ['closed_at' => null];
        DB::table('platform_tickets')->where('id', $ticket->id)->update([...$extra, 'status' => $status, 'updated_at' => now()]);
        $this->ops->audit($request, 'support_ticket_' . $status, 'platform_tickets', $ticket->id, (array) $ticket, ['status' => $status], $request->input('notes'));
        return $this->success(['ticket' => $this->ticketRecord($ticket->id)], $message);
    }
    private function ticketQuery()
    {
        return DB::table('platform_tickets')
            ->leftJoin('tenants', 'tenants.id', '=', 'platform_tickets.tenant_id')
            ->leftJoin('platform_users as assignees', 'assignees.id', '=', 'platform_tickets.assigned_to')
            ->select('platform_tickets.*', 'tenants.uuid as tenant_uuid', 'tenants.display_name as tenant_name', 'assignees.uuid as assigned_to_uuid', 'assignees.display_name as assigned_to_name', 'assignees.email as assigned_to_email');
    }
    private function ticketRecord(int $id)
    {
        return $this->ticketQuery()->where('platform_tickets.id', $id)->first();
    }
    private function ticketComments(int $ticketId)
    {
        return DB::table('platform_ticket_comments')
            ->leftJoin('platform_users', 'platform_users.id', '=', 'platform_ticket_comments.platform_user_id')
            ->leftJoin('users', 'users.id', '=', 'platform_ticket_comments.user_id')
            ->where('platform_ticket_comments.platform_ticket_id', $ticketId)
            ->latest('platform_ticket_comments.id')
            ->get(['platform_ticket_comments.*', 'platform_users.uuid as platform_user_uuid', 'platform_users.display_name as platform_user_name', 'users.uuid as user_uuid', 'users.display_name as user_name']);
    }
    private function ticketComment(int $id)
    {
        return DB::table('platform_ticket_comments')
            ->leftJoin('platform_users', 'platform_users.id', '=', 'platform_ticket_comments.platform_user_id')
            ->leftJoin('users', 'users.id', '=', 'platform_ticket_comments.user_id')
            ->where('platform_ticket_comments.id', $id)
            ->first(['platform_ticket_comments.*', 'platform_users.uuid as platform_user_uuid', 'platform_users.display_name as platform_user_name', 'users.uuid as user_uuid', 'users.display_name as user_name']);
    }
    private function ticketAttachments(int $ticketId)
    {
        return DB::table('platform_ticket_attachments')
            ->join('files', 'files.id', '=', 'platform_ticket_attachments.file_id')
            ->leftJoin('platform_users', 'platform_users.id', '=', 'platform_ticket_attachments.created_by')
            ->where('platform_ticket_attachments.platform_ticket_id', $ticketId)
            ->whereNull('files.deleted_at')
            ->latest('platform_ticket_attachments.id')
            ->get(['platform_ticket_attachments.id', 'platform_ticket_attachments.created_at', 'files.uuid as file_uuid', 'files.original_name', 'files.mime_type', 'files.size_bytes', 'files.visibility', 'platform_users.uuid as created_by_uuid', 'platform_users.display_name as created_by_name'])
            ->map(function ($attachment) {
                $attachment->preview_url = $this->shared->signedDownloadUrl($attachment->file_uuid);
                $attachment->preview_expires_at = now()->addMinutes(10)->toISOString();
                return $attachment;
            });
    }
    private function articleStatus(Request $request, string $uuid, string $status, array $extra)
    {
        $a = $this->ops->byUuid('knowledge_base_articles', $uuid);
        DB::table('knowledge_base_articles')->where('id', $a->id)->update([...$extra, 'status' => $status, 'updated_at' => now()]);
        $this->ops->audit($request, 'knowledge_base_article_' . $status, 'knowledge_base_articles', $a->id, (array) $a, ['status' => $status]);
        return $this->success(['article' => DB::table('knowledge_base_articles')->where('id', $a->id)->first()], 'Article status updated.');
    }
    private function articleData(Request $request, bool $partial = false): array
    {
        return $request->validate(['category_uuid' => ['nullable', 'uuid'], 'title' => [$partial ? 'sometimes' : 'required', 'string'], 'slug' => ['nullable', 'string'], 'body' => [$partial ? 'sometimes' : 'required', 'string'], 'audience' => ['nullable', 'string'], 'status' => ['nullable', Rule::in(['draft', 'published', 'archived'])]]);
    }
}
