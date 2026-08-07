<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\SeedsRecords;
use Illuminate\Database\Seeder;

class BillingCatalogSeeder extends Seeder
{
    use SeedsRecords;

    public function run(): void
    {
        $featureIds = [];

        foreach ($this->features() as $feature) {
            $featureIds[$feature['code']] = $this->seedRecord('features', ['code' => $feature['code']], [
                'module' => $feature['module'],
                'name' => $feature['name'],
                'data_type' => $feature['data_type'],
                'unit' => $feature['unit'] ?? null,
                'description' => $feature['description'] ?? null,
                'status' => 'active',
            ], true);
        }

        foreach ($this->plans() as $plan) {
            $planId = $this->seedRecord('plans', ['code' => $plan['code']], [
                'name' => $plan['name'],
                'description' => $plan['description'],
                'billing_cycle' => $plan['billing_cycle'],
                'base_price' => $plan['base_price'],
                'currency' => $plan['currency'],
                'trial_days' => $plan['trial_days'],
                'is_custom' => false,
                'is_public' => true,
                'status' => 'active',
            ], true);

            foreach ($plan['features'] as $featureCode => $value) {
                if (! isset($featureIds[$featureCode])) {
                    continue;
                }

                $this->seedRecord('plan_features', ['plan_id' => $planId, 'feature_id' => $featureIds[$featureCode]], [
                    'value' => (string) $value,
                    'metadata' => null,
                ]);
            }
        }

        foreach ($this->addons() as $addon) {
            $this->seedRecord('addon_plans', ['code' => $addon['code']], [
                'name' => $addon['name'],
                'pricing_type' => $addon['pricing_type'],
                'price' => $addon['price'],
                'currency' => $addon['currency'],
                'status' => 'active',
            ], true);
        }
    }

    /** @return list<array<string, mixed>> */
    private function features(): array
    {
        return [
            ['module' => 'users', 'name' => 'Tenant Users', 'code' => 'users.limit', 'data_type' => 'integer', 'unit' => 'users'],
            ['module' => 'storage', 'name' => 'Storage', 'code' => 'storage.gb', 'data_type' => 'integer', 'unit' => 'gb'],
            ['module' => 'projects', 'name' => 'Projects', 'code' => 'projects.limit', 'data_type' => 'integer', 'unit' => 'projects'],
            ['module' => 'clients', 'name' => 'Clients', 'code' => 'clients.limit', 'data_type' => 'integer', 'unit' => 'clients'],
            ['module' => 'finance', 'name' => 'Finance Module', 'code' => 'module.finance', 'data_type' => 'boolean'],
            ['module' => 'hrms', 'name' => 'HRMS Module', 'code' => 'module.hrms', 'data_type' => 'boolean'],
            ['module' => 'payroll', 'name' => 'Payroll Module', 'code' => 'module.payroll', 'data_type' => 'boolean'],
            ['module' => 'api', 'name' => 'API Requests', 'code' => 'api.requests.monthly', 'data_type' => 'integer', 'unit' => 'requests'],
            ['module' => 'support', 'name' => 'Priority Support', 'code' => 'support.priority', 'data_type' => 'boolean'],
        ];
    }

    /** @return list<array<string, mixed>> */
    private function plans(): array
    {
        return [
            [
                'name' => 'Starter', 'code' => 'starter', 'description' => 'For small teams starting with CRM basics.',
                'billing_cycle' => 'monthly', 'base_price' => 999, 'currency' => 'INR', 'trial_days' => 14,
                'features' => ['users.limit' => 5, 'storage.gb' => 5, 'projects.limit' => 25, 'clients.limit' => 250, 'module.finance' => 0, 'module.hrms' => 0, 'module.payroll' => 0, 'api.requests.monthly' => 10000, 'support.priority' => 0],
            ],
            [
                'name' => 'Growth', 'code' => 'growth', 'description' => 'For growing teams with projects and finance.',
                'billing_cycle' => 'monthly', 'base_price' => 2999, 'currency' => 'INR', 'trial_days' => 14,
                'features' => ['users.limit' => 25, 'storage.gb' => 50, 'projects.limit' => 250, 'clients.limit' => 2500, 'module.finance' => 1, 'module.hrms' => 1, 'module.payroll' => 0, 'api.requests.monthly' => 100000, 'support.priority' => 0],
            ],
            [
                'name' => 'Enterprise', 'code' => 'enterprise', 'description' => 'For larger organizations requiring advanced modules and priority support.',
                'billing_cycle' => 'monthly', 'base_price' => 9999, 'currency' => 'INR', 'trial_days' => 30,
                'features' => ['users.limit' => 250, 'storage.gb' => 500, 'projects.limit' => 5000, 'clients.limit' => 100000, 'module.finance' => 1, 'module.hrms' => 1, 'module.payroll' => 1, 'api.requests.monthly' => 1000000, 'support.priority' => 1],
            ],
        ];
    }

    /** @return list<array<string, mixed>> */
    private function addons(): array
    {
        return [
            ['name' => 'Extra 10 Users', 'code' => 'extra_10_users', 'pricing_type' => 'recurring', 'price' => 499, 'currency' => 'INR'],
            ['name' => 'Extra 100GB Storage', 'code' => 'extra_100gb_storage', 'pricing_type' => 'recurring', 'price' => 799, 'currency' => 'INR'],
            ['name' => 'Payroll Module', 'code' => 'payroll_module', 'pricing_type' => 'recurring', 'price' => 1499, 'currency' => 'INR'],
            ['name' => 'Priority Support', 'code' => 'priority_support', 'pricing_type' => 'recurring', 'price' => 1999, 'currency' => 'INR'],
        ];
    }
}