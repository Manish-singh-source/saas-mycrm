<?php

namespace App\Http\Controllers;

use App\Http\Requests\ListKnowledgeBaseCategoriesRequest;
use App\Http\Requests\StoreKnowledgeBaseCategoryRequest;
use App\Http\Requests\UpdateKnowledgeBaseCategoryRequest;
use App\Models\KnowledgeBaseCategory;
use App\Support\ActivityLogger;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

final class PlatformKnowledgeBaseCategoryController extends Controller
{
    public function index(ListKnowledgeBaseCategoriesRequest $request): mixed
    {
        $query = KnowledgeBaseCategory::query()->with(['parent', 'children'])->withCount('articles');
        if (($search = $request->validated('search')) !== null && $search !== '') {
            $query->where(fn ($q) => $q->where('name', 'like', '%'.$search.'%')->orWhere('slug', 'like', '%'.$search.'%'));
        }
        $paginator = $query->latest('created_at')->paginate($request->integer('per_page', 25))->withQueryString();
        return ApiResponse::success($paginator->items(), 'Knowledge-base categories fetched.', 200, [
            'current_page' => $paginator->currentPage(), 'per_page' => $paginator->perPage(),
            'total' => $paginator->total(), 'last_page' => $paginator->lastPage(),
        ]);
    }

    public function store(StoreKnowledgeBaseCategoryRequest $request): mixed
    {
        $input = $request->validated();
        $parentId = null;
        if (! empty($input['parent_uuid'])) {
            $parentId = KnowledgeBaseCategory::where('uuid', $input['parent_uuid'])->value('id');
            if (! $parentId) return ApiResponse::error('Knowledge-base parent category not found.', 404, null, 'CATEGORY_NOT_FOUND');
        }
        $slug = trim((string) ($input['slug'] ?? '')); 
        $category = KnowledgeBaseCategory::create([
            'uuid' => (string) Str::uuid(), 'parent_id' => $parentId, 'name' => $input['name'],
            'slug' => $slug !== '' ? $slug : $this->uniqueSlug($input['name']), 'audience' => $input['audience'] ?? 'all',
            'status' => $input['status'] ?? 'active',
        ]);
        ActivityLogger::record($request, 'created', $category, 'Knowledge-base category created.');
        return ApiResponse::success(['category' => $this->present($category)], 'Knowledge-base category created successfully.', 201);
    }

    public function update(UpdateKnowledgeBaseCategoryRequest $request, string $categoryUuid): mixed
    {
        $category = $this->findCategory($categoryUuid);
        if (! $category) return ApiResponse::error('Knowledge-base category not found.', 404, null, 'CATEGORY_NOT_FOUND');
        $input = $request->validated();
        if (array_key_exists('name', $input) && ! array_key_exists('slug', $input)) $input['slug'] = $this->uniqueSlug($input['name'], $category->id);
        $category->update(collect($input)->only(['name', 'slug', 'audience', 'status'])->all());
        ActivityLogger::record($request, 'updated', $category, 'Knowledge-base category updated.', $input);
        return ApiResponse::success(['category' => $this->present($category->fresh())], 'Knowledge-base category updated successfully.');
    }

    private function findCategory(string $uuid): ?KnowledgeBaseCategory { return KnowledgeBaseCategory::where('uuid', $uuid)->first(); }

    private function present(KnowledgeBaseCategory $category): KnowledgeBaseCategory
    {
        return $category->load(['parent', 'children'])->loadCount('articles');
    }

    private function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'category';
        $slug = $base; $suffix = 1;
        while (KnowledgeBaseCategory::where('slug', $slug)->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))->exists()) $slug = $base.'-'.($suffix++);
        return $slug;
    }
}