<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLegalDocumentRequest;
use App\Http\Requests\UpdateLegalDocumentRequest;
use App\Models\LegalDocument;
use App\Models\TenantLegalAcceptance;
use App\Support\ActivityLogger;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

final class PlatformLegalDocumentController extends Controller
{
    public function index(Request $request): mixed
    {
        $query = LegalDocument::with('createdBy')->latest('created_at');
        if ($request->filled('document_type')) $query->where('document_type', (string) $request->string('document_type'));
        if ($request->filled('status')) $query->where('status', (string) $request->string('status'));
        if ($request->filled('search')) { $search = (string) $request->string('search'); $query->where(fn ($q) => $q->where('title', 'like', "%{$search}%")->orWhere('version', 'like', "%{$search}%")); }
        $page = $query->paginate(min(max($request->integer('per_page', 25), 1), 100))->withQueryString();
        return ApiResponse::success($page->items(), 'Legal documents fetched.', 200, $this->meta($page));
    }

    public function store(StoreLegalDocumentRequest $request): mixed
    {
        $data = $request->validated();
        $document = LegalDocument::create($data + ['uuid' => (string) Str::uuid(), 'created_by' => $request->user()->id, 'status' => $data['status'] ?? 'draft']);
        ActivityLogger::record($request, 'legal_document.created', $document, 'Legal document created.', ['document_type' => $document->document_type, 'version' => $document->version, 'status' => $document->status]);
        return ApiResponse::success(['document' => $this->load($document)], 'Legal document created successfully.', 201);
    }

    public function show(string $uuid): mixed
    {
        $document = LegalDocument::with(['createdBy', 'acceptances' => fn ($q) => $q->with(['tenant', 'user'])->latest('accepted_at')->limit(25)])->where('uuid', $uuid)->first();
        if (! $document) return ApiResponse::error('Legal document not found.', 404, null, 'LEGAL_DOCUMENT_NOT_FOUND');
        return ApiResponse::success(['document' => $document], 'Legal document fetched.');
    }

    public function update(UpdateLegalDocumentRequest $request, string $uuid): mixed
    {
        $document = LegalDocument::where('uuid', $uuid)->first();
        if (! $document) return ApiResponse::error('Legal document not found.', 404, null, 'LEGAL_DOCUMENT_NOT_FOUND');
        $data = $request->validated();
        if ($document->status === 'published' && array_intersect(array_keys($data), ['document_type', 'title', 'version', 'content'])) return ApiResponse::error('Published legal documents are immutable. Create a new version instead.', 409, null, 'LEGAL_DOCUMENT_IMMUTABLE');
        $old = $document->only(array_keys($data)); $document->update($data);
        ActivityLogger::record($request, 'legal_document.updated', $document, 'Legal document updated.', ['old' => $old, 'new' => $data]);
        return ApiResponse::success(['document' => $this->load($document->fresh())], 'Legal document updated successfully.');
    }

    public function publish(Request $request, string $uuid): mixed
    {
        $document = LegalDocument::where('uuid', $uuid)->first();
        if (! $document) return ApiResponse::error('Legal document not found.', 404, null, 'LEGAL_DOCUMENT_NOT_FOUND');
        if ($document->status === 'published') return ApiResponse::success(['document' => $this->load($document)], 'Legal document is already published.');
        if (! in_array($document->status, ['draft', 'review'], true)) return ApiResponse::error('Only draft or review documents can be published.', 422, null, 'INVALID_LEGAL_DOCUMENT_TRANSITION');
        $document->update(['status' => 'published', 'published_at' => now()]);
        ActivityLogger::record($request, 'legal_document.published', $document, 'Legal document published.', ['version' => $document->version]);
        return ApiResponse::success(['document' => $this->load($document->fresh())], 'Legal document published successfully.');
    }

    public function acceptances(Request $request, string $uuid): mixed
    {
        $document = LegalDocument::where('uuid', $uuid)->first();
        if (! $document) return ApiResponse::error('Legal document not found.', 404, null, 'LEGAL_DOCUMENT_NOT_FOUND');
        $query = TenantLegalAcceptance::with(['tenant', 'user', 'document'])->where('legal_document_id', $document->id)->latest('accepted_at');
        if ($request->filled('tenant_id')) $query->where('tenant_id', $request->integer('tenant_id'));
        if ($request->filled('user_id')) $query->where('user_id', $request->integer('user_id'));
        $page = $query->paginate(min(max($request->integer('per_page', 25), 1), 100))->withQueryString();
        return ApiResponse::success($page->items(), 'Legal document acceptances fetched.', 200, $this->meta($page));
    }

    private function load(LegalDocument $document): LegalDocument { return $document->load(['createdBy', 'acceptances.tenant', 'acceptances.user']); }
    private function meta($page): array { return ['current_page' => $page->currentPage(), 'per_page' => $page->perPage(), 'total' => $page->total(), 'last_page' => $page->lastPage()]; }
}
