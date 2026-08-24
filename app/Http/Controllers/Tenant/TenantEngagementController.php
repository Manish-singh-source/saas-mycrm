<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Shared\BaseApiController;
use App\Services\Shared\CrmMailerService;
use App\Services\Shared\TwilioSmsService;
use App\Tenancy\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TenantEngagementController extends BaseApiController
{
    public function __construct(private readonly TenantContext $tenant, private readonly CrmMailerService $mailer, private readonly TwilioSmsService $sms) {}

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

        return $this->list(collect($paginator->items())->map(fn($row) => $this->notificationPayload($row))->all(), $paginator);
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

    public function showNotification(string $notification_id)
    {
        return $this->success([
            'notification' => $this->notificationPayload($this->notificationQuery($notification_id)->first()),
        ]);
    }
    public function markRead(string $notification_id)
    {
        $this->notificationQuery($notification_id)->update(['read_at' => now(), 'updated_at' => now()]);

        return $this->success(['notification' => $this->notificationPayload($this->notificationQuery($notification_id)->first())], 'Notification marked read.');
    }

    public function markUnread(string $notification_id)
    {
        $this->notificationQuery($notification_id)->update(['read_at' => null, 'updated_at' => now()]);

        return $this->success(['notification' => $this->notificationPayload($this->notificationQuery($notification_id)->first())], 'Notification marked unread.');
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
        if (! $this->communicationEnabled('email')) {
            $id = $this->logCommunication($request, 'email', $data['subject'], $data['body'], 'blocked', $partyId, ['to' => $data['to'], 'reason' => 'email_notifications_disabled', ...($data['metadata'] ?? [])]);

            return $this->success(['log' => DB::table('communication_logs')->where('id', $id)->first(), 'sent' => false, 'blocked' => true], 'Email notifications are disabled for this tenant.', 423);
        }

        $mailOptions = $this->tenantMailOptions();
        $sent = $this->mailer->send($data['to'], $data['subject'], $data['subject'], $data['body'], [], null, null, null, [], $mailOptions['from_name'], $mailOptions['reply_to']);
        $id = $this->logCommunication($request, 'email', $data['subject'], $data['body'], $sent ? 'sent' : 'failed', $partyId, ['to' => $data['to'], ...($data['metadata'] ?? [])], $sent ? now() : null, 'smtp');

        return $this->success(['log' => DB::table('communication_logs')->where('id', $id)->first(), 'sent' => $sent], $sent ? 'Email sent.' : 'Email could not be sent. Check mail logs/configuration.', $sent ? 200 : 202);
    }

    public function sendSms(Request $request)
    {
        return $this->sendQueuedChannel($request, 'sms', 'SMS');
    }

    public function sendWhatsApp(Request $request)
    {
        return $this->sendQueuedChannel($request, 'whatsapp', 'WhatsApp');
    }

    public function sendPush(Request $request)
    {
        $data = $request->validate([
            'to' => ['nullable', 'string', 'max:150'],
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'party_uuid' => ['nullable', 'uuid'],
            'metadata' => ['nullable', 'array'],
        ]);

        if (! $this->communicationEnabled('push')) {
            $id = $this->logCommunication($request, 'push', $data['subject'], $data['body'], 'blocked', null, ['to' => $data['to'] ?? 'workspace', 'reason' => 'push_notifications_disabled', ...($data['metadata'] ?? [])]);

            return $this->success(['log' => DB::table('communication_logs')->where('id', $id)->first(), 'sent' => false, 'blocked' => true], 'Push notifications are disabled for this tenant.', 423);
        }

        $notificationId = $this->createPushNotification($request, $data['subject'], $data['body'], $data['metadata'] ?? []);
        $id = $this->logCommunication($request, 'push', $data['subject'], $data['body'], 'sent', null, ['to' => $data['to'] ?? 'workspace', 'notification_id' => $notificationId, ...($data['metadata'] ?? [])], now(), 'in_app');

        return $this->success(['log' => DB::table('communication_logs')->where('id', $id)->first(), 'notification_id' => $notificationId, 'sent' => true], 'Push notification created.', 201);
    }

    public function retryCommunication(Request $request, string $log_uuid)
    {
        $log = DB::table('communication_logs')
            ->where('tenant_id', $this->tenant->id())
            ->where('uuid', $log_uuid)
            ->first();

        abort_if(! $log, 404);

        if ($log->channel === 'sms') {
            if (! $this->communicationEnabled('sms')) {
                DB::table('communication_logs')->where('id', $log->id)->update([
                    'status' => 'blocked',
                    'failed_reason' => 'sms_notifications_disabled',
                ]);

                return $this->success(['log' => DB::table('communication_logs')->where('id', $log->id)->first(), 'sent' => false, 'blocked' => true], 'SMS notifications are disabled for this tenant.', 423);
            }

            $metadata = json_decode((string) $log->metadata, true) ?: [];
            $to = $metadata['to'] ?? null;
            abort_if(! $to, 422, 'Original SMS recipient is missing from communication log metadata.');

            $result = $this->sms->send((string) $to, (string) $log->body);

            DB::table('communication_logs')->where('id', $log->id)->update([
                'provider' => 'twilio',
                'provider_message_id' => $result['provider_message_id'] ?? null,
                'status' => $result['sent'] ? 'sent' : 'failed',
                'sent_at' => $result['sent'] ? now() : null,
                'failed_reason' => $result['error'] ?? null,
                'metadata' => json_encode([...$metadata, 'retry_at' => now()->toISOString(), 'twilio_status' => $result['status'] ?? null]),
            ]);

            return $this->success(
                ['log' => DB::table('communication_logs')->where('id', $log->id)->first(), 'sent' => (bool) $result['sent'], 'provider_status' => $result['status'] ?? null],
                $result['sent'] ? 'SMS resent through Twilio.' : 'SMS retry failed through Twilio.',
                $result['sent'] ? 200 : 202
            );
        }

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
        return $this->success([
            'release_notes' => [
                ['version' => '2026.08', 'title' => 'Workspace improvements', 'summary' => 'Improved staff, team, and profile workflows across the tenant workspace.', 'released_at' => '2026-08-01'],
                ['version' => '2026.07', 'title' => 'Faster exports', 'summary' => 'List pages now provide clearer export feedback and more consistent filters.', 'released_at' => '2026-07-15'],
            ],
        ]);
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

    private function sendQueuedChannel(Request $request, string $channel, string $label)
    {
        $data = $request->validate([
            'to' => ['required', 'string', 'max:150'],
            'subject' => ['nullable', 'string', 'max:255'],
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

        if (! $this->communicationEnabled($channel)) {
            $id = $this->logCommunication($request, $channel, $data['subject'] ?? $label, $data['body'], 'blocked', $partyId, ['to' => $data['to'], 'reason' => $channel . '_notifications_disabled', ...($data['metadata'] ?? [])]);

            return $this->success(['log' => DB::table('communication_logs')->where('id', $id)->first(), 'sent' => false, 'blocked' => true], $label . ' notifications are disabled for this tenant.', 423);
        }
        if ($channel === 'sms') {
            $result = $this->sms->send($data['to'], $data['body']);
            $status = $result['sent'] ? 'sent' : 'failed';
            $id = $this->logCommunication(
                $request,
                'sms',
                $data['subject'] ?? $label,
                $data['body'],
                $status,
                $partyId,
                ['to' => $data['to'], 'twilio_status' => $result['status'] ?? null, ...($data['metadata'] ?? [])],
                $result['sent'] ? now() : null,
                'twilio',
                $result['provider_message_id'] ?? null,
                $result['error'] ?? null
            );

            return $this->success(
                ['log' => DB::table('communication_logs')->where('id', $id)->first(), 'sent' => (bool) $result['sent'], 'provider_status' => $result['status'] ?? null],
                $result['sent'] ? 'SMS sent through Twilio.' : 'SMS could not be sent through Twilio.',
                $result['sent'] ? 200 : 202
            );
        }

        $id = $this->logCommunication($request, $channel, $data['subject'] ?? $label, $data['body'], 'queued', $partyId, ['to' => $data['to'], 'provider_ready' => true, ...($data['metadata'] ?? [])], null, 'provider_queue');

        return $this->success(['log' => DB::table('communication_logs')->where('id', $id)->first(), 'queued' => true, 'sent' => false], $label . ' message queued for provider delivery.', 202);
    }

    private function communicationEnabled(string $channel): bool
    {
        $key = match ($channel) {
            'email' => 'email_notifications',
            'sms' => 'sms_notifications',
            'whatsapp' => 'whatsapp_notifications',
            'push', 'in_app' => 'push_notifications',
            default => null,
        };

        if (! $key) return true;

        $value = DB::table('tenant_settings')
            ->where('tenant_id', $this->tenant->id())
            ->where('group', 'communication')
            ->where('key', $key)
            ->value('value');

        return $value === null ? true : filter_var($this->settingValue($value), FILTER_VALIDATE_BOOLEAN);
    }

    private function logCommunication(Request $request, string $channel, ?string $subject, ?string $body, string $status, ?int $partyId = null, array $metadata = [], mixed $sentAt = null, ?string $provider = 'manual', ?string $providerMessageId = null, ?string $failedReason = null): int
    {
        return DB::table('communication_logs')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'tenant_id' => $this->tenant->id(),
            'user_id' => $request->user()?->id,
            'party_id' => $partyId,
            'channel' => $channel,
            'direction' => 'outbound',
            'subject' => $subject,
            'body' => $body,
            'provider' => $provider,
            'provider_message_id' => $providerMessageId,
            'status' => $status,
            'sent_at' => $sentAt,
            'failed_reason' => $failedReason ?: ($status === 'blocked' ? ($metadata['reason'] ?? 'disabled_by_tenant_preferences') : null),
            'metadata' => json_encode($metadata),
            'created_at' => now(),
        ]);
    }

    private function createPushNotification(Request $request, string $title, string $body, array $metadata = []): string
    {
        $id = (string) Str::uuid();

        DB::table('notifications')->insert([
            'id' => $id,
            'tenant_id' => $this->tenant->id(),
            'type' => 'tenant_push',
            'notifiable_type' => 'tenant_user',
            'notifiable_id' => (int) ($request->user()?->id ?? 0),
            'data' => json_encode(['title' => $title, 'message' => $body, ...$metadata]),
            'read_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $id;
    }

    private function tenantMailOptions(): array
    {
        $settings = DB::table('tenant_settings')
            ->where('tenant_id', $this->tenant->id())
            ->where('group', 'communication')
            ->whereIn('key', ['sender_name', 'reply_to_email'])
            ->pluck('value', 'key')
            ->map(fn($value) => $this->settingValue($value));

        return [
            'from_name' => (string) ($settings['sender_name'] ?? $this->mailer->tenantName($this->tenant->id())),
            'reply_to' => (string) ($settings['reply_to_email'] ?? ''),
        ];
    }

    private function settingValue(mixed $value): mixed
    {
        return json_decode((string) $value, true);
    }
    private function notificationPayload(object $notification): object
    {
        $data = json_decode((string) $notification->data, true);
        $payload = is_array($data) ? $data : [];

        $notification->data_payload = $payload;
        $notification->title = (string) ($payload['title'] ?? $payload['subject'] ?? Str::headline((string) $notification->type));
        $notification->message = (string) ($payload['message'] ?? $payload['body'] ?? $payload['description'] ?? '');
        $notification->is_read = filled($notification->read_at);

        return $notification;
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









