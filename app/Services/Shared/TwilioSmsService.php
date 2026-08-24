<?php

namespace App\Services\Shared;

use Throwable;
use Twilio\Rest\Client;

class TwilioSmsService
{
    public function configured(): bool
    {
        return filled(config('services.twilio.sid'))
            && filled(config('services.twilio.token'))
            && filled(config('services.twilio.from'));
    }

    public function send(string $to, string $body): array
    {
        $this->ensureTwilioLoaded();

        if (! $this->configured()) {
            return [
                'sent' => false,
                'status' => 'not_configured',
                'error' => 'Twilio SMS credentials are not configured.',
            ];
        }

        try {
            $message = (new Client(config('services.twilio.sid'), config('services.twilio.token')))
                ->messages
                ->create($to, [
                    'from' => config('services.twilio.from'),
                    'body' => $body,
                ]);

            return [
                'sent' => true,
                'status' => $message->status ?: 'queued',
                'provider_message_id' => $message->sid,
            ];
        } catch (Throwable $exception) {
            return [
                'sent' => false,
                'status' => 'failed',
                'error' => $exception->getMessage(),
            ];
        }
    }

    private function ensureTwilioLoaded(): void
    {
        if (class_exists(Client::class)) {
            return;
        }

        $autoload = base_path('vendor/twilio/sdk/src/Twilio/autoload.php');
        if (is_file($autoload)) {
            require_once $autoload;
        }
    }

}
