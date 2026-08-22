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
    ) {}

    public function build(): self
    {
        return $this
            ->subject($this->subjectLine)
            ->view('emails.crm-notification');
    }
}
