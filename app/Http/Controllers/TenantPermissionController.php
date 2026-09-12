<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Shared\BaseApiController;
use App\Models\Permission;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TenantPermissionController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        $query = $this->filteredQuery($request);
        $kpiRows = (clone $query)->withCount('roles')->get();
        $paginator = (clone $query)
            ->with(['roles'])
            ->withCount('roles')
            ->paginate($this->perPage($request))
            ->withQueryString();

        $kpis = [
            'total' => $kpiRows->count(),
            'active' => $kpiRows->where('status', 'active')->count(),
            'inactive' => $kpiRows->where('status', 'inactive')->count(),
            'system' => $kpiRows->where('is_system', true)->count(),
            'custom' => $kpiRows->where('is_system', false)->count(),
            'assignments' => $kpiRows->sum('roles_count'),
        ];

        return ApiResponse::success($paginator->items(), 'Tenant permissions fetched.', 200, [
            'current_page' => $paginator->currentPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'last_page' => $paginator->lastPage(),
            'kpis' => $kpis,
        ]);
    }

    private function filteredQuery(Request $request)
    {
        $query = Permission::query();
        if ($request->filled('search')) {
            $query->where(fn ($q) => $q
                ->where('name', 'like', '%'.$request->search.'%')
                ->orWhere('display_name', 'like', '%'.$request->search.'%')
                ->orWhere('module', 'like', '%'.$request->search.'%'));
        }
        foreach (['module', 'guard_name', 'status'] as $field) {
            if ($request->filled('filter.'.$field)) {
                $query->where($field, $request->input('filter.'.$field));
            }
        }

        $sort = $request->input('sort', 'module');
        $sort = $sort === 'created' ? 'created_at' : $sort;
        if (! in_array($sort, ['module', 'name', 'display_name', 'status', 'created_at', 'updated_at'], true)) {
            $sort = 'module';
        }

        return $query->orderBy($sort, $request->input('direction', 'asc') === 'desc' ? 'desc' : 'asc');
    }

    private function perPage(Request $request): int
    {
        return min(max((int) $request->integer('per_page', 10), 1), 100);
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
