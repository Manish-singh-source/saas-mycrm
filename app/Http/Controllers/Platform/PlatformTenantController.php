<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Shared\BaseApiController;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Platform\PlatformAdminService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
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
        $p = $q->latest('id')->paginate((int) $request->integer('per_page', 25));
        return $this->list($p->items(), $p);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'organization_name' => ['required', 'string', 'max:200'],
            'legal_name' => ['nullable', 'string', 'max:200'],
            'display_name' => ['nullable', 'string', 'max:200'],
            'organization_code' => ['required', 'string', 'max:50', 'unique:tenants,organization_code'],
            'slug' => ['required', 'string', 'max:150', 'unique:tenants,slug'],
            'default_currency' => ['nullable', 'string', 'size:3'],
            'default_timezone' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(['pending', 'trial', 'active'])],
            'owner' => ['required', 'array'],
            'owner.first_name' => ['required', 'string', 'max:100'],
            'owner.last_name' => ['nullable', 'string', 'max:100'],
            'owner.email' => ['required', 'email', 'max:150'],
            'owner.mobile' => ['nullable', 'string', 'max:20'],
            'owner.password' => ['nullable', 'string', 'min:8'],
            'office' => ['required', 'array'],
            'office.office_name' => ['nullable', 'string', 'max:150'],
            'office.address_line_1' => ['nullable', 'string'],
            'plan_uuid' => ['nullable', 'uuid'],
            'trial_days' => ['nullable', 'integer', 'min:0', 'max:365'],
        ]);

        return DB::transaction(function () use ($request, $data) {
            $tenantId = DB::table('tenants')->insertGetId([
                'uuid' => (string) Str::uuid(),
                'organization_name' => $data['organization_name'],
                'legal_name' => $data['legal_name'] ?? null,
                'display_name' => $data['display_name'] ?? $data['organization_name'],
                'organization_code' => $data['organization_code'],
                'slug' => $data['slug'],
                'default_currency' => $data['default_currency'] ?? 'INR',
                'default_timezone' => $data['default_timezone'] ?? 'Asia/Kolkata',
                'status' => $data['status'] ?? 'trial',
                'trial_ends_at' => now()->addDays($data['trial_days'] ?? 14),
                'onboarded_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $tenant = DB::table('tenants')->where('id', $tenantId)->first();
            $password = $data['owner']['password'] ?? Str::password(16);
            $ownerId = DB::table('users')->insertGetId([
                'uuid' => (string) Str::uuid(),
                'tenant_id' => $tenantId,
                'first_name' => $data['owner']['first_name'],
                'last_name' => $data['owner']['last_name'] ?? null,
                'display_name' => trim($data['owner']['first_name'] . ' ' . ($data['owner']['last_name'] ?? '')),
                'email' => Str::lower($data['owner']['email']),
                'mobile' => $data['owner']['mobile'] ?? null,
                'password' => Hash::make($password),
                'timezone' => $data['default_timezone'] ?? 'Asia/Kolkata',
                'locale' => 'en',
                'email_verified_at' => now(),
                'account_type' => 'owner',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('tenant_offices')->insert([
                'uuid' => (string) Str::uuid(),
                'tenant_id' => $tenantId,
                'office_name' => $data['office']['office_name'] ?? 'Head Office',
                'office_code' => 'HO',
                'office_type' => 'head_office',
                'is_head_office' => true,
                'is_default' => true,
                'address_line_1' => $data['office']['address_line_1'] ?? null,
                'contact_email' => Str::lower($data['owner']['email']),
                'timezone' => $data['default_timezone'] ?? 'Asia/Kolkata',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $roleId = DB::table('roles')->insertGetId(['uuid' => (string) Str::uuid(), 'tenant_id' => $tenantId, 'name' => 'owner', 'display_name' => 'Owner', 'guard_name' => 'tenant', 'description' => 'Tenant owner', 'is_system' => true, 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
            DB::table('model_has_roles')->insert(['tenant_id' => $tenantId, 'role_id' => $roleId, 'model_type' => User::class, 'model_id' => $ownerId]);
            $plan = ! empty($data['plan_uuid']) ? DB::table('plans')->where('uuid', $data['plan_uuid'])->first() : DB::table('plans')->where('status', 'active')->orderBy('id')->first();
            if ($plan) {
                $subscriptionId = DB::table('subscriptions')->insertGetId(['uuid' => (string) Str::uuid(), 'subscription_number' => 'SUB-' . Str::upper(Str::random(10)), 'tenant_id' => $tenantId, 'plan_id' => $plan->id, 'billing_cycle' => $plan->billing_cycle, 'status' => 'trial', 'starts_at' => now(), 'trial_starts_at' => now(), 'trial_ends_at' => now()->addDays($data['trial_days'] ?? $plan->trial_days), 'base_amount' => $plan->base_price, 'taxable_amount' => $plan->base_price, 'payable_amount' => $plan->base_price, 'currency' => $plan->currency, 'created_by' => $request->user()?->id, 'created_at' => now(), 'updated_at' => now()]);
                DB::table('subscription_versions')->insert(['subscription_id' => $subscriptionId, 'version' => 1, 'plan_id' => $plan->id, 'billing_cycle' => $plan->billing_cycle, 'starts_at' => now(), 'pricing_snapshot' => json_encode((array) $plan), 'created_by' => $request->user()?->id, 'created_at' => now()]);
            }
            $this->admin->createDefaultTenantSettings($tenantId);
            $this->admin->audit($request, 'tenant_created', Tenant::class, $tenantId, null, (array) $tenant);

            return $this->success(['tenant' => $tenant, 'owner' => DB::table('users')->where('id', $ownerId)->first(), 'temporary_password' => app()->isLocal() ? $password : null], 'Tenant created.', 201);
        });
    }

    public function show(string $tenant_uuid)
    {
        $tenant = $this->tenant($tenant_uuid);
        return $this->success(['tenant' => $tenant, 'owner' => DB::table('users')->where('tenant_id', $tenant->id)->where('account_type', 'owner')->first(), 'subscription' => DB::table('subscriptions')->where('tenant_id', $tenant->id)->latest('id')->first()]);
    }

    public function update(Request $request, string $tenant_uuid)
    {
        $tenant = $this->tenant($tenant_uuid);
        $data = $request->validate(['organization_name' => ['sometimes', 'string'], 'legal_name' => ['nullable', 'string'], 'display_name' => ['nullable', 'string'], 'website' => ['nullable', 'string'], 'default_currency' => ['nullable', 'string', 'size:3'], 'default_timezone' => ['nullable', 'string']]);
        $data['updated_at'] = now();
        DB::table('tenants')->where('id', $tenant->id)->update($data);
        $fresh = DB::table('tenants')->where('id', $tenant->id)->first();
        $this->admin->audit($request, 'tenant_updated', Tenant::class, $tenant->id, (array) $tenant, (array) $fresh);
        return $this->success(['tenant' => $fresh], 'Tenant updated.');
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
            'subscription' => DB::table('subscriptions')->where('tenant_id', $tenant->id)->latest('id')->first(),
            'billing' => ['invoices' => DB::table('platform_invoices')->where('tenant_id', $tenant->id)->get(), 'payments' => DB::table('platform_payments')->where('tenant_id', $tenant->id)->get()],
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
