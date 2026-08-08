<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Mail\PasswordResetInstructions;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Shared\AuthAuditService;
use App\Services\Shared\TotpService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class TenantAuthController extends Controller
{
    public function __construct(private readonly AuthAuditService $audit, private readonly TotpService $totp) {}

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'tenant' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:120'],
            'remember' => ['nullable', 'boolean'],
            'two_factor_code' => ['nullable', 'string'],
        ]);

        /** @var Tenant|null $tenant */
        $tenant = $request->attributes->get('tenant');
        $user = $tenant ? User::query()->where('tenant_id', $tenant->id)->where('email', $data['email'])->first() : null;

        if (! $tenant || ! $user || ! Hash::check($data['password'], $user->password) || $user->status !== 'active') {
            $this->audit->log($request, 'tenant_login_failed', 'warning', metadata: ['email' => $data['email'], 'tenant' => $data['tenant']]);

            return ApiResponse::businessError('Invalid tenant credentials.', 'INVALID_CREDENTIALS', Response::HTTP_UNAUTHORIZED);
        }

        if ($user->two_factor_enabled && ! $this->totp->verify((string) $user->two_factor_secret, (string) ($data['two_factor_code'] ?? ''))) {
            $this->audit->log($request, 'tenant_2fa_failed', 'warning', tenantUser: $user);

            return ApiResponse::businessError('Valid 2FA code is required.', 'TWO_FACTOR_REQUIRED', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $token = $user->createToken($data['device_name'] ?? 'tenant-api', ['tenant:'.$tenant->uuid], $data['remember'] ?? false ? now()->addDays(30) : now()->addHours(12));
        $user->forceFill(['last_login_at' => now(), 'last_login_ip' => $request->ip()])->save();
        $this->audit->log($request, 'tenant_login_success', tenantUser: $user, metadata: ['token_id' => $token->accessToken->id]);

        return ApiResponse::success(['access_token' => $token->plainTextToken, 'token_type' => 'Bearer', 'tenant' => $this->tenantPayload($tenant), 'user' => $this->userPayload($user)], 'Logged in.');
    }

    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();
        $user?->currentAccessToken()?->delete();
        $this->audit->log($request, 'tenant_logout', tenantUser: $user instanceof User ? $user : null);

        return ApiResponse::success(null, 'Logged out.');
    }

    public function refresh(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        /** @var Tenant $tenant */
        $tenant = $request->attributes->get('tenant');
        $user->currentAccessToken()?->delete();
        $token = $user->createToken($request->input('device_name', 'tenant-api'), ['tenant:'.$tenant->uuid], now()->addHours(12));
        $this->audit->log($request, 'tenant_token_refreshed', tenantUser: $user, metadata: ['token_id' => $token->accessToken->id]);

        return ApiResponse::success(['access_token' => $token->plainTextToken, 'token_type' => 'Bearer'], 'Token refreshed.');
    }

    public function me(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        /** @var Tenant $tenant */
        $tenant = $request->attributes->get('tenant');

        return ApiResponse::success(['tenant' => $this->tenantPayload($tenant), 'user' => $this->userPayload($user), 'roles' => $this->roles($user), 'permissions' => $this->permissions($user)]);
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $data = $request->validate(['tenant' => ['required', 'string'], 'email' => ['required', 'email']]);
        /** @var Tenant|null $tenant */
        $tenant = $request->attributes->get('tenant');
        $token = Str::random(64);

        if ($tenant) {
            DB::table('password_reset_tokens')->updateOrInsert(['email' => 'tenant:'.$tenant->id.':'.$data['email']], ['token' => Hash::make($token), 'created_at' => now()]);
            Mail::to($data['email'])->send(new PasswordResetInstructions($data['email'], $token, $this->passwordResetUrl($data['tenant'], $data['email'], $token), 'tenant', $data['tenant']));
        }

        $this->audit->log($request, 'tenant_password_reset_requested', metadata: ['email' => $data['email']]);

        return ApiResponse::success(app()->isLocal() ? ['reset_token' => $token] : null, 'Password reset instructions queued.');
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $data = $request->validate(['tenant' => ['required', 'string'], 'email' => ['required', 'email'], 'token' => ['required', 'string'], 'password' => ['required', 'confirmed', 'min:8']]);
        /** @var Tenant $tenant */
        $tenant = $request->attributes->get('tenant');
        $key = 'tenant:'.$tenant->id.':'.$data['email'];
        $record = DB::table('password_reset_tokens')->where('email', $key)->first();

        if (! $record || ! Hash::check($data['token'], $record->token)) {
            return ApiResponse::businessError('Invalid password reset token.', 'INVALID_RESET_TOKEN', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user = User::query()->where('tenant_id', $tenant->id)->where('email', $data['email'])->firstOrFail();
        $user->forceFill(['password' => Hash::make($data['password'])])->save();
        DB::table('password_reset_tokens')->where('email', $key)->delete();
        $user->tokens()->delete();
        $this->audit->log($request, 'tenant_password_reset_completed', tenantUser: $user);

        return ApiResponse::success(null, 'Password reset completed.');
    }

    private function passwordResetUrl(string $tenant, string $email, string $token): string
    {
        return url('/reset-password?surface=tenant&tenant='.urlencode($tenant).'&email='.urlencode($email).'&token='.urlencode($token));
    }

    public function resendVerification(Request $request): JsonResponse
    {
        $this->audit->log($request, 'tenant_email_verification_resent', tenantUser: $request->user());

        return ApiResponse::success(null, 'Verification email queued.');
    }

    public function profile(Request $request): JsonResponse
    {
        return ApiResponse::success(['user' => $this->userPayload($request->user())]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        /** @var User $user */
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
        $this->audit->log($request, 'tenant_profile_updated', tenantUser: $user);

        return ApiResponse::success(['user' => $this->userPayload($user->fresh())], 'Profile updated.');
    }

    public function changePassword(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $data = $request->validate(['current_password' => ['required', 'string'], 'password' => ['required', 'confirmed', 'min:8']]);

        if (! Hash::check($data['current_password'], $user->password)) {
            return ApiResponse::businessError('Current password is incorrect.', 'INVALID_CURRENT_PASSWORD', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user->forceFill(['password' => Hash::make($data['password'])])->save();
        $this->audit->log($request, 'tenant_password_changed', tenantUser: $user);

        return ApiResponse::success(null, 'Password changed.');
    }

    public function preferences(Request $request): JsonResponse
    {
        $items = DB::table('user_preferences')->where('tenant_id', $request->attributes->get('tenant_id'))->where('user_id', $request->user()->id)->get();

        return ApiResponse::success(['preferences' => $items]);
    }

    public function updatePreferences(Request $request): JsonResponse
    {
        $data = $request->validate(['preferences' => ['required', 'array']]);
        $tenantId = $request->attributes->get('tenant_id');
        foreach ($data['preferences'] as $group => $values) {
            foreach ((array) $values as $key => $value) {
                DB::table('user_preferences')->updateOrInsert(['tenant_id' => $tenantId, 'user_id' => $request->user()->id, 'group' => (string) $group, 'key' => (string) $key], ['value' => json_encode($value), 'updated_at' => now(), 'created_at' => now()]);
            }
        }
        $this->audit->log($request, 'tenant_preferences_updated', tenantUser: $request->user());

        return $this->preferences($request);
    }

    public function sessions(Request $request): JsonResponse
    {
        return ApiResponse::success(['sessions' => $request->user()->tokens()->latest()->get(['id', 'name', 'abilities', 'last_used_at', 'expires_at', 'created_at'])]);
    }

    public function revokeSession(Request $request, int $sessionId): JsonResponse
    {
        $request->user()->tokens()->where('id', $sessionId)->delete();
        $this->audit->log($request, 'tenant_token_revoked', tenantUser: $request->user(), metadata: ['session_id' => $sessionId]);

        return ApiResponse::success(null, 'Session revoked.');
    }

    public function enable2fa(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $secret = $this->totp->generateSecret();
        $user->forceFill(['two_factor_secret' => $secret, 'two_factor_enabled' => false, 'two_factor_confirmed_at' => null])->save();
        $this->audit->log($request, 'tenant_2fa_enable_started', tenantUser: $user);

        return ApiResponse::success(['secret' => $secret, 'provisioning_uri' => $this->totp->provisioningUri(config('app.name', 'SaaS CRM'), $user->email, $secret)], 'Confirm 2FA setup.');
    }

    public function confirm2fa(Request $request): JsonResponse
    {
        $data = $request->validate(['code' => ['required', 'string']]);
        /** @var User $user */
        $user = $request->user();

        if (! $user->two_factor_secret || ! $this->totp->verify((string) $user->two_factor_secret, $data['code'])) {
            return ApiResponse::businessError('Invalid 2FA code.', 'INVALID_TWO_FACTOR_CODE', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $codes = collect(range(1, 8))->map(fn () => Str::upper(Str::random(10)))->all();
        $user->forceFill(['two_factor_enabled' => true, 'two_factor_confirmed_at' => now(), 'two_factor_recovery_codes' => $codes])->save();
        $this->audit->log($request, 'tenant_2fa_enabled', tenantUser: $user);

        return ApiResponse::success(['recovery_codes' => $codes], '2FA enabled.');
    }

    public function disable2fa(Request $request): JsonResponse
    {
        $data = $request->validate(['password' => ['required', 'string']]);
        /** @var User $user */
        $user = $request->user();

        if (! Hash::check($data['password'], $user->password)) {
            return ApiResponse::businessError('Password is incorrect.', 'INVALID_PASSWORD', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user->forceFill(['two_factor_enabled' => false, 'two_factor_secret' => null, 'two_factor_recovery_codes' => null, 'two_factor_confirmed_at' => null])->save();
        $this->audit->log($request, 'tenant_2fa_disabled', tenantUser: $user);

        return ApiResponse::success(null, '2FA disabled.');
    }

    private function userPayload(User $user): array
    {
        return $user->only(['uuid', 'tenant_id', 'staff_id', 'client_contact_id', 'default_office_id', 'employee_code', 'first_name', 'last_name', 'display_name', 'email', 'mobile', 'timezone', 'locale', 'email_verified_at', 'mobile_verified_at', 'two_factor_enabled', 'account_type', 'status']);
    }

    private function tenantPayload(Tenant $tenant): array
    {
        return $tenant->only(['uuid', 'organization_name', 'display_name', 'organization_code', 'slug', 'default_currency', 'default_timezone', 'status']);
    }

    private function roles(User $user): array
    {
        return DB::table('model_has_roles')->join('roles', 'roles.id', '=', 'model_has_roles.role_id')->where('model_has_roles.tenant_id', $user->tenant_id)->where('model_type', User::class)->where('model_id', $user->id)->pluck('roles.name')->all();
    }

    private function permissions(User $user): array
    {
        return DB::table('model_has_roles')->join('role_has_permissions', 'role_has_permissions.role_id', '=', 'model_has_roles.role_id')->join('permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')->where('model_has_roles.tenant_id', $user->tenant_id)->where('model_has_roles.model_type', User::class)->where('model_has_roles.model_id', $user->id)->distinct()->pluck('permissions.name')->all();
    }
}