<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PlatformDashboardDemoSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        foreach ([
            'dashboard-growth' => ['Dashboard Growth', 4999],
            'dashboard-scale' => ['Dashboard Scale', 12999],
        ] as $code => [$name, $price]) {
            DB::table('plans')->updateOrInsert(['code' => $code], [
                'uuid' => (string) Str::uuid(),
                'name' => $name,
                'description' => 'Local platform dashboard demo plan.',
                'billing_cycle' => 'monthly',
                'base_price' => $price,
                'currency' => 'INR',
                'trial_days' => 14,
                'is_custom' => false,
                'is_public' => true,
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $planIds = DB::table('plans')->whereIn('code', ['dashboard-growth', 'dashboard-scale'])->pluck('id', 'code');
        $tenants = [
            ['dash-acme', 'DASH-ACME', 'Acme CRM Labs', 'active', 'dashboard-growth', 'active', 'Anika Rao', 'owner.acme.dashboard@example.test', 4999],
            ['dash-globex', 'DASH-GLOBEX', 'Globex Sales Hub', 'trial', 'dashboard-scale', 'trial', 'Kabir Mehta', 'owner.globex.dashboard@example.test', 0],
            ['dash-initech', 'DASH-INITECH', 'Initech Support Desk', 'suspended', 'dashboard-growth', 'suspended', 'Neha Shah', 'owner.initech.dashboard@example.test', 4999],
            ['dash-stark', 'DASH-STARK', 'Stark Automation', 'active', 'dashboard-scale', 'active', 'Rohan Iyer', 'owner.stark.dashboard@example.test', 12999],
        ];

        foreach ($tenants as $index => [$slug, $code, $name, $status, $plan, $subStatus, $owner, $email, $amount]) {
            DB::table('tenants')->updateOrInsert(['slug' => $slug], [
                'uuid' => (string) Str::uuid(),
                'organization_name' => $name,
                'legal_name' => $name.' Private Limited',
                'display_name' => $name,
                'organization_code' => $code,
                'company_size' => $index === 1 ? 'small' : 'medium',
                'default_currency' => 'INR',
                'default_timezone' => 'Asia/Kolkata',
                'onboarded_at' => $now->copy()->subDays(35 - ($index * 6)),
                'trial_ends_at' => $index === 1 ? $now->copy()->addDays(9) : null,
                'status' => $status,
                'created_at' => $now->copy()->subDays(35 - ($index * 6)),
                'updated_at' => $now,
            ]);

            $tenantId = DB::table('tenants')->where('slug', $slug)->value('id');
            DB::table('tenant_offices')->updateOrInsert(['tenant_id' => $tenantId, 'office_code' => 'HO'], [
                'uuid' => (string) Str::uuid(),
                'office_name' => 'Head Office',
                'office_type' => 'head_office',
                'is_head_office' => true,
                'is_default' => true,
                'contact_person' => $owner,
                'contact_email' => $email,
                'timezone' => 'Asia/Kolkata',
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $officeId = DB::table('tenant_offices')->where('tenant_id', $tenantId)->where('office_code', 'HO')->value('id');
            [$first, $last] = explode(' ', $owner, 2);
            DB::table('users')->updateOrInsert(['tenant_id' => $tenantId, 'email' => $email], [
                'uuid' => (string) Str::uuid(),
                'default_office_id' => $officeId,
                'employee_code' => 'OWNER-'.$code,
                'first_name' => $first,
                'last_name' => $last,
                'display_name' => $owner,
                'password' => Hash::make('Password@123'),
                'timezone' => 'Asia/Kolkata',
                'locale' => 'en',
                'email_verified_at' => $now,
                'two_factor_enabled' => false,
                'account_type' => 'owner',
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $subNo = 'DASH-SUB-'.($index + 1);
            DB::table('subscriptions')->updateOrInsert(['subscription_number' => $subNo], [
                'uuid' => (string) Str::uuid(),
                'tenant_id' => $tenantId,
                'plan_id' => $planIds[$plan],
                'current_version' => 1,
                'type' => 'standard',
                'billing_cycle' => 'monthly',
                'status' => $subStatus,
                'renewal_type' => 'auto',
                'starts_at' => $now->copy()->subDays(35 - ($index * 6)),
                'expires_at' => $now->copy()->addDays(25 + ($index * 6)),
                'next_billing_at' => $now->copy()->addDays(5 + ($index * 4)),
                'base_amount' => $amount,
                'taxable_amount' => $amount,
                'tax_amount' => round($amount * 0.18, 2),
                'payable_amount' => round($amount * 1.18, 2),
                'currency' => 'INR',
                'auto_renew' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $subscriptionId = DB::table('subscriptions')->where('subscription_number', $subNo)->value('id');
            $total = round($amount * 1.18, 2);
            $isOverdue = $index === 2;
            $invoiceStatus = $isOverdue ? 'overdue' : ($index === 1 ? 'draft' : 'paid');
            $paidAmount = $invoiceStatus === 'paid' ? $total : 0;
            $invoiceNo = 'DASH-INV-'.($index + 1);

            DB::table('platform_invoices')->updateOrInsert(['invoice_number' => $invoiceNo], [
                'uuid' => (string) Str::uuid(),
                'tenant_id' => $tenantId,
                'subscription_id' => $subscriptionId,
                'invoice_date' => $now->copy()->subDays(18 - $index)->toDateString(),
                'due_date' => $isOverdue ? $now->copy()->subDays(4)->toDateString() : $now->copy()->addDays(12 + $index)->toDateString(),
                'subtotal' => $amount,
                'taxable_amount' => $amount,
                'tax_amount' => round($amount * 0.18, 2),
                'total_amount' => $total,
                'paid_amount' => $paidAmount,
                'balance_amount' => max($total - $paidAmount, 0),
                'currency' => 'INR',
                'status' => $invoiceStatus,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $invoiceId = DB::table('platform_invoices')->where('invoice_number', $invoiceNo)->value('id');
            $paymentStatus = $index === 2 ? 'failed' : ($index === 1 ? 'pending' : 'paid');
            DB::table('platform_payments')->updateOrInsert(['payment_number' => 'DASH-PAY-'.($index + 1)], [
                'uuid' => (string) Str::uuid(),
                'tenant_id' => $tenantId,
                'platform_invoice_id' => $invoiceId,
                'subscription_id' => $subscriptionId,
                'gateway' => $index % 2 === 0 ? 'razorpay' : 'stripe',
                'gateway_payment_id' => 'dash-gateway-'.($index + 1),
                'payment_method' => $index % 2 === 0 ? 'card' : 'upi',
                'amount' => $total,
                'currency' => 'INR',
                'payment_status' => $paymentStatus,
                'paid_at' => $paymentStatus === 'paid' ? $now->copy()->subDays(3 + $index) : null,
                'failure_reason' => $paymentStatus === 'failed' ? 'Demo card authorization failed' : null,
                'raw_response' => json_encode(['source' => 'platform-dashboard-demo']),
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            for ($period = 0; $period < 6; $period++) {
                $periodStart = $now->copy()->subMonths(5 - $period)->startOfMonth();
                DB::table('tenant_usage_snapshots')->updateOrInsert(
                    ['tenant_id' => $tenantId, 'period_start' => $periodStart->toDateString(), 'period_end' => $periodStart->copy()->endOfMonth()->toDateString()],
                    [
                        'users_count' => 8 + ($index * 5) + $period,
                        'storage_bytes' => (8 + $index + $period) * 1024 * 1024 * 1024,
                        'api_requests' => 1800 + ($index * 600) + ($period * 230),
                        'projects_count' => 4 + $index + $period,
                        'invoices_count' => 3 + $index + $period,
                    ]
                );
            }
        }

        DB::table('queue_job_logs')->updateOrInsert(['job_name' => 'DashboardDemoFailedInvoiceJob', 'queue' => 'billing'], [
            'status' => 'failed',
            'attempts' => 3,
            'exception' => 'Demo timeout while syncing invoice.',
            'started_at' => $now->copy()->subMinutes(55),
            'finished_at' => $now->copy()->subMinutes(53),
        ]);
        DB::table('queue_job_logs')->updateOrInsert(['job_name' => 'DashboardDemoWebhookJob', 'queue' => 'webhooks'], [
            'status' => 'completed',
            'attempts' => 1,
            'exception' => null,
            'started_at' => $now->copy()->subMinutes(25),
            'finished_at' => $now->copy()->subMinutes(24),
        ]);

        DB::table('system_incidents')->updateOrInsert(['title' => 'Dashboard Demo Billing Worker Delay'], [
            'severity' => 'warning',
            'status' => 'investigating',
            'started_at' => $now->copy()->subHours(2),
            'resolved_at' => null,
            'summary' => 'Billing queue latency is above the dashboard demo threshold.',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('system_incidents')->updateOrInsert(['title' => 'Dashboard Demo Resolved API Latency'], [
            'severity' => 'info',
            'status' => 'resolved',
            'started_at' => $now->copy()->subDays(2),
            'resolved_at' => $now->copy()->subDay(),
            'summary' => 'Transient API latency recovered.',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('monitoring_alerts')->updateOrInsert(
            ['alertable_type' => 'queue_job_logs', 'alertable_id' => 1, 'message' => 'Dashboard demo billing worker queue delay'],
            ['severity' => 'warning', 'status' => 'open', 'triggered_at' => $now->copy()->subMinutes(40), 'resolved_at' => null]
        );

        $tenantId = DB::table('tenants')->where('slug', 'dash-acme')->value('id');
        $userId = DB::table('users')->where('tenant_id', $tenantId)->where('email', 'owner.acme.dashboard@example.test')->value('id');
        DB::table('security_events')->updateOrInsert(['tenant_id' => $tenantId, 'event' => 'dashboard.demo.multiple_failed_logins'], [
            'user_id' => $userId,
            'severity' => 'warning',
            'ip_address' => '127.0.0.1',
            'metadata' => json_encode(['attempts' => 4, 'source' => 'dashboard-demo']),
            'created_at' => $now->copy()->subMinutes(35),
        ]);
    }
}

