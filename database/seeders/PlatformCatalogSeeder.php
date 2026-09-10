<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PlatformCatalogSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $this->seedModules();
            $plans = $this->seedPlans();
            $features = $this->seedFeatures();
            $addons = $this->seedAddons();
            $coupons = $this->seedCoupons();

            $this->seedPlanFeatures($plans, $features);
            $this->seedPlanAddons($plans, $addons);
            $this->seedCouponPlans($coupons, $plans);
        });
    }

    private function seedModules(): void
    {
        $modules = [
            ['name' => 'Dashboard', 'code' => 'dashboard', 'description' => 'Platform overview and operational metrics.', 'icon' => 'layout-dashboard', 'category' => 'core', 'is_core' => true, 'sort_order' => 1],
            ['name' => 'Platform Users', 'code' => 'platform_user', 'description' => 'Platform account administration.', 'icon' => 'users', 'category' => 'administration', 'is_core' => true, 'sort_order' => 2],
            ['name' => 'Platform Roles', 'code' => 'platform_role', 'description' => 'Platform role administration.', 'icon' => 'shield-check', 'category' => 'administration', 'is_core' => true, 'sort_order' => 3],
            ['name' => 'Platform Permissions', 'code' => 'platform_permission', 'description' => 'Platform permission administration.', 'icon' => 'key-round', 'category' => 'administration', 'is_core' => true, 'sort_order' => 4],
            ['name' => 'Platform Teams', 'code' => 'platform_team', 'description' => 'Platform team administration.', 'icon' => 'users-round', 'category' => 'administration', 'is_core' => false, 'sort_order' => 5],
            ['name' => 'Tenants', 'code' => 'tenant', 'description' => 'Tenant lifecycle management.', 'icon' => 'building-2', 'category' => 'platform', 'is_core' => true, 'sort_order' => 6],
            ['name' => 'Subscriptions', 'code' => 'subscription', 'description' => 'Tenant subscription management.', 'icon' => 'repeat', 'category' => 'billing', 'is_core' => true, 'sort_order' => 7],
            ['name' => 'Plans', 'code' => 'plan', 'description' => 'Subscription plan catalog.', 'icon' => 'layers-3', 'category' => 'billing', 'is_core' => true, 'sort_order' => 8],
            ['name' => 'Features', 'code' => 'feature', 'description' => 'Plan feature catalog.', 'icon' => 'puzzle', 'category' => 'billing', 'is_core' => true, 'sort_order' => 9],
            ['name' => 'Billing', 'code' => 'billing', 'description' => 'Invoices and payments.', 'icon' => 'receipt', 'category' => 'billing', 'is_core' => true, 'sort_order' => 10],
            ['name' => 'Coupons', 'code' => 'coupon', 'description' => 'Promotional coupon management.', 'icon' => 'ticket-percent', 'category' => 'billing', 'is_core' => false, 'sort_order' => 11],
            ['name' => 'Modules', 'code' => 'module', 'description' => 'Application module catalog.', 'icon' => 'boxes', 'category' => 'platform', 'is_core' => true, 'sort_order' => 12],
            ['name' => 'Support', 'code' => 'support', 'description' => 'Support tickets and knowledge base.', 'icon' => 'life-buoy', 'category' => 'operations', 'is_core' => false, 'sort_order' => 13],
            ['name' => 'Monitoring', 'code' => 'monitoring', 'description' => 'Platform service monitoring.', 'icon' => 'activity', 'category' => 'operations', 'is_core' => true, 'sort_order' => 14],
            ['name' => 'Integrations', 'code' => 'integration', 'description' => 'External service integrations.', 'icon' => 'plug', 'category' => 'operations', 'is_core' => false, 'sort_order' => 15],
            ['name' => 'Settings', 'code' => 'setting', 'description' => 'Platform configuration.', 'icon' => 'settings', 'category' => 'administration', 'is_core' => true, 'sort_order' => 16],
            ['name' => 'Audit Log', 'code' => 'audit_log', 'description' => 'Platform activity and audit history.', 'icon' => 'scroll-text', 'category' => 'security', 'is_core' => true, 'sort_order' => 17],
            ['name' => 'Reports', 'code' => 'report', 'description' => 'Platform reporting and exports.', 'icon' => 'chart-column', 'category' => 'analytics', 'is_core' => false, 'sort_order' => 18],
            ['name' => 'Documents', 'code' => 'document', 'description' => 'Shared files and documents.', 'icon' => 'file-text', 'category' => 'content', 'is_core' => false, 'sort_order' => 19],
        ];

        $this->upsert('modules', $modules, ['code'], ['name', 'description', 'icon', 'category', 'is_core', 'sort_order', 'status', 'updated_at']);
    }

    private function seedPlans(): array
    {
        $plans = [
            ['name' => 'Starter', 'code' => 'starter', 'description' => 'Essential tools for small teams getting started.', 'billing_cycle' => 'monthly', 'base_price' => 999, 'currency' => 'INR', 'trial_days' => 14, 'is_custom' => false, 'is_public' => true],
            ['name' => 'Professional', 'code' => 'professional', 'description' => 'Advanced collaboration and reporting for growing businesses.', 'billing_cycle' => 'monthly', 'base_price' => 2499, 'currency' => 'INR', 'trial_days' => 14, 'is_custom' => false, 'is_public' => true],
            ['name' => 'Enterprise', 'code' => 'enterprise', 'description' => 'Flexible controls, security, and scale for larger organizations.', 'billing_cycle' => 'yearly', 'base_price' => 0, 'currency' => 'INR', 'trial_days' => 30, 'is_custom' => true, 'is_public' => true],
        ];

        $this->upsert('plans', $plans, ['code'], ['name', 'description', 'billing_cycle', 'base_price', 'currency', 'trial_days', 'is_custom', 'is_public', 'status', 'updated_at']);
        return DB::table('plans')->whereIn('code', array_column($plans, 'code'))->pluck('id', 'code')->all();
    }

    private function seedFeatures(): array
    {
        $features = [
            ['module' => 'tenant', 'name' => 'Users', 'code' => 'users_limit', 'data_type' => 'integer', 'unit' => 'users', 'description' => 'Maximum active users per tenant.'],
            ['module' => 'document', 'name' => 'Storage', 'code' => 'storage_limit', 'data_type' => 'integer', 'unit' => 'GB', 'description' => 'Document storage allocation.'],
            ['module' => 'project', 'name' => 'Projects', 'code' => 'projects_limit', 'data_type' => 'integer', 'unit' => 'projects', 'description' => 'Maximum active projects.'],
            ['module' => 'client', 'name' => 'Clients', 'code' => 'clients_limit', 'data_type' => 'integer', 'unit' => 'clients', 'description' => 'Maximum client records.'],
            ['module' => 'report', 'name' => 'Advanced Reports', 'code' => 'advanced_reports', 'data_type' => 'boolean', 'unit' => null, 'description' => 'Advanced reporting and customization.'],
            ['module' => 'integration', 'name' => 'API Access', 'code' => 'api_access', 'data_type' => 'boolean', 'unit' => null, 'description' => 'Programmatic API access.'],
            ['module' => 'audit_log', 'name' => 'Audit History', 'code' => 'audit_history', 'data_type' => 'boolean', 'unit' => null, 'description' => 'Extended audit history and exports.'],
            ['module' => 'support', 'name' => 'Priority Support', 'code' => 'priority_support', 'data_type' => 'boolean', 'unit' => null, 'description' => 'Priority support response times.'],
        ];

        $this->upsert('features', $features, ['code'], ['module', 'name', 'data_type', 'unit', 'description', 'status', 'updated_at']);
        return DB::table('features')->whereIn('code', array_column($features, 'code'))->pluck('id', 'code')->all();
    }

    private function seedAddons(): array
    {
        $addons = [
            ['name' => 'Additional Storage', 'code' => 'additional_storage_100gb', 'pricing_type' => 'fixed', 'price' => 499, 'currency' => 'INR', 'is_public' => true],
            ['name' => 'Priority Support', 'code' => 'priority_support', 'pricing_type' => 'fixed', 'price' => 999, 'currency' => 'INR', 'is_public' => true],
            ['name' => 'Advanced Analytics', 'code' => 'advanced_analytics', 'pricing_type' => 'fixed', 'price' => 1499, 'currency' => 'INR', 'is_public' => true],
        ];

        $this->upsert('addon_plans', $addons, ['code'], ['name', 'pricing_type', 'price', 'currency', 'is_public', 'status', 'updated_at']);
        return DB::table('addon_plans')->whereIn('code', array_column($addons, 'code'))->pluck('id', 'code')->all();
    }

    private function seedCoupons(): array
    {
        $coupons = [
            ['code' => 'WELCOME20', 'name' => 'Welcome 20% Off', 'discount_type' => 'percentage', 'discount_value' => 20, 'starts_at' => '2026-01-01 00:00:00', 'expires_at' => '2026-12-31 23:59:59', 'max_redemptions' => 1000],
            ['code' => 'ANNUAL15', 'name' => 'Annual Plan 15% Off', 'discount_type' => 'percentage', 'discount_value' => 15, 'starts_at' => '2026-01-01 00:00:00', 'expires_at' => '2026-12-31 23:59:59', 'max_redemptions' => null],
        ];

        $this->upsert('coupons', $coupons, ['code'], ['name', 'discount_type', 'discount_value', 'starts_at', 'expires_at', 'max_redemptions', 'status', 'updated_at']);
        return DB::table('coupons')->whereIn('code', array_column($coupons, 'code'))->pluck('id', 'code')->all();
    }

    private function seedPlanFeatures(array $plans, array $features): void
    {
        $values = [
            ['plan' => 'starter', 'feature' => 'users_limit', 'value' => '10'], ['plan' => 'starter', 'feature' => 'storage_limit', 'value' => '10'], ['plan' => 'starter', 'feature' => 'projects_limit', 'value' => '5'], ['plan' => 'starter', 'feature' => 'clients_limit', 'value' => '100'],
            ['plan' => 'professional', 'feature' => 'users_limit', 'value' => '50'], ['plan' => 'professional', 'feature' => 'storage_limit', 'value' => '100'], ['plan' => 'professional', 'feature' => 'projects_limit', 'value' => '25'], ['plan' => 'professional', 'feature' => 'clients_limit', 'value' => '1000'], ['plan' => 'professional', 'feature' => 'advanced_reports', 'value' => 'true'], ['plan' => 'professional', 'feature' => 'api_access', 'value' => 'true'],
            ['plan' => 'enterprise', 'feature' => 'users_limit', 'value' => '-1'], ['plan' => 'enterprise', 'feature' => 'storage_limit', 'value' => '-1'], ['plan' => 'enterprise', 'feature' => 'projects_limit', 'value' => '-1'], ['plan' => 'enterprise', 'feature' => 'clients_limit', 'value' => '-1'], ['plan' => 'enterprise', 'feature' => 'advanced_reports', 'value' => 'true'], ['plan' => 'enterprise', 'feature' => 'api_access', 'value' => 'true'], ['plan' => 'enterprise', 'feature' => 'audit_history', 'value' => 'true'], ['plan' => 'enterprise', 'feature' => 'priority_support', 'value' => 'true'],
        ];
        $rows = array_map(fn (array $row): array => ['plan_id' => $plans[$row['plan']], 'feature_id' => $features[$row['feature']], 'value' => $row['value'], 'metadata' => null, 'created_at' => now(), 'updated_at' => now()], $values);
        DB::table('plan_features')->upsert($rows, ['plan_id', 'feature_id'], ['value', 'metadata', 'updated_at']);
    }

    private function seedPlanAddons(array $plans, array $addons): void
    {
        $rows = [['plan_id' => $plans['starter'], 'addon_plan_id' => $addons['additional_storage_100gb']], ['plan_id' => $plans['professional'], 'addon_plan_id' => $addons['additional_storage_100gb']], ['plan_id' => $plans['professional'], 'addon_plan_id' => $addons['priority_support']], ['plan_id' => $plans['enterprise'], 'addon_plan_id' => $addons['advanced_analytics']]];
        DB::table('plan_addons')->upsert(array_map(fn (array $row): array => $row + ['created_at' => now(), 'updated_at' => now()], $rows), ['plan_id', 'addon_plan_id'], ['updated_at']);
    }

    private function seedCouponPlans(array $coupons, array $plans): void
    {
        DB::table('coupon_plan_assignments')->upsert([
            ['coupon_id' => $coupons['WELCOME20'], 'plan_id' => $plans['starter']],
            ['coupon_id' => $coupons['WELCOME20'], 'plan_id' => $plans['professional']],
            ['coupon_id' => $coupons['ANNUAL15'], 'plan_id' => $plans['enterprise']],
        ], ['coupon_id', 'plan_id']);
    }

    private function upsert(string $table, array $rows, array $uniqueBy, array $updateColumns): void
    {
        $now = now();
        $rows = array_map(fn (array $row): array => $row + ['uuid' => (string) Str::uuid(), 'status' => 'active', 'created_at' => $now, 'updated_at' => $now], $rows);
        DB::table($table)->upsert($rows, $uniqueBy, $updateColumns);
    }
}
