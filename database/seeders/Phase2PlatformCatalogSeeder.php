<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\SeedsRecords;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class Phase2PlatformCatalogSeeder extends Seeder
{
    use SeedsRecords;

    public function run(): void
    {
        $actorId = (int) DB::table('platform_users')->where('email', env('PLATFORM_SUPER_ADMIN_EMAIL', 'support@technofra.com'))->value('id');

        $addonIds = $this->seedAddons($actorId);
        $featureIds = $this->seedFeatures($actorId);
        $planIds = $this->seedPlans($featureIds, $actorId);

        $this->seedCatalogActivity('addon_plans', $addonIds, $actorId, 'Seeded phase 2 platform add-on catalog.');
        $this->seedCatalogActivity('features', $featureIds, $actorId, 'Seeded phase 2 platform feature catalog.');
        $this->seedCatalogActivity('plans', $planIds, $actorId, 'Seeded phase 2 platform plan catalog.');
    }

    /** @return array<string, int> */
    private function seedAddons(int $actorId): array
    {
        $addons = [
            ['Extra 10 Users', 'extra_10_users', 'recurring', 499],
            ['Extra 25 Users', 'extra_25_users', 'recurring', 999],
            ['Extra 100 Users', 'extra_100_users', 'recurring', 2999],
            ['Extra 100GB Storage', 'extra_100gb_storage', 'recurring', 799],
            ['Extra 500GB Storage', 'extra_500gb_storage', 'recurring', 2999],
            ['Payroll Module', 'payroll_module', 'recurring', 1499],
            ['Priority Support', 'priority_support', 'recurring', 1999],
            ['Dedicated Account Manager', 'dedicated_account_manager', 'recurring', 4999],
            ['Advanced Analytics', 'advanced_analytics', 'recurring', 2499],
            ['API Burst Pack', 'api_burst_pack', 'one_time', 999],
            ['White Label Branding', 'white_label_branding', 'recurring', 3999],
            ['Custom Domain', 'custom_domain', 'recurring', 699],
            ['Data Migration Pack', 'data_migration_pack', 'one_time', 7999],
            ['Sandbox Workspace', 'sandbox_workspace', 'recurring', 1999],
            ['Compliance Archive', 'compliance_archive', 'recurring', 2499],
        ];

        $ids = [];
        foreach ($addons as [$name, $code, $pricingType, $price]) {
            $ids[$code] = $this->seedRecord('addon_plans', ['code' => $code], [
                'name' => $name,
                'pricing_type' => $pricingType,
                'price' => $price,
                'currency' => 'INR',
                'status' => 'active',
            ], true);
        }

        return $ids;
    }

    /** @return array<string, int> */
    private function seedFeatures(int $actorId): array
    {
        $features = [
            ['users', 'Tenant Users', 'users.limit', 'integer', 'users'],
            ['storage', 'Storage', 'storage.gb', 'integer', 'gb'],
            ['projects', 'Projects', 'projects.limit', 'integer', 'projects'],
            ['clients', 'Clients', 'clients.limit', 'integer', 'clients'],
            ['vendors', 'Vendors', 'vendors.limit', 'integer', 'vendors'],
            ['leads', 'Leads', 'leads.limit', 'integer', 'leads'],
            ['finance', 'Finance Module', 'module.finance', 'boolean', null],
            ['hrms', 'HRMS Module', 'module.hrms', 'boolean', null],
            ['payroll', 'Payroll Module', 'module.payroll', 'boolean', null],
            ['projects', 'Project Management Module', 'module.projects', 'boolean', null],
            ['support', 'Support Desk Module', 'module.support', 'boolean', null],
            ['api', 'API Requests', 'api.requests.monthly', 'integer', 'requests'],
            ['support', 'Priority Support', 'support.priority', 'boolean', null],
            ['integrations', 'Integrations', 'integrations.limit', 'integer', 'connections'],
            ['automation', 'Automation Workflows', 'automation.workflows', 'integer', 'workflows'],
            ['reports', 'Advanced Reports', 'module.advanced_reports', 'boolean', null],
            ['security', 'Audit Retention', 'audit.retention.days', 'integer', 'days'],
            ['backup', 'Backup Retention', 'backup.retention.days', 'integer', 'days'],
            ['branding', 'White Label Branding', 'module.white_label', 'boolean', null],
            ['workspaces', 'Sandbox Workspaces', 'sandbox.workspaces', 'integer', 'workspaces'],
        ];

        $ids = [];
        foreach ($features as [$module, $name, $code, $dataType, $unit]) {
            $ids[$code] = $this->seedRecord('features', ['code' => $code], [
                'module' => $module,
                'name' => $name,
                'data_type' => $dataType,
                'unit' => $unit,
                'description' => $name.' entitlement for platform plans.',
                'status' => 'active',
            ], true);
        }

        return $ids;
    }

    /**
     * @param  array<string, int>  $featureIds
     * @return array<string, int>
     */
    private function seedPlans(array $featureIds, int $actorId): array
    {
        $plans = [
            ['Starter', 'starter', 'For small teams starting with CRM basics.', 'monthly', 999, 14, ['users.limit' => 5, 'storage.gb' => 5, 'projects.limit' => 25, 'clients.limit' => 250, 'vendors.limit' => 50, 'leads.limit' => 500, 'module.finance' => 0, 'module.hrms' => 0, 'module.payroll' => 0, 'module.projects' => 1, 'module.support' => 0, 'api.requests.monthly' => 10000, 'support.priority' => 0, 'integrations.limit' => 1, 'automation.workflows' => 2, 'module.advanced_reports' => 0, 'audit.retention.days' => 30, 'backup.retention.days' => 7, 'module.white_label' => 0, 'sandbox.workspaces' => 0]],
            ['Growth', 'growth', 'For growing teams with projects and finance.', 'monthly', 2999, 14, ['users.limit' => 25, 'storage.gb' => 50, 'projects.limit' => 250, 'clients.limit' => 2500, 'vendors.limit' => 500, 'leads.limit' => 5000, 'module.finance' => 1, 'module.hrms' => 1, 'module.payroll' => 0, 'module.projects' => 1, 'module.support' => 1, 'api.requests.monthly' => 100000, 'support.priority' => 0, 'integrations.limit' => 5, 'automation.workflows' => 15, 'module.advanced_reports' => 1, 'audit.retention.days' => 90, 'backup.retention.days' => 30, 'module.white_label' => 0, 'sandbox.workspaces' => 0]],
            ['Enterprise', 'enterprise', 'For larger organizations requiring advanced modules and priority support.', 'monthly', 9999, 30, ['users.limit' => 250, 'storage.gb' => 500, 'projects.limit' => 5000, 'clients.limit' => 100000, 'vendors.limit' => 25000, 'leads.limit' => 200000, 'module.finance' => 1, 'module.hrms' => 1, 'module.payroll' => 1, 'module.projects' => 1, 'module.support' => 1, 'api.requests.monthly' => 1000000, 'support.priority' => 1, 'integrations.limit' => 25, 'automation.workflows' => 100, 'module.advanced_reports' => 1, 'audit.retention.days' => 365, 'backup.retention.days' => 90, 'module.white_label' => 1, 'sandbox.workspaces' => 2]],
            ['Solo', 'solo', 'For one-person businesses.', 'monthly', 499, 7, ['users.limit' => 1, 'storage.gb' => 2, 'projects.limit' => 10, 'clients.limit' => 100, 'vendors.limit' => 25, 'leads.limit' => 200, 'module.finance' => 0, 'module.hrms' => 0, 'module.payroll' => 0, 'module.projects' => 1, 'module.support' => 0, 'api.requests.monthly' => 2500, 'support.priority' => 0, 'integrations.limit' => 0, 'automation.workflows' => 1, 'module.advanced_reports' => 0, 'audit.retention.days' => 15, 'backup.retention.days' => 7, 'module.white_label' => 0, 'sandbox.workspaces' => 0]],
            ['Team', 'team', 'For compact internal teams.', 'monthly', 1999, 14, ['users.limit' => 15, 'storage.gb' => 25, 'projects.limit' => 100, 'clients.limit' => 1000, 'vendors.limit' => 250, 'leads.limit' => 2500, 'module.finance' => 1, 'module.hrms' => 0, 'module.payroll' => 0, 'module.projects' => 1, 'module.support' => 1, 'api.requests.monthly' => 50000, 'support.priority' => 0, 'integrations.limit' => 3, 'automation.workflows' => 8, 'module.advanced_reports' => 0, 'audit.retention.days' => 60, 'backup.retention.days' => 14, 'module.white_label' => 0, 'sandbox.workspaces' => 0]],
            ['Business', 'business', 'For standard business operations.', 'monthly', 4999, 14, ['users.limit' => 50, 'storage.gb' => 100, 'projects.limit' => 750, 'clients.limit' => 10000, 'vendors.limit' => 2500, 'leads.limit' => 25000, 'module.finance' => 1, 'module.hrms' => 1, 'module.payroll' => 1, 'module.projects' => 1, 'module.support' => 1, 'api.requests.monthly' => 250000, 'support.priority' => 0, 'integrations.limit' => 10, 'automation.workflows' => 40, 'module.advanced_reports' => 1, 'audit.retention.days' => 180, 'backup.retention.days' => 45, 'module.white_label' => 0, 'sandbox.workspaces' => 1]],
            ['Business Plus', 'business_plus', 'For businesses needing heavier automation.', 'monthly', 6999, 21, ['users.limit' => 100, 'storage.gb' => 200, 'projects.limit' => 1500, 'clients.limit' => 25000, 'vendors.limit' => 5000, 'leads.limit' => 50000, 'module.finance' => 1, 'module.hrms' => 1, 'module.payroll' => 1, 'module.projects' => 1, 'module.support' => 1, 'api.requests.monthly' => 500000, 'support.priority' => 1, 'integrations.limit' => 15, 'automation.workflows' => 75, 'module.advanced_reports' => 1, 'audit.retention.days' => 270, 'backup.retention.days' => 60, 'module.white_label' => 0, 'sandbox.workspaces' => 1]],
            ['Enterprise Plus', 'enterprise_plus', 'For high-scale enterprise teams.', 'monthly', 14999, 30, ['users.limit' => 500, 'storage.gb' => 1000, 'projects.limit' => 10000, 'clients.limit' => 250000, 'vendors.limit' => 50000, 'leads.limit' => 500000, 'module.finance' => 1, 'module.hrms' => 1, 'module.payroll' => 1, 'module.projects' => 1, 'module.support' => 1, 'api.requests.monthly' => 2500000, 'support.priority' => 1, 'integrations.limit' => 50, 'automation.workflows' => 250, 'module.advanced_reports' => 1, 'audit.retention.days' => 730, 'backup.retention.days' => 180, 'module.white_label' => 1, 'sandbox.workspaces' => 5]],
            ['Startup Annual', 'startup_annual', 'Annual plan for startup teams.', 'annual', 9999, 14, ['users.limit' => 10, 'storage.gb' => 20, 'projects.limit' => 100, 'clients.limit' => 1000, 'vendors.limit' => 200, 'leads.limit' => 2000, 'module.finance' => 1, 'module.hrms' => 0, 'module.payroll' => 0, 'module.projects' => 1, 'module.support' => 1, 'api.requests.monthly' => 50000, 'support.priority' => 0, 'integrations.limit' => 3, 'automation.workflows' => 10, 'module.advanced_reports' => 0, 'audit.retention.days' => 60, 'backup.retention.days' => 30, 'module.white_label' => 0, 'sandbox.workspaces' => 0]],
            ['Growth Annual', 'growth_annual', 'Annual growth plan.', 'annual', 29990, 14, ['users.limit' => 25, 'storage.gb' => 75, 'projects.limit' => 300, 'clients.limit' => 3000, 'vendors.limit' => 750, 'leads.limit' => 7500, 'module.finance' => 1, 'module.hrms' => 1, 'module.payroll' => 0, 'module.projects' => 1, 'module.support' => 1, 'api.requests.monthly' => 125000, 'support.priority' => 0, 'integrations.limit' => 8, 'automation.workflows' => 25, 'module.advanced_reports' => 1, 'audit.retention.days' => 120, 'backup.retention.days' => 45, 'module.white_label' => 0, 'sandbox.workspaces' => 1]],
            ['Business Annual', 'business_annual', 'Annual business plan.', 'annual', 49990, 21, ['users.limit' => 75, 'storage.gb' => 200, 'projects.limit' => 1000, 'clients.limit' => 20000, 'vendors.limit' => 4000, 'leads.limit' => 40000, 'module.finance' => 1, 'module.hrms' => 1, 'module.payroll' => 1, 'module.projects' => 1, 'module.support' => 1, 'api.requests.monthly' => 350000, 'support.priority' => 1, 'integrations.limit' => 20, 'automation.workflows' => 100, 'module.advanced_reports' => 1, 'audit.retention.days' => 365, 'backup.retention.days' => 90, 'module.white_label' => 0, 'sandbox.workspaces' => 2]],
            ['Enterprise Annual', 'enterprise_annual', 'Annual enterprise plan.', 'annual', 99990, 30, ['users.limit' => 300, 'storage.gb' => 750, 'projects.limit' => 7500, 'clients.limit' => 150000, 'vendors.limit' => 30000, 'leads.limit' => 300000, 'module.finance' => 1, 'module.hrms' => 1, 'module.payroll' => 1, 'module.projects' => 1, 'module.support' => 1, 'api.requests.monthly' => 1500000, 'support.priority' => 1, 'integrations.limit' => 40, 'automation.workflows' => 200, 'module.advanced_reports' => 1, 'audit.retention.days' => 730, 'backup.retention.days' => 180, 'module.white_label' => 1, 'sandbox.workspaces' => 5]],
            ['Finance Suite', 'finance_suite', 'Focused plan for finance-heavy businesses.', 'monthly', 5999, 14, ['users.limit' => 40, 'storage.gb' => 100, 'projects.limit' => 250, 'clients.limit' => 15000, 'vendors.limit' => 7500, 'leads.limit' => 10000, 'module.finance' => 1, 'module.hrms' => 0, 'module.payroll' => 0, 'module.projects' => 1, 'module.support' => 1, 'api.requests.monthly' => 200000, 'support.priority' => 0, 'integrations.limit' => 12, 'automation.workflows' => 50, 'module.advanced_reports' => 1, 'audit.retention.days' => 365, 'backup.retention.days' => 60, 'module.white_label' => 0, 'sandbox.workspaces' => 1]],
            ['HR Payroll Suite', 'hr_payroll_suite', 'Focused plan for HR and payroll teams.', 'monthly', 6499, 14, ['users.limit' => 75, 'storage.gb' => 150, 'projects.limit' => 250, 'clients.limit' => 5000, 'vendors.limit' => 1000, 'leads.limit' => 5000, 'module.finance' => 1, 'module.hrms' => 1, 'module.payroll' => 1, 'module.projects' => 1, 'module.support' => 1, 'api.requests.monthly' => 200000, 'support.priority' => 0, 'integrations.limit' => 12, 'automation.workflows' => 60, 'module.advanced_reports' => 1, 'audit.retention.days' => 365, 'backup.retention.days' => 60, 'module.white_label' => 0, 'sandbox.workspaces' => 1]],
            ['Custom Enterprise', 'custom_enterprise', 'Private negotiated enterprise plan.', 'monthly', 0, 30, ['users.limit' => 1000, 'storage.gb' => 5000, 'projects.limit' => 50000, 'clients.limit' => 1000000, 'vendors.limit' => 250000, 'leads.limit' => 1000000, 'module.finance' => 1, 'module.hrms' => 1, 'module.payroll' => 1, 'module.projects' => 1, 'module.support' => 1, 'api.requests.monthly' => 10000000, 'support.priority' => 1, 'integrations.limit' => 250, 'automation.workflows' => 1000, 'module.advanced_reports' => 1, 'audit.retention.days' => 1095, 'backup.retention.days' => 365, 'module.white_label' => 1, 'sandbox.workspaces' => 10], true, false],
        ];

        $ids = [];
        foreach ($plans as $plan) {
            [$name, $code, $description, $billingCycle, $price, $trialDays, $features] = $plan;
            $isCustom = (bool) ($plan[7] ?? false);
            $isPublic = (bool) ($plan[8] ?? true);

            $planId = $this->seedRecord('plans', ['code' => $code], [
                'name' => $name,
                'description' => $description,
                'billing_cycle' => $billingCycle,
                'base_price' => $price,
                'currency' => 'INR',
                'trial_days' => $trialDays,
                'is_custom' => $isCustom,
                'is_public' => $isPublic,
                'status' => 'active',
            ], true);

            $ids[$code] = $planId;

            foreach ($features as $featureCode => $value) {
                if (! isset($featureIds[$featureCode])) {
                    continue;
                }

                $planFeatureId = $this->seedRecord('plan_features', ['plan_id' => $planId, 'feature_id' => $featureIds[$featureCode]], [
                    'value' => (string) $value,
                    'metadata' => json_encode(['phase' => 2, 'plan_code' => $code]),
                ]);

                $this->seedActivityLog('plan_features', $planFeatureId, $actorId, 'Attached phase 2 feature '.$featureCode.' to plan '.$code.'.');
            }
        }

        return $ids;
    }

    /** @param  array<string, int>  $ids */
    private function seedCatalogActivity(string $table, array $ids, int $actorId, string $description): void
    {
        foreach ($ids as $code => $id) {
            $this->seedActivityLog($table, $id, $actorId, $description.' Code: '.$code);
        }
    }

    private function seedActivityLog(string $subjectType, int $subjectId, int $actorId, string $description): void
    {
        $this->seedRecord('activity_logs', [
            'tenant_id' => null,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'event' => 'phase2.seeded',
        ], [
            'actor_platform_user_id' => $actorId ?: null,
            'description' => $description,
            'new_values' => json_encode(['phase' => 2, 'table' => $subjectType]),
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Phase2PlatformCatalogSeeder',
            'created_at' => now(),
        ]);
    }
}
