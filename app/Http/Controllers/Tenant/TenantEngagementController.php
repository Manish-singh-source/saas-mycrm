<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Shared\BaseApiController;
use App\Tenancy\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TenantEngagementController extends BaseApiController
{
    public function __construct(private readonly TenantContext $tenant) {}

    public function notifications(Request $request)
    {
        $query = DB::table('notifications')->where('tenant_id', $this->tenant->id());

        if ($request->boolean('unread') || $request->input('filter.read') === 'unread') {
            $query->whereNull('read_at');
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search): void {
                $q->where('data', 'like', '%' . $search . '%')
                    ->orWhere('type', 'like', '%' . $search . '%');
            });
        }

        $paginator = $query->latest('id')->paginate((int) $request->integer('per_page', 25));

        return $this->list($paginator->items(), $paginator);
    }

    public function unreadCount()
    {
        return $this->success([
            'unread_count' => DB::table('notifications')
                ->where('tenant_id', $this->tenant->id())
                ->whereNull('read_at')
                ->count(),
        ]);
    }

    public function markRead(string $notification_id)
    {
        $this->notificationQuery($notification_id)->update(['read_at' => now(), 'updated_at' => now()]);

        return $this->success(['notification' => $this->notificationQuery($notification_id)->first()], 'Notification marked read.');
    }

    public function markUnread(string $notification_id)
    {
        $this->notificationQuery($notification_id)->update(['read_at' => null, 'updated_at' => now()]);

        return $this->success(['notification' => $this->notificationQuery($notification_id)->first()], 'Notification marked unread.');
    }

    public function bulkRead(Request $request)
    {
        $data = $request->validate(['ids' => ['nullable', 'array'], 'ids.*' => ['string']]);
        $query = DB::table('notifications')->where('tenant_id', $this->tenant->id())->whereNull('read_at');

        if (($data['ids'] ?? []) !== []) {
            $query->whereIn('id', $data['ids']);
        }

        $updated = $query->update(['read_at' => now(), 'updated_at' => now()]);

        return $this->success(['updated' => $updated], 'Notifications marked read.');
    }

    public function deleteNotification(string $notification_id)
    {
        $this->notificationQuery($notification_id)->delete();

        return $this->success(null, 'Notification deleted.');
    }

    public function communicationLogs(Request $request)
    {
        $query = DB::table('communication_logs')->where('tenant_id', $this->tenant->id());

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search): void {
                $q->where('channel', 'like', '%' . $search . '%')
                    ->orWhere('subject', 'like', '%' . $search . '%')
                    ->orWhere('status', 'like', '%' . $search . '%');
            });
        }

        $paginator = $query->latest('id')->paginate((int) $request->integer('per_page', 25));

        return $this->list($paginator->items(), $paginator);
    }

    public function sendEmail(Request $request)
    {
        $data = $request->validate([
            'to' => ['required', 'email', 'max:150'],
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'party_uuid' => ['nullable', 'uuid'],
            'metadata' => ['nullable', 'array'],
        ]);
        $partyId = null;
        if (! empty($data['party_uuid'])) {
            $partyId = DB::table('parties')
                ->where('tenant_id', $this->tenant->id())
                ->where('uuid', $data['party_uuid'])
                ->value('id');
            abort_if(! $partyId, 404, 'Party not found.');
        }
        $id = DB::table('communication_logs')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'tenant_id' => $this->tenant->id(),
            'user_id' => $request->user()?->id,
            'party_id' => $partyId,
            'channel' => 'email',
            'direction' => 'outbound',
            'subject' => $data['subject'],
            'body' => $data['body'],
            'provider' => 'manual',
            'status' => 'queued',
            'metadata' => json_encode(['to' => $data['to'], ...($data['metadata'] ?? [])]),
            'created_at' => now(),
        ]);

        return $this->success(['log' => DB::table('communication_logs')->where('id', $id)->first()], 'Email queued.', 202);
    }

    public function retryCommunication(Request $request, string $log_uuid)
    {
        $log = DB::table('communication_logs')
            ->where('tenant_id', $this->tenant->id())
            ->where('uuid', $log_uuid)
            ->first();

        abort_if(! $log, 404);

        DB::table('communication_logs')->where('id', $log->id)->update([
            'status' => 'retry_queued',
        ]);

        return $this->success(['log' => DB::table('communication_logs')->where('id', $log->id)->first()], 'Communication retry queued.');
    }

    public function helpArticles(Request $request)
    {
        if (! $this->tableExists('knowledge_base_articles')) {
            return $this->success(['articles' => [], 'placeholder' => true]);
        }

        $query = DB::table('knowledge_base_articles')->where('status', 'published');
        if ($this->columnExists('knowledge_base_articles', 'visibility')) {
            $query->whereIn('visibility', ['public', 'tenant']);
        }
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search): void {
                $q->where('title', 'like', '%' . $search . '%')->orWhere('body', 'like', '%' . $search . '%');
            });
        }
        $paginator = $query->latest('id')->paginate((int) $request->integer('per_page', 25));

        return $this->list($paginator->items(), $paginator);
    }

    public function helpArticle(string $slug)
    {
        abort_if(! $this->tableExists('knowledge_base_articles'), 404);
        $article = DB::table('knowledge_base_articles')
            ->where('slug', $slug)
            ->where('status', 'published')
            ->first();

        abort_if(! $article, 404);

        return $this->success(['article' => $article]);
    }

    public function faqs()
    {
        return $this->success([
            'faqs' => [
                ['question' => 'How do I contact support?', 'answer' => 'Use Contact Support from Help Center and include the affected module.'],
                ['question' => 'Where are exports delivered?', 'answer' => 'Immediate CSV exports download from the browser; queued exports are completed by the queue worker.'],
            ],
        ]);
    }

    public function releaseNotes()
    {
        return $this->success(['release_notes' => []]);
    }

    public function contactSupport(Request $request)
    {
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'priority' => ['nullable', 'string', 'max:50'],
            'attachments' => ['nullable', 'array'],
        ]);

        $id = null;
        if ($this->tableExists('platform_tickets')) {
            $ticket = [
                'uuid' => (string) \Illuminate\Support\Str::uuid(),
                'ticket_number' => 'TCK-' . now()->format('ymd') . '-' . str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT),
                'tenant_id' => $this->tenant->id(),
                'subject' => $data['subject'],
                'description' => $data['description'],
                'priority' => $data['priority'] ?? 'medium',
                'status' => 'open',
                'created_at' => now(),
                'updated_at' => now(),
            ];
            if ($this->columnExists('platform_tickets', 'source')) $ticket['source'] = 'tenant_help_center';
            if ($this->columnExists('platform_tickets', 'opened_at')) $ticket['opened_at'] = now();
            $id = DB::table('platform_tickets')->insertGetId($ticket);
        }

        return $this->success(['ticket_id' => $id, 'queued' => $id === null], 'Support request submitted.', 201);
    }

    public function systemStatus()
    {
        $alerts = $this->tableExists('monitoring_alerts')
            ? DB::table('monitoring_alerts')->whereIn('status', ['open', 'triggered'])->count()
            : 0;

        return $this->success([
            'status' => $alerts > 0 ? 'degraded' : 'operational',
            'open_alerts' => $alerts,
        ]);
    }

    private function notificationQuery(string $id)
    {
        $query = DB::table('notifications')->where('tenant_id', $this->tenant->id())->where('id', $id);
        abort_if(! $query->exists(), 404);

        return $query;
    }

    private function tableExists(string $table): bool
    {
        return DB::getSchemaBuilder()->hasTable($table);
    }

    private function columnExists(string $table, string $column): bool
    {
        return DB::getSchemaBuilder()->hasColumn($table, $column);
    }
}
