<?php

namespace App\Http\Controllers;

use App\Http\Requests\TenantActionRequest;
use App\Http\Requests\TenantWriteRequest;
use App\Models\ActivityLog;
use App\Models\File;
use App\Models\RemoteLoginSession;
use App\Models\SecurityEvent;
use App\Models\Tenant;
use App\Models\TenantOnboardingStep;
use App\Models\TenantIntegration;
use App\Models\TenantModuleOverride;
use App\Models\TenantSetting;
use App\Models\TenantUsageSnapshot;
use App\Services\TenantBillingService;
use App\Services\TenantProvisioningService;
use App\Support\ApiResponse;
use App\Support\TenantAudit;
use App\Support\TenantPresenter;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

final class PlatformTenantController extends Controller
{
    public function __construct(private TenantProvisioningService $provisioning, private TenantBillingService $billing) {}

    public function index(TenantActionRequest $request): mixed
    {
        $data = $request->validated();
        $query = Tenant::with(['owner:id,tenant_id,display_name,email', 'subscription' => fn ($query) => $query->select(['subscriptions.id', 'subscriptions.tenant_id', 'subscriptions.plan_id', 'subscriptions.status']), 'subscription.plan:id,name'])->withCount('users');
        if (! empty($data['search'])) {
            $query->where(fn ($q) => $q->where('organization_name', 'like', '%'.$data['search'].'%')->orWhere('slug', 'like', '%'.$data['search'].'%')->orWhere('organization_code', 'like', '%'.$data['search'].'%'));
        }
        if (! empty($data['filter']['status'])) {
            $query->where('status', $data['filter']['status']);
        }
        $page = $query->latest('id')->paginate($data['per_page'] ?? 10);
        $stats = ['total' => Tenant::count()];
        foreach (['active', 'trial', 'suspended'] as $status) {
            $stats[$status] = Tenant::where('status', $status)->count();
        }

        return ApiResponse::success(collect($page->items())->map(fn ($tenant) => TenantPresenter::summary($tenant))->all(), 'Tenants fetched successfully.', 200, ['current_page' => $page->currentPage(), 'per_page' => $page->perPage(), 'total' => $page->total(), 'last_page' => $page->lastPage(), 'stats' => $stats]);
    }

    public function store(TenantWriteRequest $request): mixed
    {
        return DB::transaction(function () use ($request) {
            [$tenant, , , $password] = $this->provisioning->create($request, $request->validated());
            $result = TenantPresenter::detail($tenant->fresh());
            if (app()->isLocal()) {
                $result['temporary_password'] = $password;
            }

            return ApiResponse::success($result, 'Tenant created successfully.', 201);
        });
    }

    public function show(string $tenant_uuid): mixed
    {
        return ApiResponse::success(TenantPresenter::summary($this->tenant($tenant_uuid)), 'Tenant fetched successfully.');
    }

    public function update(TenantWriteRequest $request, string $tenant_uuid): mixed
    {
        return DB::transaction(function () use ($request, $tenant_uuid) {
            $tenant = $this->provisioning->update($request, $this->tenant($tenant_uuid, lock: true), $request->validated());

            return ApiResponse::success(TenantPresenter::detail($tenant), 'Tenant updated successfully.');
        });
    }

    public function bulkDestroy(TenantActionRequest $request): mixed
    {
        return DB::transaction(function () use ($request) {
            $tenants = Tenant::whereIn('uuid', $request->validated('tenant_uuids'))->lockForUpdate()->get();
            foreach ($tenants as $tenant) {
                $this->setStatus($request, $tenant, 'archived', 'tenant_archived');
            }

            return ApiResponse::success(['archived' => $tenants->count()], 'Tenants archived successfully.');
        });
    }

    public function destroy(TenantActionRequest $request, string $tenant_uuid): mixed
    {
        return $this->lifecycle($request, $tenant_uuid, 'archived', 'tenant_archived', true);
    }

    public function archive(TenantActionRequest $request, string $tenant_uuid): mixed
    {
        return $this->lifecycle($request, $tenant_uuid, 'archived', 'tenant_archived');
    }

    public function activate(TenantActionRequest $request, string $tenant_uuid): mixed
    {
        return $this->lifecycle($request, $tenant_uuid, 'active', 'tenant_activated');
    }

    public function reactivate(TenantActionRequest $request, string $tenant_uuid): mixed
    {
        return $this->lifecycle($request, $tenant_uuid, 'active', 'tenant_reactivated');
    }

    public function suspend(TenantActionRequest $request, string $tenant_uuid): mixed
    {
        return $this->lifecycle($request, $tenant_uuid, 'suspended', 'tenant_suspended');
    }

    public function restore(TenantActionRequest $request, string $tenant_uuid): mixed
    {
        return DB::transaction(function () use ($request, $tenant_uuid) {
            $tenant = $this->tenant($tenant_uuid, true, true);
            $tenant->restore();
            TenantAudit::record($request, $tenant, 'tenant_restored', $request->validated(), true);

            return ApiResponse::success(TenantPresenter::detail($tenant->fresh()), 'Tenant restored successfully.');
        });
    }

    public function extendTrial(TenantActionRequest $request, string $tenant_uuid): mixed
    {
        return DB::transaction(function () use ($request, $tenant_uuid) {
            $tenant = $this->tenant($tenant_uuid, lock: true);
            $tenant->update(['trial_ends_at' => $request->validated('trial_ends_at')]);
            $tenant->subscription()->first()?->update(['trial_ends_at' => $request->validated('trial_ends_at')]);
            TenantAudit::record($request, $tenant, 'tenant_trial_extended', $request->validated());

            return ApiResponse::success(TenantPresenter::detail($tenant->fresh()), 'Trial extended successfully.');
        });
    }

    public function trialIndex(TenantActionRequest $request): mixed
    {
        $page = Tenant::with('subscription')->where('status', 'trial')->latest('id')->paginate($request->validated('per_page', 25));
        $data = collect($page->items())->map(fn ($tenant) => TenantPresenter::summary($tenant))->all();
        return ApiResponse::success($data, 'Trials fetched successfully.', 200, ['current_page' => $page->currentPage(), 'per_page' => $page->perPage(), 'total' => $page->total(), 'last_page' => $page->lastPage()]);
    }

    public function convertTrial(TenantActionRequest $request, string $tenant_uuid): mixed
    {
        return DB::transaction(function () use ($request, $tenant_uuid) {
            $tenant = $this->tenant($tenant_uuid, lock: true);
            $tenant->update(['status' => 'active']);
            $tenant->subscription()->first()?->update(['status' => 'active']);
            TenantAudit::record($request, $tenant, 'tenant_trial_converted', ['status' => 'active']);
            return ApiResponse::success(['tenant' => TenantPresenter::summary($tenant->fresh())], 'Trial converted.');
        });
    }

    public function onboardingIndex(TenantActionRequest $request): mixed
    {
        $page = Tenant::withCount('onboardingSteps')->latest('id')->paginate($request->validated('per_page', 25));
        $data = collect($page->items())->map(fn ($tenant) => ['uuid' => $tenant->uuid, 'organization_name' => $tenant->organization_name, 'status' => $tenant->status, 'steps' => (int) $tenant->onboarding_steps_count])->all();
        return ApiResponse::success($data, 'Onboarding tenants fetched successfully.', 200, ['current_page' => $page->currentPage(), 'per_page' => $page->perPage(), 'total' => $page->total(), 'last_page' => $page->lastPage()]);
    }

    public function onboardingShow(string $tenant_uuid): mixed
    {
        $tenant = $this->tenant($tenant_uuid);
        return ApiResponse::success(['tenant' => TenantPresenter::safe($tenant->toArray()), 'steps' => TenantPresenter::safe($tenant->onboardingSteps()->with('updatedBy')->orderBy('id')->get()->toArray())], 'Onboarding tenant fetched successfully.');
    }

    public function updateOnboardingStep(TenantActionRequest $request, string $tenant_uuid, string $step_code): mixed
    {
        $tenant = $this->tenant($tenant_uuid);
        $step = $tenant->onboardingSteps()->updateOrCreate(['step_code' => $step_code], ['status' => $request->validated('status'), 'metadata' => $request->validated('metadata'), 'updated_by' => $request->user()->id]);
        return ApiResponse::success(['step' => TenantPresenter::safe($step->fresh()->toArray())], 'Onboarding step updated.');
    }
    public function changePlan(TenantActionRequest $request, string $tenant_uuid): mixed
    {
        return DB::transaction(function () use ($request, $tenant_uuid) {
            $tenant = $this->tenant($tenant_uuid, lock: true);
            $subscription = $this->provisioning->subscription($request, $tenant, $request->validated(), $this->provisioning->plan($request->validated('plan_uuid')));
            TenantAudit::record($request, $tenant, 'tenant_plan_changed', $request->validated());

            return ApiResponse::success(['subscription' => TenantPresenter::safe($subscription->load('tenant'))], 'Tenant plan changed successfully.');
        });
    }

    public function resetOwnerPassword(TenantActionRequest $request, string $tenant_uuid): mixed
    {
        return DB::transaction(function () use ($request, $tenant_uuid) {
            $tenant = $this->tenant($tenant_uuid, lock: true);
            $owner = $tenant->owner;
            if (! $owner) {
                $this->missing('Tenant owner');
            }
            $password = $request->validated('password') ?? Str::password(16);
            $owner->forceFill(['password' => Hash::make($password)])->save();
            $owner->tokens()->delete();
            TenantAudit::record($request, $tenant, 'tenant_owner_password_reset', ['owner_uuid' => $owner->uuid, 'reason' => $request->validated('reason')], true);
            if ($request->boolean('notify_owner')) {
                $this->provisioning->notify($owner, 'Password reset', 'Your account password was reset by a platform administrator. Use password recovery if you need to choose a new password.');
            }
            $result = ['owner' => TenantPresenter::safe($owner->load(['defaultOffice', 'roles']))];
            if (app()->isLocal()) {
                $result['temporary_password'] = $password;
            }

            return ApiResponse::success($result, 'Owner password reset successfully.');
        });
    }

    public function paymentOrder(TenantActionRequest $request, string $tenant_uuid): mixed
    {
        return DB::transaction(function () use ($request, $tenant_uuid) {
            $tenant = $this->tenant($tenant_uuid, lock: true);
            $data = $request->validated();
            $subscription = isset($data['subscription_uuid']) ? $tenant->subscription()->getRelated()->newQuery()->where('tenant_id', $tenant->id)->where('uuid', $data['subscription_uuid'])->first() : $tenant->subscription;
            if (isset($data['subscription_uuid']) && ! $subscription) {
                $this->missing('Subscription');
            }
            $result = $this->billing->payment($request, $tenant, $subscription, (float) $data['amount'], $data['currency'] ?? $subscription?->currency ?? $tenant->default_currency, $data['method'], $data['notes'] ?? []);

            return ApiResponse::success(TenantPresenter::safe($result), $data['method'] === 'cash' ? 'Cash payment recorded successfully.' : 'Payment order created successfully.', 201);
        });
    }

    public function impersonate(TenantActionRequest $request, string $tenant_uuid): mixed
    {
        return DB::transaction(function () use ($request, $tenant_uuid) {
            $tenant = $this->tenant($tenant_uuid, lock: true);
            $data = $request->validated();
            $user = isset($data['target_user_uuid']) ? $tenant->users()->where('uuid', $data['target_user_uuid'])->first() : $tenant->owner;
            if (! $user) {
                $this->missing('Target user');
            }
            $session = RemoteLoginSession::create(['uuid' => (string) Str::uuid(), 'tenant_id' => $tenant->id, 'platform_user_id' => $request->user()->id, 'target_user_id' => $user->id, 'reason' => $data['reason'], 'duration_minutes' => $data['duration_minutes'], 'expires_at' => now()->addMinutes($data['duration_minutes']), 'status' => 'active', 'ip_address' => $request->ip(), 'user_agent' => $request->userAgent()]);
            TenantAudit::record($request, $tenant, 'tenant_remote_login_started', ['session_uuid' => $session->uuid, 'reason' => $data['reason']], true);

            return ApiResponse::success(['session' => TenantPresenter::safe($session->load(['tenant', 'platformUser', 'targetUser']))], 'Remote login session started successfully.', 201);
        });
    }

    public function endImpersonation(TenantActionRequest $request, string $tenant_uuid, string $session_uuid): mixed
    {
        return DB::transaction(function () use ($request, $tenant_uuid, $session_uuid) {
            $tenant = $this->tenant($tenant_uuid, lock: true);
            $session = RemoteLoginSession::where('tenant_id', $tenant->id)->where('uuid', $session_uuid)->lockForUpdate()->first();
            if (! $session) {
                $this->missing('Remote login session');
            }
            if ($session->status !== 'ended') {
                $session->update(['status' => 'ended', 'ended_at' => now()]);
                TenantAudit::record($request, $tenant, 'tenant_remote_login_ended', ['session_uuid' => $session_uuid], true);
            }

            return ApiResponse::success(null, 'Remote login session ended successfully.');
        });
    }

    public function tab(string $tenant_uuid, string $tab): mixed
    {
        $tenant = $this->tenant($tenant_uuid);
        $data = match ($tab) {
            'users' => $tenant->users()->with(['defaultOffice', 'roles'])->get(),
            'offices' => $tenant->offices()->with(['country', 'state', 'city'])->get(),
            'subscription' => $tenant->subscription()->with('plan')->first(),
            'billing' => ['invoices' => $tenant->platformInvoices()->with(['items', 'subscription.plan', 'pdfFile'])->get(), 'payments' => $tenant->platformPayments()->with(['invoice', 'subscription.plan'])->get()],
            'usage' => TenantUsageSnapshot::where('tenant_id', $tenant->id)->with('tenant')->latest('id')->limit(12)->get(),
            'modules' => $this->overrides($tenant),
            'settings' => TenantSetting::where('tenant_id', $tenant->id)->get()->map(function ($setting) {
                $row = $setting->toArray();
                $row['value'] = $setting->is_encrypted ? null : json_decode($setting->value ?? 'null', true);

                return $row;
            }),
            'integrations' => TenantIntegration::where('tenant_id', $tenant->id)->with('provider')->get(),
            'security' => SecurityEvent::where('tenant_id', $tenant->id)->with(['user', 'tenant'])->latest('id')->get(),
            'support' => ['tickets' => $tenant->platformTickets()->with('assignedTo')->get(), 'remote_login_sessions' => RemoteLoginSession::where('tenant_id', $tenant->id)->with(['platformUser', 'targetUser'])->latest('id')->get()],
            'files' => File::where('tenant_id', $tenant->id)->get(['uuid', 'original_name', 'mime_type', 'size_bytes', 'visibility', 'created_at']),
            'activity' => ActivityLog::where('tenant_id', $tenant->id)->with(['actorUser', 'actorPlatformUser', 'tenant'])->latest('id')->get(),
            default => $this->missing('Tenant tab'),
        };

        return ApiResponse::success([$tab => TenantPresenter::safe($data)], 'Tenant '.$tab.' fetched successfully.');
    }

    public function modules(TenantActionRequest $request, string $tenant_uuid): mixed
    {
        return DB::transaction(function () use ($request, $tenant_uuid) {
            $tenant = $this->tenant($tenant_uuid, lock: true);
            foreach ($request->validated('modules') as $data) {
                $override = TenantModuleOverride::firstOrNew(['tenant_id' => $tenant->id, 'module_code' => $data['module_code']]);
                if (! $override->exists) {
                    $override->uuid = (string) Str::uuid();
                }
                $override->fill($data + ['updated_by' => $request->user()->id])->save();
            }
            TenantAudit::record($request, $tenant, 'tenant_module_overrides_updated', $request->validated());

            return ApiResponse::success(['modules' => TenantPresenter::safe($this->overrides($tenant))], 'Module overrides updated successfully.');
        });
    }

    public function moduleEntitlements(string $tenant_uuid): mixed
    {
        $tenant = $this->tenant($tenant_uuid);
        $modules = Module::query()->orderBy('sort_order')->orderBy('name')->get()->map(function (Module $module) use ($tenant) {
            $override = $tenant->moduleOverrides()->where('module_code', $module->code)->first();
            $row = $module->toArray();
            $row['tenant_enabled'] = $override?->enabled;
            $row['tenant_limits'] = $override?->limits;
            return $row;
        });
        return ApiResponse::success(['modules' => $modules], 'Tenant module entitlements fetched.');
    }

    public function upsertModuleOverride(UpsertTenantModuleOverrideRequest $request, string $tenant_uuid, string $module_code): mixed
    {
        return DB::transaction(function () use ($request, $tenant_uuid, $module_code) {
            $tenant = $this->tenant($tenant_uuid, lock: true);
            $module = Module::where('code', $module_code)->first();
            if (! $module) return ApiResponse::error('Platform module not found.', 404, null, 'MODULE_NOT_FOUND');
            $data = $request->validated();
            $override = TenantModuleOverride::firstOrNew(['tenant_id' => $tenant->id, 'module_code' => $module->code]);
            if (! $override->exists) $override->uuid = (string) Str::uuid();
            $override->fill(collect($data)->except('reason')->all() + ['updated_by' => $request->user()->id])->save();
            TenantAudit::record($request, $tenant, 'tenant_module_override_updated', $data);
            return ApiResponse::success(['override' => $override->load(['tenant', 'module', 'updatedBy'])], 'Tenant module override saved.');
        });
    }

    private function overrides(Tenant $tenant): mixed
    {
        return TenantModuleOverride::where('tenant_id', $tenant->id)->with(['module', 'updatedBy'])->get();
    }

    private function lifecycle(TenantActionRequest $request, string $uuid, string $status, string $event, bool $delete = false): mixed
    {
        return DB::transaction(function () use ($request, $uuid, $status, $event, $delete) {
            $tenant = $this->tenant($uuid, lock: true);
            $this->setStatus($request, $tenant, $status, $event);

            return ApiResponse::success($delete ? null : TenantPresenter::detail($tenant), 'Tenant '.$status.' successfully.');
        });
    }

    private function setStatus(TenantActionRequest $request, Tenant $tenant, string $status, string $event): void
    {
        $tenant->update(['status' => $status]);
        if ($status === 'archived') {
            $tenant->delete();
        }
        TenantAudit::record($request, $tenant, $event, $request->validated() + ['status' => $status], true);
        if ($request->boolean('notify_owner') && $tenant->owner) {
            $this->provisioning->notify($tenant->owner, 'Tenant status updated', 'Your organization status is now '.$status.'.');
        }
    }

    private function tenant(string $uuid, bool $trashed = false, bool $lock = false): Tenant
    {
        $query = $trashed ? Tenant::withTrashed() : Tenant::query();
        if ($lock) {
            $query->lockForUpdate();
        }

        return $query->where('uuid', $uuid)->first() ?? $this->missing('Tenant');
    }

    private function missing(string $name): never
    {
        throw new HttpResponseException(ApiResponse::error($name.' not found.', 404));
    }
}
