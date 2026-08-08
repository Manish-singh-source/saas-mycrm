<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Shared\BaseApiController;
use App\Models\Permission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TenantPermissionController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        $query = Permission::query()->withCount('roles');
        if ($request->filled('search')) {
            $query->where(fn ($q) => $q->where('name', 'like', '%'.$request->search.'%')->orWhere('display_name', 'like', '%'.$request->search.'%'));
        }
        foreach (['module', 'guard_name', 'status'] as $field) {
            if ($request->filled('filter.'.$field)) {
                $query->where($field, $request->input('filter.'.$field));
            }
        }
        $permissions = $query->orderBy('module')->orderBy('name')->paginate((int) $request->integer('per_page', 50));

        return $this->list($permissions->items(), $permissions);
    }

    public function grouped(): JsonResponse
    {
        $permissions = Permission::query()->where('status', 'active')->orderBy('module')->orderBy('name')->get();

        return $this->success(['permissions' => $permissions->groupBy('module')->map(fn ($items) => $items->values()->all())->all()]);
    }

    public function show(string $permission_uuid): JsonResponse
    {
        return $this->success(['permission' => Permission::query()->where('uuid', $permission_uuid)->withCount('roles')->firstOrFail()]);
    }
}
