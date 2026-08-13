<?php

namespace App\Http\Controllers\Tenant;

use App\Models\User;
use App\Services\Tenant\TenantWorkspaceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TenantUserController extends BaseTenantController
{
    public function __construct(private readonly TenantWorkspaceService $tenant) {}

    public function index(Request $request): JsonResponse
    {
        $query = User::query()->where('tenant_id', app(\App\Tenancy\TenantContext::class)->id());
        foreach (['status', 'account_type'] as $field) {
            if ($request->filled('filter.' . $field)) {
                $query->where($field, $request->input('filter.' . $field));
            }
        }
        if ($request->filled('search')) {
            $query->where(fn($q) => $q->where('display_name', 'like', '%' . $request->search . '%')->orWhere('email', 'like', '%' . $request->search . '%'));
        }
        $page = $query->orderBy('display_name')->paginate((int) $request->integer('per_page', 25));

        return $this->list($page->items(), $page);
    }

    public function invite(Request $request): JsonResponse
    {
        $data = $request->validate(['first_name' => ['required', 'string', 'max:100'], 'last_name' => ['nullable', 'string', 'max:100'], 'display_name' => ['nullable', 'string', 'max:200'], 'email' => ['required', 'email', 'max:150'], 'mobile' => ['nullable', 'string', 'max:20'], 'staff_id' => ['nullable', 'string'], 'default_office_id' => ['nullable', 'string'], 'account_type' => ['nullable', 'in:owner,staff,client'], 'status' => ['nullable', 'in:invited,active,inactive,suspended'], 'role_ids' => ['nullable', 'array'], 'role_ids.*' => ['required', 'string']]);
        $tenantId = app(\App\Tenancy\TenantContext::class)->id();
        if (User::query()->where('tenant_id', $tenantId)->where('email', $data['email'])->exists()) {
            return $this->businessError('Email already exists for this tenant.', 'TENANT_USER_EMAIL_EXISTS', 409);
        }
        $password = Str::password(16);
        $user = User::query()->create(['uuid' => (string) Str::uuid(), 'tenant_id' => $tenantId, 'staff_id' => $this->tenant->uuidToId('staff', $data['staff_id'] ?? null, true), 'default_office_id' => $this->tenant->uuidToId('tenant_offices', $data['default_office_id'] ?? null, true), 'first_name' => $data['first_name'], 'last_name' => $data['last_name'] ?? null, 'display_name' => $data['display_name'] ?? trim($data['first_name'] . ' ' . ($data['last_name'] ?? '')), 'email' => $data['email'], 'mobile' => $data['mobile'] ?? null, 'password' => Hash::make($password), 'account_type' => $data['account_type'] ?? 'staff', 'status' => $data['status'] ?? 'invited', 'created_by' => $request->user()?->id]);
        $this->syncRoles($request, $user, $data['role_ids'] ?? []);
        $this->tenant->audit($request, 'tenant_user_invited', 'user', $user->id, null, $user->toArray());

        return $this->success(['user' => $user->fresh(), 'temporary_password' => app()->isLocal() ? $password : null], 'Tenant user invited.', 201);
    }

    public function show(string $user_uuid): JsonResponse
    {
        $user = $this->findUser($user_uuid);

        return $this->success(['user' => $user, 'roles' => $this->roles($user->id)]);
    }

    public function update(Request $request, string $user_uuid): JsonResponse
    {
        $user = $this->findUser($user_uuid);
        $data = $request->validate(['first_name' => ['sometimes', 'string', 'max:100'], 'last_name' => ['nullable', 'string', 'max:100'], 'display_name' => ['sometimes', 'string', 'max:200'], 'mobile' => ['nullable', 'string', 'max:20'], 'staff_id' => ['nullable', 'string'], 'default_office_id' => ['nullable', 'string'], 'timezone' => ['nullable', 'string', 'max:100'], 'locale' => ['nullable', 'string', 'max:20'], 'status' => ['nullable', 'in:invited,active,inactive,suspended']]);
        foreach (['staff_id' => 'staff', 'default_office_id' => 'tenant_offices'] as $key => $table) {
            if (array_key_exists($key, $data)) {
                $data[$key] = $this->tenant->uuidToId($table, $data[$key], true);
            }
        }
        $old = $user->toArray();
        $user->fill([...$data, 'updated_by' => $request->user()?->id])->save();
        $this->tenant->audit($request, 'tenant_user_updated', 'user', $user->id, $old, $user->fresh()->toArray());

        return $this->success(['user' => $user->fresh()], 'Tenant user updated.');
    }

    public function syncUserRoles(Request $request, string $user_uuid): JsonResponse
    {
        $user = $this->findUser($user_uuid);
        $data = $request->validate(['role_ids' => ['required', 'array'], 'role_ids.*' => ['required', 'string']]);
        if ($this->removesFinalOwnerAdmin($user, $data['role_ids'])) {
            return $this->businessError('Cannot remove the final owner/admin role from this tenant.', 'FINAL_OWNER_ADMIN_ROLE_REQUIRED', 409);
        }
        $this->syncRoles($request, $user, $data['role_ids']);

        return $this->success(['roles' => $this->roles($user->id)], 'User roles updated.');
    }

    public function suspend(Request $request, string $user_uuid): JsonResponse
    {
        return $this->status($request, $user_uuid, 'suspended');
    }

    public function activate(Request $request, string $user_uuid): JsonResponse
    {
        return $this->status($request, $user_uuid, 'active');
    }

    public function resetPassword(Request $request, string $user_uuid): JsonResponse
    {
        $user = $this->findUser($user_uuid);
        $password = Str::password(16);
        $old = ['password_reset' => false];
        $user->forceFill(['password' => Hash::make($password), 'updated_by' => $request->user()?->id])->save();
        $this->tenant->audit($request, 'tenant_user_password_reset', 'user', $user->id, $old, ['password_reset' => true]);

        return $this->success(['temporary_password' => app()->isLocal() ? $password : null], 'Password reset queued.');
    }

    private function status(Request $request, string $uuid, string $status): JsonResponse
    {
        $user = $this->findUser($uuid);
        if ($status === 'suspended' && $this->removesFinalOwnerAdmin($user, [])) {
            return $this->businessError('Cannot suspend the final owner/admin user from this tenant.', 'FINAL_OWNER_ADMIN_ROLE_REQUIRED', 409);
        }
        $old = $user->toArray();
        $user->forceFill(['status' => $status, 'updated_by' => $request->user()?->id])->save();
        $this->tenant->audit($request, 'tenant_user_' . $status, 'user', $user->id, $old, $user->fresh()->toArray());

        return $this->success(['user' => $user->fresh()], 'Tenant user ' . $status . '.');
    }

    private function findUser(string $uuid): User
    {
        return User::query()->where('tenant_id', app(\App\Tenancy\TenantContext::class)->id())->where('uuid', $uuid)->firstOrFail();
    }

    private function syncRoles(Request $request, User $user, array $roleUuids): void
    {
        $roleIds = DB::table('roles')->where('tenant_id', app(\App\Tenancy\TenantContext::class)->id())->whereIn('uuid', $roleUuids)->pluck('id')->all();
        DB::table('model_has_roles')->where('tenant_id', app(\App\Tenancy\TenantContext::class)->id())->where('model_type', User::class)->where('model_id', $user->id)->delete();
        foreach ($roleIds as $roleId) {
            DB::table('model_has_roles')->insert(['tenant_id' => app(\App\Tenancy\TenantContext::class)->id(), 'role_id' => $roleId, 'model_id' => $user->id, 'model_type' => User::class]);
        }
        $this->tenant->audit($request, 'tenant_user_roles_synced', 'user', $user->id, null, ['role_ids' => $roleIds]);
    }

    private function roles(int $userId): array
    {
        return DB::table('model_has_roles')->join('roles', 'roles.id', '=', 'model_has_roles.role_id')->where('model_has_roles.tenant_id', app(\App\Tenancy\TenantContext::class)->id())->where('model_has_roles.model_type', User::class)->where('model_has_roles.model_id', $userId)->get(['roles.uuid', 'roles.name', 'roles.display_name', 'roles.status'])->all();
    }

    private function removesFinalOwnerAdmin(User $user, array $newRoleUuids): bool
    {
        $currentAdmin = collect($this->roles($user->id))->contains(fn($role) => in_array($role->name, ['owner', 'admin'], true));
        if (! $currentAdmin) {
            return false;
        }
        $newAdmin = DB::table('roles')->where('tenant_id', app(\App\Tenancy\TenantContext::class)->id())->whereIn('uuid', $newRoleUuids)->whereIn('name', ['owner', 'admin'])->exists();
        if ($newAdmin) {
            return false;
        }

        return DB::table('model_has_roles')->join('roles', 'roles.id', '=', 'model_has_roles.role_id')->where('model_has_roles.tenant_id', app(\App\Tenancy\TenantContext::class)->id())->where('model_has_roles.model_type', User::class)->whereIn('roles.name', ['owner', 'admin'])->where('roles.status', 'active')->distinct('model_has_roles.model_id')->count('model_has_roles.model_id') <= 1;
    }
}
