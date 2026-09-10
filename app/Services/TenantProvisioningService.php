<?php

namespace App\Services;

use App\Mail\TenantAccountNotification;
use App\Models\Plan;
use App\Models\PlatformUser;
use App\Models\Role;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\TenantOffice;
use App\Models\User;
use App\Support\ApiResponse;
use App\Support\TenantAudit;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

final class TenantProvisioningService
{
    private const TENANT_FIELDS = ['organization_name', 'legal_name', 'display_name', 'organization_code', 'slug', 'business_type_id', 'industry_id', 'company_size', 'gst_number', 'pan_number', 'registration_number', 'website', 'description', 'logo_file_id', 'favicon_file_id', 'default_currency', 'default_timezone', 'status'];

    public function create(Request $request, array $data, bool $public = false): array
    {
        $plan = $this->plan($data['plan_uuid'] ?? null, $public);
        $days = $data['trial_days'] ?? $plan->trial_days ?? 15;
        $tenant = Tenant::create(array_replace([
            'uuid' => (string) Str::uuid(), 'display_name' => $data['organization_name'],
            'organization_code' => $this->unique($data['organization_name'], 'organization_code'),
            'slug' => $this->unique($data['organization_name'], 'slug'), 'default_currency' => 'INR',
            'default_timezone' => 'Asia/Kolkata', 'status' => 'trial', 'onboarded_at' => now(),
            'trial_ends_at' => $data['subscription']['trial_ends_at'] ?? now()->addDays($days),
        ], $this->tenantFields($data)));
        $office = $this->office($tenant, $data['office'] ?? [], $data['owner']);
        [$owner, $password] = $this->owner($tenant, $data['owner'], $office);
        $subscription = $this->subscription($request, $tenant, $data['subscription'] ?? [], $plan);
        foreach ([['general', 'locale', 'en', 'string'], ['security', 'require_2fa', false, 'boolean'], ['modules', 'overrides', [], 'json']] as [$group, $key, $value, $type]) {
            DB::table('tenant_settings')->insert(['tenant_id' => $tenant->id, 'group' => $group, 'key' => $key, 'value' => json_encode($value), 'value_type' => $type, 'is_encrypted' => false, 'created_at' => now(), 'updated_at' => now()]);
        }
        TenantAudit::record($request, $tenant, $public ? 'tenant_registered' : 'tenant_created', ['owner_uuid' => $owner->uuid, 'subscription_uuid' => $subscription->uuid], true);
        if ($public || ($data['owner']['send_invite'] ?? false)) {
            $this->notify($owner, 'Tenant registration', 'Your account for '.$tenant->organization_name.' has been created. Use your account email to sign in or request a password reset.');
        }

        return [$tenant, $owner, $subscription, $password];
    }

    public function update(Request $request, Tenant $tenant, array $data): Tenant
    {
        $fields = $this->tenantFields($data);
        if (isset($data['trial_days'])) {
            $fields['trial_ends_at'] = now()->addDays($data['trial_days']);
        }
        if (isset($data['subscription']['trial_ends_at'])) {
            $fields['trial_ends_at'] = $data['subscription']['trial_ends_at'];
        }
        $tenant->update($fields);
        $office = $tenant->headOffice;
        if (isset($data['office'])) {
            $office = $this->office($tenant, $data['office'], $data['owner'] ?? []);
        }
        if (isset($data['owner'])) {
            $this->owner($tenant, $data['owner'], $office);
        }
        if (isset($data['subscription']) || isset($data['plan_uuid']) || isset($data['trial_days'])) {
            $existing = $tenant->subscription()->first();
            $plan = isset($data['plan_uuid']) ? $this->plan($data['plan_uuid']) : ($existing?->plan ?? $this->plan());
            $this->subscription($request, $tenant, $data['subscription'] ?? [], $plan);
            if (isset($data['plan_uuid'])) {
                TenantAudit::record($request, $tenant, 'tenant_plan_changed', ['plan_uuid' => $plan->uuid]);
            }
        }
        TenantAudit::record($request, $tenant, 'tenant_updated', $data);

        return $tenant->fresh();
    }

    public function plan(?string $uuid = null, bool $public = false): Plan
    {
        $query = Plan::query();
        if ($uuid) {
            $query->where('uuid', $uuid);
        } else {
            $query->where('status', 'active')->orderBy('base_price')->orderBy('id');
        }
        if ($public) {
            $query->where('status', 'active')->where('is_public', true);
        }
        $plan = $query->first();
        if (! $plan) {
            throw new HttpResponseException(ApiResponse::validation(['plan_uuid' => ['No eligible plan is available.']]));
        }

        return $plan;
    }

    public function subscription(Request $request, Tenant $tenant, array $data, Plan $plan): Subscription
    {
        $existing = $tenant->subscription()->first();
        $changed = $existing && $existing->plan_id !== $plan->id;
        $cycle = $data['billing_cycle'] ?? ($changed ? $plan->billing_cycle : ($existing?->billing_cycle ?? $plan->billing_cycle));
        $type = $data['type'] ?? $existing?->type ?? ((float) $plan->base_price > 0 ? 'trial' : 'free');
        $start = $data['starts_at'] ?? $existing?->starts_at ?? now();
        $expires = $data['expires_at'] ?? ($changed || isset($data['billing_cycle']) ? $this->billingEnd($start, $cycle) : ($existing?->expires_at ?? $this->billingEnd($start, $cycle)));
        if ($expires && Carbon::parse($expires)->lt(Carbon::parse($start))) {
            throw new HttpResponseException(ApiResponse::validation(['expires_at' => ['Expiry must be on or after the start date.']]));
        }
        $version = ($existing?->current_version ?? 0) + 1;
        $actor = $request->user() instanceof PlatformUser ? $request->user()->id : null;
        $payload = array_replace(array_filter(Arr::only($data, ['renewal_type', 'auto_renew', 'trial_starts_at', 'trial_ends_at']), fn ($value) => $value !== null), [
            'tenant_id' => $tenant->id, 'plan_id' => $plan->id, 'billing_cycle' => $cycle, 'type' => $type,
            'starts_at' => $start, 'expires_at' => $expires, 'current_version' => $version,
            'trial_starts_at' => $data['trial_starts_at'] ?? $existing?->trial_starts_at ?? now(),
            'trial_ends_at' => $data['trial_ends_at'] ?? $tenant->trial_ends_at,
            'status' => isset($data['type']) || ! $existing ? ($type === 'paid' ? 'pending_payment' : ($type === 'free' ? 'active' : 'trial')) : $existing->status,
            'updated_by' => $actor,
        ]);
        if (! $existing || $changed) {
            $payload = array_replace($payload, ['base_amount' => $plan->base_price, 'taxable_amount' => $plan->base_price, 'payable_amount' => $plan->base_price, 'currency' => $plan->currency]);
        }
        if ($existing) {
            $existing->update($payload);
            $subscription = $existing;
        } else {
            $subscription = Subscription::create($payload + ['uuid' => (string) Str::uuid(), 'subscription_number' => 'SUB-'.Str::upper(Str::random(12)), 'created_by' => $actor]);
        }
        DB::table('subscription_versions')->insert([
            'subscription_id' => $subscription->id, 'version' => $version, 'plan_id' => $plan->id,
            'billing_cycle' => $cycle, 'starts_at' => $start, 'pricing_snapshot' => $plan->toJson(),
            'change_reason' => isset($data['reason']) ? Str::limit($data['reason'], 255, '') : null,
            'created_by' => $actor, 'created_at' => now(),
        ]);

        return $subscription->fresh()->load('plan');
    }

    private function tenantFields(array $data): array
    {
        $fields = Arr::only($data, self::TENANT_FIELDS);
        foreach (['display_name', 'organization_code', 'slug', 'default_currency', 'default_timezone', 'status'] as $key) {
            if (array_key_exists($key, $fields) && $fields[$key] === null) {
                unset($fields[$key]);
            }
        }
        if (isset($fields['default_currency'])) {
            $fields['default_currency'] = strtoupper($fields['default_currency']);
        }
        if (($fields['status'] ?? null) === 'inactive') {
            $fields['status'] = 'pending';
        }

        return $fields;
    }

    private function office(Tenant $tenant, array $data, array $owner): TenantOffice
    {
        $existing = $tenant->headOffice()->first();
        $fields = Arr::only($data, ['office_name', 'office_code', 'office_type', 'address_line_1', 'address_line_2', 'landmark', 'country_id', 'state_id', 'city_id', 'postal_code', 'contact_person', 'contact_email', 'contact_phone', 'gst_number', 'status', 'working_hours']);
        if (array_key_exists('working_hours', $fields)) {
            $fields['working_hours'] = $fields['working_hours'] === null ? null : json_encode($fields['working_hours']);
        }
        foreach (['office_name', 'office_code', 'office_type', 'status'] as $key) {
            if (array_key_exists($key, $fields) && $fields[$key] === null) {
                unset($fields[$key]);
            }
        }
        if ($existing) {
            $existing->update($fields);

            return $existing;
        }

        return TenantOffice::create($fields + ['uuid' => (string) Str::uuid(), 'tenant_id' => $tenant->id, 'office_name' => 'Head Office', 'office_code' => 'HO', 'office_type' => 'head_office', 'is_head_office' => true, 'is_default' => true, 'timezone' => $tenant->default_timezone, 'status' => 'active', 'contact_email' => $owner['email'] ?? null, 'contact_person' => $owner['first_name'] ?? null]);
    }

    private function owner(Tenant $tenant, array $data, ?TenantOffice $office): array
    {
        $owner = $tenant->owner()->first();
        if (! $owner && (empty($data['first_name']) || empty($data['email']))) {
            throw new HttpResponseException(ApiResponse::validation(['owner' => ['First name and email are required to create the missing owner.']]));
        }
        $password = $data['password'] ?? ($owner ? null : Str::password(16));
        $fields = Arr::only($data, ['first_name', 'last_name', 'display_name', 'email', 'mobile', 'status']);
        if (! isset($fields['display_name']) && (! $owner || isset($data['first_name']) || array_key_exists('last_name', $data))) {
            $fields['display_name'] = trim(($data['first_name'] ?? $owner?->first_name).' '.(array_key_exists('last_name', $data) ? $data['last_name'] : $owner?->last_name));
        }
        if (array_key_exists('display_name', $fields) && $fields['display_name'] === null) {
            unset($fields['display_name']);
        }
        if (array_key_exists('status', $fields) && $fields['status'] === null) {
            unset($fields['status']);
        }
        if ($password !== null) {
            $fields['password'] = Hash::make($password);
        }
        if ($office) {
            $fields['default_office_id'] = $office->id;
        }
        if (! $owner) {
            $owner = new User;
            $fields += ['uuid' => (string) Str::uuid(), 'tenant_id' => $tenant->id, 'account_type' => 'owner', 'status' => 'active', 'timezone' => $tenant->default_timezone, 'locale' => 'en'];
        }
        // The existing User fillable configuration is retained; only validated fields are assigned.
        $owner->forceFill($fields)->save();
        $role = Role::firstOrCreate(['tenant_id' => $tenant->id, 'name' => 'owner', 'guard_name' => 'tenant'], ['uuid' => (string) Str::uuid(), 'display_name' => 'Owner', 'is_system' => true, 'status' => 'active']);
        DB::table('model_has_roles')->updateOrInsert(['tenant_id' => $tenant->id, 'role_id' => $role->id, 'model_type' => User::class, 'model_id' => $owner->id], []);
        foreach (DB::table('permissions')->where('guard_name', 'tenant')->where('status', 'active')->pluck('id') as $id) {
            DB::table('role_has_permissions')->updateOrInsert(['role_id' => $role->id, 'permission_id' => $id], []);
        }

        return [$owner, $password];
    }

    public function notify(User $owner, string $title, string $text): void
    {
        Mail::to($owner->email)->queue((new TenantAccountNotification($title, $text))->afterCommit());
    }

    private function unique(string $name, string $column): string
    {
        $base = $column === 'slug' ? Str::limit(Str::slug($name) ?: 'tenant', 120, '') : Str::upper(Str::substr(preg_replace('/[^A-Za-z0-9]/', '', $name) ?: 'TENANT', 0, 12));
        $value = $base;
        for ($i = 2; Tenant::withTrashed()->where($column, $value)->exists(); $i++) {
            $value = $base.'-'.$i;
        }

        return $value;
    }

    private function billingEnd(mixed $start, string $cycle): ?string
    {
        $date = Carbon::parse($start);

        return match ($cycle) {
            'quarterly' => $date->addMonthsNoOverflow(3)->toDateTimeString(),
            'half-yearly' => $date->addMonthsNoOverflow(6)->toDateTimeString(),
            'yearly' => $date->addYearNoOverflow()->toDateTimeString(),
            'lifetime', 'one_time' => null,
            default => $date->addMonthNoOverflow()->toDateTimeString(),
        };
    }
}
