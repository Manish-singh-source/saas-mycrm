<?php

namespace App\Http\Controllers;

use App\Http\Requests\ListKnowledgeBaseArticlesRequest;
use App\Http\Requests\StoreKnowledgeBaseArticleRequest;
use App\Http\Requests\UpdateKnowledgeBaseArticleRequest;
use App\Models\KnowledgeBaseArticle;
use App\Models\KnowledgeBaseCategory;
use App\Support\ActivityLogger;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

final class PlatformKnowledgeBaseArticleController extends Controller
{
    public function index(ListKnowledgeBaseArticlesRequest $request): mixed
    {
        $query = KnowledgeBaseArticle::query()->with(['category', 'createdBy'])->latest('created_at');
        if (($search = $request->validated('search')) !== null && $search !== '') {
            $query->where(fn ($q) => $q->where('title', 'like', '%'.$search.'%')->orWhere('slug', 'like', '%'.$search.'%'));
        }
        if (($status = $request->validated('status')) !== null && $status !== '') $query->where('status', $status);
        $paginator = $query->paginate($request->integer('per_page', 25))->withQueryString();
        return ApiResponse::success($paginator->items(), 'Knowledge-base articles fetched.', 200, [
            'current_page' => $paginator->currentPage(), 'per_page' => $paginator->perPage(),
            'total' => $paginator->total(), 'last_page' => $paginator->lastPage(),
        ]);
    }

    public function store(StoreKnowledgeBaseArticleRequest $request): mixed
    {
        $input = $request->validated();
        $categoryId = $this->resolveCategory($input['category_uuid'] ?? null);
        if (($input['category_uuid'] ?? null) !== null && ! $categoryId) return ApiResponse::error('Knowledge-base category not found.', 404, null, 'CATEGORY_NOT_FOUND');
        $status = $input['status'] ?? 'draft';
        $slug = trim((string) ($input['slug'] ?? '')); 
        $article = KnowledgeBaseArticle::create([
            'uuid' => (string) Str::uuid(), 'category_id' => $categoryId, 'title' => $input['title'],
            'slug' => $slug !== '' ? $slug : $this->uniqueSlug($input['title']), 'body' => $input['body'],
            'audience' => $input['audience'] ?? 'all', 'status' => $status,
            'created_by' => $request->user()->id, 'published_at' => $status === 'published' ? now() : null,
        ]);
        ActivityLogger::record($request, 'created', $article, 'Knowledge-base article created.');
        return ApiResponse::success(['article' => $this->present($article)], 'Knowledge-base article created successfully.', 201);
    }

    public function show(string $articleUuid): mixed
    {
        $article = $this->findArticle($articleUuid);
        if (! $article) return ApiResponse::error('Knowledge-base article not found.', 404, null, 'ARTICLE_NOT_FOUND');
        return ApiResponse::success(['article' => $this->present($article)], 'Knowledge-base article fetched.');
    }

    public function update(UpdateKnowledgeBaseArticleRequest $request, string $articleUuid): mixed
    {
        $article = $this->findArticle($articleUuid);
        if (! $article) return ApiResponse::error('Knowledge-base article not found.', 404, null, 'ARTICLE_NOT_FOUND');
        $input = $request->validated();
        $data = collect($input)->only(['title', 'slug', 'body', 'audience', 'status'])->all();
        if (array_key_exists('category_uuid', $input)) {
            $categoryId = $this->resolveCategory($input['category_uuid']);
            if ($input['category_uuid'] !== null && ! $categoryId) return ApiResponse::error('Knowledge-base category not found.', 404, null, 'CATEGORY_NOT_FOUND');
            $data['category_id'] = $categoryId;
        }
        if (array_key_exists('status', $data)) $data['published_at'] = $data['status'] === 'published' ? ($article->published_at ?? now()) : null;
        $article->update($data);
        ActivityLogger::record($request, 'updated', $article, 'Knowledge-base article updated.', $data);
        return ApiResponse::success(['article' => $this->present($article->fresh())], 'Knowledge-base article updated successfully.');
    }

    public function publish(Request $request, string $articleUuid): mixed { return $this->setPublication($request, $articleUuid, 'published'); }
    public function unpublish(Request $request, string $articleUuid): mixed { return $this->setPublication($request, $articleUuid, 'draft'); }

    public function archive(Request $request, string $articleUuid): mixed
    {
        $article = $this->findArticle($articleUuid);
        if (! $article) return ApiResponse::error('Knowledge-base article not found.', 404, null, 'ARTICLE_NOT_FOUND');
        $article->update(['status' => 'archived']);
        ActivityLogger::record($request, 'archived', $article, 'Knowledge-base article archived.');
        $article->delete();
        return ApiResponse::success(['article' => $article->load(['category', 'createdBy'])->loadCount('activityLogs')], 'Knowledge-base article archived.');
    }

    private function setPublication(Request $request, string $uuid, string $status): mixed
    {
        $article = $this->findArticle($uuid);
        if (! $article) return ApiResponse::error('Knowledge-base article not found.', 404, null, 'ARTICLE_NOT_FOUND');
        $article->update(['status' => $status, 'published_at' => $status === 'published' ? now() : null]);
        ActivityLogger::record($request, $status, $article, 'Knowledge-base article publication status changed.');
        return ApiResponse::success(['article' => $this->present($article->fresh())], 'Knowledge-base article status updated.');
    }

    private function findArticle(string $uuid): ?KnowledgeBaseArticle { return KnowledgeBaseArticle::where('uuid', $uuid)->first(); }
    private function resolveCategory(?string $uuid): ?int { return $uuid === null ? null : KnowledgeBaseCategory::where('uuid', $uuid)->value('id'); }

    private function present(KnowledgeBaseArticle $article): KnowledgeBaseArticle
    {
        return $article->load(['category.parent', 'createdBy', 'activityLogs' => fn ($q) => $q->latest('created_at')->limit(25)]);
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'article'; $slug = $base; $suffix = 1;
        while (KnowledgeBaseArticle::withTrashed()->where('slug', $slug)->exists()) $slug = $base.'-'.($suffix++);
        return $slug;
    }
}