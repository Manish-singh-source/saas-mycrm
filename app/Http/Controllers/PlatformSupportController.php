<?php
namespace App\Http\Controllers;

use App\Models\RemoteLoginSession;
use App\Support\ApiResponse;
use App\Support\TenantAudit;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;

final class PlatformSupportController extends Controller
{
    public function sessions(Request $request): mixed
    {
        $page = RemoteLoginSession::with(['tenant:id,uuid,organization_name', 'platformUser:id,display_name,email', 'targetUser:id,display_name,email'])->latest('id')->paginate(min(max((int) $request->input('per_page', 25), 1), 100));
        $data = collect($page->items())->map(fn ($session) => $this->session($session))->all();
        return ApiResponse::success($data, 'Remote login sessions fetched.', 200, ['current_page' => $page->currentPage(), 'per_page' => $page->perPage(), 'total' => $page->total(), 'last_page' => $page->lastPage()]);
    }

    public function show(string $session_uuid): mixed
    {
        return ApiResponse::success(['session' => $this->session($this->find($session_uuid, true))], 'Remote login session fetched.');
    }

    public function end(Request $request, string $session_uuid): mixed
    {
        $session = $this->find($session_uuid, true);
        if ($session->status !== 'ended') $session->update(['status' => 'ended', 'ended_at' => now()]);
        TenantAudit::record($request, $session->tenant, 'tenant_remote_login_ended', ['session_uuid' => $session->uuid], true);
        return ApiResponse::success(['session' => ['uuid' => $session->uuid, 'status' => $session->status, 'ended_at' => $session->ended_at]], 'Remote login session ended.');
    }

    private function find(string $uuid, bool $with = false): RemoteLoginSession
    {
        $query = RemoteLoginSession::query();
        if ($with) $query->with(['tenant', 'platformUser', 'targetUser']);
        return $query->where('uuid', $uuid)->first() ?? throw new HttpResponseException(ApiResponse::error('Remote login session not found.', 404));
    }

    private function session(RemoteLoginSession $session): array
    {
        return ['uuid' => $session->uuid, 'tenant_id' => $session->tenant_id, 'tenant_name' => $session->tenant?->organization_name, 'platform_user_id' => $session->platform_user_id, 'platform_user_name' => $session->platformUser?->display_name, 'target_user_id' => $session->target_user_id, 'target_user_name' => $session->targetUser?->display_name, 'reason' => $session->reason, 'duration_minutes' => $session->duration_minutes, 'expires_at' => $session->expires_at, 'status' => $session->status, 'ip_address' => $session->ip_address, 'user_agent' => $session->user_agent, 'ended_at' => $session->ended_at, 'created_at' => $session->created_at];
    }
}