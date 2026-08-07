<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\PlatformUser;
use App\Services\Shared\AuthAuditService;
use App\Services\Shared\TotpService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class PlatformAuthController extends Controller
{
    public function __construct(private readonly AuthAuditService $audit, private readonly TotpService $totp) {}

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:120'],
            'remember' => ['nullable', 'boolean'],
            'two_factor_code' => ['nullable', 'string'],
        ]);

        $user = PlatformUser::query()->where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password) || $user->status !== 'active') {
            $this->audit->log($request, 'platform_login_failed', 'warning', metadata: ['email' => $data['email']]);

            return ApiResponse::businessError('Invalid credentials.', 'INVALID_CREDENTIALS', Response::HTTP_UNAUTHORIZED);
        }

        if ($user->two_factor_enabled && ! $this->totp->verify((string) $user->two_factor_secret, (string) ($data['two_factor_code'] ?? ''))) {
            $this->audit->log($request, 'platform_2fa_failed', 'warning', platformUser: $user);

            return ApiResponse::businessError('Valid 2FA code is required.', 'TWO_FACTOR_REQUIRED', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $token = $user->createToken($data['device_name'] ?? 'platform-api', ['platform:*'], $data['remember'] ?? false ? now()->addDays(30) : now()->addHours(12));

        $user->forceFill(['last_login_at' => now(), 'last_login_ip' => $request->ip()])->save();
        $this->audit->log($request, 'platform_login_success', platformUser: $user, metadata: ['token_id' => $token->accessToken->id]);

        return ApiResponse::success(['access_token' => $token->plainTextToken, 'token_type' => 'Bearer', 'user' => $this->userPayload($user)], 'Logged in.');
    }

    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();
        $token = $user?->currentAccessToken();
        $token?->delete();
        $this->audit->log($request, 'platform_logout', platformUser: $user instanceof PlatformUser ? $user : null);

        return ApiResponse::success(null, 'Logged out.');
    }

    public function refresh(Request $request): JsonResponse
    {
        /** @var PlatformUser $user */
        $user = $request->user();
        $old = $user->currentAccessToken();
        $old?->delete();
        $token = $user->createToken($request->input('device_name', 'platform-api'), ['platform:*'], now()->addHours(12));
        $this->audit->log($request, 'platform_token_refreshed', platformUser: $user, metadata: ['token_id' => $token->accessToken->id]);

        return ApiResponse::success(['access_token' => $token->plainTextToken, 'token_type' => 'Bearer'], 'Token refreshed.');
    }

    public function me(Request $request): JsonResponse
    {
        /** @var PlatformUser $user */
        $user = $request->user();

        return ApiResponse::success(['user' => $this->userPayload($user), 'roles' => $this->roles($user), 'permissions' => $this->permissions($user)]);
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $data = $request->validate(['email' => ['required', 'email']]);
        $token = Str::random(64);

        DB::table('password_reset_tokens')->updateOrInsert(['email' => 'platform:'.$data['email']], ['token' => Hash::make($token), 'created_at' => now()]);
        $this->audit->log($request, 'platform_password_reset_requested', metadata: ['email' => $data['email']]);

        return ApiResponse::success(app()->isLocal() ? ['reset_token' => $token] : null, 'Password reset instructions queued.');
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $data = $request->validate(['email' => ['required', 'email'], 'token' => ['required', 'string'], 'password' => ['required', 'confirmed', 'min:8']]);
        $record = DB::table('password_reset_tokens')->where('email', 'platform:'.$data['email'])->first();

        if (! $record || ! Hash::check($data['token'], $record->token)) {
            return ApiResponse::businessError('Invalid password reset token.', 'INVALID_RESET_TOKEN', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user = PlatformUser::query()->where('email', $data['email'])->firstOrFail();
        $user->forceFill(['password' => Hash::make($data['password'])])->save();
        DB::table('password_reset_tokens')->where('email', 'platform:'.$data['email'])->delete();
        $user->tokens()->delete();
        $this->audit->log($request, 'platform_password_reset_completed', platformUser: $user);

        return ApiResponse::success(null, 'Password reset completed.');
    }

    public function resendVerification(Request $request): JsonResponse
    {
        $this->audit->log($request, 'platform_email_verification_resent', platformUser: $request->user());

        return ApiResponse::success(null, 'Verification email queued.');
    }

    public function profile(Request $request): JsonResponse
    {
        return ApiResponse::success(['user' => $this->userPayload($request->user())]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        /** @var PlatformUser $user */
        $user = $request->user();
        $data = $request->validate([
            'first_name' => ['sometimes', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'display_name' => ['sometimes', 'string', 'max:200'],
            'mobile' => ['nullable', 'string', 'max:20'],
            'profile_photo_file_id' => ['nullable', 'integer'],
            'timezone' => ['sometimes', 'string', 'max:100'],
            'locale' => ['sometimes', 'string', 'max:20'],
        ]);
        $user->fill($data)->save();
        $this->audit->log($request, 'platform_profile_updated', platformUser: $user);

        return ApiResponse::success(['user' => $this->userPayload($user->fresh())], 'Profile updated.');
    }

    public function changePassword(Request $request): JsonResponse
    {
        /** @var PlatformUser $user */
        $user = $request->user();
        $data = $request->validate(['current_password' => ['required', 'string'], 'password' => ['required', 'confirmed', 'min:8']]);

        if (! Hash::check($data['current_password'], $user->password)) {
            return ApiResponse::businessError('Current password is incorrect.', 'INVALID_CURRENT_PASSWORD', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user->forceFill(['password' => Hash::make($data['password'])])->save();
        $this->audit->log($request, 'platform_password_changed', platformUser: $user);

        return ApiResponse::success(null, 'Password changed.');
    }

    public function preferences(Request $request): JsonResponse
    {
        $items = DB::table('platform_user_preferences')->where('platform_user_id', $request->user()->id)->get();

        return ApiResponse::success(['preferences' => $items]);
    }

    public function updatePreferences(Request $request): JsonResponse
    {
        $data = $request->validate(['preferences' => ['required', 'array']]);
        foreach ($data['preferences'] as $group => $values) {
            foreach ((array) $values as $key => $value) {
                DB::table('platform_user_preferences')->updateOrInsert(['platform_user_id' => $request->user()->id, 'group' => (string) $group, 'key' => (string) $key], ['value' => json_encode($value), 'updated_at' => now(), 'created_at' => now()]);
            }
        }
        $this->audit->log($request, 'platform_preferences_updated', platformUser: $request->user());

        return $this->preferences($request);
    }

    public function sessions(Request $request): JsonResponse
    {
        return ApiResponse::success(['sessions' => $request->user()->tokens()->latest()->get(['id', 'name', 'abilities', 'last_used_at', 'expires_at', 'created_at'])]);
    }

    public function revokeSession(Request $request, int $sessionId): JsonResponse
    {
        $request->user()->tokens()->where('id', $sessionId)->delete();
        $this->audit->log($request, 'platform_token_revoked', platformUser: $request->user(), metadata: ['session_id' => $sessionId]);

        return ApiResponse::success(null, 'Session revoked.');
    }

    public function enable2fa(Request $request): JsonResponse
    {
        /** @var PlatformUser $user */
        $user = $request->user();
        $secret = $this->totp->generateSecret();
        $user->forceFill(['two_factor_secret' => $secret, 'two_factor_enabled' => false, 'two_factor_confirmed_at' => null])->save();
        $this->audit->log($request, 'platform_2fa_enable_started', platformUser: $user);

        return ApiResponse::success(['secret' => $secret, 'provisioning_uri' => $this->totp->provisioningUri(config('app.name', 'SaaS CRM'), $user->email, $secret)], 'Confirm 2FA setup.');
    }

    public function confirm2fa(Request $request): JsonResponse
    {
        $data = $request->validate(['code' => ['required', 'string']]);
        /** @var PlatformUser $user */
        $user = $request->user();

        if (! $user->two_factor_secret || ! $this->totp->verify((string) $user->two_factor_secret, $data['code'])) {
            return ApiResponse::businessError('Invalid 2FA code.', 'INVALID_TWO_FACTOR_CODE', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $codes = collect(range(1, 8))->map(fn () => Str::upper(Str::random(10)))->all();
        $user->forceFill(['two_factor_enabled' => true, 'two_factor_confirmed_at' => now(), 'two_factor_recovery_codes' => $codes])->save();
        $this->audit->log($request, 'platform_2fa_enabled', platformUser: $user);

        return ApiResponse::success(['recovery_codes' => $codes], '2FA enabled.');
    }

    public function disable2fa(Request $request): JsonResponse
    {
        $data = $request->validate(['password' => ['required', 'string']]);
        /** @var PlatformUser $user */
        $user = $request->user();

        if (! Hash::check($data['password'], $user->password)) {
            return ApiResponse::businessError('Password is incorrect.', 'INVALID_PASSWORD', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user->forceFill(['two_factor_enabled' => false, 'two_factor_secret' => null, 'two_factor_recovery_codes' => null, 'two_factor_confirmed_at' => null])->save();
        $this->audit->log($request, 'platform_2fa_disabled', platformUser: $user);

        return ApiResponse::success(null, '2FA disabled.');
    }

    private function userPayload(PlatformUser $user): array
    {
        return $user->only(['uuid', 'employee_code', 'first_name', 'last_name', 'display_name', 'email', 'mobile', 'designation', 'department', 'timezone', 'locale', 'email_verified_at', 'two_factor_enabled', 'status']);
    }

    private function roles(PlatformUser $user): array
    {
        return DB::table('platform_model_has_roles')->join('platform_roles', 'platform_roles.id', '=', 'platform_model_has_roles.role_id')->where('model_type', PlatformUser::class)->where('model_id', $user->id)->pluck('platform_roles.name')->all();
    }

    private function permissions(PlatformUser $user): array
    {
        return DB::table('platform_model_has_roles')->join('platform_role_has_permissions', 'platform_role_has_permissions.role_id', '=', 'platform_model_has_roles.role_id')->join('platform_permissions', 'platform_permissions.id', '=', 'platform_role_has_permissions.permission_id')->where('platform_model_has_roles.model_type', PlatformUser::class)->where('platform_model_has_roles.model_id', $user->id)->distinct()->pluck('platform_permissions.name')->all();
    }
}