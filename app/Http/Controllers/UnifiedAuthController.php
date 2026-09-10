<?php

namespace App\Http\Controllers;

use App\Models\PlatformUser;
use App\Models\User;
use App\Support\ActivityLogger;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

final class UnifiedAuthController extends Controller
{
    public function discover(Request $request): mixed
    {
        $input = $request->validate(['email' => ['required', 'email'], 'device_name' => ['nullable', 'string', 'max:120']]);
        $accounts = [];
        foreach (PlatformUser::where('email', $input['email'])->where('status', 'active')->get() as $user) {
            $accounts[] = $this->account($user, 'platform', null);
        }
        foreach (User::where('email', $input['email'])->where('status', 'active')->get() as $user) {
            $accounts[] = $this->account($user, $user->account_type, $user->tenant_id);
        }
        $token = Str::random(64);
        $lookup = collect($accounts)->mapWithKeys(fn (array $account): array => [$account['account_ref'] => [$account['surface'], $account['id']]])->all();
        Cache::put('auth.discovery.' . hash('sha256', $token), ['email' => $input['email'], 'accounts' => $lookup], now()->addMinutes(5));
        $accounts = array_map(fn (array $account): array => collect($account)->except('id')->all(), $accounts);
        return ApiResponse::success(['email' => $input['email'], 'accounts' => $accounts, 'discovery_token' => $token, 'expires_in_seconds' => 300], 'Accounts discovered.');
    }

    public function login(Request $request): mixed
    {
        $input = $request->validate(['email' => ['required', 'email'], 'discovery_token' => ['required', 'string'], 'account_ref' => ['required', 'string'], 'password' => ['required', 'string'], 'remember' => ['sometimes', 'boolean'], 'device_name' => ['nullable', 'string', 'max:120']]);
        $key = 'auth.discovery.' . hash('sha256', $input['discovery_token']);
        $discovery = Cache::get($key);
        if (! $discovery || $discovery['email'] !== $input['email']) return ApiResponse::error('Discovery token expired.', 401, null, 'DISCOVERY_TOKEN_EXPIRED');
        $selected = $discovery['accounts'][$input['account_ref']] ?? null;
        if (! $selected) return ApiResponse::error('Invalid account reference.', 422, null, 'INVALID_ACCOUNT_REF');
        [$surface, $id] = $selected;
        $model = $surface === 'platform' ? PlatformUser::find($id) : User::find($id);
        if (! $model || $model->email !== $input['email'] || $model->status !== 'active' || ! Hash::check($input['password'], $model->password)) return ApiResponse::error('Invalid credentials.', 401, null, 'INVALID_CREDENTIALS');
        if ($model->two_factor_required && ! $model->two_factor_enabled) {
            return ApiResponse::success([
                'requires_2fa_setup' => true,
                'account_type' => $surface === 'platform' ? 'platform' : $model->account_type,
                'surface' => $surface,
            ], 'Two-factor authentication setup is required.');
        }
        if ($model->two_factor_enabled) {
            $challenge = Str::random(64);
            Cache::put('auth.challenge.' . hash('sha256', $challenge), ['model_type' => $model::class, 'model_id' => $model->getKey(), 'surface' => $surface, 'account_type' => $surface === 'platform' ? 'platform' : $model->account_type], now()->addMinutes(5));
            return ApiResponse::success(['requires_2fa' => true, 'challenge_token' => $challenge, 'methods' => ['totp'], 'account_type' => $surface === 'platform' ? 'platform' : $model->account_type, 'surface' => $surface], 'Two-factor authentication required.');
        }
        Cache::forget($key);
        return $this->issueToken($request, $model, $input['device_name'] ?? 'api');
    }

    public function verifyTwoFactor(Request $request): mixed
    {
        $input = $request->validate(['challenge_token' => ['required', 'string'], 'code' => ['required', 'digits:6'], 'remember_device' => ['sometimes', 'boolean'], 'device_name' => ['nullable', 'string', 'max:120']]);
        $key = 'auth.challenge.' . hash('sha256', $input['challenge_token']);
        $challenge = Cache::pull($key);
        if (! $challenge) return ApiResponse::error('Two-factor challenge expired.', 401, null, 'TWO_FACTOR_CHALLENGE_EXPIRED');
        $model = $challenge['model_type']::find($challenge['model_id']);
        if (! $model || ! $this->validTotp($model->two_factor_secret, $input['code'])) return ApiResponse::error('Invalid two-factor code.', 422, null, 'INVALID_2FA_CODE');
        return $this->issueToken($request, $model, $input['device_name'] ?? 'api');
    }

    public function me(Request $request): mixed
    {
        $user = $request->user();
        return ApiResponse::success(['user' => $user instanceof PlatformUser ? $user->load(['department', 'designation', 'manager', 'subordinates', 'roles', 'teams', 'permissions', 'profilePhotoFile']) : $user, 'account_type' => $user instanceof PlatformUser ? 'platform' : $user->account_type, 'surface' => $user instanceof PlatformUser ? 'platform' : 'tenant', 'tenant_id' => $user->tenant_id ?? null], 'Current session fetched.');
    }

    public function logout(Request $request): mixed
    {
        if ($request->boolean('all_devices')) $request->user()->tokens()->delete(); else $request->user()->currentAccessToken()?->delete();
        ActivityLogger::record($request, 'logout', $request->user(), 'Account logout.');
        return ApiResponse::success(['logged_out' => true], 'Logged out successfully.');
    }

    public function refresh(Request $request): mixed
    {
        $user = $request->user();
        $abilities = $request->user()->currentAccessToken()?->abilities ?? [];
        $request->user()->currentAccessToken()?->delete();
        $token = $user->createToken('api', $abilities, now()->addHours(12));
        return ApiResponse::success(['access_token' => $token->plainTextToken, 'token_type' => 'Bearer'], 'Token refreshed.');
    }

    private function issueToken(Request $request, object $model, string $deviceName): mixed
    {
        $isPlatform = $model instanceof PlatformUser;
        $abilities = $isPlatform ? $model->roles()->where('platform_roles.status', 'active')->with('permissions')->get()->flatMap(fn ($role) => $role->permissions->where('status', 'active')->pluck('name'))->merge($model->permissions()->where('platform_permissions.status', 'active')->pluck('name'))->unique()->values()->all() : $this->tenantPermissions($model);
        $token = $model->createToken($deviceName, $abilities, now()->addHours(12));
        $model->forceFill(['last_login_at' => now(), 'last_login_ip' => $request->ip()])->save();
        ActivityLogger::record($request, 'login', $model, 'Account login.');
        $tenant = $model instanceof PlatformUser ? null : $model->load('tenant')->tenant;
        return ApiResponse::success(['access_token' => $token->plainTextToken, 'token_type' => 'Bearer', 'user' => $model instanceof PlatformUser ? $model->fresh()->load(['department', 'designation', 'manager', 'subordinates', 'roles', 'teams', 'permissions', 'profilePhotoFile']) : $model, 'surface' => $model instanceof PlatformUser ? 'platform' : 'tenant', 'permissions' => $abilities, 'tenant' => $tenant?->only(['uuid', 'organization_name', 'display_name', 'slug', 'default_currency', 'default_timezone', 'status']), 'account_type' => $model instanceof PlatformUser ? 'platform' : $model->account_type, 'tenant_id' => $model->tenant_id ?? null], 'Login successful.');
    }

    private function account(object $user, string $type, mixed $tenantId): array
    {
        $ref = Str::random(40);
        $tenant = $user instanceof PlatformUser ? null : $user->tenant;
        return ['account_ref' => $ref, 'id' => $user->getKey(), 'account_type' => $type, 'auth_guard' => $user instanceof PlatformUser ? 'platform' : 'tenant', 'surface' => $user instanceof PlatformUser ? 'platform' : 'tenant', 'display_label' => $user->display_name, 'tenant_id' => $tenant?->uuid ?? $tenantId, 'tenant' => $tenant?->only(['uuid', 'slug', 'status']), 'roles' => [], 'status' => $user->status, 'last_login_at' => $user->last_login_at];
    }

    private function validTotp(?string $secret, string $code): bool
    {
        if (! $secret) return false;
        $secret = strtoupper(preg_replace('/[^A-Z2-7]/', '', $secret));
        $key = $this->base32Decode($secret);
        $counter = intdiv(time(), 30);
        for ($offset = -1; $offset <= 1; $offset++) {
            $bin = pack('N*', 0) . pack('N*', $counter + $offset);
            $hash = hash_hmac('sha1', $bin, $key, true);
            $index = ord($hash[19]) & 0xf;
            $otp = ((ord($hash[$index]) & 0x7f) << 24 | (ord($hash[$index + 1]) & 0xff) << 16 | (ord($hash[$index + 2]) & 0xff) << 8 | (ord($hash[$index + 3]) & 0xff)) % 1000000;
            if (hash_equals(str_pad((string) $otp, 6, '0', STR_PAD_LEFT), $code)) return true;
        }
        return false;
    }

    private function base32Decode(string $value): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567'; $buffer = 0; $bits = 0; $output = '';
        foreach (str_split($value) as $char) { $buffer = ($buffer << 5) | strpos($alphabet, $char); $bits += 5; if ($bits >= 8) { $bits -= 8; $output .= chr(($buffer >> $bits) & 0xff); } }
        return $output;
    }
}
