<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Services\Shared\AuthAuditService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class TenantApiTokenController extends Controller
{
    public function __construct(private readonly AuthAuditService $audit) {}

    public function index(Request $request): JsonResponse
    {
        $tokens = DB::table('tenant_api_tokens')
            ->where('tenant_id', $request->attributes->get('tenant_id'))
            ->whereNull('deleted_at')
            ->latest()
            ->get(['uuid', 'name', 'abilities', 'last_used_at', 'expires_at', 'created_by', 'created_at']);

        return ApiResponse::success(['tokens' => $tokens]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:150'], 'abilities' => ['nullable', 'array'], 'expires_at' => ['nullable', 'date']]);
        [$id, $uuid, $token] = $this->createToken($request, $data);
        $this->audit->log($request, 'tenant_api_token_created', tenantUser: $request->user(), metadata: ['token_id' => $id, 'name' => $data['name']]);

        return ApiResponse::success(['uuid' => $uuid, 'name' => $data['name'], 'token' => $token, 'expires_at' => $data['expires_at'] ?? null], 'API token created.', Response::HTTP_CREATED);
    }

    public function rotate(Request $request, string $tokenUuid): JsonResponse
    {
        $existing = DB::table('tenant_api_tokens')
            ->where('tenant_id', $request->attributes->get('tenant_id'))
            ->where('uuid', $tokenUuid)
            ->whereNull('deleted_at')
            ->first();

        if (! $existing) {
            return ApiResponse::businessError('API token not found in current tenant.', 'API_TOKEN_NOT_FOUND', Response::HTTP_NOT_FOUND);
        }

        $raw = 'tenant_'.$request->attributes->get('tenant_id').'_'.Str::random(64);
        DB::table('tenant_api_tokens')->where('id', $existing->id)->update(['token_hash' => hash('sha256', $raw), 'encrypted_token_preview' => Crypt::encryptString(substr($raw, -8)), 'updated_at' => now()]);
        $this->audit->log($request, 'tenant_api_token_rotated', tenantUser: $request->user(), metadata: ['token_id' => $existing->id]);

        return ApiResponse::success(['uuid' => $tokenUuid, 'name' => $existing->name, 'token' => $raw, 'expires_at' => $existing->expires_at], 'API token rotated.');
    }

    public function revoke(Request $request, string $tokenUuid): JsonResponse
    {
        DB::table('tenant_api_tokens')
            ->where('tenant_id', $request->attributes->get('tenant_id'))
            ->where('uuid', $tokenUuid)
            ->whereNull('deleted_at')
            ->update(['deleted_at' => now(), 'updated_at' => now()]);
        $this->audit->log($request, 'tenant_api_token_revoked', tenantUser: $request->user(), metadata: ['token_uuid' => $tokenUuid]);

        return ApiResponse::success(null, 'API token revoked.');
    }

    private function createToken(Request $request, array $data): array
    {
        $raw = 'tenant_'.$request->attributes->get('tenant_id').'_'.Str::random(64);
        $uuid = (string) Str::uuid();
        $id = DB::table('tenant_api_tokens')->insertGetId([
            'uuid' => $uuid,
            'tenant_id' => $request->attributes->get('tenant_id'),
            'name' => $data['name'],
            'token_hash' => hash('sha256', $raw),
            'encrypted_token_preview' => Crypt::encryptString(substr($raw, -8)),
            'abilities' => isset($data['abilities']) ? json_encode($data['abilities']) : null,
            'expires_at' => $data['expires_at'] ?? null,
            'created_by' => $request->user()?->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$id, $uuid, $raw];
    }
}