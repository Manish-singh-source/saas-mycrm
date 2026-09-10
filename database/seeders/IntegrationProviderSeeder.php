<?php

namespace Database\Seeders;

use App\Models\IntegrationProvider;
use Illuminate\Database\Seeder;

final class IntegrationProviderSeeder extends Seeder
{
    public function run(): void
    {
        $providers = [];

        if (filled(config('mail.mailers.smtp.host'))) {
            $providers[] = [
                'name' => 'SMTP Mail',
                'code' => 'smtp-mail',
                'category' => 'communication',
                'auth_type' => 'basic',
                'metadata' => [
                    'mailer' => 'smtp',
                    'host' => config('mail.mailers.smtp.host'),
                    'port' => config('mail.mailers.smtp.port'),
                    'encryption' => config('mail.mailers.smtp.encryption'),
                    'from_address' => config('mail.from.address'),
                ],
                'status' => 'active',
            ];
        }

        if (filled(env('RAZORPAY_KEY_ID')) && filled(env('RAZORPAY_KEY_SECRET'))) {
            $providers[] = [
                'name' => 'Razorpay',
                'code' => 'razorpay',
                'category' => 'billing',
                'auth_type' => 'api_key',
                'metadata' => ['mode' => app()->environment('production') ? 'live' : 'test'],
                'status' => 'active',
            ];
        }

        if (filled(env('TWILIO_ACCOUNT_SID')) && filled(env('TWILIO_AUTH_TOKEN'))) {
            $providers[] = [
                'name' => 'Twilio',
                'code' => 'twilio',
                'category' => 'communication',
                'auth_type' => 'api_key',
                'metadata' => ['from_number' => env('TWILIO_FROM_NUMBER')],
                'status' => 'active',
            ];
        }

        foreach ($providers as $provider) {
            IntegrationProvider::query()->updateOrCreate(
                ['code' => $provider['code']],
                $provider,
            );
        }
    }
}
