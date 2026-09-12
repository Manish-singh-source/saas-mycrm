<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TenantFullDemoSeeder extends Seeder
{
    private const PASSWORD = '123465789';

    public function run(): void
    {
        $this->call([PlatformCatalogSeeder::class, TenantPermissionMapSeeder::class]);

        DB::transaction(function (): void {
            $now = now();
            $actor = DB::table('platform_users')->orderBy('id')->value('id');
            $india = DB::table('countries')->where('name', 'India')->value('id');
            $tenants = $this->tenants();
            $existingTenantIds = DB::table('tenants')->whereIn('organization_code', array_column($tenants, 'code'))->pluck('id')->all();
            $this->clear($existingTenantIds);

            foreach ($tenants as $index => $tenant) {
                $plan = DB::table('plans')->where('code', $tenant['plan'])->first();
                if (! $plan) continue;
                $place = $this->place($tenant['state'], $tenant['city'], $india);
                $tenantId = $this->upsertId('tenants', ['organization_code' => $tenant['code']], [
                    'uuid' => $tenant['uuid'], 'organization_name' => $tenant['name'], 'legal_name' => $tenant['legal'], 'display_name' => $tenant['display'],
                    'organization_code' => $tenant['code'], 'slug' => $tenant['slug'], 'business_type_id' => $this->idByName('business_types', $tenant['business']),
                    'industry_id' => $this->idByName('industries', $tenant['industry']), 'company_size' => $tenant['size'], 'gst_number' => $tenant['gst'], 'pan_number' => $tenant['pan'],
                    'registration_number' => $tenant['reg'], 'website' => $tenant['website'], 'default_currency' => 'INR', 'default_timezone' => 'Asia/Kolkata',
                    'description' => $tenant['description'], 'onboarded_at' => $tenant['onboarded_at'], 'trial_ends_at' => $tenant['trial_ends_at'], 'status' => $tenant['status'],
                    'created_at' => $now->copy()->subDays(120 - ($index * 18)), 'updated_at' => $now,
                ]);
                $officeId = $this->office($tenantId, $tenant, $place, true, $actor);
                foreach ($tenant['offices'] as $office) $this->office($tenantId, $tenant + $office, $this->place($office['state'], $office['city'], $india), false, $actor);
                $roles = $this->roles($tenantId, $tenant['display']);
                $users = $this->users($tenantId, $officeId, $tenant, $actor);
                $this->assignRoles($tenantId, $users, $roles);
                $subscriptionId = $this->subscription($tenantId, (int) $plan->id, $tenant, $actor);
                $invoiceId = $this->invoice($tenantId, $subscriptionId, $tenant);
                $paymentId = $this->payment($tenantId, $subscriptionId, $invoiceId, $tenant);
                if ($tenant['refund'] > 0) $this->refund($tenantId, $paymentId, $tenant);
                DB::table('subscriptions')->where('id', $subscriptionId)->update(['last_platform_invoice_id' => $invoiceId, 'last_platform_payment_id' => $paymentId, 'updated_at' => $now]);
                $this->subscriptionExtras($tenantId, $subscriptionId, (int) $plan->id, $tenant, $actor);
                $this->parties($tenantId, $users['owner'], $tenant, $place);
                $this->settings($tenantId);
                $this->onboarding($tenantId, $actor);
            }
        });
    }

    private function tenants(): array
    {
        return [
            ['uuid' => '11111111-1111-4111-8111-111111111111', 'name' => 'Acme Growth Solutions', 'legal' => 'Acme Growth Solutions Private Limited', 'display' => 'Acme Growth', 'code' => 'ACMEGROWTH', 'slug' => 'acme-growth', 'business' => 'Private Limited Company', 'industry' => 'Information Technology', 'size' => 'medium', 'status' => 'active', 'plan' => 'professional', 'sub_status' => 'active', 'cycle' => 'monthly', 'base' => 2499, 'discount' => 250, 'tax' => 450, 'paid' => true, 'refund' => 500, 'state' => 'Maharashtra', 'city' => 'Mumbai', 'gst' => '27AAGCA4521K1Z5', 'pan' => 'AAGCA4521K', 'reg' => 'U72900MH2020PTC345678', 'website' => 'https://acmegrowth.example.com', 'description' => 'Revenue operations consultancy using CRM, billing, and projects workflows.', 'onboarded_at' => now()->subDays(96), 'trial_ends_at' => now()->subDays(70), 'owner' => ['Rhea', 'Kapadia', 'rhea.kapadia@acmegrowth.example.com', '+919820011001'], 'staff' => [['Arjun', 'Menon', 'arjun.menon@acmegrowth.example.com', 'sales_manager'], ['Isha', 'Patel', 'isha.patel@acmegrowth.example.com', 'finance_user']], 'offices' => [['office_name' => 'Pune Delivery Centre', 'office_code' => 'ACG-PUN', 'office_type' => 'branch', 'state' => 'Maharashtra', 'city' => 'Pune']], 'contacts' => [['client', 'BluePeak Software Pvt Ltd', 'procurement@bluepeak.example.com', 'Nikhil', 'Shah', 'Head of Operations'], ['vendor', 'InvoiceDesk Services', 'billing@invoicedesk.example.com', 'Sneha', 'Rao', 'Account Manager']]],
            ['uuid' => '22222222-2222-4222-8222-222222222222', 'name' => 'Nimbus Medical Clinics', 'legal' => 'Nimbus Medical Clinics LLP', 'display' => 'Nimbus Clinics', 'code' => 'NIMBUSMED', 'slug' => 'nimbus-medical', 'business' => 'Limited Liability Partnership', 'industry' => 'Healthcare', 'size' => 'small', 'status' => 'trial', 'plan' => 'starter', 'sub_status' => 'trial', 'cycle' => 'monthly', 'base' => 999, 'discount' => 0, 'tax' => 180, 'paid' => false, 'refund' => 0, 'state' => 'Karnataka', 'city' => 'Bengaluru', 'gst' => '29AANFN7821L1Z3', 'pan' => 'AANFN7821L', 'reg' => 'AAJ-2234', 'website' => 'https://nimbusclinics.example.com', 'description' => 'Clinic network evaluating appointment, support, and billing workflows.', 'onboarded_at' => now()->subDays(8), 'trial_ends_at' => now()->addDays(22), 'owner' => ['Kavya', 'Nair', 'kavya.nair@nimbusclinics.example.com', '+918040011002'], 'staff' => [['Manav', 'Hegde', 'manav.hegde@nimbusclinics.example.com', 'support_agent']], 'offices' => [['office_name' => 'Indiranagar Clinic', 'office_code' => 'NMC-IND', 'office_type' => 'branch', 'state' => 'Karnataka', 'city' => 'Bengaluru']], 'contacts' => [['vendor', 'MediSupply India', 'orders@medisupply.example.com', 'Anita', 'George', 'Supplier Coordinator']]],
            ['uuid' => '33333333-3333-4333-8333-333333333333', 'name' => 'Crestline Manufacturing', 'legal' => 'Crestline Manufacturing Limited', 'display' => 'Crestline', 'code' => 'CRESTLINE', 'slug' => 'crestline-manufacturing', 'business' => 'Public Limited Company', 'industry' => 'Manufacturing', 'size' => 'large', 'status' => 'active', 'plan' => 'enterprise', 'sub_status' => 'active', 'cycle' => 'yearly', 'base' => 240000, 'discount' => 24000, 'tax' => 43200, 'paid' => true, 'refund' => 0, 'state' => 'Delhi', 'city' => 'Delhi', 'gst' => '07AACCC9842M1Z7', 'pan' => 'AACCC9842M', 'reg' => 'L27100DL2008PLC182111', 'website' => 'https://crestline.example.com', 'description' => 'Industrial equipment manufacturer using enterprise CRM, projects, support, and audit modules.', 'onboarded_at' => now()->subDays(180), 'trial_ends_at' => now()->subDays(150), 'owner' => ['Vikram', 'Suri', 'vikram.suri@crestline.example.com', '+911140011003'], 'staff' => [['Neha', 'Bansal', 'neha.bansal@crestline.example.com', 'sales_manager'], ['Farhan', 'Qureshi', 'farhan.qureshi@crestline.example.com', 'support_agent']], 'offices' => [['office_name' => 'Ahmedabad Factory Office', 'office_code' => 'CRL-AMD', 'office_type' => 'factory', 'state' => 'Gujarat', 'city' => 'Ahmedabad']], 'contacts' => [['client', 'MetroRail Components Board', 'contracts@metrorail.example.com', 'Prakash', 'Iyer', 'Procurement Director'], ['vendor', 'SteelGrid Logistics', 'support@steelgrid.example.com', 'Harpreet', 'Singh', 'Logistics Lead']]],
            ['uuid' => '44444444-4444-4444-8444-444444444444', 'name' => 'GreenCart Retail', 'legal' => 'GreenCart Retail Private Limited', 'display' => 'GreenCart', 'code' => 'GREENCART', 'slug' => 'greencart-retail', 'business' => 'Private Limited Company', 'industry' => 'Retail', 'size' => 'medium', 'status' => 'suspended', 'plan' => 'professional', 'sub_status' => 'suspended', 'cycle' => 'monthly', 'base' => 2499, 'discount' => 0, 'tax' => 450, 'paid' => false, 'refund' => 0, 'state' => 'Gujarat', 'city' => 'Ahmedabad', 'gst' => '24AAICG5512Q1Z2', 'pan' => 'AAICG5512Q', 'reg' => 'U52520GJ2019PTC108765', 'website' => 'https://greencart.example.com', 'description' => 'Regional grocery retailer suspended because of unresolved payment failures.', 'onboarded_at' => now()->subDays(70), 'trial_ends_at' => now()->subDays(42), 'owner' => ['Mehul', 'Desai', 'mehul.desai@greencart.example.com', '+917940011004'], 'staff' => [['Pooja', 'Trivedi', 'pooja.trivedi@greencart.example.com', 'finance_user']], 'offices' => [['office_name' => 'Jaipur Store Office', 'office_code' => 'GCR-JAI', 'office_type' => 'store', 'state' => 'Rajasthan', 'city' => 'Jaipur']], 'contacts' => [['vendor', 'FreshRoute Distributors', 'accounts@freshroute.example.com', 'Komal', 'Jain', 'Partner Success']]],
            ['uuid' => '55555555-5555-4555-8555-555555555555', 'name' => 'UrbanEdge Realty', 'legal' => 'UrbanEdge Realty LLP', 'display' => 'UrbanEdge', 'code' => 'URBANEDGE', 'slug' => 'urbanedge-realty', 'business' => 'Limited Liability Partnership', 'industry' => 'Construction and Real Estate', 'size' => 'small', 'status' => 'expired', 'plan' => 'starter', 'sub_status' => 'expired', 'cycle' => 'monthly', 'base' => 999, 'discount' => 0, 'tax' => 180, 'paid' => false, 'refund' => 0, 'state' => 'Rajasthan', 'city' => 'Jaipur', 'gst' => '08AAEFU2314D1Z9', 'pan' => 'AAEFU2314D', 'reg' => 'AAX-7781', 'website' => 'https://urbanedge.example.com', 'description' => 'Real estate brokerage with an expired starter subscription retained for lifecycle testing.', 'onboarded_at' => now()->subDays(115), 'trial_ends_at' => now()->subDays(85), 'owner' => ['Tanvi', 'Rathore', 'tanvi.rathore@urbanedge.example.com', '+911414011005'], 'staff' => [['Dev', 'Mathur', 'dev.mathur@urbanedge.example.com', 'sales_manager']], 'offices' => [], 'contacts' => [['client', 'Skyline Habitat Buyers Association', 'committee@skylinehabitat.example.com', 'Aditi', 'Mehra', 'Association Secretary']]],
        ];
    }

    private function clear(array $tenantIds): void
    {
        if ($tenantIds === []) return;
        $invoiceIds = DB::table('platform_invoices')->whereIn('tenant_id', $tenantIds)->pluck('id')->all();
        $paymentIds = DB::table('platform_payments')->whereIn('tenant_id', $tenantIds)->pluck('id')->all();
        $subscriptionIds = DB::table('subscriptions')->whereIn('tenant_id', $tenantIds)->pluck('id')->all();
        $partyIds = DB::table('parties')->whereIn('tenant_id', $tenantIds)->pluck('id')->all();
        DB::table('platform_refunds')->whereIn('platform_payment_id', $paymentIds)->delete();
        DB::table('platform_payments')->whereIn('tenant_id', $tenantIds)->delete();
        DB::table('platform_invoice_items')->whereIn('platform_invoice_id', $invoiceIds)->delete();
        DB::table('platform_invoices')->whereIn('tenant_id', $tenantIds)->delete();
        foreach (['subscription_addons', 'subscription_usage', 'subscription_versions', 'subscription_renewals'] as $table) DB::table($table)->whereIn('subscription_id', $subscriptionIds)->delete();
        DB::table('subscriptions')->whereIn('tenant_id', $tenantIds)->delete();
        DB::table('party_contacts')->whereIn('party_id', $partyIds)->delete();
        DB::table('party_addresses')->whereIn('party_id', $partyIds)->delete();
        DB::table('client_profiles')->whereIn('party_id', $partyIds)->delete();
        DB::table('vendor_profiles')->whereIn('party_id', $partyIds)->delete();
        DB::table('parties')->whereIn('tenant_id', $tenantIds)->delete();
        foreach (['tenant_settings', 'tenant_onboarding_steps', 'tenant_usage_snapshots', 'model_has_roles'] as $table) DB::table($table)->whereIn('tenant_id', $tenantIds)->delete();
        DB::table('roles')->whereIn('tenant_id', $tenantIds)->delete();
        DB::table('users')->whereIn('tenant_id', $tenantIds)->delete();
        DB::table('tenant_offices')->whereIn('tenant_id', $tenantIds)->delete();
    }

    private function office(int $tenantId, array $tenant, array $place, bool $head, ?int $actor): int
    {
        $code = $tenant['office_code'] ?? substr($tenant['code'], 0, 3).'-HO';
        return $this->upsertId('tenant_offices', ['tenant_id' => $tenantId, 'office_code' => $code], [
            'uuid' => (string) Str::uuid(), 'tenant_id' => $tenantId, 'office_name' => $tenant['office_name'] ?? 'Corporate Office', 'office_code' => $code,
            'office_type' => $head ? 'head_office' : $tenant['office_type'], 'is_head_office' => $head, 'is_default' => $head,
            'address_line_1' => $head ? '12 Business Park Road' : '4 Commercial Centre', 'address_line_2' => $head ? 'Suite 502' : null,
            'landmark' => $head ? 'Near Metro Station' : 'Opposite Main Market', 'country_id' => $place['country'], 'state_id' => $place['state'], 'city_id' => $place['city'],
            'postal_code' => $head ? '400001' : '411001', 'contact_person' => $tenant['owner'][0].' '.$tenant['owner'][1], 'contact_email' => $tenant['owner'][2], 'contact_phone' => $tenant['owner'][3],
            'timezone' => 'Asia/Kolkata', 'working_hours' => json_encode(['mon_fri' => '09:30-18:30', 'sat' => '10:00-14:00']), 'gst_number' => $tenant['gst'], 'status' => 'active',
            'created_by' => $actor, 'updated_by' => $actor, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function roles(int $tenantId, string $tenantName): array
    {
        $definitions = [
            'owner_admin' => ['Owner Administrator', ['dashboard.view', 'role.view', 'role.assign_permissions', 'staff.view', 'staff.create', 'client.view', 'vendor.view', 'lead.view', 'project.view', 'finance.invoice.view', 'finance.payment.view', 'setting.view', 'setting.edit']],
            'sales_manager' => ['Sales Manager', ['dashboard.view', 'client.view', 'client.create', 'client.edit', 'lead.view', 'lead.create', 'lead.edit', 'lead.convert', 'project.view', 'task.view']],
            'finance_user' => ['Finance Executive', ['dashboard.view', 'finance.invoice.view', 'finance.invoice.create', 'finance.invoice.edit', 'finance.payment.view', 'finance.payment.create', 'finance.expense.view', 'finance.bank_account.view']],
            'support_agent' => ['Support Agent', ['dashboard.view', 'client.view', 'vendor.view', 'project.view', 'task.view', 'issue.view', 'issue.create', 'document.view']],
        ];
        $roles = [];
        foreach ($definitions as $name => [$display, $permissions]) {
            $roleId = $this->upsertId('roles', ['tenant_id' => $tenantId, 'name' => $name], ['uuid' => (string) Str::uuid(), 'tenant_id' => $tenantId, 'name' => $name, 'display_name' => $display, 'guard_name' => 'tenant', 'description' => $display.' for '.$tenantName, 'is_system' => $name === 'owner_admin', 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
            $roles[$name] = $roleId;
            foreach (DB::table('permissions')->where('guard_name', 'tenant')->whereIn('name', $permissions)->pluck('id') as $permissionId) DB::table('role_has_permissions')->updateOrInsert(['role_id' => $roleId, 'permission_id' => $permissionId]);
        }
        return $roles;
    }

    private function users(int $tenantId, int $officeId, array $tenant, ?int $actor): array
    {
        $users = [];
        $owner = $tenant['owner'];
        $users['owner'] = $this->user($tenantId, $officeId, $owner[0], $owner[1], $owner[2], $owner[3], 'owner', 'OWN', $actor);
        foreach ($tenant['staff'] as $i => $staff) $users[$staff[3]] = $this->user($tenantId, $officeId, $staff[0], $staff[1], $staff[2], '+919900'.str_pad((string) ($i + 1100), 6, '0', STR_PAD_LEFT), 'staff', 'EMP'.($i + 1), $actor);
        return $users;
    }

    private function user(int $tenantId, int $officeId, string $first, string $last, string $email, string $mobile, string $type, string $code, ?int $actor): int
    {
        return $this->upsertId('users', ['tenant_id' => $tenantId, 'email' => $email], ['uuid' => (string) Str::uuid(), 'tenant_id' => $tenantId, 'default_office_id' => $officeId, 'employee_code' => 'T'.$tenantId.'-'.$code, 'first_name' => $first, 'last_name' => $last, 'display_name' => trim($first.' '.$last), 'email' => $email, 'mobile' => $mobile, 'password' => Hash::make(self::PASSWORD), 'timezone' => 'Asia/Kolkata', 'locale' => 'en', 'email_verified_at' => now()->subDays(10), 'account_type' => $type, 'last_login_at' => now()->subDays(2), 'last_login_ip' => '127.0.0.1', 'status' => 'active', 'created_by' => $actor, 'updated_by' => $actor, 'created_at' => now(), 'updated_at' => now()]);
    }

    private function assignRoles(int $tenantId, array $users, array $roles): void
    {
        foreach ($users as $role => $userId) {
            $roleName = $role === 'owner' ? 'owner_admin' : $role;
            if (isset($roles[$roleName])) DB::table('model_has_roles')->updateOrInsert(['tenant_id' => $tenantId, 'role_id' => $roles[$roleName], 'model_id' => $userId, 'model_type' => User::class]);
        }
    }

    private function subscription(int $tenantId, int $planId, array $tenant, ?int $actor): int
    {
        $starts = now()->parse($tenant['onboarded_at'])->startOfDay();
        $expires = $tenant['sub_status'] === 'expired' ? now()->subDays(5) : ($tenant['cycle'] === 'yearly' ? now()->addYear() : now()->addMonth());
        $payable = $tenant['base'] - $tenant['discount'] + $tenant['tax'];
        return $this->upsertId('subscriptions', ['subscription_number' => 'SUB-'.$tenant['code'].'-001'], [
            'uuid' => (string) Str::uuid(), 'subscription_number' => 'SUB-'.$tenant['code'].'-001', 'tenant_id' => $tenantId, 'plan_id' => $planId, 'current_version' => 1,
            'type' => 'standard', 'billing_cycle' => $tenant['cycle'], 'status' => $tenant['sub_status'], 'renewal_type' => 'manual', 'starts_at' => $starts, 'expires_at' => $expires,
            'next_billing_at' => $expires, 'trial_starts_at' => $starts, 'trial_ends_at' => $tenant['trial_ends_at'], 'base_amount' => $tenant['base'], 'addon_amount' => 0,
            'discount_amount' => $tenant['discount'], 'taxable_amount' => $tenant['base'] - $tenant['discount'], 'tax_amount' => $tenant['tax'], 'payable_amount' => $payable,
            'currency' => 'INR', 'auto_renew' => false, 'notes' => 'Seeded demo subscription for billing and lifecycle testing.', 'created_by' => $actor, 'updated_by' => $actor,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function invoice(int $tenantId, int $subscriptionId, array $tenant): int
    {
        $amount = $tenant['base'] - $tenant['discount'] + $tenant['tax'];
        $invoiceId = $this->upsertId('platform_invoices', ['invoice_number' => 'INV-'.$tenant['code'].'-001'], [
            'uuid' => (string) Str::uuid(), 'invoice_number' => 'INV-'.$tenant['code'].'-001', 'tenant_id' => $tenantId, 'subscription_id' => $subscriptionId,
            'invoice_date' => now()->subDays(18)->toDateString(), 'due_date' => now()->addDays($tenant['paid'] ? -3 : 7)->toDateString(), 'subtotal' => $tenant['base'],
            'discount_amount' => $tenant['discount'], 'taxable_amount' => $tenant['base'] - $tenant['discount'], 'tax_amount' => $tenant['tax'], 'total_amount' => $amount,
            'paid_amount' => $tenant['paid'] ? $amount : 0, 'balance_amount' => $tenant['paid'] ? 0 : $amount, 'currency' => 'INR',
            'status' => $tenant['paid'] ? 'paid' : ($tenant['sub_status'] === 'suspended' ? 'overdue' : 'sent'), 'created_at' => now()->subDays(18), 'updated_at' => now(),
        ]);
        DB::table('platform_invoice_items')->insert(['platform_invoice_id' => $invoiceId, 'item_type' => 'subscription', 'description' => $tenant['display'].' '.$tenant['cycle'].' subscription', 'quantity' => 1, 'unit_price' => $tenant['base'], 'amount' => $tenant['base'], 'metadata' => json_encode(['plan' => $tenant['plan']])]);
        return $invoiceId;
    }

    private function payment(int $tenantId, int $subscriptionId, int $invoiceId, array $tenant): int
    {
        $amount = $tenant['base'] - $tenant['discount'] + $tenant['tax'];
        return $this->upsertId('platform_payments', ['payment_number' => 'PAY-'.$tenant['code'].'-001'], [
            'uuid' => (string) Str::uuid(), 'payment_number' => 'PAY-'.$tenant['code'].'-001', 'tenant_id' => $tenantId, 'platform_invoice_id' => $invoiceId, 'subscription_id' => $subscriptionId,
            'gateway' => 'razorpay', 'gateway_payment_id' => 'pay_demo_'.strtolower($tenant['code']), 'payment_method' => $tenant['paid'] ? 'upi' : 'card', 'amount' => $amount,
            'currency' => 'INR', 'payment_status' => $tenant['paid'] ? 'captured' : 'failed', 'paid_at' => $tenant['paid'] ? now()->subDays(15) : null,
            'failure_reason' => $tenant['paid'] ? null : 'Payment authentication failed during demo billing cycle.', 'raw_response' => json_encode(['seeded' => true, 'gateway' => 'razorpay']),
            'created_at' => now()->subDays(15), 'updated_at' => now(),
        ]);
    }

    private function refund(int $tenantId, int $paymentId, array $tenant): void
    {
        DB::table('platform_refunds')->updateOrInsert(['refund_number' => 'REF-'.$tenant['code'].'-001'], ['uuid' => (string) Str::uuid(), 'tenant_id' => $tenantId, 'platform_payment_id' => $paymentId, 'amount' => $tenant['refund'], 'currency' => 'INR', 'reason' => 'Goodwill credit for onboarding delay.', 'status' => 'processed', 'refunded_at' => now()->subDays(7), 'raw_response' => json_encode(['seeded' => true]), 'created_at' => now()->subDays(7), 'updated_at' => now()]);
    }

    private function subscriptionExtras(int $tenantId, int $subscriptionId, int $planId, array $tenant, ?int $actor): void
    {
        DB::table('subscription_versions')->updateOrInsert(['subscription_id' => $subscriptionId, 'version' => 1], ['plan_id' => $planId, 'billing_cycle' => $tenant['cycle'], 'starts_at' => $tenant['onboarded_at'], 'ends_at' => null, 'pricing_snapshot' => json_encode(['base' => $tenant['base'], 'tax' => $tenant['tax'], 'discount' => $tenant['discount']]), 'feature_snapshot' => json_encode(['source' => 'demo_seed']), 'change_reason' => 'Initial seeded subscription', 'created_by' => $actor, 'created_at' => now()]);
        $addonCode = $tenant['plan'] === 'enterprise' ? 'advanced_analytics' : 'additional_storage_100gb';
        $addonId = DB::table('addon_plans')->where('code', $addonCode)->value('id');
        if ($addonId && $tenant['sub_status'] !== 'trial') DB::table('subscription_addons')->updateOrInsert(['subscription_id' => $subscriptionId, 'addon_plan_id' => $addonId], ['quantity' => 1, 'unit_price' => $addonCode === 'advanced_analytics' ? 1499 : 499, 'starts_at' => now()->subDays(30), 'ends_at' => null, 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
        foreach (DB::table('features')->limit(4)->pluck('id') as $featureId) DB::table('subscription_usage')->updateOrInsert(['tenant_id' => $tenantId, 'subscription_id' => $subscriptionId, 'feature_id' => $featureId, 'period_start' => now()->startOfMonth()->toDateString(), 'period_end' => now()->endOfMonth()->toDateString()], ['used_value' => 12 + $tenantId, 'limit_value' => 250]);
        DB::table('tenant_usage_snapshots')->updateOrInsert(['tenant_id' => $tenantId, 'period_start' => now()->startOfMonth()->toDateString(), 'period_end' => now()->endOfMonth()->toDateString()], ['users_count' => DB::table('users')->where('tenant_id', $tenantId)->count(), 'storage_bytes' => 750000000 + ($tenantId * 10000), 'api_requests' => 1200 + ($tenantId * 111), 'projects_count' => 3 + $tenantId, 'invoices_count' => 2 + $tenantId]);
    }

    private function parties(int $tenantId, int $ownerId, array $tenant, array $place): void
    {
        foreach ($tenant['contacts'] as $index => $contact) {
            [$type, $name, $email, $first, $last, $designation] = $contact;
            $partyId = $this->upsertId('parties', ['tenant_id' => $tenantId, 'display_name' => $name], ['uuid' => (string) Str::uuid(), 'tenant_id' => $tenantId, 'party_type' => $type, 'display_name' => $name, 'legal_name' => $name, 'email' => $email, 'phone' => '+9122400'.str_pad((string) ($tenantId * 100 + $index), 5, '0', STR_PAD_LEFT), 'industry_id' => $this->idByName('industries', $tenant['industry']), 'owner_user_id' => $ownerId, 'metadata' => json_encode(['seeded' => true]), 'created_by' => $ownerId, 'updated_by' => $ownerId, 'created_at' => now(), 'updated_at' => now()]);
            DB::table('party_contacts')->updateOrInsert(['tenant_id' => $tenantId, 'party_id' => $partyId, 'email' => strtolower($first.'.'.$last).'@'.Str::slug($name).'.example.com'], ['uuid' => (string) Str::uuid(), 'first_name' => $first, 'last_name' => $last, 'display_name' => $first.' '.$last, 'mobile' => '+919811'.str_pad((string) ($tenantId * 100 + $index), 6, '0', STR_PAD_LEFT), 'designation' => $designation, 'department' => $type === 'client' ? 'Procurement' : 'Account Management', 'is_primary' => true, 'portal_enabled' => $type === 'client', 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
            DB::table('party_addresses')->updateOrInsert(['tenant_id' => $tenantId, 'party_id' => $partyId, 'address_type' => 'billing'], ['address_line_1' => '88 Client Business Avenue', 'country_id' => $place['country'], 'state_id' => $place['state'], 'city_id' => $place['city'], 'postal_code' => '400002', 'is_default' => true, 'created_at' => now(), 'updated_at' => now()]);
            if ($type === 'client') DB::table('client_profiles')->updateOrInsert(['tenant_id' => $tenantId, 'party_id' => $partyId], ['client_code' => 'CL-'.$tenant['code'].'-'.str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT), 'client_type' => 'business', 'credit_limit' => 250000, 'payment_terms_days' => 30, 'onboarding_date' => now()->subDays(30)->toDateString(), 'account_manager_id' => $ownerId, 'created_at' => now(), 'updated_at' => now()]);
            else DB::table('vendor_profiles')->updateOrInsert(['tenant_id' => $tenantId, 'party_id' => $partyId], ['vendor_code' => 'VN-'.$tenant['code'].'-'.str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT), 'payment_terms_days' => 15, 'rating' => 4.30, 'created_at' => now(), 'updated_at' => now()]);
        }
    }

    private function settings(int $tenantId): void
    {
        foreach ([['general', 'date_format', 'd M Y'], ['billing', 'invoice_prefix', 'INV'], ['security', 'require_2fa', false]] as [$group, $key, $value]) {
            DB::table('tenant_settings')->updateOrInsert(['tenant_id' => $tenantId, 'group' => $group, 'key' => $key], ['value' => json_encode($value), 'is_encrypted' => false, 'created_at' => now(), 'updated_at' => now()]);
        }
    }

    private function onboarding(int $tenantId, ?int $actor): void
    {
        foreach ([['company_profile', 'completed'], ['billing_setup', 'completed'], ['invite_team', 'in_progress'], ['import_contacts', 'pending']] as [$step, $status]) {
            DB::table('tenant_onboarding_steps')->updateOrInsert(['tenant_id' => $tenantId, 'step_code' => $step], ['status' => $status, 'metadata' => json_encode(['seeded' => true]), 'updated_by' => $actor, 'created_at' => now(), 'updated_at' => now()]);
        }
    }

    private function upsertId(string $table, array $match, array $data): int
    {
        DB::table($table)->updateOrInsert($match, $data);
        return (int) DB::table($table)->where($match)->value('id');
    }

    private function idByName(string $table, string $name): ?int
    {
        return DB::table($table)->where('name', $name)->value('id');
    }

    private function place(string $state, string $city, ?int $countryId): array
    {
        $stateId = DB::table('states')->where('country_id', $countryId)->where('name', $state)->value('id') ?? DB::table('states')->where('name', $state)->value('id');
        $cityId = DB::table('cities')->where('state_id', $stateId)->where('name', $city)->value('id') ?? DB::table('cities')->where('name', $city)->value('id');
        return ['country' => $countryId, 'state' => $stateId, 'city' => $cityId];
    }
}

