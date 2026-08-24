<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\SeedsRecords;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DemoTenantSettingsSeeder extends Seeder
{
    use SeedsRecords;

    public function run(): void
    {
        $tenantId = (int) DB::table('tenants')->where('slug', DemoTenantFoundationSeeder::TENANT_SLUG)->value('id');
        if (! $tenantId) {
            return;
        }

        $ownerId = (int) DB::table('users')->where('tenant_id', $tenantId)->where('email', DemoTenantFoundationSeeder::OWNER_EMAIL)->value('id');

        $this->seedSettings($tenantId, $ownerId ?: null);
        $this->seedTenantLookups($tenantId);
        $this->seedNotificationTemplates($tenantId);
        $this->seedBackups($tenantId);
        $this->seedIntegrations($tenantId, $ownerId ?: null);
    }

    private function seedSettings(int $tenantId, ?int $ownerId): void
    {
        $settings = [
            'general' => [
                'workspace_name' => 'Technofra Demo CRM',
                'tenant_name' => 'Technofra Demo',
                'workspace_slug' => DemoTenantFoundationSeeder::TENANT_SLUG,
                'workspace_description' => 'Tenant workspace for CRM, ERP, HR, finance, support, and operations.',
                'website' => 'https://demo.technofra.example.test',
                'status' => 'active',
                'subscription_status' => 'trial',
                'billing_status' => 'paid',
            ],
            'company' => [
                'legal_company_name' => 'Technofra Demo CRM Private Limited',
                'trade_name' => 'Technofra Demo',
                'registration_number' => 'U72900GJ2026PTC000001',
                'gst_number' => '24ABCDE1234F1Z7',
                'pan_number' => 'ABCDE1234F',
                'tax_id' => 'TAX-TF-DEMO-001',
                'industry' => 'technology',
                'company_size' => '11-50',
                'business_type' => 'private',
                'founded_date' => '2021-04-12',
                'company_email' => 'accounts.demo@example.test',
                'phone' => '+91 99990 00001',
                'alternate_phone' => '+91 99990 00002',
                'address_line_1' => 'C G Road',
                'address_line_2' => 'Near Commerce Six Roads',
                'country' => 'India',
                'state' => 'Gujarat',
                'city' => 'Ahmedabad',
                'postal_code' => '380009',
            ],
            'branding' => [
                'light_logo' => 'tenant-assets/technofra/logo-light.svg',
                'dark_logo' => 'tenant-assets/technofra/logo-dark.svg',
                'favicon' => 'tenant-assets/technofra/favicon.ico',
                'primary_color' => '#2563eb',
                'secondary_color' => '#006d77',
                'accent_color' => '#16a34a',
                'custom_domain' => 'crm.demo.technofra.example.test',
                'dns_status' => 'verified',
            ],
            'localization' => [
                'language' => 'en',
                'timezone' => 'Asia/Kolkata',
                'currency' => 'INR',
                'date_format' => 'DD MMM YYYY',
                'time_format' => '12-hour',
                'week_start' => 'monday',
                'number_format' => '1,234.56',
            ],
            'communication' => [
                'sender_name' => 'Technofra Demo CRM',
                'sender_email' => 'notifications.demo@example.test',
                'reply_to_email' => 'support.demo@example.test',
                'email_notifications' => true,
                'sms_notifications' => false,
                'whatsapp_notifications' => true,
                'push_notifications' => true,
            ],
            'security' => [
                'two_factor_required' => false,
                'password_min_length' => 10,
                'require_uppercase' => true,
                'require_number' => true,
                'require_special_character' => true,
                'password_expiry_days' => 90,
                'session_timeout_minutes' => 60,
                'remember_me_days' => 14,
                'maximum_devices' => 5,
            ],
            'storage' => [
                'storage_limit_gb' => 50,
                'storage_used_gb' => 12.4,
                'retention_days' => 30,
                'backup_enabled' => true,
                'backup_frequency' => 'daily',
            ],
            'hr' => [
                'work_start_time' => '09:30',
                'work_end_time' => '18:30',
                'working_days' => 'monday-friday',
                'annual_leave_days' => 18,
                'sick_leave_days' => 12,
                'casual_leave_days' => 6,
                'late_mark_grace_minutes' => 15,
                'overtime_enabled' => false,
                'employee_number_format' => 'EMP-00001',
            ],
            'crm' => [
                'default_lead_pipeline' => 'sales',
                'lead_stages' => 'New, Contacted, Qualified, Proposal, Won, Lost',
                'lookup_management' => 'Lead Source, Customer Type, Industry, Priority',
            ],
            'integrations' => [
                'webhook_retry_attempts' => 3,
                'sync_retry_attempts' => 3,
                'default_timeout_seconds' => 15,
            ],
        ];

        foreach ($settings as $group => $values) {
            foreach ($values as $key => $value) {
                $this->seedRecord('tenant_settings', ['tenant_id' => $tenantId, 'group' => $group, 'key' => $key], [
                    'value' => json_encode($value),
                    'value_type' => gettype($value),
                    'is_encrypted' => false,
                    'updated_by' => $ownerId,
                ]);
            }
        }
    }

    private function seedTenantLookups(int $tenantId): void
    {
        $groups = [
            'lead_source' => [['website', 'Website'], ['referral', 'Referral'], ['linkedin', 'LinkedIn'], ['event', 'Event']],
            'customer_type' => [['enterprise', 'Enterprise'], ['smb', 'SMB'], ['startup', 'Startup'], ['government', 'Government']],
            'industry' => [['technology', 'Technology'], ['manufacturing', 'Manufacturing'], ['retail', 'Retail'], ['professional_services', 'Professional Services']],
            'priority' => [['low', 'Low'], ['medium', 'Medium'], ['high', 'High'], ['urgent', 'Urgent']],
            'lead_stage' => [['new', 'New'], ['contacted', 'Contacted'], ['qualified', 'Qualified'], ['proposal', 'Proposal'], ['won', 'Won'], ['lost', 'Lost']],
        ];

        foreach ($groups as $group => $items) {
            foreach ($items as $index => [$code, $name]) {
                $this->seedRecord('tenant_lookups', ['tenant_id' => $tenantId, 'group' => $group, 'code' => $code], [
                    'name' => $name,
                    'sort_order' => $index + 1,
                    'is_default' => $index === 0,
                    'is_system' => false,
                    'status' => 'active',
                ], true);
            }
        }
    }

    private function seedNotificationTemplates(int $tenantId): void
    {
        $templates = [
            ['welcome_email', 'email', 'Welcome to {{workspace_name}}', 'Hello {{name}}, your workspace access is ready.', ['workspace_name', 'name']],
            ['password_reset', 'email', 'Reset your workspace password', 'Use {{reset_url}} to reset your password before {{expires_at}}.', ['reset_url', 'expires_at']],
            ['invoice', 'email', 'Invoice {{invoice_number}} is ready', 'Invoice {{invoice_number}} for {{amount}} is attached.', ['invoice_number', 'amount']],
            ['hr_announcement', 'email', 'HR announcement: {{title}}', '{{body}}', ['title', 'body']],
        ];

        foreach ($templates as [$code, $channel, $subject, $body, $variables]) {
            $this->seedRecord('notification_templates', ['tenant_id' => $tenantId, 'code' => $code, 'channel' => $channel], [
                'subject' => $subject,
                'body' => $body,
                'variables' => json_encode($variables),
                'status' => 'active',
            ], true);
        }
    }

    private function seedBackups(int $tenantId): void
    {
        $runs = [
            ['scheduled', 'completed', now()->subDay()->setTime(1, 0), now()->subDay()->setTime(1, 8)],
            ['manual', 'completed', now()->subDays(3)->setTime(16, 30), now()->subDays(3)->setTime(16, 39)],
            ['scheduled', 'failed', now()->subDays(7)->setTime(1, 0), now()->subDays(7)->setTime(1, 4)],
        ];

        foreach ($runs as [$type, $status, $started, $finished]) {
            $this->seedRecord('tenant_backup_runs', ['tenant_id' => $tenantId, 'backup_type' => $type, 'started_at' => $started], [
                'status' => $status,
                'finished_at' => $finished,
                'error_message' => $status === 'failed' ? 'Storage provider timeout during upload finalization.' : null,
            ], true);
        }
    }

    private function seedIntegrations(int $tenantId, ?int $ownerId): void
    {
        $connections = [
            ['google_calendar', 'Google Calendar', 'active'],
            ['slack', 'Slack Notifications', 'active'],
            ['razorpay', 'Razorpay Payments', 'disconnected'],
        ];

        foreach ($connections as [$providerCode, $name, $status]) {
            $providerId = (int) DB::table('integration_providers')->where('code', $providerCode)->value('id');
            if (! $providerId) {
                continue;
            }

            $integrationId = $this->seedRecord('tenant_integrations', ['tenant_id' => $tenantId, 'provider_id' => $providerId, 'name' => $name], [
                'status' => $status,
                'connected_by' => $ownerId,
                'connected_at' => $status === 'active' ? now()->subDays(4) : null,
            ], true);

            $this->seedRecord('integration_credentials', ['tenant_integration_id' => $integrationId, 'key' => 'api_key'], [
                'encrypted_value' => Crypt::encryptString('demo-'.Str::slug($providerCode).'-secret'),
                'expires_at' => now()->addDays(90),
            ]);

            $this->seedRecord('integration_webhooks', ['tenant_integration_id' => $integrationId, 'event' => 'invoice.paid'], [
                'secret_hash' => hash('sha256', 'invoice.paid-'.$integrationId),
                'status' => $status === 'active' ? 'active' : 'paused',
            ]);

            $this->seedRecord('integration_sync_jobs', ['tenant_integration_id' => $integrationId, 'sync_type' => 'contacts', 'started_at' => now()->subHours(8)], [
                'direction' => 'outbound',
                'status' => $status === 'active' ? 'completed' : 'skipped',
                'finished_at' => now()->subHours(8)->addMinutes(3),
            ]);
        }
    }
}
