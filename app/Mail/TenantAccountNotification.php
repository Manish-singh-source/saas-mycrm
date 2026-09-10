<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

final class TenantAccountNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public string $title, public string $text) {}

    public function build(): static
    {
        return $this->subject($this->title)->text('mail.tenant-account');
    }
}
