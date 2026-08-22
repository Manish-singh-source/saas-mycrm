<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Shared\AuthAuditService;
use App\Services\Shared\CrmMailerService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class TenantRegistrationController extends Controller
{
    public function __construct(private readonly AuthAuditService $audit, private readonly CrmMailerService $mailer) {}

    public function plans(): JsonResponse
    {
        $plans = DB::table('plans')
            ->where('status', 'active')
            ->where('is_public', true)
            ->whereNull('deleted_at')
            ->orderBy('base_price')
            ->get(['uuid', 'name', 'code', 'billing_cycle', 'base_price', 'currency', 'trial_days', 'description']);

        return ApiResponse::success(['plans' => $plans], 'Public plans fetched successfully.');
    }
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'organization_name' => ['required', 'string', 'max:200'],
            'legal_name' => ['nullable', 'string', 'max:200'],
            'display_name' => ['nullable', 'string', 'max:200'],
            'organization_code' => ['nullable', 'string', 'max:50', 'alpha_dash', Rule::unique('tenants', 'organization_code')],
            'slug' => ['nullable', 'string', 'max:150', 'alpha_dash', Rule::unique('tenants', 'slug')],
            'business_type_id' => ['nullable', 'integer', 'exists:business_types,id'],
            'industry_id' => ['nullable', 'integer', 'exists:industries,id'],
            'company_size' => ['nullable', Rule::in(['self', 'small', 'medium', 'large', 'enterprise'])],
            'gst_number' => ['nullable', 'string', 'max:30'],
            'pan_number' => ['nullable', 'string', 'max:30'],
            'registration_number' => ['nullable', 'string', 'max:80'],
            'website' => ['nullable', 'string', 'max:255'],
            'default_currency' => ['nullable', 'string', 'size:3'],
            'default_timezone' => ['nullable', 'string', 'max:100'],
            'owner.first_name' => ['required', 'string', 'max:100'],
            'owner.last_name' => ['nullable', 'string', 'max:100'],
            'owner.display_name' => ['nullable', 'string', 'max:200'],
            'owner.email' => ['required', 'email', 'max:150'],
            'owner.mobile' => ['nullable', 'string', 'max:20'],
            'owner.password' => ['required', 'confirmed', 'min:8'],
            'office.office_name' => ['nullable', 'string', 'max:150'],
            'office.address_line_1' => ['nullable', 'string', 'max:255'],
            'office.address_line_2' => ['nullable', 'string', 'max:255'],
            'office.landmark' => ['nullable', 'string', 'max:255'],
            'office.country_id' => ['nullable', 'integer', 'exists:countries,id'],
            'office.state_id' => ['nullable', 'integer', 'exists:states,id'],
            'office.city_id' => ['nullable', 'integer', 'exists:cities,id'],
            'office.postal_code' => ['nullable', 'string', 'max:20'],
            'office.contact_phone' => ['nullable', 'string', 'max:20'],
            'plan_uuid' => [
                'nullable',
                'uuid',
                Rule::exists('plans', 'uuid')->where(fn ($query) => $query->where('status', 'active')->where('is_public', true)),
            ],
            'trial_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'subscription.type' => ['nullable', 'string', 'max:50'],
            'subscription.billing_cycle' => ['nullable', 'string', 'max:50'],
            'payment.method' => ['nullable', Rule::in(['online', 'cash', 'free'])],
        ]);

        $email = Str::lower($data['owner']['email']);

        return DB::transaction(function () use ($request, $data, $email): JsonResponse {
            $tenant = Tenant::query()->create([
                'uuid' => (string) Str::uuid(),
                'organization_name' => $data['organization_name'],
                'legal_name' => $data['legal_name'] ?? null,
                'display_name' => $data['display_name'] ?? $data['organization_name'],
                'organization_code' => $data['organization_code'] ?? $this->uniqueCode($data['organization_name']),
                'slug' => $data['slug'] ?? $this->uniqueSlug($data['organization_name']),
                'business_type_id' => $data['business_type_id'] ?? null,
                'industry_id' => $data['industry_id'] ?? null,
                'company_size' => $data['company_size'] ?? null,
                'gst_number' => $data['gst_number'] ?? null,
                'pan_number' => $data['pan_number'] ?? null,
                'registration_number' => $data['registration_number'] ?? null,
                'website' => $data['website'] ?? null,
                'default_currency' => strtoupper($data['default_currency'] ?? 'INR'),
                'default_timezone' => $data['default_timezone'] ?? 'Asia/Kolkata',
                'onboarded_at' => now(),
                'trial_ends_at' => now()->addDays((int) ($data['trial_days'] ?? 15)),
                'status' => 'trial',
            ]);

            $officeId = $this->createHeadOffice($tenant, $data['office'] ?? [], $data['owner']);
            $owner = User::query()->create([
                'uuid' => (string) Str::uuid(),
                'tenant_id' => $tenant->id,
                'default_office_id' => $officeId,
                'first_name' => $data['owner']['first_name'],
                'last_name' => $data['owner']['last_name'] ?? null,
                'display_name' => $data['owner']['display_name'] ?? trim($data['owner']['first_name'].' '.($data['owner']['last_name'] ?? '')),
                'email' => $email,
                'mobile' => $data['owner']['mobile'] ?? null,
                'password' => Hash::make($data['owner']['password']),
                'timezone' => $tenant->default_timezone,
                'locale' => 'en',
                'account_type' => 'owner',
                'email_verified_at' => now(),
                'status' => 'active',
            ]);

            $this->assignOwnerRole($tenant, $owner);
            $subscription = $this->createInitialSubscription($request, $tenant, $data);
            $invoice = $this->createRegistrationInvoice($tenant, $subscription);
            $paymentOrder = $this->createRegistrationPayment($tenant, $subscription, $invoice, $data);
            $token = $owner->createToken('tenant-registration', ['tenant:'.$tenant->uuid], now()->addHours(12));
            $this->audit->log($request, 'tenant_registered', tenantUser: $owner, metadata: ['tenant_uuid' => $tenant->uuid, 'tenant_slug' => $tenant->slug]);
            $this->sendRegistrationEmail($owner, $tenant, $subscription, $invoice['invoice'] ?? null, $paymentOrder['payment'] ?? null);

            return ApiResponse::success([
                'access_token' => $token->plainTextToken,
                'token_type' => 'Bearer',
                'expires_at' => optional($token->accessToken->expires_at)->toISOString(),
                'tenant' => $tenant->only(['uuid', 'organization_name', 'display_name', 'organization_code', 'slug', 'default_currency', 'default_timezone', 'status', 'trial_ends_at']),
                'owner' => $owner->only(['uuid', 'display_name', 'email', 'mobile', 'account_type', 'status']),
                'subscription' => $subscription,
                'invoice' => $invoice['invoice'] ?? null,
                'invoice_items' => $invoice['items'] ?? [],
                'payment_order' => $paymentOrder['order'] ?? null,
                'payment' => $paymentOrder['payment'] ?? null,
                'razorpay_key' => $paymentOrder['key'] ?? null,
                'roles' => ['owner'],
                'permissions' => DB::table('permissions')->where('guard_name', 'tenant')->pluck('name')->all(),
            ], 'Tenant registered.', Response::HTTP_CREATED);
        });
    }

    private function createInitialSubscription(Request $request, Tenant $tenant, array $data): ?object
    {
        $plan = ! empty($data['plan_uuid'])
            ? DB::table('plans')->where('uuid', $data['plan_uuid'])->first()
            : DB::table('plans')->where('status', 'active')->where('is_public', true)->orderBy('base_price')->first();

        if (! $plan) {
            return null;
        }

        $type = $data['subscription']['type'] ?? (((float) $plan->base_price) > 0 ? 'trial' : 'free');
        $billingCycle = $data['subscription']['billing_cycle'] ?? $plan->billing_cycle;
        $trialDays = (int) ($data['trial_days'] ?? $plan->trial_days ?? 15);
        $subscriptionId = DB::table('subscriptions')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'subscription_number' => 'SUB-' . Str::upper(Str::random(10)),
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'type' => $type,
            'billing_cycle' => $billingCycle,
            'status' => $type === 'paid' ? 'pending_payment' : 'trial',
            'renewal_type' => 'manual',
            'starts_at' => now(),
            'trial_starts_at' => now(),
            'trial_ends_at' => now()->addDays($trialDays),
            'base_amount' => $plan->base_price,
            'taxable_amount' => $plan->base_price,
            'payable_amount' => $plan->base_price,
            'currency' => $plan->currency,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('subscription_versions')->insert(['subscription_id' => $subscriptionId, 'version' => 1, 'plan_id' => $plan->id, 'billing_cycle' => $billingCycle, 'starts_at' => now(), 'pricing_snapshot' => json_encode((array) $plan), 'created_at' => now()]);

        return DB::table('subscriptions')->where('id', $subscriptionId)->first();
    }
    private function createRegistrationInvoice(Tenant $tenant, ?object $subscription): ?array
    {
        if (! $subscription) {
            return null;
        }

        $amount = (float) $subscription->payable_amount;
        $currency = $subscription->currency ?? $tenant->default_currency ?? 'INR';
        $invoiceId = DB::table('platform_invoices')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'invoice_number' => 'INV-' . Str::upper(Str::random(10)),
            'tenant_id' => $tenant->id,
            'subscription_id' => $subscription->id,
            'invoice_date' => now()->toDateString(),
            'due_date' => $amount > 0 ? now()->addDays(7)->toDateString() : now()->toDateString(),
            'subtotal' => $subscription->base_amount,
            'discount_amount' => $subscription->discount_amount ?? 0,
            'taxable_amount' => $subscription->taxable_amount,
            'tax_amount' => $subscription->tax_amount ?? 0,
            'total_amount' => $amount,
            'paid_amount' => $amount <= 0 ? 0 : 0,
            'balance_amount' => $amount,
            'currency' => $currency,
            'status' => $amount <= 0 ? 'paid' : 'sent',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('platform_invoice_items')->insert([
            'platform_invoice_id' => $invoiceId,
            'item_type' => 'subscription',
            'description' => 'Initial subscription charge for ' . $subscription->subscription_number,
            'quantity' => 1,
            'unit_price' => $subscription->base_amount,
            'amount' => $subscription->base_amount,
            'metadata' => json_encode([
                'subscription_uuid' => $subscription->uuid,
                'billing_cycle' => $subscription->billing_cycle,
            ]),
        ]);

        DB::table('subscriptions')->where('id', $subscription->id)->update([
            'last_platform_invoice_id' => $invoiceId,
            'updated_at' => now(),
        ]);

        return [
            'invoice' => DB::table('platform_invoices')->where('id', $invoiceId)->first(),
            'items' => DB::table('platform_invoice_items')->where('platform_invoice_id', $invoiceId)->get(),
        ];
    }

    private function createRegistrationPayment(Tenant $tenant, ?object $subscription, ?array $invoice, array $data): ?array
    {
        if (! $subscription || ! ($invoice['invoice'] ?? null)) {
            return null;
        }

        $method = $data['payment']['method'] ?? 'free';
        $amount = (float) $subscription->payable_amount;
        $invoiceRow = $invoice['invoice'];

        if ($method !== 'online' || $amount <= 0) {
            $payment = $this->createRegistrationPaymentRecord($tenant, $subscription, $invoiceRow, [
                'gateway' => $method === 'cash' ? 'cash' : null,
                'gateway_payment_id' => null,
                'payment_method' => $method,
                'amount' => $amount,
                'payment_status' => $amount <= 0 ? 'paid' : 'pending',
                'paid_at' => $amount <= 0 ? now() : null,
                'raw_response' => ['source' => 'tenant_registration', 'method' => $method],
            ]);

            if ($amount <= 0) {
                DB::table('platform_invoices')->where('id', $invoiceRow->id)->update([
                    'paid_amount' => 0,
                    'balance_amount' => 0,
                    'status' => 'paid',
                    'updated_at' => now(),
                ]);
            }

            return ['payment' => $payment];
        }

        $key = env('RAZORPAY_KEY_ID');
        $secret = env('RAZORPAY_KEY_SECRET');
        if (! $key || ! $secret) {
            throw ValidationException::withMessages([
                'payment.method' => 'Razorpay is not configured for online registration payments.',
            ]);
        }

        $orderResponse = Http::withBasicAuth($key, $secret)->post('https://api.razorpay.com/v1/orders', [
            'amount' => (int) round($amount * 100),
            'currency' => $subscription->currency ?? $tenant->default_currency ?? 'INR',
            'receipt' => 'reg-'.$tenant->id.'-'.Str::lower(Str::random(8)),
            'notes' => [
                'tenant_uuid' => $tenant->uuid,
                'subscription_uuid' => $subscription->uuid,
                'invoice_uuid' => $invoiceRow->uuid,
            ],
        ]);

        if (! $orderResponse->successful()) {
            throw ValidationException::withMessages([
                'payment.method' => 'Unable to initialize Razorpay payment. Please try again.',
            ]);
        }

        $order = $orderResponse->json();
        $payment = $this->createRegistrationPaymentRecord($tenant, $subscription, $invoiceRow, [
            'gateway' => 'razorpay',
            'gateway_payment_id' => $order['id'] ?? null,
            'payment_method' => 'online',
            'amount' => $amount,
            'payment_status' => 'pending',
            'paid_at' => null,
            'raw_response' => $order,
        ]);

        return [
            'key' => $key,
            'order' => $order,
            'payment' => $payment,
        ];
    }

    private function createRegistrationPaymentRecord(Tenant $tenant, object $subscription, object $invoice, array $payment): object
    {
        $paymentId = DB::table('platform_payments')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'payment_number' => 'PAY-' . Str::upper(Str::random(10)),
            'tenant_id' => $tenant->id,
            'platform_invoice_id' => $invoice->id,
            'subscription_id' => $subscription->id,
            'gateway' => $payment['gateway'],
            'gateway_payment_id' => $payment['gateway_payment_id'],
            'payment_method' => $payment['payment_method'],
            'amount' => $payment['amount'],
            'currency' => $subscription->currency ?? $tenant->default_currency ?? 'INR',
            'payment_status' => $payment['payment_status'],
            'paid_at' => $payment['paid_at'],
            'raw_response' => json_encode($payment['raw_response']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('subscriptions')->where('id', $subscription->id)->update([
            'last_platform_payment_id' => $paymentId,
            'updated_at' => now(),
        ]);

        return DB::table('platform_payments')->where('id', $paymentId)->first();
    }    private function createHeadOffice(Tenant $tenant, array $office, array $owner): int
    {
        return (int) DB::table('tenant_offices')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'tenant_id' => $tenant->id,
            'office_name' => $office['office_name'] ?? 'Head Office',
            'office_code' => 'HO',
            'office_type' => 'head_office',
            'is_head_office' => true,
            'is_default' => true,
            'address_line_1' => $office['address_line_1'] ?? null,
            'address_line_2' => $office['address_line_2'] ?? null,
            'landmark' => $office['landmark'] ?? null,
            'country_id' => $office['country_id'] ?? null,
            'state_id' => $office['state_id'] ?? null,
            'city_id' => $office['city_id'] ?? null,
            'postal_code' => $office['postal_code'] ?? null,
            'contact_person' => $owner['display_name'] ?? trim($owner['first_name'].' '.($owner['last_name'] ?? '')),
            'contact_email' => Str::lower($owner['email']),
            'contact_phone' => $office['contact_phone'] ?? $owner['mobile'] ?? null,
            'timezone' => $tenant->default_timezone,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function assignOwnerRole(Tenant $tenant, User $owner): void
    {
        $roleId = (int) DB::table('roles')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'tenant_id' => $tenant->id,
            'name' => 'owner',
            'display_name' => 'Owner',
            'guard_name' => 'tenant',
            'description' => 'Full tenant ownership access.',
            'is_system' => true,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach (DB::table('permissions')->where('guard_name', 'tenant')->pluck('id') as $permissionId) {
            DB::table('role_has_permissions')->insert(['role_id' => $roleId, 'permission_id' => $permissionId]);
        }

        DB::table('model_has_roles')->insert([
            'tenant_id' => $tenant->id,
            'role_id' => $roleId,
            'model_id' => $owner->id,
            'model_type' => User::class,
        ]);
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'tenant';
        $slug = $base;
        $i = 2;
        while (Tenant::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }
        return $slug;
    }

    private function uniqueCode(string $name): string
    {
        $base = Str::upper(Str::substr(preg_replace('/[^A-Za-z0-9]/', '', $name) ?: 'TENANT', 0, 12));
        $code = $base;
        $i = 2;
        while (Tenant::query()->where('organization_code', $code)->exists()) {
            $code = Str::substr($base, 0, 10).$i++;
        }
        return $code;
    }
}







