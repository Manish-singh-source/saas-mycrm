<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TenantIntegrationProviderSeeder extends Seeder
{
    public function run(): void
    {
        $providers = [
            'twilio_sms' => [
                'name' => 'Twilio SMS',
                'category' => 'messaging',
                'auth_type' => 'api_key',
                'supports' => ['sms', 'delivery_status'],
                'credential_template' => [
                    'account_sid' => null,
                    'auth_token' => null,
                    'from_number' => null,
                ],
                'env_reference' => ['TWILIO_ACCOUNT_SID', 'TWILIO_AUTH_TOKEN', 'TWILIO_FROM_NUMBER'],
            ],
            'razorpay' => [
                'name' => 'Razorpay',
                'category' => 'payment',
                'auth_type' => 'api_key',
                'supports' => ['payments', 'refunds', 'webhooks'],
                'credential_template' => [
                    'key_id' => null,
                    'key_secret' => null,
                ],
                'env_reference' => ['RAZORPAY_KEY_ID', 'RAZORPAY_KEY_SECRET'],
            ],
            'aws_s3' => [
                'name' => 'AWS S3',
                'category' => 'storage',
                'auth_type' => 'api_key',
                'supports' => ['files', 'backups', 'exports'],
                'credential_template' => [
                    'access_key_id' => null,
                    'secret_access_key' => null,
                    'region' => null,
                    'bucket' => null,
                ],
                'env_reference' => ['AWS_ACCESS_KEY_ID', 'AWS_SECRET_ACCESS_KEY', 'AWS_DEFAULT_REGION', 'AWS_BUCKET'],
            ],
            'smtp_mail' => [
                'name' => 'SMTP Mail',
                'category' => 'email',
                'auth_type' => 'password',
                'supports' => ['send_mail', 'reply_to'],
                'credential_template' => [
                    'mail_host' => null,
                    'mail_port' => null,
                    'mail_encryption' => null,
                    'mail_username' => null,
                    'mail_password' => null,
                    'mail_from_address' => null,
                    'mail_from_name' => null,
                ],
                'env_reference' => ['MAIL_HOST', 'MAIL_PORT', 'MAIL_ENCRYPTION', 'MAIL_USERNAME', 'MAIL_PASSWORD', 'MAIL_FROM_ADDRESS', 'MAIL_FROM_NAME'],
            ],
        ];

        foreach ($providers as $code => $provider) {
            DB::table('integration_providers')->updateOrInsert(
                ['code' => $code],
                [
                    'name' => $provider['name'],
                    'category' => $provider['category'],
                    'auth_type' => $provider['auth_type'],
                    'status' => 'active',
                    'metadata' => json_encode([
                        'supports' => $provider['supports'],
                        'credential_template' => $provider['credential_template'],
                        'env_reference' => $provider['env_reference'],
                        'tenant_managed' => true,
                    ]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
