<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePlatformAnnouncementRequest;
use App\Http\Requests\UpdatePlatformAnnouncementRequest;
use App\Models\PlatformAnnouncement;
use App\Support\ActivityLogger;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

final class PlatformAnnouncementController extends Controller
{

    public function publicIndex(): mixed
    {
        $announcements = PlatformAnnouncement::query()
            ->where('status', 'published')
            ->where('audience', 'all')
            ->latest('published_at')
            ->get(['uuid', 'title', 'body', 'audience', 'status', 'published_at']);

        return ApiResponse::success($announcements, 'Published announcements fetched.');
    }

    public function index(Request $request): mixed
    {
        $query = PlatformAnnouncement::with('createdBy')->latest('created_at');
        if ($request->filled('audience')) $query->where('audience', (string) $request->string('audience'));
        if ($request->filled('status')) $query->where('status', (string) $request->string('status'));
        if ($request->filled('search')) { $search = (string) $request->string('search'); $query->where(fn ($q) => $q->where('title', 'like', "%{$search}%")->orWhere('body', 'like', "%{$search}%")); }
        $page = $query->paginate(min(max($request->integer('per_page', 25), 1), 100))->withQueryString();
        return ApiResponse::success($page->items(), 'Platform announcements fetched.', 200, ['current_page' => $page->currentPage(), 'per_page' => $page->perPage(), 'total' => $page->total(), 'last_page' => $page->lastPage()]);
    }

    public function store(StorePlatformAnnouncementRequest $request): mixed
    {
        $data = $request->validated();
        $announcement = PlatformAnnouncement::create($data + ['uuid' => (string) Str::uuid(), 'created_by' => $request->user()->id, 'audience' => $data['audience'] ?? 'all', 'status' => $data['status'] ?? 'draft']);
        ActivityLogger::record($request, 'platform_announcement.created', $announcement, 'Platform announcement created.', $this->auditData($announcement));
        return ApiResponse::success(['announcement' => $this->load($announcement)], 'Platform announcement created successfully.', 201);
    }

    public function show(string $uuid): mixed
    {
        $announcement = PlatformAnnouncement::with('createdBy')->where('uuid', $uuid)->first();
        if (! $announcement) return ApiResponse::error('Platform announcement not found.', 404, null, 'ANNOUNCEMENT_NOT_FOUND');
        return ApiResponse::success(['announcement' => $announcement], 'Platform announcement fetched.');
    }

    public function update(UpdatePlatformAnnouncementRequest $request, string $uuid): mixed
    {
        $announcement = PlatformAnnouncement::where('uuid', $uuid)->first();
        if (! $announcement) return ApiResponse::error('Platform announcement not found.', 404, null, 'ANNOUNCEMENT_NOT_FOUND');
        $data = $request->validated();
        if ($announcement->status === 'published' && array_intersect(array_keys($data), ['title', 'body', 'audience'])) return ApiResponse::error('Published announcements are immutable.', 409, null, 'ANNOUNCEMENT_IMMUTABLE');
        $old = $announcement->only(array_keys($data)); $announcement->update($data);
        ActivityLogger::record($request, 'platform_announcement.updated', $announcement, 'Platform announcement updated.', ['old' => $old, 'new' => $data]);
        return ApiResponse::success(['announcement' => $this->load($announcement->fresh())], 'Platform announcement updated successfully.');
    }

    public function publish(Request $request, string $uuid): mixed
    {
        $announcement = PlatformAnnouncement::where('uuid', $uuid)->first();
        if (! $announcement) return ApiResponse::error('Platform announcement not found.', 404, null, 'ANNOUNCEMENT_NOT_FOUND');
        if ($announcement->status === 'published') return ApiResponse::success(['announcement' => $this->load($announcement)], 'Platform announcement is already published.');
        if (! in_array($announcement->status, ['draft', 'review', 'scheduled'], true)) return ApiResponse::error('Only draft, review, or scheduled announcements can be published.', 422, null, 'INVALID_ANNOUNCEMENT_TRANSITION');
        $announcement->update(['status' => 'published', 'published_at' => now()]);
        ActivityLogger::record($request, 'platform_announcement.published', $announcement, 'Platform announcement published.', ['audience' => $announcement->audience]);
        return ApiResponse::success(['announcement' => $this->load($announcement->fresh())], 'Platform announcement published successfully.');
    }

    public function archive(Request $request, string $uuid): mixed
    {
        $announcement = PlatformAnnouncement::where('uuid', $uuid)->first();
        if (! $announcement) return ApiResponse::error('Platform announcement not found.', 404, null, 'ANNOUNCEMENT_NOT_FOUND');
        if ($announcement->status !== 'archived') { $announcement->update(['status' => 'archived']); $announcement->delete(); ActivityLogger::record($request, 'platform_announcement.archived', $announcement, 'Platform announcement archived.'); }
        return ApiResponse::success(null, 'Platform announcement archived successfully.');
    }

    public function destroy(Request $request, string $uuid): mixed
    {
        $announcement = PlatformAnnouncement::where('uuid', $uuid)->first();
        if (! $announcement) return ApiResponse::error('Platform announcement not found.', 404, null, 'ANNOUNCEMENT_NOT_FOUND');

        $announcement->update(['status' => 'deleted']);
        $announcement->delete();
        ActivityLogger::record($request, 'platform_announcement.deleted', $announcement, 'Platform announcement deleted.', $this->auditData($announcement));

        return ApiResponse::success(null, 'Platform announcement deleted successfully.');
    }
    private function load(PlatformAnnouncement $announcement): PlatformAnnouncement { return $announcement->load('createdBy'); }
    private function auditData(PlatformAnnouncement $announcement): array { return $announcement->only(['uuid', 'title', 'audience', 'status', 'published_at', 'created_by']); }
}
