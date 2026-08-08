<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PasswordResetInstructions extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly string $email,
        public readonly string $token,
        public readonly string $resetUrl,
        public readonly string $surface = 'account',
        public readonly ?string $tenant = null,
    ) {}

    public function build(): self
    {
        return $this
            ->subject('Reset your '.config('app.name', 'SaaS CRM').' password')
            ->view('emails.password-reset');
    }
}
