<?php

namespace App\Http\Controllers;

use App\Http\Requests\ListPlatformDepartmentsRequest;
use App\Http\Requests\StorePlatformDepartmentRequest;
use App\Http\Requests\UpdatePlatformDepartmentRequest;
use App\Models\PlatformDepartment;
use App\Models\PlatformUser;
use App\Support\ActivityLogger;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

final class PlatformDepartmentController extends Controller
{
    public function index(ListPlatformDepartmentsRequest $request): mixed
    {
        $input = $request->validated();
        $query = PlatformDepartment::with(['parent', 'manager', 'children', 'users', 'teams'])
            ->withCount(['users', 'children', 'teams'])
            ->orderBy($input['sort'] ?? 'name', $input['direction'] ?? 'asc');

        if (! empty($input['search'])) {
            $query->where(fn ($q) => $q->where('name', 'like', '%'.$input['search'].'%')
                ->orWhere('code', 'like', '%'.$input['search'].'%'));
        }
        if (! empty($input['filter']['status'])) $query->where('status', $input['filter']['status']);
        if (array_key_exists('parent_uuid', $input['filter'] ?? [])) {
            $parentId = $input['filter']['parent_uuid']
                ? PlatformDepartment::where('uuid', $input['filter']['parent_uuid'])->value('id')
                : null;
            $query->where('parent_id', $parentId);
        }

        return ApiResponse::success($query->get(), 'Platform departments fetched.');
    }

    public function store(StorePlatformDepartmentRequest $request): mixed
    {
        $input = $request->validated();
        $parentId = array_key_exists('parent_uuid', $input) ? $this->resolveActiveDepartment($input['parent_uuid']) : ($input['parent_id'] ?? null);
        if (($input['parent_uuid'] ?? $input['parent_id'] ?? null) && ! $parentId) {
            return ApiResponse::error('The selected parent department is inactive.', 422, null, 'INACTIVE_PARENT_DEPARTMENT');
        }

        $managerId = array_key_exists('manager_platform_user_uuid', $input) ? $this->resolveActiveUser($input['manager_platform_user_uuid']) : ($input['platform_manager_user_id'] ?? null);
        if (($input['manager_platform_user_uuid'] ?? $input['platform_manager_user_id'] ?? null) && ! $managerId) {
            return ApiResponse::error('The selected department manager is inactive.', 422, null, 'INACTIVE_DEPARTMENT_MANAGER');
        }

        $department = PlatformDepartment::create([
            'uuid' => (string) Str::uuid(),
            'parent_id' => $parentId,
            'name' => $input['name'],
            'code' => $input['code'] ?? null,
            'platform_manager_user_id' => $managerId,
            'status' => $input['status'] ?? 'active',
        ]);

        ActivityLogger::record($request, 'platform_department.created', $department, 'Platform department created.');
        return ApiResponse::success(['department' => $this->load($department)], 'Platform department created successfully.', 201);
    }

    public function show(string $uuid): mixed
    {
        $department = $this->find($uuid);
        if (! $department) return ApiResponse::error('Platform department not found.', 404, null, 'PLATFORM_DEPARTMENT_NOT_FOUND');
        return ApiResponse::success(['department' => $this->load($department)], 'Platform department fetched.');
    }

    public function update(UpdatePlatformDepartmentRequest $request, string $uuid): mixed
    {
        $department = $this->find($uuid);
        if (! $department) return ApiResponse::error('Platform department not found.', 404, null, 'PLATFORM_DEPARTMENT_NOT_FOUND');

        $input = $request->validated();
        if (($input['parent_uuid'] ?? null) === $uuid) {
            return ApiResponse::error('A department cannot be its own parent.', 422, null, 'DEPARTMENT_PARENT_INVALID');
        }

        $data = [];
        if (array_key_exists('name', $input)) $data['name'] = $input['name'];
        if (array_key_exists('code', $input)) $data['code'] = $input['code'];
        if (array_key_exists('status', $input)) $data['status'] = $input['status'];

        if (array_key_exists('parent_uuid', $input) || array_key_exists('parent_id', $input)) {
            $parentReference = array_key_exists('parent_uuid', $input) ? $input['parent_uuid'] : ($input['parent_id'] ?? null);
            $data['parent_id'] = array_key_exists('parent_uuid', $input)
                ? $this->resolveActiveDepartment($input['parent_uuid'])
                : ($input['parent_id'] ?? null);
            if ($parentReference && ! $data['parent_id']) {
                return ApiResponse::error('The selected parent department is inactive.', 422, null, 'INACTIVE_PARENT_DEPARTMENT');
            }
        }

        if (array_key_exists('manager_platform_user_uuid', $input) || array_key_exists('platform_manager_user_id', $input)) {
            $managerReference = array_key_exists('manager_platform_user_uuid', $input) ? $input['manager_platform_user_uuid'] : ($input['platform_manager_user_id'] ?? null);
            $data['platform_manager_user_id'] = array_key_exists('manager_platform_user_uuid', $input)
                ? $this->resolveActiveUser($input['manager_platform_user_uuid'])
                : ($input['platform_manager_user_id'] ?? null);
            if ($managerReference && ! $data['platform_manager_user_id']) {
                return ApiResponse::error('The selected department manager is inactive.', 422, null, 'INACTIVE_DEPARTMENT_MANAGER');
            }
        }

        $department->update($data);
        ActivityLogger::record($request, 'platform_department.updated', $department, 'Platform department updated.');
        return ApiResponse::success(['department' => $this->load($department)], 'Platform department updated successfully.');
    }

    public function destroy(Request $request, string $uuid): mixed
    {
        $department = $this->find($uuid);
        if (! $department) return ApiResponse::error('Platform department not found.', 404, null, 'PLATFORM_DEPARTMENT_NOT_FOUND');
        $department->update(['status' => 'inactive']);
        ActivityLogger::record($request, 'platform_department.archived', $department, 'Platform department archived.');
        return ApiResponse::success(null, 'Platform department archived successfully.');
    }

    private function find(string $uuid): ?PlatformDepartment
    {
        return PlatformDepartment::where('uuid', $uuid)->first();
    }

    private function load(PlatformDepartment $department): PlatformDepartment
    {
        return $department->fresh()->load(['parent', 'manager', 'children', 'users', 'teams'])->loadCount(['users', 'children', 'teams']);
    }

    private function resolveActiveDepartment(?string $uuid): ?int
    {
        if ($uuid === null) return null;
        return PlatformDepartment::where('uuid', $uuid)->where('status', 'active')->value('id');
    }

    private function resolveActiveUser(?string $uuid): ?int
    {
        if ($uuid === null) return null;
        return PlatformUser::where('uuid', $uuid)->where('status', 'active')->value('id');
    }
}