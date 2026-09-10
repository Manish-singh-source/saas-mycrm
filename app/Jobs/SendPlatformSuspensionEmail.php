<?php

namespace App\Jobs;

use App\Models\NotificationTemplate;
use App\Models\PlatformUser;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

final class SendPlatformSuspensionEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public PlatformUser $user,
        public ?string $reason = null,
        public ?string $effectiveUntil = null,
    ) {
    }

    public function handle(): void
    {
        $template = NotificationTemplate::query()
            ->whereNull('tenant_id')
            ->where('channel', 'email')
            ->where('code', 'platform_user.suspended')
            ->where('status', 'active')
            ->latest('id')
            ->first();

        $variables = [
            '{{display_name}}' => (string) ($this->user->display_name ?: trim($this->user->first_name.' '.($this->user->last_name ?? ''))),
            '{{email}}' => (string) $this->user->email,
            '{{reason}}' => (string) ($this->reason ?: 'Security or policy review'),
            '{{effective_until}}' => (string) ($this->effectiveUntil ?: 'Until further notice'),
            '{{app_name}}' => (string) config('app.name'),
        ];
        $subject = $template?->subject ?: 'Your {{app_name}} account has been suspended';
        $body = $template?->body ?: "Hello {{display_name}},\n\nYour {{app_name}} account has been suspended.\nReason: {{reason}}\nEffective until: {{effective_until}}\n\nPlease contact your administrator if you need assistance.";

        Mail::raw(strtr($body, $variables), function ($message) use ($subject, $variables): void {
            $message->to((string) $this->user->email, (string) ($this->user->display_name ?: $this->user->email))
                ->subject(strtr($subject, $variables));
        });
    }
}