<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\PlatformUser;
use App\Services\Shared\AuthAuditService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class PlatformApiTokenController extends Controller
{
    public function __construct(private readonly AuthAuditService $audit) {}

    public function index(Request $request): JsonResponse
    {
        return ApiResponse::success(['tokens' => DB::table('platform_api_tokens')->whereNull('deleted_at')->latest()->get(['uuid', 'name', 'abilities', 'last_used_at', 'expires_at', 'created_by', 'created_at'])]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:150'], 'abilities' => ['nullable', 'array'], 'expires_at' => ['nullable', 'date']]);
        [$id, $uuid, $token] = $this->createToken($data, $request->user()?->id);
        $this->audit->log($request, 'platform_api_token_created', platformUser: $request->user(), metadata: ['token_id' => $id, 'name' => $data['name']]);

        return ApiResponse::success(['uuid' => $uuid, 'name' => $data['name'], 'token' => $token, 'expires_at' => $data['expires_at'] ?? null], 'API token created.', Response::HTTP_CREATED);
    }

    public function show(string $tokenUuid): JsonResponse
    {
        $token = DB::table('platform_api_tokens')->where('uuid', $tokenUuid)->whereNull('deleted_at')->first();

        if (! $token) {
            return ApiResponse::businessError('API token not found.', 'API_TOKEN_NOT_FOUND', Response::HTTP_NOT_FOUND);
        }

        return ApiResponse::success(['token' => $token]);
    }

    public function rotate(Request $request, string $tokenUuid): JsonResponse
    {
        $existing = DB::table('platform_api_tokens')->where('uuid', $tokenUuid)->whereNull('deleted_at')->first();
        if (! $existing) {
            return ApiResponse::businessError('API token not found.', 'API_TOKEN_NOT_FOUND', Response::HTTP_NOT_FOUND);
        }

        $raw = 'plat_'.Str::random(64);
        DB::table('platform_api_tokens')->where('id', $existing->id)->update(['token_hash' => hash('sha256', $raw), 'encrypted_token_preview' => Crypt::encryptString(substr($raw, -8)), 'updated_at' => now()]);
        $this->audit->log($request, 'platform_api_token_rotated', platformUser: $request->user(), metadata: ['token_id' => $existing->id]);

        return ApiResponse::success(['uuid' => $tokenUuid, 'name' => $existing->name, 'token' => $raw, 'expires_at' => $existing->expires_at], 'API token rotated.');
    }

    public function revoke(Request $request, string $tokenUuid): JsonResponse
    {
        DB::table('platform_api_tokens')->where('uuid', $tokenUuid)->whereNull('deleted_at')->update(['deleted_at' => now(), 'updated_at' => now()]);
        $this->audit->log($request, 'platform_api_token_revoked', platformUser: $request->user(), metadata: ['token_uuid' => $tokenUuid]);

        return ApiResponse::success(null, 'API token revoked.');
    }

    private function createToken(array $data, ?int $createdBy): array
    {
        $raw = 'plat_'.Str::random(64);
        $uuid = (string) Str::uuid();
        $id = DB::table('platform_api_tokens')->insertGetId([
            'uuid' => $uuid,
            'name' => $data['name'],
            'token_hash' => hash('sha256', $raw),
            'encrypted_token_preview' => Crypt::encryptString(substr($raw, -8)),
            'abilities' => isset($data['abilities']) ? json_encode($data['abilities']) : null,
            'expires_at' => $data['expires_at'] ?? null,
            'created_by' => $createdBy,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$id, $uuid, $raw];
    }
}