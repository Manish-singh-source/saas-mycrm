<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Shared\BaseApiController;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Platform\PlatformAdminService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PlatformTenantController extends BaseApiController
{
    public function __construct(private readonly PlatformAdminService $admin) {}

    public function index(Request $request)
    {
        $q = DB::table('tenants')->whereNull('deleted_at');
        if ($request->filled('search')) $q->where(fn($x) => $x->where('organization_name', 'like', '%' . $request->string('search') . '%')->orWhere('slug', 'like', '%' . $request->string('search') . '%')->orWhere('organization_code', 'like', '%' . $request->string('search') . '%'));
        if ($request->filled('filter.status')) $q->where('status', $request->input('filter.status'));
        $p = $q->latest('id')->paginate((int) $request->integer('per_page', 10));
        $rows = collect($p->items())->map(fn ($tenant) => $this->tenantSummary($tenant))->all();

        return $this->list($rows, $p, 'Tenants fetched successfully.', ['stats' => $this->tenantStats()]);
    }

    public function store(Request $request)
    {
        $data = $this->tenantData($request);

        return DB::transaction(function () use ($request, $data) {
            $result = $this->createTenantFromData($request, $data);

            return $this->success($result, 'Tenant created.', 201);
        });
    }

    public function show(string $tenant_uuid)
    {
        $tenant = $this->tenant($tenant_uuid);

        return $this->success($this->tenantDetail($tenant), 'Tenant fetched successfully.');
    }

    public function update(Request $request, string $tenant_uuid)
    {
        $tenant = $this->tenant($tenant_uuid);
        $data = $this->tenantData($request, $tenant->id, true);

        return DB::transaction(function () use ($request, $tenant, $data) {
            $tenantPayload = $this->tenantPayload($data, $tenant, true);
            if ($tenantPayload !== []) {
                DB::table('tenants')->where('id', $tenant->id)->update([...$tenantPayload, 'updated_at' => now()]);
            }

            if (! empty($data['owner'])) {
                $this->upsertOwner($tenant->id, $data['owner'], $data, true);
            }

            if (! empty($data['office'])) {
                $this->upsertOffice($tenant->id, $data['office'], $data, $request->user()?->id, true);
            }

            if (! empty($data['subscription'])) {
                $this->upsertSubscription($request, $tenant->id, $data['subscription'], $data['plan_uuid'] ?? null, true);
            }

            $fresh = DB::table('tenants')->where('id', $tenant->id)->first();
            $this->admin->audit($request, 'tenant_updated', Tenant::class, $tenant->id, (array) $tenant, (array) $fresh);

            return $this->success($this->tenantDetail($fresh), 'Tenant updated.');
        });
    }
    public function destroy(Request $r, string $uuid)
    {
        return $this->tenantStatus($r, $uuid, 'archived', 'tenant_archived');
    }
    public function restore(Request $r, string $uuid)
    {
        $tenant = Tenant::withTrashed()->where('uuid', $uuid)->firstOrFail();
        $tenant->restore();
        return $this->success(['tenant' => $tenant->fresh()], 'Tenant restored.');
    }
    public function activate(Request $r, string $uuid)
    {
        return $this->tenantStatus($r, $uuid, 'active', 'tenant_activated');
    }
    public function suspend(Request $r, string $uuid)
    {
        $r->validate(['reason' => ['required', 'string'], 'notify_owner' => ['nullable', 'boolean'], 'suspended_until' => ['nullable', 'date']]);
        return $this->tenantStatus($r, $uuid, 'suspended', 'tenant_suspended');
    }
    public function reactivate(Request $r, string $uuid)
    {
        return $this->tenantStatus($r, $uuid, 'active', 'tenant_reactivated');
    }
    public function archive(Request $r, string $uuid)
    {
        return $this->tenantStatus($r, $uuid, 'archived', 'tenant_archived');
    }

    public function bulkDelete(Request $request)
    {
        $data = $request->validate([
            'tenant_uuids' => ['required', 'array', 'min:1'],
            'tenant_uuids.*' => ['required', 'uuid'],
            'reason' => ['nullable', 'string'],
        ]);
        $tenants = DB::table('tenants')->whereIn('uuid', $data['tenant_uuids'])->whereNull('deleted_at')->get();
        $archived = 0;

        DB::transaction(function () use ($request, $tenants, $data, &$archived): void {
            foreach ($tenants as $tenant) {
                DB::table('tenants')->where('id', $tenant->id)->update(['status' => 'archived', 'deleted_at' => now(), 'updated_at' => now()]);
                $this->admin->audit($request, 'tenant_archived', Tenant::class, $tenant->id, (array) $tenant, ['status' => 'archived'], $data['reason'] ?? 'Bulk tenant archive');
                $archived++;
            }
        });

        return $this->success(['archived' => $archived], 'Tenant bulk delete completed.');
    }

    public function changePlan(Request $request, string $uuid)
    {
        $tenant = $this->tenant($uuid);
        $data = $request->validate([
            'plan_uuid' => ['required', 'uuid', 'exists:plans,uuid'],
            'billing_cycle' => ['nullable', 'string', 'max:50'],
            'starts_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date'],
            'renewal_type' => ['nullable', Rule::in(['manual', 'auto'])],
            'auto_renew' => ['nullable', 'boolean'],
            'reason' => ['nullable', 'string'],
        ]);

        return DB::transaction(function () use ($request, $tenant, $data) {
            $subscription = $this->upsertSubscription($request, $tenant->id, $data, $data['plan_uuid'], true);
            $this->admin->audit($request, 'tenant_plan_changed', Tenant::class, $tenant->id, null, $data, $data['reason'] ?? null);
            return $this->success(['subscription' => $subscription], 'Tenant plan changed.');
        });
    }
    public function extendTrial(Request $r, string $uuid)
    {
        $d = $r->validate(['trial_ends_at' => ['required', 'date'], 'reason' => ['nullable', 'string']]);
        $tenant = $this->tenant($uuid);
        $old = (array) $tenant;
        DB::table('tenants')->where('id', $tenant->id)->update(['trial_ends_at' => $d['trial_ends_at'], 'updated_at' => now()]);
        DB::table('subscriptions')->where('tenant_id', $tenant->id)->latest('id')->limit(1)->update(['trial_ends_at' => $d['trial_ends_at'], 'updated_at' => now()]);
        $this->admin->audit($r, 'tenant_trial_extended', Tenant::class, $tenant->id, $old, ['trial_ends_at' => $d['trial_ends_at']], $d['reason'] ?? null);
        return $this->success(['tenant' => DB::table('tenants')->where('id', $tenant->id)->first()], 'Trial extended.');
    }

    public function resetOwnerPassword(Request $request, string $uuid)
    {
        $tenant = $this->tenant($uuid);
        $data = $request->validate([
            'password' => ['nullable', 'string', 'min:8'],
            'notify_owner' => ['nullable', 'boolean'],
            'reason' => ['nullable', 'string'],
        ]);
        $password = $data['password'] ?? Str::password(16);
        $owner = DB::table('users')->where('tenant_id', $tenant->id)->where('account_type', 'owner')->first();
        abort_if(! $owner, 404, 'Tenant owner not found.');

        DB::table('users')->where('id', $owner->id)->update(['password' => Hash::make($password), 'updated_at' => now()]);
        $this->admin->security($request, 'tenant_owner_password_reset', 'warning', $tenant->id, ['notify_owner' => (bool) ($data['notify_owner'] ?? false)]);
        $this->admin->audit($request, 'tenant_owner_password_reset', Tenant::class, $tenant->id, null, ['owner_uuid' => $owner->uuid], $data['reason'] ?? null);

        return $this->success([
            'owner' => DB::table('users')->where('id', $owner->id)->first(),
            'temporary_password' => app()->isLocal() ? $password : null,
        ], 'Owner password reset.');
    }

    public function paymentOrder(Request $request, string $uuid)
    {
        $tenant = $this->tenant($uuid);
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
            'currency' => ['nullable', 'string', 'size:3'],
            'method' => ['required', Rule::in(['online', 'cash'])],
            'subscription_uuid' => ['nullable', 'uuid'],
            'notes' => ['nullable', 'array'],
        ]);
        $subscription = ! empty($data['subscription_uuid']) ? DB::table('subscriptions')->where('tenant_id', $tenant->id)->where('uuid', $data['subscription_uuid'])->first() : DB::table('subscriptions')->where('tenant_id', $tenant->id)->latest('id')->first();
        $currency = strtoupper($data['currency'] ?? $subscription->currency ?? $tenant->default_currency ?? 'INR');

        if ($data['method'] === 'cash') {
            $payment = $this->recordPlatformPayment($tenant->id, $subscription?->id, (float) $data['amount'], $currency, 'cash', 'paid', ['method' => 'cash', 'notes' => $data['notes'] ?? []]);
            return $this->success(['payment' => $payment], 'Cash payment recorded.', 201);
        }

        $key = config('services.razorpay.key') ?: env('RAZORPAY_KEY_ID');
        $secret = config('services.razorpay.secret') ?: env('RAZORPAY_KEY_SECRET');
        if (! $key || ! $secret) {
            return ApiResponse::businessError('Razorpay credentials are not configured.', 'RAZORPAY_NOT_CONFIGURED', 503);
        }

        $orderResponse = Http::withBasicAuth($key, $secret)->post('https://api.razorpay.com/v1/orders', [
            'amount' => (int) round(((float) $data['amount']) * 100),
            'currency' => $currency,
            'receipt' => 'tenant-'.$tenant->id.'-'.Str::lower(Str::random(8)),
            'notes' => ['tenant_uuid' => $tenant->uuid, ...($data['notes'] ?? [])],
        ]);

        if (! $orderResponse->successful()) {
            return ApiResponse::businessError('Unable to create Razorpay order.', 'RAZORPAY_ORDER_FAILED', 502, ['gateway' => $orderResponse->json() ?? $orderResponse->body()]);
        }

        $order = $orderResponse->json();
        $payment = $this->recordPlatformPayment($tenant->id, $subscription?->id, (float) $data['amount'], $currency, 'razorpay', 'pending', $order, $order['id'] ?? null);
        return $this->success(['order' => $order, 'payment' => $payment, 'razorpay_key' => $key], 'Razorpay order created.', 201);
    }
    public function remoteLogin(Request $r, string $uuid)
    {
        $d = $r->validate(['reason' => ['required', 'string', 'min:5'], 'duration_minutes' => ['required', 'integer', 'min:5', 'max:240'], 'target_user_uuid' => ['nullable', 'uuid']]);
        $tenant = $this->tenant($uuid);
        $targetId = ! empty($d['target_user_uuid']) ? DB::table('users')->where('tenant_id', $tenant->id)->where('uuid', $d['target_user_uuid'])->value('id') : DB::table('users')->where('tenant_id', $tenant->id)->where('account_type', 'owner')->value('id');
        $id = DB::table('remote_login_sessions')->insertGetId(['uuid' => (string) Str::uuid(), 'tenant_id' => $tenant->id, 'platform_user_id' => $r->user()->id, 'target_user_id' => $targetId, 'reason' => $d['reason'], 'duration_minutes' => $d['duration_minutes'], 'expires_at' => now()->addMinutes($d['duration_minutes']), 'status' => 'active', 'ip_address' => $r->ip(), 'user_agent' => $r->userAgent(), 'created_at' => now(), 'updated_at' => now()]);
        $session = DB::table('remote_login_sessions')->where('id', $id)->first();
        $this->admin->security($r, 'remote_login_started', 'warning', $tenant->id, ['session_uuid' => $session->uuid, 'reason' => $d['reason']]);
        $this->admin->audit($r, 'tenant_remote_login_started', Tenant::class, $tenant->id, null, (array) $session);
        return $this->success(['session' => $session], 'Remote login session started.', 201);
    }

    public function endRemoteLogin(Request $r, string $uuid, string $session_uuid)
    {
        $tenant = $this->tenant($uuid);
        DB::table('remote_login_sessions')->where('tenant_id', $tenant->id)->where('uuid', $session_uuid)->update(['status' => 'ended', 'ended_at' => now(), 'updated_at' => now()]);
        $this->admin->security($r, 'remote_login_ended', 'info', $tenant->id, ['session_uuid' => $session_uuid]);
        return $this->success(null, 'Remote login session ended.');
    }

    public function tab(string $uuid, string $tab)
    {
        $tenant = $this->tenant($uuid);
        $data = match ($tab) {
            'users' => DB::table('users')->where('tenant_id', $tenant->id)->get(),
            'offices' => DB::table('tenant_offices')->where('tenant_id', $tenant->id)->get(),
            'subscription' => DB::table('subscriptions')
                ->leftJoin('plans', 'plans.id', '=', 'subscriptions.plan_id')
                ->where('subscriptions.tenant_id', $tenant->id)
                ->latest('subscriptions.id')
                ->select('subscriptions.*', 'plans.uuid as plan_uuid', 'plans.name as plan_name', 'plans.code as plan_code')
                ->first(),
            'billing' => [
                'invoices' => DB::table('platform_invoices')
                    ->leftJoin('subscriptions', 'subscriptions.id', '=', 'platform_invoices.subscription_id')
                    ->leftJoin('plans', 'plans.id', '=', 'subscriptions.plan_id')
                    ->where('platform_invoices.tenant_id', $tenant->id)
                    ->select('platform_invoices.*', 'subscriptions.uuid as subscription_uuid', 'subscriptions.subscription_number', 'plans.name as plan_name')
                    ->get(),
                'payments' => DB::table('platform_payments')
                    ->leftJoin('platform_invoices', 'platform_invoices.id', '=', 'platform_payments.platform_invoice_id')
                    ->leftJoin('subscriptions', 'subscriptions.id', '=', 'platform_payments.subscription_id')
                    ->where('platform_payments.tenant_id', $tenant->id)
                    ->select('platform_payments.*', 'platform_invoices.uuid as invoice_uuid', 'platform_invoices.invoice_number', 'subscriptions.uuid as subscription_uuid', 'subscriptions.subscription_number')
                    ->get(),
            ],
            'usage' => DB::table('tenant_usage_snapshots')->where('tenant_id', $tenant->id)->latest('id')->limit(12)->get(),
            'modules' => DB::table('tenant_module_overrides')->where('tenant_id', $tenant->id)->get(),
            'settings' => DB::table('tenant_settings')->where('tenant_id', $tenant->id)->get(),
            'integrations' => DB::table('tenant_integrations')->where('tenant_id', $tenant->id)->get(),
            'security' => DB::table('security_events')->where('tenant_id', $tenant->id)->latest('id')->get(),
            'support' => ['tickets' => DB::table('platform_tickets')->where('tenant_id', $tenant->id)->get(), 'remote_login_sessions' => DB::table('remote_login_sessions')->where('tenant_id', $tenant->id)->latest('id')->get()],
            'files' => DB::table('files')->where('tenant_id', $tenant->id)->whereNull('deleted_at')->get(['uuid', 'original_name', 'mime_type', 'size_bytes', 'visibility', 'created_at']),
            'activity' => DB::table('activity_logs')->where('tenant_id', $tenant->id)->latest('id')->get(),
            default => abort(404),
        };
        return $this->success([$tab => $data]);
    }

    public function moduleOverrides(Request $r, string $uuid)
    {
        $tenant = $this->tenant($uuid);
        $d = $r->validate(['modules' => ['required', 'array'], 'modules.*.module_code' => ['required', 'string'], 'modules.*.enabled' => ['required', 'boolean'], 'modules.*.limits' => ['nullable', 'array'], 'modules.*.metadata' => ['nullable', 'array']]);
        foreach ($d['modules'] as $module) DB::table('tenant_module_overrides')->updateOrInsert(['tenant_id' => $tenant->id, 'module_code' => $module['module_code']], ['uuid' => (string) Str::uuid(), 'enabled' => $module['enabled'], 'limits' => isset($module['limits']) ? json_encode($module['limits']) : null, 'metadata' => isset($module['metadata']) ? json_encode($module['metadata']) : null, 'updated_by' => $r->user()?->id, 'created_at' => now(), 'updated_at' => now()]);
        $this->admin->audit($r, 'tenant_module_overrides_updated', Tenant::class, $tenant->id, null, $d);
        return $this->success(['modules' => DB::table('tenant_module_overrides')->where('tenant_id', $tenant->id)->get()], 'Module overrides updated.');
    }

    private function tenantData(Request $request, ?int $tenantId = null, bool $partial = false): array
    {
        return $request->validate([
            'organization_name' => [$partial ? 'sometimes' : 'required', 'string', 'max:200'],
            'legal_name' => ['nullable', 'string', 'max:200'],
            'display_name' => ['nullable', 'string', 'max:200'],
            'organization_code' => ['nullable', 'string', 'max:50', Rule::unique('tenants', 'organization_code')->ignore($tenantId)],
            'slug' => ['nullable', 'string', 'max:150', Rule::unique('tenants', 'slug')->ignore($tenantId)],
            'business_type_id' => ['nullable', 'integer', 'exists:business_types,id'],
            'industry_id' => ['nullable', 'integer', 'exists:industries,id'],
            'company_size' => ['nullable', Rule::in(['self', 'small', 'medium', 'large', 'enterprise'])],
            'gst_number' => ['nullable', 'string', 'max:30'],
            'pan_number' => ['nullable', 'string', 'max:30'],
            'registration_number' => ['nullable', 'string', 'max:80'],
            'website' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'logo_file_id' => ['nullable', 'integer'],
            'favicon_file_id' => ['nullable', 'integer'],
            'default_currency' => ['nullable', 'string', 'size:3'],
            'default_timezone' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(['pending', 'trial', 'active', 'suspended', 'expired', 'cancelled', 'archived', 'inactive'])],
            'plan_uuid' => ['nullable', 'uuid', 'exists:plans,uuid'],
            'trial_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'owner' => [$partial ? 'nullable' : 'required', 'array'],
            'owner.first_name' => [$partial ? 'sometimes' : 'required', 'string', 'max:100'],
            'owner.last_name' => ['nullable', 'string', 'max:100'],
            'owner.display_name' => ['nullable', 'string', 'max:200'],
            'owner.email' => [$partial ? 'sometimes' : 'required', 'email', 'max:150'],
            'owner.mobile' => ['nullable', 'string', 'max:20'],
            'owner.password' => ['nullable', 'string', 'min:8'],
            'owner.status' => ['nullable', Rule::in(['active', 'inactive', 'invited', 'suspended'])],
            'owner.send_invite' => ['nullable', 'boolean'],
            'office' => [$partial ? 'nullable' : 'required', 'array'],
            'office.office_name' => ['nullable', 'string', 'max:150'],
            'office.office_code' => ['nullable', 'string', 'max:50'],
            'office.office_type' => ['nullable', 'string', 'max:50'],
            'office.address_line_1' => ['nullable', 'string', 'max:255'],
            'office.address_line_2' => ['nullable', 'string', 'max:255'],
            'office.landmark' => ['nullable', 'string', 'max:255'],
            'office.country_id' => ['nullable', 'integer', 'exists:countries,id'],
            'office.state_id' => ['nullable', 'integer', 'exists:states,id'],
            'office.city_id' => ['nullable', 'integer', 'exists:cities,id'],
            'office.postal_code' => ['nullable', 'string', 'max:20'],
            'office.contact_person' => ['nullable', 'string', 'max:150'],
            'office.contact_email' => ['nullable', 'email', 'max:150'],
            'office.contact_phone' => ['nullable', 'string', 'max:20'],
            'office.gst_number' => ['nullable', 'string', 'max:30'],
            'office.status' => ['nullable', Rule::in(['active', 'inactive'])],
            'office.working_hours' => ['nullable', 'array'],
            'subscription' => ['nullable', 'array'],
            'subscription.plan_id' => ['nullable', 'uuid'],
            'subscription.type' => ['nullable', 'string', 'max:50'],
            'subscription.billing_cycle' => ['nullable', 'string', 'max:50'],
            'subscription.starts_at' => ['nullable', 'date'],
            'subscription.expires_at' => ['nullable', 'date'],
            'subscription.trial_starts_at' => ['nullable', 'date'],
            'subscription.trial_ends_at' => ['nullable', 'date'],
            'subscription.renewal_type' => ['nullable', Rule::in(['manual', 'auto'])],
            'subscription.auto_renew' => ['nullable', 'boolean'],
        ]);
    }

    private function createTenantFromData(Request $request, array $data): array
    {
        $tenantId = DB::table('tenants')->insertGetId([...$this->tenantPayload($data), 'created_at' => now(), 'updated_at' => now()]);
        $tenant = DB::table('tenants')->where('id', $tenantId)->first();
        $owner = $this->upsertOwner($tenantId, $data['owner'], $data);
        $this->upsertOffice($tenantId, $data['office'], $data, $request->user()?->id);
        $subscription = $this->upsertSubscription($request, $tenantId, $data['subscription'] ?? [], $data['plan_uuid'] ?? ($data['subscription']['plan_id'] ?? null));
        $this->admin->createDefaultTenantSettings($tenantId);
        $this->admin->audit($request, 'tenant_created', Tenant::class, $tenantId, null, (array) $tenant);

        return ['tenant' => $this->tenantSummary($tenant), 'owner' => $owner, 'subscription' => $subscription, 'temporary_password' => app()->isLocal() ? ($data['owner']['password'] ?? null) : null];
    }

    private function tenantPayload(array $data, ?object $current = null, bool $partial = false): array
    {
        $name = $data['organization_name'] ?? $current?->organization_name ?? 'Tenant';
        $payload = [
            'uuid' => $current?->uuid ?? (string) Str::uuid(),
            'organization_name' => $name,
            'legal_name' => $data['legal_name'] ?? null,
            'display_name' => $data['display_name'] ?? $name,
            'organization_code' => $data['organization_code'] ?? $this->uniqueTenantCode($name, $current?->id),
            'slug' => $data['slug'] ?? $this->uniqueTenantSlug($name, $current?->id),
            'business_type_id' => $data['business_type_id'] ?? null,
            'industry_id' => $data['industry_id'] ?? null,
            'company_size' => $data['company_size'] ?? null,
            'gst_number' => $data['gst_number'] ?? null,
            'pan_number' => $data['pan_number'] ?? null,
            'registration_number' => $data['registration_number'] ?? null,
            'website' => $data['website'] ?? null,
            'logo_file_id' => $data['logo_file_id'] ?? null,
            'favicon_file_id' => $data['favicon_file_id'] ?? null,
            'default_currency' => strtoupper($data['default_currency'] ?? 'INR'),
            'default_timezone' => $data['default_timezone'] ?? 'Asia/Kolkata',
            'trial_ends_at' => $data['subscription']['trial_ends_at'] ?? now()->addDays($data['trial_days'] ?? 15),
            'onboarded_at' => $current?->onboarded_at ?? now(),
            'status' => ($data['status'] ?? 'trial') === 'inactive' ? 'pending' : ($data['status'] ?? 'trial'),
        ];
        if (Schema::hasColumn('tenants', 'description')) $payload['description'] = $data['description'] ?? null;
        return $partial ? array_filter($payload, fn ($value, $key) => array_key_exists($key, $data) || $key === 'description', ARRAY_FILTER_USE_BOTH) : $payload;
    }

    private function upsertOwner(int $tenantId, array $owner, array $tenantData, bool $partial = false): object
    {
        $existing = DB::table('users')->where('tenant_id', $tenantId)->where('account_type', 'owner')->first();
        $password = $owner['password'] ?? ($existing ? null : Str::password(16));
        $payload = [
            'tenant_id' => $tenantId,
            'first_name' => $owner['first_name'] ?? $existing?->first_name,
            'last_name' => $owner['last_name'] ?? $existing?->last_name,
            'display_name' => $owner['display_name'] ?? trim(($owner['first_name'] ?? $existing?->first_name ?? '').' '.($owner['last_name'] ?? $existing?->last_name ?? '')),
            'email' => isset($owner['email']) ? Str::lower($owner['email']) : $existing?->email,
            'mobile' => $owner['mobile'] ?? $existing?->mobile,
            'timezone' => $tenantData['default_timezone'] ?? $existing?->timezone ?? 'Asia/Kolkata',
            'locale' => 'en',
            'email_verified_at' => $existing?->email_verified_at ?? now(),
            'account_type' => 'owner',
            'status' => $owner['status'] ?? $existing?->status ?? 'active',
            'updated_at' => now(),
        ];
        if ($password) $payload['password'] = Hash::make($password);
        if ($existing) {
            DB::table('users')->where('id', $existing->id)->update($payload);
            return DB::table('users')->where('id', $existing->id)->first();
        }
        $id = DB::table('users')->insertGetId([...$payload, 'uuid' => (string) Str::uuid(), 'created_at' => now()]);
        $roleId = DB::table('roles')->insertGetId(['uuid' => (string) Str::uuid(), 'tenant_id' => $tenantId, 'name' => 'owner', 'display_name' => 'Owner', 'guard_name' => 'tenant', 'description' => 'Tenant owner', 'is_system' => true, 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('model_has_roles')->insert(['tenant_id' => $tenantId, 'role_id' => $roleId, 'model_type' => User::class, 'model_id' => $id]);
        return DB::table('users')->where('id', $id)->first();
    }

    private function upsertOffice(int $tenantId, array $office, array $tenantData, ?int $userId = null, bool $partial = false): object
    {
        $existing = DB::table('tenant_offices')->where('tenant_id', $tenantId)->where('is_head_office', true)->whereNull('deleted_at')->first();
        $payload = [
            'tenant_id' => $tenantId,
            'office_name' => $office['office_name'] ?? $existing?->office_name ?? 'Head Office',
            'office_code' => $office['office_code'] ?? $existing?->office_code ?? 'HO',
            'office_type' => $office['office_type'] ?? $existing?->office_type ?? 'head_office',
            'is_head_office' => true,
            'is_default' => true,
            'address_line_1' => $office['address_line_1'] ?? $existing?->address_line_1,
            'address_line_2' => $office['address_line_2'] ?? $existing?->address_line_2,
            'landmark' => $office['landmark'] ?? $existing?->landmark,
            'country_id' => $office['country_id'] ?? $existing?->country_id,
            'state_id' => $office['state_id'] ?? $existing?->state_id,
            'city_id' => $office['city_id'] ?? $existing?->city_id,
            'postal_code' => $office['postal_code'] ?? $existing?->postal_code,
            'contact_person' => $office['contact_person'] ?? $existing?->contact_person,
            'contact_email' => isset($office['contact_email']) ? Str::lower($office['contact_email']) : $existing?->contact_email,
            'contact_phone' => $office['contact_phone'] ?? $existing?->contact_phone,
            'timezone' => $tenantData['default_timezone'] ?? $existing?->timezone ?? 'Asia/Kolkata',
            'working_hours' => isset($office['working_hours']) ? json_encode($office['working_hours']) : $existing?->working_hours,
            'gst_number' => $office['gst_number'] ?? $existing?->gst_number,
            'status' => $office['status'] ?? $existing?->status ?? 'active',
            'updated_by' => $userId,
            'updated_at' => now(),
        ];
        if ($existing) {
            DB::table('tenant_offices')->where('id', $existing->id)->update($payload);
            return DB::table('tenant_offices')->where('id', $existing->id)->first();
        }
        $id = DB::table('tenant_offices')->insertGetId([...$payload, 'uuid' => (string) Str::uuid(), 'created_by' => $userId, 'created_at' => now()]);
        return DB::table('tenant_offices')->where('id', $id)->first();
    }

    private function upsertSubscription(Request $request, int $tenantId, array $subscriptionData, ?string $planUuid = null, bool $updateExisting = false): ?object
    {
        $plan = $planUuid ? DB::table('plans')->where('uuid', $planUuid)->first() : DB::table('plans')->where('status', 'active')->orderBy('id')->first();
        if (! $plan) return null;
        $existing = DB::table('subscriptions')->where('tenant_id', $tenantId)->whereNull('deleted_at')->latest('id')->first();
        $startsAt = $subscriptionData['starts_at'] ?? now();
        $billingCycle = $subscriptionData['billing_cycle'] ?? $plan->billing_cycle;
        $payload = ['tenant_id' => $tenantId, 'plan_id' => $plan->id, 'type' => $subscriptionData['type'] ?? 'trial', 'billing_cycle' => $billingCycle, 'status' => $subscriptionData['type'] === 'paid' ? 'pending_payment' : 'trial', 'renewal_type' => $subscriptionData['renewal_type'] ?? 'manual', 'starts_at' => $startsAt, 'expires_at' => $subscriptionData['expires_at'] ?? $this->billingEndDate($startsAt, $billingCycle), 'trial_starts_at' => $subscriptionData['trial_starts_at'] ?? now(), 'trial_ends_at' => $subscriptionData['trial_ends_at'] ?? now()->addDays((int) ($subscriptionData['trial_days'] ?? $plan->trial_days ?? 15)), 'base_amount' => $plan->base_price, 'taxable_amount' => $plan->base_price, 'payable_amount' => $plan->base_price, 'currency' => $plan->currency, 'auto_renew' => (bool) ($subscriptionData['auto_renew'] ?? false), 'updated_by' => $request->user()?->id, 'updated_at' => now()];
        if ($existing && $updateExisting) {
            DB::table('subscriptions')->where('id', $existing->id)->update($payload);
            $subscriptionId = $existing->id;
            $version = ((int) $existing->current_version) + 1;
            DB::table('subscriptions')->where('id', $subscriptionId)->update(['current_version' => $version]);
        } else {
            $subscriptionId = DB::table('subscriptions')->insertGetId([...$payload, 'uuid' => (string) Str::uuid(), 'subscription_number' => 'SUB-' . Str::upper(Str::random(10)), 'current_version' => 1, 'created_by' => $request->user()?->id, 'created_at' => now()]);
            $version = 1;
        }
        DB::table('subscription_versions')->insert(['subscription_id' => $subscriptionId, 'version' => $version, 'plan_id' => $plan->id, 'billing_cycle' => $billingCycle, 'starts_at' => $startsAt, 'pricing_snapshot' => json_encode((array) $plan), 'created_by' => $request->user()?->id, 'created_at' => now()]);
        return DB::table('subscriptions')->where('id', $subscriptionId)->first();
    }

    private function tenantDetail(object $tenant): array
    {
        return [
            'tenant' => $this->tenantSummary($tenant),
            'owner' => DB::table('users')->where('tenant_id', $tenant->id)->where('account_type', 'owner')->first(),
            'office' => DB::table('tenant_offices')->where('tenant_id', $tenant->id)->where('is_head_office', true)->whereNull('deleted_at')->first(),
            'subscription' => DB::table('subscriptions')
                ->leftJoin('plans', 'plans.id', '=', 'subscriptions.plan_id')
                ->where('subscriptions.tenant_id', $tenant->id)
                ->latest('subscriptions.id')
                ->select('subscriptions.*', 'plans.uuid as plan_uuid', 'plans.name as plan_name', 'plans.code as plan_code')
                ->first(),
        ];
    }

    private function tenantSummary(object $tenant): object
    {
        $owner = DB::table('users')->where('tenant_id', $tenant->id)->where('account_type', 'owner')->first();
        $subscription = DB::table('subscriptions')->leftJoin('plans', 'plans.id', '=', 'subscriptions.plan_id')->where('subscriptions.tenant_id', $tenant->id)->whereNull('subscriptions.deleted_at')->latest('subscriptions.id')->select('subscriptions.*', 'plans.uuid as plan_uuid', 'plans.name as plan_name')->first();
        $tenant->owner = $owner;
        $tenant->owner_name = $owner->display_name ?? null;
        $tenant->owner_email = $owner->email ?? null;
        $tenant->subscription_status = $subscription->status ?? null;
        $tenant->current_plan = $subscription->plan_name ?? null;
        $tenant->plan_name = $subscription->plan_name ?? null;
        $tenant->plan_uuid = $subscription->plan_uuid ?? null;
        $tenant->users_count = DB::table('users')->where('tenant_id', $tenant->id)->count();
        return $tenant;
    }

    private function tenantStats(): array
    {
        $base = DB::table('tenants')->whereNull('deleted_at');
        return ['total' => (clone $base)->count(), 'active' => (clone $base)->where('status', 'active')->count(), 'trial' => (clone $base)->where('status', 'trial')->count(), 'suspended' => (clone $base)->where('status', 'suspended')->count()];
    }

    private function recordPlatformPayment(int $tenantId, ?int $subscriptionId, float $amount, string $currency, string $gateway, string $status, array $raw, ?string $gatewayId = null): object
    {
        $id = DB::table('platform_payments')->insertGetId(['uuid' => (string) Str::uuid(), 'payment_number' => 'PAY-' . Str::upper(Str::random(10)), 'tenant_id' => $tenantId, 'subscription_id' => $subscriptionId, 'gateway' => $gateway, 'gateway_payment_id' => $gatewayId, 'payment_method' => $gateway, 'amount' => $amount, 'currency' => $currency, 'payment_status' => $status, 'paid_at' => $status === 'paid' ? now() : null, 'raw_response' => json_encode($raw), 'created_at' => now(), 'updated_at' => now()]);
        return DB::table('platform_payments')->where('id', $id)->first();
    }

    private function billingEndDate(mixed $start, string $cycle): string
    {
        $date = \Illuminate\Support\Carbon::parse($start);
        $end = match ($cycle) {
            'quarterly' => $date->copy()->addMonths(3),
            'half-yearly' => $date->copy()->addMonths(6),
            'yearly' => $date->copy()->addYear(),
            default => $date->copy()->addMonth(),
        };

        return $end->toDateTimeString();
    }
    private function uniqueTenantSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'tenant';
        $slug = $base;
        $i = 2;
        while (DB::table('tenants')->where('slug', $slug)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) $slug = $base.'-'.$i++;
        return $slug;
    }

    private function uniqueTenantCode(string $name, ?int $ignoreId = null): string
    {
        $base = Str::upper(Str::substr(preg_replace('/[^A-Za-z0-9]/', '', $name) ?: 'TENANT', 0, 12));
        $code = $base;
        $i = 2;
        while (DB::table('tenants')->where('organization_code', $code)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) $code = Str::substr($base, 0, 10).$i++;
        return $code;
    }
    private function tenant(string $uuid): object
    {
        $tenant = DB::table('tenants')->where('uuid', $uuid)->whereNull('deleted_at')->first();
        abort_if(! $tenant, 404);
        return $tenant;
    }
    private function tenantStatus(Request $r, string $uuid, string $status, string $event)
    {
        $tenant = $this->tenant($uuid);
        $old = (array) $tenant;
        DB::table('tenants')->where('id', $tenant->id)->update(['status' => $status, 'updated_at' => now(), 'deleted_at' => $status === 'archived' ? now() : null]);
        if ($status === 'suspended') DB::table('users')->where('tenant_id', $tenant->id)->update(['status' => 'suspended']);
        $fresh = DB::table('tenants')->where('id', $tenant->id)->first();
        $this->admin->security($r, $event, $status === 'suspended' ? 'warning' : 'info', $tenant->id, ['reason' => $r->input('reason')]);
        $this->admin->audit($r, $event, Tenant::class, $tenant->id, $old, (array) $fresh, $r->input('reason'));
        return $this->success(['tenant' => $fresh], 'Tenant status updated.');
    }
}









