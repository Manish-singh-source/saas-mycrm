<?php

namespace App\Http\Controllers;

use App\Http\Requests\RotatePlatformApiTokenRequest;
use App\Http\Requests\StorePlatformApiTokenRequest;
use App\Models\PlatformApiToken;
use App\Support\ActivityLogger;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class PlatformApiTokenController extends Controller
{
    public function index(Request $request): mixed
    {
        $query = PlatformApiToken::with('createdBy')->latest('created_at');
        if ($request->filled('status')) {
            if ((string) $request->string('status') === 'revoked') $query->onlyTrashed();
            elseif ((string) $request->string('status') === 'active') $query->whereNull('deleted_at');
        }
        if ($request->filled('search')) { $search = (string) $request->string('search'); $query->where('name', 'like', "%{$search}%"); }
        $page = $query->paginate(min(max($request->integer('per_page', 25), 1), 100))->withQueryString();
        return ApiResponse::success(collect($page->items())->map(fn ($token) => $this->safe($token))->values(), 'Platform API tokens fetched.', 200, ['current_page' => $page->currentPage(), 'per_page' => $page->perPage(), 'total' => $page->total(), 'last_page' => $page->lastPage()]);
    }

    public function store(StorePlatformApiTokenRequest $request): mixed
    {
        $data = $request->validated(); $raw = 'plat_'.Str::random(48);
        $token = PlatformApiToken::create(['uuid' => (string) Str::uuid(), 'name' => $data['name'], 'token_hash' => $this->hashToken($raw), 'encrypted_token_preview' => Crypt::encryptString(substr($raw, -8)), 'abilities' => array_values(array_unique($data['abilities'])), 'expires_at' => $data['expires_at'] ?? null, 'created_by' => $request->user()->id]);
        ActivityLogger::record($request, 'platform_api_token.created', $token, 'Platform API token created.', ['uuid' => $token->uuid, 'abilities' => $token->abilities, 'expires_at' => $token->expires_at]);
        return ApiResponse::success(['token' => $this->safe($token), 'raw_token' => $raw], 'Platform API token created. Copy the raw token now; it cannot be recovered later.', 201);
    }

    public function show(string $uuid): mixed
    {
        $token = PlatformApiToken::with('createdBy')->where('uuid', $uuid)->first();
        if (! $token) return ApiResponse::error('Platform API token not found.', 404, null, 'PLATFORM_API_TOKEN_NOT_FOUND');
        return ApiResponse::success(['token' => $this->safe($token)], 'Platform API token fetched.');
    }

    public function rotate(RotatePlatformApiTokenRequest $request, string $uuid): mixed
    {
        $raw = 'plat_'.Str::random(48);
        $token = DB::transaction(function () use ($uuid, $request, $raw): ?PlatformApiToken {
            $token = PlatformApiToken::where('uuid', $uuid)->lockForUpdate()->first();
            if (! $token) return null;
            $token->forceFill(['token_hash' => $this->hashToken($raw), 'encrypted_token_preview' => Crypt::encryptString(substr($raw, -8)), 'expires_at' => $request->validated('expires_at', $token->expires_at)])->save();
            return $token;
        });
        if (! $token) return ApiResponse::error('Platform API token not found.', 404, null, 'PLATFORM_API_TOKEN_NOT_FOUND');
        ActivityLogger::record($request, 'platform_api_token.rotated', $token, 'Platform API token rotated; previous token invalidated immediately.', ['uuid' => $token->uuid, 'expires_at' => $token->expires_at]);
        return ApiResponse::success(['token' => $this->safe($token->fresh()->load('createdBy')), 'raw_token' => $raw], 'Platform API token rotated. The previous token is invalid immediately.', 200);
    }

    public function revoke(Request $request, string $uuid): mixed
    {
        $token = PlatformApiToken::withTrashed()->where('uuid', $uuid)->first();
        if (! $token) return ApiResponse::error('Platform API token not found.', 404, null, 'PLATFORM_API_TOKEN_NOT_FOUND');
        if ($token->trashed()) return ApiResponse::success(['token' => $this->safe($token)], 'Platform API token was already revoked.');
        $token->delete(); ActivityLogger::record($request, 'platform_api_token.revoked', $token, 'Platform API token revoked.');
        return ApiResponse::success(null, 'Platform API token revoked successfully.');
    }

    private function hashToken(string $token): string { return hash_hmac('sha256', $token, (string) config('app.key')); }
    private function safe(PlatformApiToken $token): array { return ['uuid' => $token->uuid, 'name' => $token->name, 'abilities' => $token->abilities, 'last_used_at' => $token->last_used_at, 'expires_at' => $token->expires_at, 'created_at' => $token->created_at, 'deleted_at' => $token->deleted_at, 'created_by' => $token->createdBy]; }
}
