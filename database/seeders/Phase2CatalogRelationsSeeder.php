<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\SeedsRecords;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Completes the Phase 2 catalog after the original plans, features, and add-ons
 * have been seeded by Phase2PlatformCatalogSeeder.
 */
class Phase2CatalogRelationsSeeder extends Seeder
{
    use SeedsRecords;

    public function run(): void
    {
        $moduleIds = $this->seedModules();
        $addonIds = $this->prepareAddons();
        $planIds = DB::table('plans')->pluck('id', 'code')->all();

        $this->seedPlanAddons($planIds, $addonIds);
        $couponIds = $this->seedCoupons();
        $this->seedCouponPlanAssignments($couponIds, $planIds);
        $this->seedExistingTenantAssignments($couponIds);
    }

    /** @return array<string, int> */
    private function seedModules(): array
    {
        $modules = [
            ['CRM', 'crm', 'Clients, vendors, leads, contacts, and party profiles.', 'briefcase-business', 'tenant', true, 10],
            ['Projects', 'projects', 'Projects, phases, milestones, tasks, and work logs.', 'kanban-square', 'tenant', true, 20],
            ['Finance', 'finance', 'Tenant invoices, payments, expenses, and bank accounts.', 'landmark', 'tenant', true, 30],
            ['HRMS', 'hrms', 'Staff, departments, teams, attendance, leave, and documents.', 'id-card', 'tenant', true, 40],
            ['Payroll', 'payroll', 'Payroll cycles, components, approvals, payslips, and tax settings.', 'badge-indian-rupee', 'tenant', false, 50],
            ['Support Desk', 'support', 'Client issues, platform tickets, comments, and knowledge base.', 'life-buoy', 'support', false, 60],
            ['Integrations', 'integrations', 'Provider catalog, tenant integrations, webhooks, mappings, and sync jobs.', 'plug-zap', 'platform', false, 70],
            ['Automation', 'automation', 'Automation workflows and scheduled business actions.', 'workflow', 'platform', false, 80],
            ['Advanced Reports', 'reports', 'Advanced reporting, analytics, and exports.', 'chart-no-axes-combined', 'platform', false, 90],
            ['Security', 'security', 'Audit retention and security controls.', 'shield-check', 'platform', true, 100],
            ['Backup', 'backup', 'Backup storage and retention controls.', 'database-backup', 'platform', false, 110],
            ['White Label Branding', 'branding', 'White-label identity and branding controls.', 'palette', 'platform', false, 120],
            ['Sandbox Workspaces', 'workspaces', 'Additional isolated sandbox workspaces.', 'layout-dashboard', 'platform', false, 130],
            ['API', 'api', 'API request quotas and developer access.', 'code-xml', 'platform', true, 140],
            ['User Management', 'users', 'Tenant user capacity and account management.', 'users', 'tenant', true, 150],
            ['Storage', 'storage', 'Tenant file and document storage capacity.', 'hard-drive', 'tenant', true, 160],
            ['Client Management', 'clients', 'Client record capacity.', 'contact-round', 'tenant', true, 170],
            ['Vendor Management', 'vendors', 'Vendor record capacity.', 'truck', 'tenant', true, 180],
            ['Lead Management', 'leads', 'Lead record capacity and conversion.', 'funnel', 'tenant', true, 190],
        ];

        $ids = [];
        foreach ($modules as [$name, $code, $description, $icon, $category, $isCore, $sortOrder]) {
            $ids[$code] = $this->seedRecord('modules', ['code' => $code], [
                'name' => $name,
                'description' => $description,
                'icon' => $icon,
                'category' => $category,
                'is_core' => $isCore,
                'status' => 'active',
                'sort_order' => $sortOrder,
            ], true);
        }

        return $ids;
    }

    /** @return array<string, int> */
    private function prepareAddons(): array
    {
        $ids = DB::table('addon_plans')->pluck('id', 'code')->all();

        foreach ($ids as $code => $id) {
            $this->seedRecord('addon_plans', ['code' => $code], ['is_public' => true]);
        }

        return $ids;
    }

    /** @param array<string, int> $planIds @param array<string, int> $addonIds */
    private function seedPlanAddons(array $planIds, array $addonIds): void
    {
        $assignments = [
            'starter' => ['extra_10_users', 'extra_100gb_storage'],
            'growth' => ['extra_10_users', 'extra_25_users', 'extra_100gb_storage', 'priority_support'],
            'enterprise' => ['extra_25_users', 'extra_100_users', 'extra_500gb_storage', 'payroll_module', 'priority_support', 'dedicated_account_manager', 'advanced_analytics', 'white_label_branding', 'custom_domain', 'sandbox_workspace', 'compliance_archive'],
            'solo' => ['extra_10_users', 'extra_100gb_storage'],
            'team' => ['extra_10_users', 'extra_25_users', 'extra_100gb_storage', 'priority_support'],
            'business' => ['extra_10_users', 'extra_25_users', 'extra_100gb_storage', 'extra_500gb_storage', 'payroll_module', 'priority_support', 'advanced_analytics', 'custom_domain', 'sandbox_workspace'],
            'business_plus' => ['extra_25_users', 'extra_100_users', 'extra_500gb_storage', 'payroll_module', 'priority_support', 'dedicated_account_manager', 'advanced_analytics', 'custom_domain', 'sandbox_workspace', 'compliance_archive'],
            'enterprise_plus' => array_keys($addonIds),
            'startup_annual' => ['extra_10_users', 'extra_100gb_storage', 'priority_support'],
            'growth_annual' => ['extra_10_users', 'extra_25_users', 'extra_100gb_storage', 'priority_support', 'advanced_analytics'],
            'business_annual' => ['extra_25_users', 'extra_100_users', 'extra_500gb_storage', 'payroll_module', 'priority_support', 'advanced_analytics', 'custom_domain', 'sandbox_workspace'],
            'enterprise_annual' => array_keys($addonIds),
            'finance_suite' => ['extra_10_users', 'extra_25_users', 'extra_100gb_storage', 'advanced_analytics', 'priority_support'],
            'hr_payroll_suite' => ['extra_10_users', 'extra_25_users', 'extra_100gb_storage', 'payroll_module', 'priority_support'],
            'custom_enterprise' => array_keys($addonIds),
        ];

        foreach ($assignments as $planCode => $addonCodes) {
            if (! isset($planIds[$planCode])) {
                continue;
            }

            foreach ($addonCodes as $addonCode) {
                if (isset($addonIds[$addonCode])) {
                    $this->seedPivot('plan_addons', ['plan_id' => $planIds[$planCode], 'addon_plan_id' => $addonIds[$addonCode]]);
                }
            }
        }
    }

    /** @return array<string, int> */
    private function seedCoupons(): array
    {
        $coupons = [
            ['DEMO10', 'Demo Ten Percent', 'percent', 10, now()->subDay(), now()->addMonth(), 5],
            ['STARTER20', 'Starter Launch Discount', 'percent', 20, now()->subDay(), now()->addMonths(3), 100],
            ['ANNUAL15', 'Annual Plan Discount', 'percent', 15, now()->subDay(), now()->addYear(), 250],
            ['WELCOME500', 'New Tenant Welcome Credit', 'fixed', 500, now()->subDay(), now()->addMonths(6), 500],
        ];

        $ids = [];
        foreach ($coupons as [$code, $name, $discountType, $discountValue, $startsAt, $expiresAt, $maxRedemptions]) {
            $ids[$code] = $this->seedRecord('coupons', ['code' => $code], [
                'name' => $name,
                'discount_type' => $discountType,
                'discount_value' => $discountValue,
                'starts_at' => $startsAt,
                'expires_at' => $expiresAt,
                'max_redemptions' => $maxRedemptions,
                'status' => 'active',
            ], true);
        }

        return $ids;
    }

    /** @param array<string, int> $couponIds @param array<string, int> $planIds */
    private function seedCouponPlanAssignments(array $couponIds, array $planIds): void
    {
        foreach ($planIds as $planCode => $planId) {
            foreach ($couponIds as $couponCode => $couponId) {
                $allowed = $couponCode === 'STARTER20'
                    ? $planCode === 'starter'
                    : ($couponCode === 'ANNUAL15' ? str_ends_with($planCode, '_annual') : true);

                if ($allowed) {
                    $this->seedPivot('coupon_plan_assignments', ['coupon_id' => $couponId, 'plan_id' => $planId]);
                }
            }
        }
    }

    /** @param array<string, int> $couponIds */
    private function seedExistingTenantAssignments(array $couponIds): void
    {
        $tenantIds = DB::table('tenants')->pluck('id')->all();
        foreach ($tenantIds as $tenantId) {
            foreach (['DEMO10', 'WELCOME500'] as $code) {
                if (isset($couponIds[$code])) {
                    $this->seedPivot('coupon_tenant_assignments', ['coupon_id' => $couponIds[$code], 'tenant_id' => $tenantId]);
                }
            }
        }
    }
}
