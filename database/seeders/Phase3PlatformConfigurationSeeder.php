<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\SeedsRecords;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class Phase3PlatformConfigurationSeeder extends Seeder
{
    use SeedsRecords;

    public function run(): void
    {
        $actorId = (int) DB::table('platform_users')
            ->where('email', env('PLATFORM_SUPER_ADMIN_EMAIL', 'support@technofra.com'))
            ->value('id');

        $moduleIds = $this->seedModules();
        $settingIds = $this->seedPlatformSettings($actorId);
        $providerIds = $this->seedIntegrationProviders();
        $templateIds = $this->seedPlatformTemplates();

        $this->seedActivityRows('modules', $moduleIds, $actorId, 'Seeded fixed platform module catalog.');
        $this->seedActivityRows('platform_settings', $settingIds, $actorId, 'Seeded fixed platform super-admin settings.');
        $this->seedActivityRows('integration_providers', $providerIds, $actorId, 'Seeded fixed platform integration provider catalog.');
        $this->seedActivityRows('notification_templates', $templateIds, $actorId, 'Seeded fixed platform notification templates.');
    }

    /** @return array<string, int> */
    private function seedModules(): array
    {
        $modules = [
            ['dashboard', 'Dashboard', 'Platform and tenant dashboard summaries.', 'layout-dashboard', 'core', true],
            ['tenants', 'Tenant Management', 'Tenant onboarding, lifecycle, offices, and account controls.', 'building-2', 'platform', true],
            ['platform_users', 'Platform Users', 'Platform staff, access, and security.', 'users-round', 'platform', true],
            ['platform_teams', 'Platform Teams', 'Platform team roles, members, and assignments.', 'network', 'platform', true],
            ['rbac', 'RBAC', 'Roles, permissions, and access control.', 'shield-check', 'security', true],
            ['billing', 'Billing', 'Subscriptions, invoices, payments, refunds, and coupons.', 'receipt', 'commerce', true],
            ['catalog', 'Plan Catalog', 'Plans, add-ons, feature entitlements, and modules.', 'package', 'commerce', true],
            ['crm', 'CRM', 'Clients, vendors, leads, contacts, and party profiles.', 'briefcase-business', 'tenant', true],
            ['projects', 'Projects', 'Projects, phases, milestones, tasks, and work logs.', 'kanban-square', 'tenant', true],
            ['finance', 'Finance', 'Tenant invoices, payments, expenses, and bank accounts.', 'landmark', 'tenant', true],
            ['hrms', 'HRMS', 'Staff, departments, teams, attendance, leave, and documents.', 'id-card', 'tenant', true],
            ['payroll', 'Payroll', 'Payroll cycles, components, approvals, payslips, and tax settings.', 'badge-indian-rupee', 'tenant', true],
            ['calendar', 'Calendar', 'Calendars, events, meeting rooms, reminders, and video meetings.', 'calendar-days', 'tenant', true],
            ['documents', 'Documents', 'Files, folders, attachments, notes, tags, and custom fields.', 'folder-open', 'shared', true],
            ['support', 'Support Desk', 'Client issues, platform tickets, comments, and knowledge base.', 'life-buoy', 'support', true],
            ['integrations', 'Integrations', 'Provider catalog, tenant integrations, webhooks, mappings, and sync jobs.', 'plug-zap', 'platform', true],
            ['notifications', 'Notifications', 'Notification templates, communication logs, and delivery records.', 'bell', 'shared', true],
            ['monitoring', 'Monitoring', 'Service health, alerts, incidents, queues, scheduler, and API logs.', 'activity', 'operations', true],
            ['reports', 'Reports', 'Report exports, analytics, audit exports, and operational summaries.', 'chart-no-axes-combined', 'operations', true],
            ['settings', 'Settings', 'Platform settings, tenant settings, preferences, and policy defaults.', 'settings', 'configuration', true],
            ['security', 'Security', 'Security events, API tokens, remote login sessions, and audit retention.', 'lock-keyhole', 'security', true],
            ['backup', 'Backup And Restore', 'Platform and tenant backup runs, restore requests, and retention policy.', 'database-backup', 'operations', true],
            ['onboarding', 'Onboarding', 'Onboarding checklists, tenant steps, legal acceptances, and announcements.', 'list-checks', 'platform', true],
        ];

        $ids = [];
        foreach ($modules as $index => [$code, $name, $description, $icon, $category, $isCore]) {
            $ids[$code] = $this->seedRecord('modules', ['code' => $code], [
                'name' => $name,
                'description' => $description,
                'icon' => $icon,
                'category' => $category,
                'is_core' => $isCore,
                'status' => 'active',
                'sort_order' => $index + 1,
            ], true);
        }

        return $ids;
    }

    /** @return array<string, int> */
    private function seedPlatformSettings(int $actorId): array
    {
        $settings = [
            ['general', 'app_name', 'MyCRM SaaS', 'string'],
            ['general', 'default_locale', 'en', 'string'],
            ['general', 'default_timezone', 'Asia/Kolkata', 'string'],
            ['general', 'default_currency', 'INR', 'string'],
            ['billing', 'trial_days', 14, 'integer'],
            ['billing', 'invoice_due_days', 7, 'integer'],
            ['billing', 'tax_percentage', 18, 'decimal'],
            ['billing', 'grace_period_days', 7, 'integer'],
            ['billing', 'auto_suspend_after_days', 15, 'integer'],
            ['security', 'password_min_length', 8, 'integer'],
            ['security', 'require_2fa_for_platform_users', false, 'boolean'],
            ['security', 'session_timeout_minutes', 120, 'integer'],
            ['security', 'api_token_default_expiry_days', 90, 'integer'],
            ['security', 'remote_login_max_minutes', 30, 'integer'],
            ['audit', 'retention_days', 365, 'integer'],
            ['audit', 'export_max_rows', 5000, 'integer'],
            ['backup', 'platform_retention_days', 30, 'integer'],
            ['backup', 'tenant_retention_days', 14, 'integer'],
            ['backup', 'daily_backup_enabled', true, 'boolean'],
            ['communication', 'default_email_provider', 'smtp', 'string'],
            ['communication', 'system_from_email', 'noreply@example.test', 'string'],
            ['communication', 'system_from_name', 'MyCRM Platform', 'string'],
            ['integrations', 'webhook_retry_attempts', 3, 'integer'],
            ['integrations', 'webhook_timeout_seconds', 10, 'integer'],
            ['integrations', 'sync_job_retry_attempts', 3, 'integer'],
            ['monitoring', 'default_check_interval_seconds', 60, 'integer'],
            ['monitoring', 'alert_auto_resolve_minutes', 15, 'integer'],
            ['onboarding', 'default_checklist_enabled', true, 'boolean'],
            ['support', 'default_ticket_priority', 'medium', 'string'],
            ['support', 'sla_first_response_hours', 4, 'integer'],
        ];

        $ids = [];
        foreach ($settings as [$group, $key, $value, $type]) {
            $ids[$group.'.'.$key] = $this->seedRecord('platform_settings', ['group' => $group, 'key' => $key], [
                'value' => json_encode($value),
                'value_type' => $type,
                'is_encrypted' => false,
                'updated_by' => $actorId ?: null,
            ]);
        }

        DB::table('backup_settings')->updateOrInsert(['key' => 'platform_retention_days'], [
            'value' => json_encode(30),
            'updated_by' => $actorId ?: null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('backup_settings')->updateOrInsert(['key' => 'tenant_retention_days'], [
            'value' => json_encode(14),
            'updated_by' => $actorId ?: null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('backup_settings')->updateOrInsert(['key' => 'daily_backup_enabled'], [
            'value' => json_encode(true),
            'updated_by' => $actorId ?: null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $ids;
    }

    /** @return array<string, int> */
    private function seedIntegrationProviders(): array
    {
        $providers = [
            ['Razorpay', 'razorpay', 'payment', 'api_key', ['supports' => ['payments', 'refunds', 'webhooks']]],
            ['Stripe', 'stripe', 'payment', 'api_key', ['supports' => ['payments', 'subscriptions', 'refunds', 'webhooks']]],
            ['PayPal', 'paypal', 'payment', 'oauth2', ['supports' => ['payments', 'refunds']]],
            ['Gmail', 'gmail', 'email', 'oauth2', ['supports' => ['send_mail', 'read_mail']]],
            ['Google Calendar', 'google_calendar', 'calendar', 'oauth2', ['supports' => ['events', 'attendees', 'sync']]],
            ['Google Drive', 'google_drive', 'storage', 'oauth2', ['supports' => ['files', 'folders']]],
            ['Microsoft Outlook Mail', 'outlook_mail', 'email', 'oauth2', ['supports' => ['send_mail', 'read_mail']]],
            ['Microsoft Outlook Calendar', 'outlook_calendar', 'calendar', 'oauth2', ['supports' => ['events', 'attendees', 'sync']]],
            ['Microsoft OneDrive', 'onedrive', 'storage', 'oauth2', ['supports' => ['files', 'folders']]],
            ['Slack', 'slack', 'communication', 'oauth2', ['supports' => ['messages', 'notifications']]],
            ['Microsoft Teams', 'microsoft_teams', 'communication', 'oauth2', ['supports' => ['messages', 'meetings', 'notifications']]],
            ['WhatsApp Business Cloud', 'whatsapp_business', 'messaging', 'bearer_token', ['supports' => ['messages', 'templates', 'webhooks']]],
            ['Twilio SMS', 'twilio_sms', 'messaging', 'api_key', ['supports' => ['sms', 'delivery_status']]],
            ['SendGrid', 'sendgrid', 'email', 'api_key', ['supports' => ['send_mail', 'templates', 'webhooks']]],
            ['Mailgun', 'mailgun', 'email', 'api_key', ['supports' => ['send_mail', 'events', 'webhooks']]],
            ['Zoho Books', 'zoho_books', 'accounting', 'oauth2', ['supports' => ['invoices', 'contacts', 'payments']]],
            ['QuickBooks Online', 'quickbooks_online', 'accounting', 'oauth2', ['supports' => ['invoices', 'customers', 'payments']]],
            ['TallyPrime', 'tally_prime', 'accounting', 'connector', ['supports' => ['ledgers', 'vouchers', 'sync']]],
            ['GitHub', 'github', 'developer', 'oauth2', ['supports' => ['issues', 'repositories', 'webhooks']]],
            ['Generic Webhook', 'generic_webhook', 'webhook', 'secret', ['supports' => ['inbound_webhooks', 'outbound_webhooks']]],
        ];

        $ids = [];
        foreach ($providers as [$name, $code, $category, $authType, $metadata]) {
            $ids[$code] = $this->seedRecord('integration_providers', ['code' => $code], [
                'name' => $name,
                'category' => $category,
                'auth_type' => $authType,
                'status' => 'active',
                'metadata' => json_encode([
                    ...$metadata,
                    'credential_template' => $this->credentialTemplate($authType),
                    'field_mapping_template' => $this->fieldMappingTemplate($category),
                    'webhook_events' => $this->webhookEvents($category),
                ]),
            ]);
        }

        return $ids;
    }

    /** @return array<string, int> */
    private function seedPlatformTemplates(): array
    {
        $templates = [
            ['platform_user_welcome', 'email', 'Welcome to MyCRM Platform', 'Hello {{name}}, your platform account is ready. Sign in using {{login_url}}.', ['name', 'login_url']],
            ['platform_password_reset', 'email', 'Reset your MyCRM Platform password', 'Use this link to reset your password: {{reset_url}}. It expires at {{expires_at}}.', ['reset_url', 'expires_at']],
            ['tenant_trial_started', 'email', 'Your MyCRM trial has started', 'Hello {{tenant_name}}, your trial is active until {{trial_ends_at}}.', ['tenant_name', 'trial_ends_at']],
            ['tenant_trial_ending', 'email', 'Your MyCRM trial is ending soon', 'Hello {{tenant_name}}, your trial ends on {{trial_ends_at}}.', ['tenant_name', 'trial_ends_at']],
            ['subscription_activated', 'email', 'Subscription activated', '{{tenant_name}} is now active on the {{plan_name}} plan.', ['tenant_name', 'plan_name']],
            ['subscription_payment_failed', 'email', 'Payment failed', 'Payment for {{tenant_name}} failed. Invoice: {{invoice_number}}.', ['tenant_name', 'invoice_number']],
            ['platform_invoice_generated', 'email', 'Platform invoice generated', 'Invoice {{invoice_number}} for {{tenant_name}} is ready.', ['invoice_number', 'tenant_name']],
            ['support_ticket_created', 'email', 'Support ticket created', 'Ticket {{ticket_number}} was created for {{tenant_name}}.', ['ticket_number', 'tenant_name']],
            ['support_ticket_assigned', 'email', 'Support ticket assigned', 'Ticket {{ticket_number}} is assigned to {{assignee_name}}.', ['ticket_number', 'assignee_name']],
            ['incident_created', 'email', 'Platform incident created', '{{incident_title}} has been marked {{severity}}.', ['incident_title', 'severity']],
            ['incident_resolved', 'email', 'Platform incident resolved', '{{incident_title}} has been resolved.', ['incident_title']],
            ['backup_completed', 'email', 'Backup completed', '{{backup_type}} backup completed at {{finished_at}}.', ['backup_type', 'finished_at']],
            ['backup_failed', 'email', 'Backup failed', '{{backup_type}} backup failed. Reason: {{error_message}}.', ['backup_type', 'error_message']],
            ['integration_connected', 'email', 'Integration connected', '{{provider_name}} was connected for {{tenant_name}}.', ['provider_name', 'tenant_name']],
            ['integration_sync_failed', 'email', 'Integration sync failed', '{{provider_name}} sync failed for {{tenant_name}}.', ['provider_name', 'tenant_name']],
            ['webhook_delivery_failed', 'email', 'Webhook delivery failed', 'Webhook {{endpoint_name}} failed for event {{event}}.', ['endpoint_name', 'event']],
            ['legal_document_published', 'email', 'Legal document updated', '{{document_title}} version {{version}} has been published.', ['document_title', 'version']],
            ['announcement_published', 'email', 'Platform announcement', '{{title}}: {{summary}}', ['title', 'summary']],
        ];

        $ids = [];
        foreach ($templates as [$code, $channel, $subject, $body, $variables]) {
            $ids[$code] = $this->seedRecord('notification_templates', ['tenant_id' => null, 'code' => $code, 'channel' => $channel], [
                'subject' => $subject,
                'body' => $body,
                'variables' => json_encode($variables),
                'status' => 'active',
            ], true);
        }

        return $ids;
    }

    /** @return array<string, string|null> */
    private function credentialTemplate(string $authType): array
    {
        return match ($authType) {
            'oauth2' => ['client_id' => null, 'client_secret' => null, 'redirect_uri' => null, 'scopes' => null],
            'api_key' => ['api_key' => null, 'api_secret' => null],
            'bearer_token' => ['access_token' => null],
            'connector' => ['base_url' => null, 'username' => null, 'password' => null],
            default => ['secret' => null],
        };
    }

    /** @return list<array<string, string>> */
    private function fieldMappingTemplate(string $category): array
    {
        return match ($category) {
            'accounting' => [
                ['entity_type' => 'party', 'local_field' => 'display_name', 'external_field' => 'name'],
                ['entity_type' => 'tenant_invoice', 'local_field' => 'invoice_number', 'external_field' => 'invoice_number'],
            ],
            'calendar' => [
                ['entity_type' => 'calendar_event', 'local_field' => 'title', 'external_field' => 'summary'],
                ['entity_type' => 'calendar_event', 'local_field' => 'starts_at', 'external_field' => 'start.dateTime'],
            ],
            'email' => [
                ['entity_type' => 'communication_log', 'local_field' => 'subject', 'external_field' => 'subject'],
                ['entity_type' => 'communication_log', 'local_field' => 'body', 'external_field' => 'body'],
            ],
            default => [
                ['entity_type' => 'party', 'local_field' => 'email', 'external_field' => 'email'],
            ],
        };
    }

    /** @return list<string> */
    private function webhookEvents(string $category): array
    {
        return match ($category) {
            'payment' => ['payment.succeeded', 'payment.failed', 'refund.processed'],
            'email', 'messaging', 'communication' => ['message.sent', 'message.delivered', 'message.failed'],
            'accounting' => ['invoice.created', 'invoice.paid', 'customer.updated'],
            'developer', 'webhook' => ['record.created', 'record.updated', 'record.deleted'],
            default => ['sync.completed', 'sync.failed'],
        };
    }

    /** @param  array<string, int>  $ids */
    private function seedActivityRows(string $table, array $ids, int $actorId, string $description): void
    {
        foreach ($ids as $code => $id) {
            $this->seedRecord('activity_logs', [
                'tenant_id' => null,
                'subject_type' => $table,
                'subject_id' => $id,
                'event' => 'phase3.seeded',
            ], [
                'actor_platform_user_id' => $actorId ?: null,
                'description' => $description.' Code: '.$code,
                'new_values' => json_encode(['phase' => 3, 'table' => $table, 'code' => $code]),
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Phase3PlatformConfigurationSeeder',
                'created_at' => now(),
            ]);
        }
    }
}
