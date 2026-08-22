<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Shared\BaseApiController;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\Rbac\RbacAuditLogger;
use App\Tenancy\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TenantRoleController extends BaseApiController
{
    public function __construct(
        private readonly RbacAuditLogger $audit,
        private readonly TenantContext $tenant
    ) {
    }

    public function index(Request $request)
    {
        $baseQuery = Role::query();
        $query = (clone $baseQuery)->withCount(['permissions', 'users']);

        if ($request->filled('search')) {
            $query->where(fn ($x) => $x
                ->where('name', 'like', '%'.$request->search.'%')
                ->orWhere('display_name', 'like', '%'.$request->search.'%'));
        }

        foreach (['status', 'guard_name'] as $field) {
            if ($request->filled('filter.'.$field)) {
                $query->where($field, $request->input('filter.'.$field));
            }
        }

        $page = $query->orderBy('name')->paginate((int) $request->integer('per_page', 25));

        return $this->list($page->items(), $page, 'OK', [
            'stats' => [
                'total' => (clone $baseQuery)->count(),
                'active' => (clone $baseQuery)->where('status', 'active')->count(),
                'inactive' => (clone $baseQuery)->where('status', 'inactive')->count(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);
        $permissionIds = $this->permissionIds($data['permission_ids'] ?? []);
        unset($data['permission_ids'], $data['audit_reason']);

        $role = Role::query()->create($data);
        $role->permissions()->sync($permissionIds);
        $this->audit->log($request, 'tenant_role_created', $role, null, ['permission_ids' => $permissionIds], $request->input('audit_reason'));

        return $this->success(['role' => $this->payload($role->fresh(), true)], 'Role created.', 201);
    }

    public function show($role_uuid)
    {
        return $this->success(['role' => $this->payload($this->findRole($role_uuid), true, true)]);
    }

    public function update(Request $request, $role_uuid)
    {
        $role = $this->findRole($role_uuid);
        $data = $this->validatedData($request, $role);

        if ($role->is_system && isset($data['name']) && $data['name'] !== $role->name) {
            return $this->businessError('System roles cannot be renamed.', 'SYSTEM_ROLE_RENAME_FORBIDDEN', 403);
        }

        $old = $this->snapshot($role);
        $permissionIds = array_key_exists('permission_ids', $data) ? $this->permissionIds($data['permission_ids']) : null;
        unset($data['permission_ids'], $data['audit_reason']);

        $role->fill($data)->save();

        if ($permissionIds !== null) {
            $role->permissions()->sync($permissionIds);
        }

        $this->audit->log($request, 'tenant_role_updated', $role, $old, $this->snapshot($role), $request->input('audit_reason'));

        return $this->success(['role' => $this->payload($role->fresh(), true, true)], 'Role updated.');
    }

    public function destroy(Request $request, $role_uuid)
    {
        $role = $this->findRole($role_uuid);
        $blocked = $this->deleteBlocker($role);

        if ($blocked) {
            return $this->businessError($blocked['message'], $blocked['code'], $blocked['status']);
        }

        $this->deleteRole($request, $role);

        return $this->success(null, 'Role deleted.');
    }

    public function bulkDestroy(Request $request)
    {
        $data = $request->validate([
            'role_uuids' => ['required', 'array', 'min:1'],
            'role_uuids.*' => ['required', 'uuid'],
            'audit_reason' => ['nullable', 'string', 'max:500'],
        ]);

        $roles = Role::query()->whereIn('uuid', $data['role_uuids'])->get();
        $deleted = 0;
        $skipped = 0;

        DB::transaction(function () use ($request, $roles, &$deleted, &$skipped): void {
            foreach ($roles as $role) {
                if ($this->deleteBlocker($role)) {
                    $skipped++;
                    continue;
                }

                $this->deleteRole($request, $role);
                $deleted++;
            }
        });

        return $this->success(['deleted' => $deleted, 'skipped' => $skipped], 'Selected roles processed.');
    }

    public function clone(Request $request, $role_uuid)
    {
        $source = $this->findRole($role_uuid);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150', Rule::unique('roles')->where('tenant_id', $this->tenant->id())->where('guard_name', $source->guard_name)],
            'display_name' => ['required', 'string', 'max:150'],
            'copy_permissions' => ['sometimes', 'boolean'],
            'copy_description' => ['sometimes', 'boolean'],
            'status' => ['sometimes', Rule::in(['active', 'inactive'])],
            'audit_reason' => ['nullable', 'string', 'max:500'],
        ]);

        $role = Role::query()->create([
            'name' => $data['name'],
            'display_name' => $data['display_name'],
            'guard_name' => $source->guard_name,
            'description' => ($data['copy_description'] ?? true) ? $source->description : null,
            'is_system' => false,
            'status' => $data['status'] ?? 'inactive',
        ]);

        if ($data['copy_permissions'] ?? true) {
            $role->permissions()->sync($source->permissions()->pluck('permissions.id')->all());
        }

        $this->audit->log($request, 'tenant_role_cloned', $role, null, ['source_role_id' => $source->id], $request->input('audit_reason'));

        return $this->success(['role' => $this->payload($role->fresh(), true)], 'Role cloned.', 201);
    }

    public function activate(Request $request, $role_uuid)
    {
        return $this->setStatus($request, $role_uuid, 'active');
    }

    public function deactivate(Request $request, $role_uuid)
    {
        $role = $this->findRole($role_uuid);

        if ($this->wouldRemoveFinalOwnerAdmin($role)) {
            return $this->businessError('Cannot remove the final owner/admin role from this tenant.', 'FINAL_OWNER_ADMIN_ROLE_REQUIRED', 409);
        }

        return $this->setStatus($request, $role_uuid, 'inactive');
    }

    public function permissions($role_uuid)
    {
        return $this->success(['permissions' => $this->grouped($this->findRole($role_uuid)->permissions()->orderBy('module')->orderBy('name')->get())]);
    }

    public function syncPermissions(Request $request, $role_uuid)
    {
        $role = $this->findRole($role_uuid);
        $data = $request->validate([
            'permission_ids' => ['required', 'array', 'min:1'],
            'permission_ids.*' => ['required', 'string'],
            'audit_reason' => ['nullable', 'string', 'max:500'],
        ]);
        $old = ['permission_ids' => $role->permissions()->pluck('permissions.id')->all()];
        $permissionIds = $this->permissionIds($data['permission_ids']);

        $role->permissions()->sync($permissionIds);
        $this->audit->log($request, 'tenant_role_permissions_synced', $role, $old, ['permission_ids' => $permissionIds], $request->input('audit_reason'));

        return $this->success(['role' => $this->payload($role->fresh(), true)], 'Role permissions updated.');
    }

    public function users($role_uuid)
    {
        return $this->success(['users' => $this->findRole($role_uuid)->users()->orderBy('display_name')->get(['users.uuid', 'display_name', 'email', 'account_type', 'status'])]);
    }

    public function assignUsers(Request $request, $role_uuid)
    {
        $role = $this->findRole($role_uuid);
        $data = $request->validate([
            'user_ids' => ['required', 'array'],
            'user_ids.*' => ['required', 'string'],
            'audit_reason' => ['nullable', 'string', 'max:500'],
        ]);
        $userIds = User::query()->where('tenant_id', $this->tenant->id())->whereIn('uuid', $data['user_ids'])->pluck('id')->all();

        foreach ($userIds as $userId) {
            DB::table('model_has_roles')->updateOrInsert([
                'tenant_id' => $this->tenant->id(),
                'role_id' => $role->id,
                'model_id' => $userId,
                'model_type' => User::class,
            ], []);
        }

        $this->audit->log($request, 'tenant_role_users_assigned', $role, null, ['user_ids' => $userIds], $request->input('audit_reason'));

        return $this->success(['users' => $role->users()->count()], 'Users assigned.');
    }

    public function removeUser(Request $request, $role_uuid, $user_uuid)
    {
        $role = $this->findRole($role_uuid);
        $user = User::query()->where('tenant_id', $this->tenant->id())->where('uuid', $user_uuid)->firstOrFail();

        if (in_array($role->name, ['owner', 'admin'], true) && $this->ownerAdminCount() <= 1) {
            return $this->businessError('Cannot remove the final owner/admin role from this tenant.', 'FINAL_OWNER_ADMIN_ROLE_REQUIRED', 409);
        }

        DB::table('model_has_roles')->where([
            'tenant_id' => $this->tenant->id(),
            'role_id' => $role->id,
            'model_id' => $user->id,
            'model_type' => User::class,
        ])->delete();

        $this->audit->log($request, 'tenant_role_user_removed', $role, ['user_id' => $user->id], null, $request->input('audit_reason'));

        return $this->success(null, 'User removed from role.');
    }

    private function setStatus(Request $request, $role_uuid, $status)
    {
        $role = $this->findRole($role_uuid);
        $old = $this->snapshot($role);
        $role->forceFill(['status' => $status])->save();
        $this->audit->log($request, 'tenant_role_'.$status, $role, $old, $this->snapshot($role), $request->input('audit_reason'));

        return $this->success(['role' => $this->payload($role->fresh())], 'Role '.$status.'.');
    }

    private function findRole($uuid)
    {
        return Role::query()->where('uuid', $uuid)->firstOrFail();
    }

    private function permissionIds(array $uuids)
    {
        return Permission::query()->whereIn('uuid', $uuids)->pluck('id')->all();
    }

    private function validatedData(Request $request, ?Role $role = null)
    {
        $guard = (string) $request->input('guard_name', $role?->guard_name ?? 'tenant');

        return $request->validate([
            'name' => [$role ? 'sometimes' : 'required', 'string', 'max:150', Rule::unique('roles')->where('tenant_id', $this->tenant->id())->where('guard_name', $guard)->ignore($role?->id)],
            'display_name' => [$role ? 'sometimes' : 'required', 'string', 'max:150'],
            'guard_name' => ['sometimes', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:255'],
            'is_system' => ['sometimes', 'boolean'],
            'status' => ['sometimes', Rule::in(['active', 'inactive'])],
            'permission_ids' => ['sometimes', 'array'],
            'permission_ids.*' => ['required', 'string'],
            'audit_reason' => ['nullable', 'string', 'max:500'],
        ]);
    }

    private function payload(Role $role, $withPermissions = false, $withUsers = false)
    {
        $data = $role->loadCount(['permissions', 'users'])->toArray();

        if ($withPermissions) {
            $data['permissions'] = $this->grouped($role->permissions()->orderBy('module')->orderBy('name')->get());
        }

        if ($withUsers) {
            $data['users'] = $role->users()->orderBy('display_name')->get(['users.uuid', 'display_name', 'email', 'account_type', 'status'])->all();
        }

        return $data;
    }

    private function grouped($permissions)
    {
        return $permissions->groupBy('module')->map(fn ($items) => $items->values()->map->only(['uuid', 'module', 'name', 'display_name', 'description', 'guard_name', 'is_system', 'status'])->all())->all();
    }

    private function snapshot(Role $role)
    {
        return $role->fresh()->load('permissions')->toArray();
    }

    private function deleteBlocker(Role $role): ?array
    {
        if ($role->is_system) {
            return ['message' => 'System roles cannot be deleted.', 'code' => 'SYSTEM_ROLE_DELETE_FORBIDDEN', 'status' => 403];
        }

        if ($this->wouldRemoveFinalOwnerAdmin($role)) {
            return ['message' => 'Cannot remove the final owner/admin role from this tenant.', 'code' => 'FINAL_OWNER_ADMIN_ROLE_REQUIRED', 'status' => 409];
        }

        if ($role->users()->exists()) {
            return ['message' => 'Assigned roles cannot be deleted.', 'code' => 'ROLE_IN_USE', 'status' => 409];
        }

        return null;
    }

    private function deleteRole(Request $request, Role $role): void
    {
        $old = $this->snapshot($role);
        $role->permissions()->detach();
        $role->delete();
        $this->audit->log($request, 'tenant_role_deleted', $role, $old, null, $request->input('audit_reason'));
    }

    private function wouldRemoveFinalOwnerAdmin(Role $role)
    {
        return in_array($role->name, ['owner', 'admin'], true)
            && $role->users()->exists()
            && $this->ownerAdminCount() <= $role->users()->distinct('users.id')->count('users.id');
    }

    private function ownerAdminCount()
    {
        return DB::table('model_has_roles')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('model_has_roles.tenant_id', $this->tenant->id())
            ->where('model_has_roles.model_type', User::class)
            ->where('roles.tenant_id', $this->tenant->id())
            ->where('roles.status', 'active')
            ->whereIn('roles.name', ['owner', 'admin'])
            ->distinct('model_has_roles.model_id')
            ->count('model_has_roles.model_id');
    }
}
