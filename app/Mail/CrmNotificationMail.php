<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CrmNotificationMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly string $subjectLine,
        public readonly string $heading,
        public readonly string $intro,
        public readonly array $rows = [],
        public readonly ?string $actionText = null,
        public readonly ?string $actionUrl = null,
        public readonly ?string $outro = null,
        public readonly ?string $fromName = null,
        public readonly ?string $replyTo = null,
    ) {}

    public function build(): self
    {
        $message = $this
            ->subject($this->subjectLine)
            ->from(config('mail.from.address'), $this->fromName ?: config('mail.from.name'))
            ->view('emails.crm-notification');

        if ($this->replyTo && filter_var($this->replyTo, FILTER_VALIDATE_EMAIL)) {
            $message->replyTo($this->replyTo);
        }

        return $message;
    }
}
