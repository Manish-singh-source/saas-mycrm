<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\PasswordResetInstructions;
use App\Models\PlatformUser;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Shared\AuthAuditService;
use App\Services\Shared\TotpService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class UnifiedAuthController extends Controller
{
    private const DISCOVERY_TTL_SECONDS = 300;
    private const CHALLENGE_TTL_SECONDS = 300;

    public function __construct(private readonly AuthAuditService $audit, private readonly TotpService $totp) {}

    public function discover(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:150'],
            'device_name' => ['nullable', 'string', 'max:150'],
        ]);

        $email = Str::lower($data['email']);
        $accounts = [];
        $refs = [];

        PlatformUser::query()->where('email', $email)->whereNull('deleted_at')->get()->each(function (PlatformUser $user) use (&$accounts, &$refs, $email): void {
            if (! in_array($user->status, ['active', 'inactive', 'suspended'], true)) {
                return;
            }

            $ref = 'acct_'.Str::random(64);
            $payload = ['guard' => 'platform', 'account_type' => 'platform', 'account_uuid' => $user->uuid, 'email' => $email];
            $refs[$ref] = $payload;

            $accounts[] = [
                'account_ref' => $ref,
                'account_type' => 'platform',
                'auth_guard' => 'platform',
                'label' => 'SaaS Super Admin',
                'display_name' => $user->display_name,
                'email' => $user->email,
                'avatar_url' => null,
                'organization' => null,
                'tenant' => null,
                'roles' => $this->platformRoles($user),
                'status' => $user->status,
                'last_login_at' => $user->last_login_at?->toISOString(),
            ];
        });

        User::query()
            ->with('tenant')
            ->where('email', $email)
            ->whereNull('deleted_at')
            ->get()
            ->each(function (User $user) use (&$accounts, &$refs, $email): void {
                $tenant = $user->tenant;
                if (! $tenant instanceof Tenant) {
                    return;
                }

                $accountType = match ($user->account_type) {
                    'owner' => 'tenant_owner',
                    'client' => 'client',
                    default => 'tenant_staff',
                };

                $surfaceLabel = match ($accountType) {
                    'tenant_owner' => 'Owner',
                    'client' => 'Client Portal',
                    default => 'Staff',
                };

                $ref = 'acct_'.Str::random(64);
                $payload = ['guard' => 'tenant', 'account_type' => $accountType, 'account_uuid' => $user->uuid, 'tenant_uuid' => $tenant->uuid, 'email' => $email];
                $refs[$ref] = $payload;

                $accounts[] = [
                    'account_ref' => $ref,
                    'account_type' => $accountType,
                    'auth_guard' => 'tenant',
                    'label' => $tenant->display_name.' - '.$surfaceLabel,
                    'display_name' => $user->display_name,
                    'email' => $user->email,
                    'avatar_url' => null,
                    'organization' => $tenant->organization_name,
                    'tenant' => ['uuid' => $tenant->uuid, 'slug' => $tenant->slug, 'status' => $tenant->status],
                    'roles' => $this->tenantRoles($user),
                    'status' => $user->status,
                    'last_login_at' => $user->last_login_at?->toISOString(),
                ];
            });

        $discoveryToken = $accounts === [] ? null : 'disc_'.Str::random(64);

        if ($discoveryToken !== null) {
            Cache::put($this->discoveryKey($discoveryToken), ['email' => $email, 'refs' => $refs, 'attempts' => 0], self::DISCOVERY_TTL_SECONDS);
        }

        $this->audit->log($request, 'auth.discovery_requested', metadata: ['email' => $email, 'account_count' => count($accounts)]);

        return ApiResponse::success([
            'email' => $email,
            'discovery_token' => $discoveryToken,
            'expires_in_seconds' => $discoveryToken === null ? 0 : self::DISCOVERY_TTL_SECONDS,
            'accounts' => $accounts,
        ]);
    }

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'discovery_token' => ['required', 'string'],
            'account_ref' => ['required', 'string'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
            'device_name' => ['nullable', 'string', 'max:150'],
        ]);

        $email = Str::lower($data['email']);
        $discovery = Cache::get($this->discoveryKey($data['discovery_token']));
        $account = $this->accountFromDiscovery($discovery, $email, $data['account_ref']);

        if ($account === null) {
            return ApiResponse::businessError('Discovery token expired or account selection is invalid.', 'DISCOVERY_TOKEN_EXPIRED', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        [$authenticatable, $tenant] = $this->loadAccount($account);

        if (! $authenticatable) {
            return ApiResponse::businessError('Invalid account selection.', 'INVALID_ACCOUNT_REF', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $blocked = $this->blockingError($authenticatable, $tenant);
        if ($blocked !== null) {
            $this->audit->log($request, 'auth.login_blocked', 'warning', $authenticatable instanceof User ? $authenticatable : null, $authenticatable instanceof PlatformUser ? $authenticatable : null, ['reason' => $blocked['code']]);

            return ApiResponse::businessError($blocked['message'], $blocked['code'], Response::HTTP_FORBIDDEN);
        }

        if (! Hash::check($data['password'], $authenticatable->password)) {
            $this->audit->log($request, 'auth.login_failed', 'warning', $authenticatable instanceof User ? $authenticatable : null, $authenticatable instanceof PlatformUser ? $authenticatable : null);

            return ApiResponse::businessError('Invalid password.', 'INVALID_CREDENTIALS', Response::HTTP_UNAUTHORIZED);
        }

        if ($authenticatable->two_factor_enabled) {
            $challengeToken = '2fa_'.Str::random(64);
            Cache::put($this->challengeKey($challengeToken), ['account' => $account, 'remember' => (bool) ($data['remember'] ?? false), 'device_name' => $data['device_name'] ?? null], self::CHALLENGE_TTL_SECONDS);
            $this->audit->log($request, 'auth.2fa_required', 'info', $authenticatable instanceof User ? $authenticatable : null, $authenticatable instanceof PlatformUser ? $authenticatable : null);

            return ApiResponse::success(['requires_2fa' => true, 'challenge_token' => $challengeToken, 'methods' => ['totp'], 'account_type' => $account['account_type'], 'surface' => $this->surface($account)], 'Two-factor authentication required.');
        }

        Cache::forget($this->discoveryKey($data['discovery_token']));

        return ApiResponse::success($this->issueSession($request, $authenticatable, $tenant, $account, (bool) ($data['remember'] ?? false), $data['device_name'] ?? null), 'Logged in.');
    }

    public function verifyTwoFactor(Request $request): JsonResponse
    {
        $data = $request->validate([
            'challenge_token' => ['required', 'string'],
            'code' => ['required', 'string'],
            'remember_device' => ['nullable', 'boolean'],
            'device_name' => ['nullable', 'string', 'max:150'],
        ]);

        $challenge = Cache::get($this->challengeKey($data['challenge_token']));
        if (! is_array($challenge) || ! isset($challenge['account'])) {
            return ApiResponse::businessError('Two-factor challenge expired.', 'TWO_FACTOR_CHALLENGE_EXPIRED', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        [$authenticatable, $tenant] = $this->loadAccount($challenge['account']);
        if (! $authenticatable || ! $authenticatable->two_factor_secret || ! $this->totp->verify((string) $authenticatable->two_factor_secret, $data['code'])) {
            $this->audit->log($request, 'auth.2fa_failed', 'warning', $authenticatable instanceof User ? $authenticatable : null, $authenticatable instanceof PlatformUser ? $authenticatable : null);

            return ApiResponse::businessError('Invalid authentication code.', 'INVALID_2FA_CODE', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        Cache::forget($this->challengeKey($data['challenge_token']));
        $this->audit->log($request, 'auth.2fa_success', 'info', $authenticatable instanceof User ? $authenticatable : null, $authenticatable instanceof PlatformUser ? $authenticatable : null);

        return ApiResponse::success($this->issueSession($request, $authenticatable, $tenant, $challenge['account'], (bool) ($challenge['remember'] ?? false), $data['device_name'] ?? $challenge['device_name'] ?? null), 'Logged in.');
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user instanceof PlatformUser) {
            return ApiResponse::success($this->sessionPayload($user, null, ['guard' => 'platform', 'account_type' => 'platform']));
        }

        if ($user instanceof User) {
            return ApiResponse::success($this->sessionPayload($user, $user->tenant, ['guard' => 'tenant', 'account_type' => $user->account_type === 'client' ? 'client' : ($user->account_type === 'owner' ? 'tenant_owner' : 'tenant_staff')]));
        }

        return ApiResponse::businessError('Unauthenticated.', 'AUTHENTICATION_REQUIRED', Response::HTTP_UNAUTHORIZED);
    }

    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();
        $allDevices = (bool) $request->boolean('all_devices');
        if ($user) {
            $allDevices ? $user->tokens()->delete() : $user->currentAccessToken()?->delete();
            $this->audit->log($request, 'auth.logout', tenantUser: $user instanceof User ? $user : null, platformUser: $user instanceof PlatformUser ? $user : null, metadata: ['all_devices' => $allDevices]);
        }

        return ApiResponse::success(['logged_out' => true]);
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $data = $request->validate(['email' => ['required', 'email'], 'account_ref' => ['nullable', 'string'], 'discovery_token' => ['nullable', 'string']]);
        $email = Str::lower($data['email']);
        $token = Str::random(64);
        $key = 'auth:'.$email;

        if (! empty($data['account_ref']) && ! empty($data['discovery_token'])) {
            $discovery = Cache::get($this->discoveryKey($data['discovery_token']));
            $account = $this->accountFromDiscovery($discovery, $email, $data['account_ref']);
            if ($account !== null) {
                $key = $this->resetKey($account, $email);
            }
        }

        DB::table('password_reset_tokens')->updateOrInsert(['email' => $key], ['token' => Hash::make($token), 'created_at' => now()]);
        Mail::to($email)->send(new PasswordResetInstructions($email, $token, $this->passwordResetUrl($email, $token), 'unified'));
        $this->audit->log($request, 'auth.password_reset_requested', metadata: ['email' => $email]);

        return ApiResponse::success(['sent' => true, 'message' => 'If this account can receive password reset instructions, an email has been sent.', 'reset_token' => app()->isLocal() ? $token : null]);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $data = $request->validate(['token' => ['required', 'string'], 'email' => ['required', 'email'], 'password' => ['required', 'confirmed', 'min:8']]);
        $email = Str::lower($data['email']);
        $records = DB::table('password_reset_tokens')->where('email', 'like', '%'.$email.'%')->get();

        foreach ($records as $record) {
            if (! Hash::check($data['token'], $record->token)) {
                continue;
            }

            $user = $this->resolveResetUser($record->email, $email);
            if (! $user) {
                continue;
            }

            $user->forceFill(['password' => Hash::make($data['password'])])->save();
            $user->tokens()->delete();
            DB::table('password_reset_tokens')->where('email', $record->email)->delete();
            $this->audit->log($request, 'auth.password_reset_completed', tenantUser: $user instanceof User ? $user : null, platformUser: $user instanceof PlatformUser ? $user : null);

            return ApiResponse::success(['reset' => true]);
        }

        return ApiResponse::businessError('Invalid password reset token.', 'INVALID_RESET_TOKEN', Response::HTTP_UNPROCESSABLE_ENTITY);
    }
    private function accountFromDiscovery(mixed $discovery, string $email, string $accountRef): ?array
    {
        if (! is_array($discovery) || ($discovery['email'] ?? null) !== $email || ! isset($discovery['refs'][$accountRef])) {
            return null;
        }

        return $discovery['refs'][$accountRef];
    }

    private function loadAccount(array $account): array
    {
        if (($account['guard'] ?? null) === 'platform') {
            return [PlatformUser::query()->where('uuid', $account['account_uuid'])->first(), null];
        }

        $tenant = Tenant::query()->where('uuid', $account['tenant_uuid'] ?? null)->first();
        $user = $tenant
            ? User::query()->where('tenant_id', $tenant->id)->where('uuid', $account['account_uuid'])->first()
            : null;

        return [$user, $tenant];
    }

    private function blockingError(PlatformUser|User $user, ?Tenant $tenant): ?array
    {
        if ($user instanceof PlatformUser && $user->status !== 'active') {
            return ['message' => 'This account is suspended.', 'code' => 'ACCOUNT_SUSPENDED'];
        }

        if ($user instanceof User && $user->status !== 'active') {
            return ['message' => 'This account is suspended.', 'code' => 'ACCOUNT_SUSPENDED'];
        }

        if ($tenant && in_array($tenant->status, ['suspended', 'cancelled', 'archived'], true)) {
            return ['message' => 'This tenant is suspended.', 'code' => 'TENANT_SUSPENDED'];
        }

        if ($tenant && $tenant->status === 'expired') {
            return ['message' => 'This tenant is expired.', 'code' => 'TENANT_EXPIRED'];
        }

        return null;
    }

    private function issueSession(Request $request, PlatformUser|User $user, ?Tenant $tenant, array $account, bool $remember, ?string $deviceName): array
    {
        $expiresAt = $remember ? now()->addDays(30) : now()->addHours(12);
        $surface = $this->surface($account);
        $tokenName = $surface.':'.($deviceName ?: 'api');
        $abilities = $user instanceof PlatformUser ? ['platform:*'] : ['tenant:'.$tenant?->uuid];
        $token = $user->createToken($tokenName, $abilities, $expiresAt);

        $user->forceFill(['last_login_at' => now(), 'last_login_ip' => $request->ip()])->save();
        $this->audit->log($request, 'auth.login_success', tenantUser: $user instanceof User ? $user : null, platformUser: $user instanceof PlatformUser ? $user : null, metadata: ['surface' => $surface, 'token_id' => $token->accessToken->id]);

        return [
            'access_token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'expires_at' => $expiresAt->toISOString(),
            ...$this->sessionPayload($user, $tenant, $account),
        ];
    }

    private function sessionPayload(PlatformUser|User $user, ?Tenant $tenant, array $account): array
    {
        $surface = $this->surface($account);
        $roles = $user instanceof PlatformUser ? $this->platformRoles($user) : $this->tenantRoles($user);
        $permissions = $user instanceof PlatformUser ? $this->platformPermissions($user) : $this->tenantPermissions($user);

        return [
            'surface' => $surface,
            'redirect_to' => match ($surface) {
                'platform' => '/platform/dashboard',
                'client_portal' => '/client/dashboard',
                default => '/tenant/dashboard',
            },
            'account' => [
                'account_type' => $account['account_type'],
                'auth_guard' => $account['guard'],
                'uuid' => $user->uuid,
                'display_name' => $user->display_name,
                'email' => $user->email,
                'status' => $user->status,
                'two_factor_enabled' => (bool) $user->two_factor_enabled,
                'roles' => $roles,
                'permissions' => $permissions,
            ],
            'tenant' => $tenant ? [
                'uuid' => $tenant->uuid,
                'slug' => $tenant->slug,
                'organization_name' => $tenant->organization_name,
                'status' => $tenant->status,
                'default_currency' => $tenant->default_currency,
                'default_timezone' => $tenant->default_timezone,
            ] : null,
            'modules' => $this->modules($surface, $permissions),
            'preferences' => [
                'timezone' => $user->timezone,
                'locale' => $user->locale,
            ],
        ];
    }

    private function surface(array $account): string
    {
        if (($account['guard'] ?? null) === 'platform') {
            return 'platform';
        }

        return ($account['account_type'] ?? null) === 'client' ? 'client_portal' : 'tenant';
    }

    private function modules(string $surface, array $permissions): array
    {
        if ($surface === 'platform') {
            return ['dashboard', 'tenants', 'subscriptions', 'billing', 'support', 'monitoring', 'settings'];
        }

        if ($surface === 'client_portal') {
            return ['profile', 'documents', 'issues'];
        }

        $map = ['dashboard' => 'dashboard.', 'crm' => 'client.', 'projects' => 'project.', 'finance' => 'finance.', 'hrms' => 'staff.', 'payroll' => 'payroll.', 'settings' => 'setting.'];

        return array_values(array_filter(array_keys($map), fn (string $module): bool => collect($permissions)->contains(fn (string $permission): bool => str_starts_with($permission, $map[$module]))));
    }

    private function platformRoles(PlatformUser $user): array
    {
        return DB::table('platform_model_has_roles')
            ->join('platform_roles', 'platform_roles.id', '=', 'platform_model_has_roles.role_id')
            ->where('platform_model_has_roles.model_type', PlatformUser::class)
            ->where('platform_model_has_roles.model_id', $user->id)
            ->pluck('platform_roles.name')
            ->all();
    }

    private function platformPermissions(PlatformUser $user): array
    {
        return DB::table('platform_model_has_roles')
            ->join('platform_role_has_permissions', 'platform_role_has_permissions.role_id', '=', 'platform_model_has_roles.role_id')
            ->join('platform_permissions', 'platform_permissions.id', '=', 'platform_role_has_permissions.permission_id')
            ->where('platform_model_has_roles.model_type', PlatformUser::class)
            ->where('platform_model_has_roles.model_id', $user->id)
            ->distinct()
            ->pluck('platform_permissions.name')
            ->all();
    }

    private function tenantRoles(User $user): array
    {
        return DB::table('model_has_roles')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('model_has_roles.tenant_id', $user->tenant_id)
            ->where('model_has_roles.model_type', User::class)
            ->where('model_has_roles.model_id', $user->id)
            ->pluck('roles.name')
            ->all();
    }

    private function tenantPermissions(User $user): array
    {
        return DB::table('model_has_roles')
            ->join('role_has_permissions', 'role_has_permissions.role_id', '=', 'model_has_roles.role_id')
            ->join('permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
            ->where('model_has_roles.tenant_id', $user->tenant_id)
            ->where('model_has_roles.model_type', User::class)
            ->where('model_has_roles.model_id', $user->id)
            ->distinct()
            ->pluck('permissions.name')
            ->all();
    }

    private function resetKey(array $account, string $email): string
    {
        return 'auth:'.$account['guard'].':'.$account['account_uuid'].':'.$email;
    }

    private function passwordResetUrl(string $email, string $token): string
    {
        return url('/reset-password?email='.urlencode($email).'&token='.urlencode($token));
    }

    private function resolveResetUser(string $key, string $email): PlatformUser|User|null
    {
        $parts = explode(':', $key);
        if (count($parts) >= 4 && $parts[0] === 'auth') {
            return $parts[1] === 'platform'
                ? PlatformUser::query()->where('uuid', $parts[2])->where('email', $email)->first()
                : User::query()->where('uuid', $parts[2])->where('email', $email)->first();
        }

        return PlatformUser::query()->where('email', $email)->first() ?? User::query()->where('email', $email)->first();
    }

    private function discoveryKey(string $token): string
    {
        return 'auth:discovery:'.$token;
    }

    private function challengeKey(string $token): string
    {
        return 'auth:2fa:'.$token;
    }
}