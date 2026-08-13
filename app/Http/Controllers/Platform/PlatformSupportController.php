<?php

namespace App\Http\Controllers\Platform;

use App\Services\Platform\PlatformOperationsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PlatformSupportController extends BasePlatformController
{
    public function __construct(private readonly PlatformOperationsService $ops) {}

    public function tickets(Request $request)
    {
        $q = DB::table('platform_tickets')->whereNull('deleted_at');
        foreach (['status', 'priority', 'category'] as $f) if ($request->filled($f)) $q->where($f, $request->input($f));
        if ($request->filled('tenant_uuid')) $q->where('tenant_id', $this->ops->tenantId($request->input('tenant_uuid')));
        if ($request->filled('assigned_to_uuid')) $q->where('assigned_to', $this->ops->platformUserId($request->input('assigned_to_uuid')));
        $p = $q->latest('id')->paginate((int) $request->integer('per_page', 25));
        return $this->list($p->items(), $p);
    }

    public function storeTicket(Request $request)
    {
        $d = $request->validate(['tenant_uuid' => ['nullable', 'uuid'], 'subject' => ['required', 'string'], 'description' => ['nullable', 'string'], 'priority' => ['nullable', 'string'], 'category' => ['nullable', 'string'], 'source' => ['nullable', 'string'], 'assigned_to_uuid' => ['nullable', 'uuid']]);
        $id = DB::table('platform_tickets')->insertGetId(['uuid' => (string) Str::uuid(), 'ticket_number' => 'TCK-' . Str::upper(Str::random(8)), 'tenant_id' => $this->ops->tenantId($d['tenant_uuid'] ?? null), 'subject' => $d['subject'], 'description' => $d['description'] ?? null, 'priority' => $d['priority'] ?? 'medium', 'category' => $d['category'] ?? null, 'source' => $d['source'] ?? 'platform', 'status' => 'open', 'assigned_to' => $this->ops->platformUserId($d['assigned_to_uuid'] ?? null), 'opened_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
        $ticket = DB::table('platform_tickets')->where('id', $id)->first();
        $this->ops->audit($request, 'support_ticket_created', 'platform_tickets', $id, null, (array) $ticket);
        return $this->success(['ticket' => $ticket], 'Ticket created.', 201);
    }

    public function showTicket(string $ticket_uuid)
    {
        $ticket = $this->ops->byUuid('platform_tickets', $ticket_uuid);
        return $this->success(['ticket' => $ticket, 'comments' => DB::table('platform_ticket_comments')->where('platform_ticket_id', $ticket->id)->latest('id')->get(), 'attachments' => DB::table('platform_ticket_attachments')->join('files', 'files.id', '=', 'platform_ticket_attachments.file_id')->where('platform_ticket_id', $ticket->id)->get(['files.uuid', 'files.original_name', 'files.mime_type', 'files.size_bytes', 'platform_ticket_attachments.created_at']), 'audit' => DB::table('activity_logs')->where('subject_type', 'platform_tickets')->where('subject_id', $ticket->id)->latest('id')->get()]);
    }

    public function updateTicket(Request $request, string $ticket_uuid)
    {
        $ticket = $this->ops->byUuid('platform_tickets', $ticket_uuid);
        $d = $request->validate(['subject' => ['sometimes', 'string'], 'description' => ['nullable', 'string'], 'priority' => ['nullable', 'string'], 'category' => ['nullable', 'string'], 'status' => ['nullable', 'string']]);
        DB::table('platform_tickets')->where('id', $ticket->id)->update([...$d, 'updated_at' => now()]);
        $fresh = DB::table('platform_tickets')->where('id', $ticket->id)->first();
        $this->ops->audit($request, 'support_ticket_updated', 'platform_tickets', $ticket->id, (array) $ticket, (array) $fresh);
        return $this->success(['ticket' => $fresh], 'Ticket updated.');
    }

    public function assignTicket(Request $request, string $ticket_uuid)
    {
        $ticket = $this->ops->byUuid('platform_tickets', $ticket_uuid);
        $d = $request->validate(['assigned_to_uuid' => ['nullable', 'uuid'], 'audit_reason' => ['nullable', 'string']]);
        DB::table('platform_tickets')->where('id', $ticket->id)->update(['assigned_to' => $this->ops->platformUserId($d['assigned_to_uuid'] ?? null), 'updated_at' => now()]);
        $this->ops->audit($request, 'support_ticket_assigned', 'platform_tickets', $ticket->id, (array) $ticket, ['assigned_to_uuid' => $d['assigned_to_uuid'] ?? null], $d['audit_reason'] ?? null);
        return $this->success(['ticket' => DB::table('platform_tickets')->where('id', $ticket->id)->first()], 'Ticket assigned.');
    }

    public function comment(Request $request, string $ticket_uuid)
    {
        $ticket = $this->ops->byUuid('platform_tickets', $ticket_uuid);
        $d = $request->validate(['comment' => ['required', 'string'], 'is_internal' => ['nullable', 'boolean']]);
        $id = DB::table('platform_ticket_comments')->insertGetId(['platform_ticket_id' => $ticket->id, 'platform_user_id' => $request->user()?->id, 'comment' => $d['comment'], 'is_internal' => (bool) ($d['is_internal'] ?? false), 'created_at' => now(), 'updated_at' => now()]);
        $comment = DB::table('platform_ticket_comments')->where('id', $id)->first();
        $this->ops->audit($request, $comment->is_internal ? 'support_ticket_internal_note_added' : 'support_ticket_comment_added', 'platform_tickets', $ticket->id, null, (array) $comment);
        return $this->success(['comment' => $comment], 'Comment added.', 201);
    }

    public function attach(Request $request, string $ticket_uuid)
    {
        $ticket = $this->ops->byUuid('platform_tickets', $ticket_uuid);
        $d = $request->validate(['file_uuid' => ['required', 'uuid']]);
        $file = $this->ops->byUuid('files', $d['file_uuid']);
        DB::table('platform_ticket_attachments')->updateOrInsert(['platform_ticket_id' => $ticket->id, 'file_id' => $file->id], ['created_by' => $request->user()?->id, 'created_at' => now(), 'updated_at' => now()]);
        $this->ops->audit($request, 'support_ticket_attachment_added', 'platform_tickets', $ticket->id, null, ['file_uuid' => $d['file_uuid']]);
        return $this->success(['attachments' => DB::table('platform_ticket_attachments')->where('platform_ticket_id', $ticket->id)->get()], 'Attachment added.');
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
        return $this->success(['ticket' => DB::table('platform_tickets')->where('id', $ticket->id)->first()], $message);
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
