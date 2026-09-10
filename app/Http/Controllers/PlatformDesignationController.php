<?php

namespace App\Http\Controllers;

use App\Http\Requests\ListPlatformDesignationsRequest;
use App\Http\Requests\StorePlatformDesignationRequest;
use App\Http\Requests\UpdatePlatformDesignationRequest;
use App\Models\PlatformDesignation;
use App\Support\ActivityLogger;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

final class PlatformDesignationController extends Controller
{
    public function index(ListPlatformDesignationsRequest $request): mixed
    {
        $input = $request->validated();
        $query = PlatformDesignation::with(['users'])->withCount('users')
            ->orderBy($input['sort'] ?? 'name', $input['direction'] ?? 'asc');

        if (! empty($input['search'])) {
            $query->where(fn ($q) => $q->where('name', 'like', '%'.$input['search'].'%')
                ->orWhere('code', 'like', '%'.$input['search'].'%'));
        }

        foreach ($input['filter'] ?? [] as $column => $value) {
            if (in_array($column, ['status', 'level'], true) && $value !== null && $value !== '') {
                $query->where($column, $value);
            }
        }

        return ApiResponse::success($query->get(), 'Platform designations fetched.');
    }

    public function store(StorePlatformDesignationRequest $request): mixed
    {
        $input = $request->validated();
        $designation = PlatformDesignation::create([
            'uuid' => (string) Str::uuid(),
            'name' => $input['name'],
            'code' => $input['code'] ?? null,
            'description' => $input['description'] ?? null,
            'level' => $input['level'] ?? null,
            'status' => $input['status'] ?? 'active',
        ]);

        ActivityLogger::record($request, 'platform_designation.created', $designation, 'Platform designation created.');

        return ApiResponse::success(['designation' => $this->load($designation)], 'Platform designation created successfully.', 201);
    }

    public function show(string $uuid): mixed
    {
        $designation = $this->find($uuid);
        if (! $designation) return ApiResponse::error('Platform designation not found.', 404, null, 'PLATFORM_DESIGNATION_NOT_FOUND');

        return ApiResponse::success(['designation' => $this->load($designation)], 'Platform designation fetched.');
    }

    public function update(UpdatePlatformDesignationRequest $request, string $uuid): mixed
    {
        $designation = $this->find($uuid);
        if (! $designation) return ApiResponse::error('Platform designation not found.', 404, null, 'PLATFORM_DESIGNATION_NOT_FOUND');

        $input = $request->validated();
        $data = collect($input)->only(['name', 'code', 'description', 'level', 'status'])->all();

        $designation->update($data);
        ActivityLogger::record($request, 'platform_designation.updated', $designation, 'Platform designation updated.');

        return ApiResponse::success(['designation' => $this->load($designation)], 'Platform designation updated successfully.');
    }

    public function destroy(Request $request, string $uuid): mixed
    {
        $designation = $this->find($uuid);
        if (! $designation) return ApiResponse::error('Platform designation not found.', 404, null, 'PLATFORM_DESIGNATION_NOT_FOUND');

        $designation->update(['status' => 'inactive']);
        ActivityLogger::record($request, 'platform_designation.archived', $designation, 'Platform designation archived.');

        return ApiResponse::success(null, 'Platform designation archived successfully.');
    }

    private function find(string $uuid): ?PlatformDesignation
    {
        return PlatformDesignation::where('uuid', $uuid)->first();
    }

    private function load(PlatformDesignation $designation): PlatformDesignation
    {
        return $designation->fresh()->load(['users'])->loadCount('users');
    }
}
