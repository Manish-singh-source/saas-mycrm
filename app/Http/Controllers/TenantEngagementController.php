<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Shared\BaseApiController;
use App\Models\TenantApiToken;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class TenantEngagementController extends BaseApiController
{
    private function tid(): int { return (int) request()->attributes->get('tenant_id'); }
    private function tenantUser(string $uuid): User
    {
        return User::where('tenant_id', $this->tid())->where('uuid', $uuid)->firstOrFail();
    }

    public function reminders(Request $r)
    {
        $q = DB::table('reminders')->where('tenant_id', $this->tid())->where('user_id', auth()->id());
        if ($r->filled('remindable_type')) $q->where('remindable_type', $this->remindableType((string) $r->input('remindable_type')));
        if ($r->filled('remindable_uuid')) {
            $row = $this->remindable((string) $r->input('remindable_type'), (string) $r->input('remindable_uuid'));
            $q->where('remindable_id', $row->id);
        }
        $page = $q->orderBy('remind_at')->paginate(min(max((int) $r->input('per_page', 25), 1), 100))->withQueryString();
        return $this->list($page->items(), $page, 'Reminders fetched.');
    }

    public function createReminder(Request $r)
    {
        $d = $r->validate([
            'remindable_type' => ['required', 'string'],
            'remindable_uuid' => ['required', 'uuid'],
            'target_user_uuid' => ['nullable', 'uuid'],
            'channel' => ['required', 'in:in_app,email,push,sms,whatsapp'],
            'remind_at' => ['required', 'date'],
            'metadata' => ['nullable', 'array'],
        ]);
        $entity = $this->remindable($d['remindable_type'], $d['remindable_uuid']);
        $target = isset($d['target_user_uuid']) ? $this->tenantUser($d['target_user_uuid']) : $r->user();
        $id = DB::table('reminders')->insertGetId([
            'uuid' => (string) Str::uuid(), 'tenant_id' => $this->tid(),
            'remindable_type' => $this->remindableType($d['remindable_type']), 'remindable_id' => $entity->id,
            'user_id' => $target->id, 'channel' => $d['channel'], 'remind_at' => $d['remind_at'],
            'status' => 'pending', 'metadata' => json_encode($d['metadata'] ?? []), 'created_at' => now(), 'updated_at' => now(),
        ]);
        return $this->success(['reminder' => $this->reminder($id)], 'Reminder created.', 201);
    }

    public function updateReminder(Request $r, string $uuid)
    {
        $q = DB::table('reminders')->where('tenant_id', $this->tid())->where('user_id', auth()->id())->where('uuid', $uuid);
        if (!$q->exists()) abort(404);
        $d = $r->validate(['channel' => ['sometimes', 'in:in_app,email,push,sms,whatsapp'], 'remind_at' => ['sometimes', 'date'], 'status' => ['sometimes', 'in:pending,sent,cancelled,failed'], 'metadata' => ['nullable', 'array']]);
        if (array_key_exists('metadata', $d)) $d['metadata'] = json_encode($d['metadata']);
        $q->update($d + ['updated_at' => now()]);
        return $this->success(['reminder' => $this->reminder(null, $uuid)], 'Reminder updated.');
    }

    public function deleteReminder(string $uuid)
    {
        $q = DB::table('reminders')->where('tenant_id', $this->tid())->where('user_id', auth()->id())->where('uuid', $uuid);
        if (!$q->exists()) abort(404);
        $q->delete();
        return $this->success(null, 'Reminder deleted.');
    }

    public function communicationLogs(Request $r)
    {
        $q = DB::table('communication_logs')->where('tenant_id', $this->tid());
        foreach (['channel', 'status'] as $field) if ($r->filled($field)) $q->where($field, $r->input($field));
        if ($r->filled('search')) $q->where(fn($x) => $x->where('subject', 'like', '%'.$r->input('search').'%')->orWhere('body', 'like', '%'.$r->input('search').'%'));
        $page = $q->latest('created_at')->paginate(min(max((int) $r->input('per_page', 25), 1), 100))->withQueryString();
        return $this->list(collect($page->items())->map(fn($x) => $this->communication($x))->all(), $page, 'Communication logs fetched.');
    }

    public function send(Request $r, string $channel)
    {
        $rules = ['channel' => ['sometimes', 'in:email,sms,whatsapp,push'], 'to' => ['required'], 'subject' => ['nullable', 'string', 'max:255'], 'body' => ['required', 'string'], 'party_uuid' => ['nullable', 'uuid'], 'metadata' => ['nullable', 'array']];
        $d = $r->validate($rules); $partyId = null;
        if (!empty($d['party_uuid'])) $partyId = DB::table('parties')->where('tenant_id', $this->tid())->where('uuid', $d['party_uuid'])->value('id');
        if (!empty($d['party_uuid']) && !$partyId) abort(404);
        $enabled = DB::table('tenant_settings')->where('tenant_id', $this->tid())->where('group', 'communication')->where('key', $channel.'_notifications')->value('value');
        $blocked = $enabled !== null && in_array(strtolower((string) json_decode($enabled, true)), ['0', 'false', 'off', 'disabled'], true);
        $id = DB::table('communication_logs')->insertGetId([
            'uuid' => (string) Str::uuid(), 'tenant_id' => $this->tid(), 'user_id' => auth()->id(), 'party_id' => $partyId,
            'channel' => $channel, 'direction' => 'outbound', 'subject' => $d['subject'] ?? null, 'body' => $d['body'],
            'status' => $blocked ? 'blocked' : ($channel === 'email' ? 'queued' : 'queued'), 'metadata' => json_encode(['to' => $d['to'], ...($d['metadata'] ?? [])]),
            'created_at' => now(),
        ]);
        return $this->success(['log' => $this->communication(DB::table('communication_logs')->where('id', $id)->first())], $blocked ? 'Communication is disabled.' : 'Communication queued.', $blocked ? 423 : 202);
    }
    public function sendEmail(Request $r) { return $this->send($r, 'email'); }
    public function sendSms(Request $r) { return $this->send($r, 'sms'); }
    public function sendWhatsApp(Request $r) { return $this->send($r, 'whatsapp'); }
    public function sendPush(Request $r) { return $this->send($r, 'push'); }

    public function retryCommunication(string $uuid)
    {
        $q = DB::table('communication_logs')->where('tenant_id', $this->tid())->where('uuid', $uuid);
        $log = $q->first();
        if (!$log) abort(404);
        if (!in_array($log->status, ['failed', 'blocked'], true)) return $this->businessError('Only failed or blocked communication can be retried.', 'COMMUNICATION_NOT_RETRYABLE');
        $q->update(['status' => 'queued', 'failed_reason' => null, 'created_at' => now()]);
        return $this->success(['log' => $this->communication($q->first())], 'Communication retry queued.', 202);
    }

    public function helpArticles(Request $r)
    {
        $q = DB::table('knowledge_base_articles')->where('status', 'published')->whereNull('deleted_at');
        if ($r->filled('search')) $q->where(fn($x) => $x->where('title', 'like', '%'.$r->input('search').'%')->orWhere('body', 'like', '%'.$r->input('search').'%'));
        if ($r->filled('category')) $q->where('category_id', $r->input('category'));
        $page = $q->latest('published_at')->paginate(min(max((int) $r->input('per_page', 25), 1), 100))->withQueryString();
        return $this->list($page->items(), $page, 'Help articles fetched.');
    }

    public function helpArticle(string $slug)
    {
        $article = DB::table('knowledge_base_articles')->where('slug', $slug)->where('status', 'published')->whereNull('deleted_at')->firstOrFail();
        return $this->success(['article' => $article], 'Help article fetched.');
    }

    public function faqs() { return $this->success(['faqs' => DB::table('knowledge_base_articles')->where('status', 'published')->whereNull('deleted_at')->where('title', 'like', '%FAQ%')->get()], 'FAQs fetched.'); }
    public function releaseNotes() { return $this->success(['release_notes' => DB::table('knowledge_base_articles')->where('status', 'published')->whereNull('deleted_at')->where(fn($q) => $q->where('title', 'like', '%release%')->orWhere('title', 'like', '%changelog%'))->latest('published_at')->get()], 'Release notes fetched.'); }
    public function systemStatus() { return $this->success(['status' => 'operational', 'checked_at' => now(), 'incidents' => []], 'System status fetched.'); }

    public function contactSupport(Request $r)
    {
        $d = $r->validate(['subject' => ['required', 'string', 'max:255'], 'description' => ['required', 'string'], 'priority' => ['sometimes', 'in:low,medium,high,urgent']]);
        $id = DB::table('platform_tickets')->insertGetId(['uuid' => (string) Str::uuid(), 'ticket_number' => 'SUP-'.now()->format('YmdHis').'-'.Str::upper(Str::random(4)), 'tenant_id' => $this->tid(), 'subject' => $d['subject'], 'description' => $d['description'], 'priority' => $d['priority'] ?? 'medium', 'status' => 'open', 'source' => 'tenant_help_center', 'opened_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
        return $this->success(['ticket' => DB::table('platform_tickets')->where('id', $id)->first()], 'Support request created.', 201);
    }
    public function supportTickets(Request $r)
    {
        $page = DB::table('platform_tickets')->where('tenant_id', $this->tid())->whereNull('deleted_at')->when($r->filled('status'), fn($q) => $q->where('status', $r->input('status')))->latest()->paginate(min(max((int) $r->input('per_page', 25), 1), 100));
        return $this->list($page->items(), $page, 'Support tickets fetched.');
    }
    public function supportTicket(string $uuid)
    {
        $t = DB::table('platform_tickets')->where('tenant_id', $this->tid())->where('uuid', $uuid)->whereNull('deleted_at')->firstOrFail();
        $comments = DB::table('platform_ticket_comments')->where('platform_ticket_id', $t->id)->where('is_internal', false)->latest()->get();
        return $this->success(['ticket' => array_merge((array) $t, ['comments' => $comments])], 'Support ticket fetched.');
    }
    public function supportComment(Request $r, string $uuid)
    {
        $d = $r->validate(['comment' => ['required', 'string']]);
        $t = DB::table('platform_tickets')->where('tenant_id', $this->tid())->where('uuid', $uuid)->whereNull('deleted_at')->firstOrFail();
        $id = DB::table('platform_ticket_comments')->insertGetId(['platform_ticket_id' => $t->id, 'user_id' => auth()->id(), 'comment' => $d['comment'], 'is_internal' => false, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('platform_tickets')->where('id', $t->id)->update(['status' => 'open', 'updated_at' => now()]);
        return $this->success(['comment' => DB::table('platform_ticket_comments')->where('id', $id)->first()], 'Support reply added.', 201);
    }

    public function tokens()
    {
        return $this->success(['tokens' => TenantApiToken::where('tenant_id', $this->tid())->where('created_by', auth()->id())->latest()->get()->map(fn($t) => $this->safeToken($t))], 'API tokens fetched.');
    }

    public function createToken(Request $r)
    {
        $d = $r->validate(['name' => ['required', 'string', 'max:255'], 'abilities' => ['nullable', 'array'], 'abilities.*' => ['string', 'max:120'], 'expires_at' => ['nullable', 'date', 'after:now']]);
        $allowed = ['tenant.read', 'tenant.write', 'profile.read', 'profile.write'];
        $abilities = array_values(array_intersect($d['abilities'] ?? ['tenant.read'], $allowed));
        $raw = 'tn_'.Str::random(48);
        $t = TenantApiToken::create(['uuid' => (string) Str::uuid(), 'tenant_id' => $this->tid(), 'name' => $d['name'], 'token_hash' => hash_hmac('sha256', $raw, (string) config('app.key')), 'encrypted_token_preview' => Crypt::encryptString(substr($raw, -8)), 'abilities' => $abilities, 'expires_at' => $d['expires_at'] ?? null, 'created_by' => auth()->id()]);
        return $this->success(['token' => $this->safeToken($t), 'raw_token' => $raw], 'API token created. Copy it now; it cannot be recovered.', 201);
    }

    public function rotateToken(string $uuid)
    {
        $t = TenantApiToken::where('tenant_id', $this->tid())->where('created_by', auth()->id())->where('uuid', $uuid)->firstOrFail();
        $raw = 'tn_'.Str::random(48); $t->forceFill(['token_hash' => hash_hmac('sha256', $raw, (string) config('app.key')), 'encrypted_token_preview' => Crypt::encryptString(substr($raw, -8))])->save();
        return $this->success(['token' => $this->safeToken($t->fresh()), 'raw_token' => $raw], 'API token rotated. The previous token is invalid immediately.');
    }

    public function revokeToken(string $uuid)
    {
        $t = TenantApiToken::where('tenant_id', $this->tid())->where('created_by', auth()->id())->where('uuid', $uuid)->firstOrFail(); $t->delete();
        return $this->success(null, 'API token revoked.');
    }

    private function safeToken(TenantApiToken $t): array { return ['uuid' => $t->uuid, 'name' => $t->name, 'abilities' => $t->abilities, 'expires_at' => $t->expires_at, 'last_used_at' => $t->last_used_at, 'created_at' => $t->created_at, 'revoked_at' => $t->deleted_at]; }
    private function communication($x): array { $a = (array) $x; if (isset($a['metadata'])) $a['metadata'] = json_decode((string) $a['metadata'], true) ?: []; if (isset($a['body'])) $a['body_preview'] = Str::limit((string) $a['body'], 160); return $a; }
    private function reminder(?int $id = null, ?string $uuid = null): array { $q = DB::table('reminders')->where('tenant_id', $this->tid())->where('user_id', auth()->id()); $x = $id ? $q->where('id', $id)->first() : $q->where('uuid', $uuid)->first(); return (array) $x; }
    private function remindableType(string $type): string
    {
        return match (strtolower($type)) { 'todo', 'todo_list', 'todo_item' => 'todo_item', 'lead', 'lead_profile' => 'lead_profile', 'renewal' => 'renewal', 'calendar_event' => 'calendar_event', 'task' => 'task', default => abort(422, 'Unsupported remindable type.') };
    }
    private function remindable(string $type, string $uuid): object
    {
        $table = match ($this->remindableType($type)) { 'todo_item' => 'todo_items', 'lead_profile' => 'lead_profiles', 'renewal' => 'renewals', 'calendar_event' => 'calendar_events', 'task' => 'tasks' };
        $x = DB::table($table)->where('tenant_id', $this->tid())->where('uuid', $uuid)->first(); if (!$x) abort(404); return $x;
    }
}
