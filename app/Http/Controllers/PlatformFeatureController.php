<?php

namespace App\Http\Controllers;

use App\Http\Requests\BulkDeletePlatformFeaturesRequest;
use App\Http\Requests\ExportPlatformFeaturesRequest;
use App\Http\Requests\ImportPlatformFeaturesRequest;
use App\Http\Requests\ListPlatformFeaturesRequest;
use App\Http\Requests\StorePlatformFeatureRequest;
use App\Http\Requests\UpdatePlatformFeatureRequest;
use App\Models\Feature;
use App\Models\Module;
use App\Models\Plan;
use App\Support\ApiResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class PlatformFeatureController extends Controller
{
    public function index(ListPlatformFeaturesRequest $request): mixed
    {
        $input = $request->validated();
        $query = $this->filteredQuery($input)->with(['moduleRelation', 'plans', 'planFeatures.plan'])->withCount('plans');
        $paginator = $query->paginate($request->integer('per_page', 25))->withQueryString();

        return ApiResponse::success($paginator->items(), 'Platform features fetched.', 200, [
            'current_page' => $paginator->currentPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'last_page' => $paginator->lastPage(),
        ]);
    }

    public function store(StorePlatformFeatureRequest $request): mixed
    {
        $input = $request->validated();
        $feature = DB::transaction(function () use ($input): Feature {
            $feature = Feature::create(collect($input)->only(['module', 'name', 'data_type', 'unit', 'description', 'status'])->all() + ['uuid' => (string) Str::uuid()]);
            $this->syncPlans($feature, $input);
            return $feature;
        });

        return ApiResponse::success(['feature' => $this->present($feature)], 'Platform feature created successfully.', 201);
    }

    public function moduleOptions(): mixed {
        $modules = Module::query()->where('status', 'active')->withCount('features')->orderBy('name')->get(['id', 'uuid', 'name', 'code', 'status']);
        return ApiResponse::success($modules, 'Feature module options fetched.');
    }

    public function show(string $featureUuid): mixed
    {
        $feature = $this->findFeature($featureUuid);
        if (! $feature) return ApiResponse::error('Platform feature not found.', 404, null, 'FEATURE_NOT_FOUND');
        return ApiResponse::success(['feature' => $this->present($feature)], 'Platform feature fetched.');
    }

    public function update(UpdatePlatformFeatureRequest $request, string $featureUuid): mixed
    {
        $feature = $this->findFeature($featureUuid);
        if (! $feature) return ApiResponse::error('Platform feature not found.', 404, null, 'FEATURE_NOT_FOUND');
        $input = $request->validated();

        DB::transaction(function () use ($feature, $input): void {
            $feature->update(collect($input)->only(['module', 'name', 'data_type', 'unit', 'description', 'status'])->all());
            if (array_key_exists('plan_uuids', $input) || array_key_exists('plan_ids', $input) || array_key_exists('plan_features', $input)) $this->syncPlans($feature, $input);
        });

        return ApiResponse::success(['feature' => $this->present($feature->fresh())], 'Platform feature updated successfully.');
    }

    public function destroy(string $featureUuid): mixed
    {
        $feature = $this->findFeature($featureUuid);
        if (! $feature) return ApiResponse::error('Platform feature not found.', 404, null, 'FEATURE_NOT_FOUND');
        if ($feature->planFeatures()->exists()) return ApiResponse::error('Feature is assigned to one or more plans.', 409, null, 'FEATURE_IN_USE');
        $feature->delete();
        return ApiResponse::success(null, 'Platform feature deleted successfully.');
    }

    public function export(ExportPlatformFeaturesRequest $request): mixed
    {
        $input = $request->validated();
        $id = DB::table('report_export_jobs')->insertGetId([
            'uuid' => (string) Str::uuid(), 'report_code' => 'platform_features', 'format' => 'csv',
            'filters' => json_encode($input), 'status' => 'queued', 'created_by' => $request->user()->id,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        return ApiResponse::success(['export' => ['job_id' => $id, 'status' => 'queued', 'format' => 'csv']], 'Platform feature export queued.', 202);
    }

    public function import(ImportPlatformFeaturesRequest $request): mixed
    {
        $input = $request->validated();
        $jobId = (string) Str::uuid();
        return ApiResponse::success(['import' => ['job_id' => $jobId, 'status' => 'queued', 'format' => 'csv', 'source' => isset($input['file_id']) ? ['file_id' => $input['file_id']] : null]], 'Platform feature import queued.', 202);
    }

    public function bulkDestroy(BulkDeletePlatformFeaturesRequest $request): mixed
    {
        $deleted = 0;
        $skipped = 0;
        $skippedFeatures = [];

        foreach ($request->validated('feature_uuids') as $uuid) {
            $feature = $this->findFeature($uuid);
            if (! $feature || $feature->planFeatures()->exists()) {
                $skipped++;
                $skippedFeatures[] = ['uuid' => $uuid, 'reason' => 'in_use_or_not_found'];
                continue;
            }
            $feature->delete();
            $deleted++;
        }

        return ApiResponse::success(['deleted' => $deleted, 'skipped' => $skipped, 'skipped_features' => $skippedFeatures], 'Platform feature bulk deletion processed.');
    }

    private function findFeature(string $uuid): ?Feature
    {
        return Feature::where('uuid', $uuid)->first();
    }

    private function present(Feature $feature): Feature
    {
        return $feature->load(['moduleRelation', 'plans', 'planFeatures.plan'])->loadCount('plans')->setAttribute('module_name', $feature->moduleRelation?->name);
    }

    private function syncPlans(Feature $feature, array $input): void
    {
        if (array_key_exists('plan_features', $input)) {
            $sync = [];
            foreach ($input['plan_features'] as $row) {
                $planId = $row['plan_id'] ?? Plan::where('uuid', $row['plan_uuid'])->value('id');
                if ($planId) $sync[$planId] = ['value' => $row['value'] ?? null, 'metadata' => $row['metadata'] ?? null];
            }
            $feature->plans()->sync($sync);
            return;
        }

        if (array_key_exists('plan_uuids', $input) || array_key_exists('plan_ids', $input)) {
            $ids = $input['plan_ids'] ?? Plan::whereIn('uuid', $input['plan_uuids'] ?? [])->pluck('id')->all();
            $feature->plans()->sync($ids);
        }
    }

    private function filteredQuery(array $input)
    {
        $query = Feature::query();
        if (! empty($input['search'])) $query->where(fn ($q) => $q->where('name', 'like', '%'.$input['search'].'%')->orWhere('code', 'like', '%'.$input['search'].'%'));
        foreach ($input['filter'] ?? [] as $column => $value) if (in_array($column, ['module', 'status'], true) && $value !== null && $value !== '') $query->where($column, $value);
        return $query->orderBy($input['sort'] ?? 'module', $input['direction'] ?? 'asc')->orderBy('name');
    }
}
