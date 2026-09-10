<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Support\ApiResponse;
use App\Support\TenantAudit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

final class TenantAuthController extends Controller
{
    public function forgotPassword(Request $request): mixed
    {
        $data = $request->validate(['tenant' => ['required', 'string'], 'email' => ['required', 'email']]);
        $tenant = $request->attributes->get('tenant');
        $token = Str::random(64);
        if ($tenant) DB::table('password_reset_tokens')->updateOrInsert(['email' => 'tenant:'.$tenant->id.':'.$data['email']], ['token' => Hash::make($token), 'created_at' => now()]);
        if ($tenant) TenantAudit::record($request, $tenant, 'tenant_password_reset_requested', ['email' => $data['email']], true);
        return ApiResponse::success(app()->isLocal() ? ['reset_token' => $token] : null, 'Password reset instructions queued.');
    }

    public function resetPassword(Request $request): mixed
    {
        $data = $request->validate(['tenant' => ['required', 'string'], 'email' => ['required', 'email'], 'token' => ['required', 'string'], 'password' => ['required', 'confirmed', 'min:8']]);
        $tenant = $request->attributes->get('tenant');
        $key = 'tenant:'.$tenant->id.':'.$data['email'];
        $record = DB::table('password_reset_tokens')->where('email', $key)->first();
        if (! $record || ! Hash::check($data['token'], $record->token)) return ApiResponse::error('Invalid password reset token.', 422, null, 'INVALID_RESET_TOKEN');
        $user = User::where('tenant_id', $tenant->id)->where('email', $data['email'])->first();
        if (! $user) return ApiResponse::error('User was not found.', 404, null, 'USER_NOT_FOUND');
        $user->forceFill(['password' => Hash::make($data['password'])])->save();
        DB::table('password_reset_tokens')->where('email', $key)->delete();
        $user->tokens()->delete();
        TenantAudit::record($request, $tenant, 'tenant_password_reset_completed', ['user_uuid' => $user->uuid], true);
        return ApiResponse::success(null, 'Password reset completed.');
    }

    public function logout(Request $request): mixed
    {
        $user = $request->user(); $tenant = $request->attributes->get('tenant');
        $request->boolean('all_devices') ? $user->tokens()->delete() : $user->currentAccessToken()?->delete();
        TenantAudit::record($request, $tenant, 'tenant_logout', [], true);
        return ApiResponse::success(['logged_out' => true], 'Logged out successfully.');
    }

    public function refresh(Request $request): mixed
    {
        $user = $request->user(); $tenant = $request->attributes->get('tenant');
        $user->currentAccessToken()?->delete();
        $token = $user->createToken($request->input('device_name', 'tenant-api'), ['tenant:'.$tenant->uuid], now()->addHours(12));
        TenantAudit::record($request, $tenant, 'tenant_token_refreshed', [], true);
        return ApiResponse::success(['access_token' => $token->plainTextToken, 'token_type' => 'Bearer'], 'Token refreshed.');
    }

    public function me(Request $request): mixed
    {
        $tenant = $request->attributes->get('tenant'); $user = $request->user();
        return ApiResponse::success(['tenant' => $this->tenantPayload($tenant), 'user' => $this->userPayload($user), 'roles' => $this->roles($user, $tenant->id), 'permissions' => $this->permissions($user, $tenant->id)], 'Current tenant session fetched.');
    }

    public function resendVerification(Request $request): mixed
    {
        TenantAudit::record($request, $request->attributes->get('tenant'), 'tenant_email_verification_resent', [], true);
        return ApiResponse::success(null, 'Verification email queued.');
    }

    public function enable2fa(Request $request): mixed
    {
        $user = $request->user(); $secret = $this->base32Encode(random_bytes(20));
        $user->forceFill(['two_factor_secret' => $secret, 'two_factor_enabled' => false, 'two_factor_confirmed_at' => null])->save();
        TenantAudit::record($request, $request->attributes->get('tenant'), 'tenant_2fa_enable_started', [], true);
        return ApiResponse::success(['secret' => $secret, 'provisioning_uri' => 'otpauth://totp/'.rawurlencode(config('app.name', 'SaaS CRM')).':'.rawurlencode($user->email).'?secret='.$secret.'&issuer='.rawurlencode(config('app.name', 'SaaS CRM'))], 'Confirm 2FA setup.');
    }

    public function confirm2fa(Request $request): mixed
    {
        $data = $request->validate(['code' => ['required', 'digits:6']]); $user = $request->user();
        if (! $user->two_factor_secret || ! $this->validTotp($user->two_factor_secret, $data['code'])) return ApiResponse::error('Invalid 2FA code.', 422, null, 'INVALID_TWO_FACTOR_CODE');
        $codes = collect(range(1, 8))->map(fn () => Str::upper(Str::random(10)))->all();
        $user->forceFill(['two_factor_enabled' => true, 'two_factor_confirmed_at' => now(), 'two_factor_recovery_codes' => json_encode($codes)])->save();
        TenantAudit::record($request, $request->attributes->get('tenant'), 'tenant_2fa_enabled', [], true);
        return ApiResponse::success(['recovery_codes' => $codes], '2FA enabled.');
    }

    public function disable2fa(Request $request): mixed
    {
        $data = $request->validate(['password' => ['required', 'string']]); $user = $request->user();
        if (! Hash::check($data['password'], $user->password)) return ApiResponse::error('Password is incorrect.', 422, null, 'INVALID_PASSWORD');
        $user->forceFill(['two_factor_enabled' => false, 'two_factor_secret' => null, 'two_factor_recovery_codes' => null, 'two_factor_confirmed_at' => null])->save();
        TenantAudit::record($request, $request->attributes->get('tenant'), 'tenant_2fa_disabled', [], true);
        return ApiResponse::success(null, '2FA disabled.');
    }

    public function profile(Request $request): mixed { return ApiResponse::success(['user' => $this->userPayload($request->user())], 'Profile fetched.'); }

    public function updateProfile(Request $request): mixed
    {
        $data = $request->validate(['first_name' => ['sometimes', 'string', 'max:100'], 'last_name' => ['nullable', 'string', 'max:100'], 'display_name' => ['sometimes', 'string', 'max:200'], 'mobile' => ['nullable', 'string', 'max:20'], 'profile_photo_file_id' => ['nullable', 'integer'], 'timezone' => ['sometimes', 'string', 'max:100'], 'locale' => ['sometimes', 'string', 'max:20']]);
        $request->user()->fill($data)->save(); TenantAudit::record($request, $request->attributes->get('tenant'), 'tenant_profile_updated', $data);
        return ApiResponse::success(['user' => $this->userPayload($request->user()->fresh())], 'Profile updated.');
    }

    public function changePassword(Request $request): mixed
    {
        $data = $request->validate(['current_password' => ['required', 'string'], 'password' => ['required', 'confirmed', 'min:8']]);
        if (! Hash::check($data['current_password'], $request->user()->password)) return ApiResponse::error('Current password is incorrect.', 422, null, 'INVALID_CURRENT_PASSWORD');
        $request->user()->forceFill(['password' => Hash::make($data['password'])])->save(); $request->user()->tokens()->delete();
        TenantAudit::record($request, $request->attributes->get('tenant'), 'tenant_password_changed', [], true);
        return ApiResponse::success(null, 'Password changed. Please sign in again.');
    }

    public function preferences(Request $request): mixed
    {
        $items = DB::table('user_preferences')->where('tenant_id', $request->attributes->get('tenant_id'))->where('user_id', $request->user()->id)->get();
        return ApiResponse::success(['preferences' => $items], 'Preferences fetched.');
    }

    public function updatePreferences(Request $request): mixed
    {
        $data = $request->validate(['preferences' => ['required', 'array']]); $tenantId = $request->attributes->get('tenant_id');
        foreach ($data['preferences'] as $group => $values) foreach ((array) $values as $key => $value) DB::table('user_preferences')->updateOrInsert(['tenant_id' => $tenantId, 'user_id' => $request->user()->id, 'group' => (string) $group, 'key' => (string) $key], ['value' => json_encode($value), 'updated_at' => now(), 'created_at' => now()]);
        TenantAudit::record($request, $request->attributes->get('tenant'), 'tenant_preferences_updated', [], true);
        return $this->preferences($request);
    }

    public function sessions(Request $request): mixed { return ApiResponse::success(['sessions' => $request->user()->tokens()->latest()->get(['id', 'name', 'abilities', 'last_used_at', 'expires_at', 'created_at'])], 'Sessions fetched.'); }
    public function revokeSession(Request $request, int $session_id): mixed { $request->user()->tokens()->where('id', $session_id)->delete(); TenantAudit::record($request, $request->attributes->get('tenant'), 'tenant_token_revoked', ['session_id' => $session_id], true); return ApiResponse::success(null, 'Session revoked.'); }

    private function tenantPayload(Tenant $tenant): array { return $tenant->only(['uuid', 'organization_name', 'display_name', 'organization_code', 'slug', 'default_currency', 'default_timezone', 'status']); }
    private function userPayload(User $user): array { return $user->only(['uuid', 'tenant_id', 'staff_id', 'client_contact_id', 'default_office_id', 'employee_code', 'first_name', 'last_name', 'display_name', 'email', 'mobile', 'timezone', 'locale', 'email_verified_at', 'mobile_verified_at', 'two_factor_enabled', 'account_type', 'status']); }
    private function roles(User $user, int $tenantId): array { return DB::table('model_has_roles')->join('roles', 'roles.id', '=', 'model_has_roles.role_id')->where('model_has_roles.tenant_id', $tenantId)->where('model_has_roles.model_type', User::class)->where('model_has_roles.model_id', $user->id)->pluck('roles.name')->all(); }
    private function permissions(User $user, int $tenantId): array { return DB::table('model_has_roles')->join('role_has_permissions', 'role_has_permissions.role_id', '=', 'model_has_roles.role_id')->join('permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')->where('model_has_roles.tenant_id', $tenantId)->where('model_has_roles.model_type', User::class)->where('model_has_roles.model_id', $user->id)->where('permissions.status', 'active')->distinct()->pluck('permissions.name')->all(); }
    private function base32Encode(string $value): string { $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567'; $bits = 0; $buffer = 0; $output = ''; foreach (unpack('C*', $value) as $byte) { $buffer = ($buffer << 8) | $byte; $bits += 8; while ($bits >= 5) { $bits -= 5; $output .= $alphabet[($buffer >> $bits) & 31]; } } if ($bits > 0) $output .= $alphabet[($buffer << (5 - $bits)) & 31]; return $output; }
    private function validTotp(string $secret, string $code): bool { $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567'; $buffer = 0; $bits = 0; $key = ''; foreach (str_split(strtoupper(preg_replace('/[^A-Z2-7]/', '', $secret))) as $char) { $buffer = ($buffer << 5) | strpos($alphabet, $char); $bits += 5; if ($bits >= 8) { $bits -= 8; $key .= chr(($buffer >> $bits) & 255); } } $counter = intdiv(time(), 30); for ($offset = -1; $offset <= 1; $offset++) { $bin = pack('N*', 0).pack('N*', $counter + $offset); $hash = hash_hmac('sha1', $bin, $key, true); $index = ord($hash[19]) & 15; $otp = (((ord($hash[$index]) & 127) << 24) | ((ord($hash[$index + 1]) & 255) << 16) | ((ord($hash[$index + 2]) & 255) << 8) | (ord($hash[$index + 3]) & 255)) % 1000000; if (hash_equals(str_pad((string) $otp, 6, '0', STR_PAD_LEFT), $code)) return true; } return false; }
}
