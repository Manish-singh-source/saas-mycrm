<?php

namespace App\Services\Shared;

use App\Mail\CrmNotificationMail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class CrmMailerService
{
    public function send(array|string|null $to, string $subject, string $heading, string $intro, array $rows = [], ?string $actionText = null, ?string $actionUrl = null, ?string $outro = null, array $cc = [], ?string $fromName = null, ?string $replyTo = null): bool
    {
        $recipients = $this->normalizeEmails($to);

        if ($recipients === []) {
            Log::warning('CRM email skipped because no recipient was available.', ['subject' => $subject]);
            return false;
        }

        try {
            $message = Mail::to($recipients);
            $cc = $this->normalizeEmails($cc);
            if ($cc !== []) {
                $message->cc($cc);
            }
            $message->send(new CrmNotificationMail($subject, $heading, $intro, $rows, $actionText, $actionUrl, $outro, $fromName, $replyTo));
            return true;
        } catch (Throwable $e) {
            Log::warning('CRM email could not be sent.', [
                'subject' => $subject,
                'recipients' => $recipients,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    public function tenantRecipients(?int $tenantId): array
    {
        if (! $tenantId) {
            return [];
        }

        return DB::table('users')
            ->where('tenant_id', $tenantId)
            ->whereNotNull('email')
            ->whereIn('account_type', ['owner', 'admin'])
            ->orderByRaw("CASE WHEN account_type = 'owner' THEN 0 ELSE 1 END")
            ->pluck('email')
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public function tenantName(?int $tenantId): string
    {
        if (! $tenantId) {
            return 'your workspace';
        }

        return (string) (DB::table('tenants')->where('id', $tenantId)->value('display_name')
            ?: DB::table('tenants')->where('id', $tenantId)->value('organization_name')
            ?: 'your workspace');
    }

    public function money(float|int|string|null $amount, ?string $currency): string
    {
        return trim(($currency ?: '') . ' ' . number_format((float) $amount, 2));
    }

    private function normalizeEmails(array|string|null $emails): array
    {
        return collect(is_array($emails) ? $emails : [$emails])
            ->filter(fn ($email) => is_string($email) && filter_var($email, FILTER_VALIDATE_EMAIL))
            ->map(fn ($email) => strtolower(trim($email)))
            ->unique()
            ->values()
            ->all();
    }
}

