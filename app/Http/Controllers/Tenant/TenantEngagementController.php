<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Shared\BaseApiController;
use App\Tenancy\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
                $q->where('title', 'like', '%'.$search.'%')
                    ->orWhere('message', 'like', '%'.$search.'%')
                    ->orWhere('type', 'like', '%'.$search.'%');
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

    public function markRead(int $notification_id)
    {
        $this->notificationQuery($notification_id)->update(['read_at' => now(), 'updated_at' => now()]);

        return $this->success(['notification' => $this->notificationQuery($notification_id)->first()], 'Notification marked read.');
    }

    public function markUnread(int $notification_id)
    {
        $this->notificationQuery($notification_id)->update(['read_at' => null, 'updated_at' => now()]);

        return $this->success(['notification' => $this->notificationQuery($notification_id)->first()], 'Notification marked unread.');
    }

    public function bulkRead(Request $request)
    {
        $data = $request->validate(['ids' => ['nullable', 'array'], 'ids.*' => ['integer']]);
        $query = DB::table('notifications')->where('tenant_id', $this->tenant->id())->whereNull('read_at');

        if (($data['ids'] ?? []) !== []) {
            $query->whereIn('id', $data['ids']);
        }

        $updated = $query->update(['read_at' => now(), 'updated_at' => now()]);

        return $this->success(['updated' => $updated], 'Notifications marked read.');
    }

    public function deleteNotification(int $notification_id)
    {
        $this->notificationQuery($notification_id)->delete();

        return $this->success(null, 'Notification deleted.');
    }

    public function communicationLogs(Request $request)
    {
        $query = DB::table('communication_logs')->where('tenant_id', $this->tenant->id());

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search): void {
                $q->where('channel', 'like', '%'.$search.'%')
                    ->orWhere('subject', 'like', '%'.$search.'%')
                    ->orWhere('status', 'like', '%'.$search.'%');
            });
        }

        $paginator = $query->latest('id')->paginate((int) $request->integer('per_page', 25));

        return $this->list($paginator->items(), $paginator);
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
            'updated_at' => now(),
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
                $q->where('title', 'like', '%'.$search.'%')->orWhere('content', 'like', '%'.$search.'%');
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
            $id = DB::table('platform_tickets')->insertGetId([
                'uuid' => (string) \Illuminate\Support\Str::uuid(),
                'tenant_id' => $this->tenant->id(),
                'opened_by_user_id' => $request->user()?->id,
                'subject' => $data['subject'],
                'description' => $data['description'],
                'priority' => $data['priority'] ?? 'medium',
                'status' => 'open',
                'source' => 'tenant_help_center',
                'opened_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
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

    private function notificationQuery(int $id)
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
