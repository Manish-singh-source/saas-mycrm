<?php
namespace App\Http\Controllers;

use App\Jobs\SendPlatformSuspensionEmail;
use App\Http\Requests\AssignRoleUsersRequest;
use App\Http\Requests\ExportPlatformUsersRequest;
use App\Http\Requests\ListPlatformUsersRequest;
use App\Http\Requests\StorePlatformUserRequest;
use App\Http\Requests\SyncPlatformUserPermissionsRequest;
use App\Http\Requests\SyncPlatformUserRolesRequest;
use App\Http\Requests\SyncPlatformUserTeamsRequest;
use App\Http\Requests\UpdatePlatformUserRequest;
use App\Models\ActivityLog;
use App\Models\PasswordResetToken;
use App\Models\NotificationTemplate;
use App\Models\PlatformDepartment;
use App\Models\PlatformDesignation;
use App\Models\PlatformPermission;
use App\Models\PlatformRole;
use App\Models\PlatformTeam;
use App\Models\PlatformUser;
use App\Support\ActivityLogger;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class PlatformUserController extends Controller
{
    private const EXPORT_COLUMNS = ['uuid','employee_code','display_name','email','status','created_at'];

    public function index(ListPlatformUsersRequest $request): mixed
    {
        $input = $request->validated();
        $query = PlatformUser::query()->orderByDesc('created_at');
        if (! empty($input['search'])) $query->where(fn ($q) => $q->where('display_name', 'like', '%'.$input['search'].'%')->orWhere('email', 'like', '%'.$input['search'].'%')->orWhere('employee_code', 'like', '%'.$input['search'].'%'));
        foreach ($input['filter'] ?? [] as $column => $value) if (in_array($column, ['status', 'department'], true)) $query->where($column === 'department' ? 'department_id' : $column, $value);

        $kpiRows = (clone $query)->withCount(['roles', 'teams', 'permissions as direct_permissions_count'])->get();
        $kpis = [
            'total' => $kpiRows->count(),
            'active' => $kpiRows->where('status', 'active')->count(),
            'inactive' => $kpiRows->where('status', 'inactive')->count(),
            'suspended' => $kpiRows->where('status', 'suspended')->count(),
            'two_factor_enabled' => $kpiRows->where('two_factor_enabled', true)->count(),
            'two_factor_required' => $kpiRows->where('two_factor_required', true)->count(),
            'with_department' => $kpiRows->whereNotNull('department_id')->count(),
            'without_department' => $kpiRows->whereNull('department_id')->count(),
            'with_designation' => $kpiRows->whereNotNull('designation_id')->count(),
            'without_designation' => $kpiRows->whereNull('designation_id')->count(),
            'with_manager' => $kpiRows->whereNotNull('manager_id')->count(),
            'without_manager' => $kpiRows->whereNull('manager_id')->count(),
            'role_assignments' => $kpiRows->sum('roles_count'),
            'team_assignments' => $kpiRows->sum('teams_count'),
            'direct_permissions' => $kpiRows->sum('direct_permissions_count'),
        ];

        $paginator = (clone $query)->with(['roles', 'teams', 'department', 'designation', 'manager', 'profilePhotoFile'])->withCount(['permissions as direct_permissions_count'])->paginate($request->integer('per_page', 25))->withQueryString();
        $users = $paginator->getCollection()->map(fn (PlatformUser $user) => $this->decorateUser($user))->all();
        return ApiResponse::success($users, 'Platform users fetched.', 200, ['current_page' => $paginator->currentPage(), 'per_page' => $paginator->perPage(), 'total' => $paginator->total(), 'last_page' => $paginator->lastPage(), 'kpis' => $kpis]);
    }

    public function store(StorePlatformUserRequest $request): mixed
    {
        $user = $this->saveUser($request->validated());
        return ApiResponse::success(['user' => $this->loadUser($user)], 'Platform user created successfully.', 201);
    }

    public function invite(StorePlatformUserRequest $request): mixed
    {
        $input = $request->validated();
        $temporaryPassword = $input['password'] ?? Str::random(24);
        $user = PlatformUser::withTrashed()->where('email', $input['email'])->first();

        if ($user?->trashed()) {
            $user->restore();
            $this->fillUser($user, array_merge($input, ['status' => $input['status'] ?? 'active']))->save();
            $user->password = Hash::make($temporaryPassword);
            $user->save();
            if (array_key_exists('role_uuids', $input) || array_key_exists('role_ids', $input)) $this->syncUserRoles($user, $input);
            if (array_key_exists('team_uuids', $input) || array_key_exists('team_ids', $input)) $this->syncUserTeams($user, $input);
        } elseif (! $user) {
            $user = $this->saveUser(array_merge($input, ['password' => $temporaryPassword]));
        } else {
            abort(409, 'A platform user with this email already exists.');
        }

        $token = Str::random(64);
        PasswordResetToken::updateOrCreate(['email' => $user->email], ['token' => json_encode(['hash' => Hash::make($token), 'targets' => [['platform', $user->id]]]), 'created_at' => now()]);
        $sent = false;
        if (($input['send_invite'] ?? true) === true) {
            $this->sendPlatformInvitationEmail($user, $token);
            $sent = true;
        }
        $data = ['user' => $this->loadUser($user), 'sent' => $sent];
        if (app()->environment('local')) $data['reset_token'] = $token;
        return ApiResponse::success($data, 'Platform user invited successfully.', 201);
    }

    private function sendPlatformInvitationEmail(PlatformUser $user, string $token): void
    {
        $template = NotificationTemplate::query()->whereNull('tenant_id')->where('channel', 'email')->where('code', 'platform_user.invitation')->where('status', 'active')->latest('id')->first();
        $inviteUrl = rtrim((string) env('FRONTEND_URL', 'http://localhost:5173'), '/') . '/auth/password/reset?email=' . rawurlencode((string) $user->email) . '&token=' . rawurlencode($token);
        $variables = [
            '{{display_name}}' => (string) ($user->display_name ?: trim($user->first_name . ' ' . ($user->last_name ?? ''))),
            '{{email}}' => (string) $user->email,
            '{{invite_url}}' => $inviteUrl,
            '{{app_name}}' => (string) config('app.name'),
        ];
        $subject = $template?->subject ?: 'You are invited to {{app_name}}';
        $body = $template?->body ?: "Hello {{display_name}},\n\nYou have been invited to {{app_name}}.\nSet your password here: {{invite_url}}\n\nIf you were not expecting this invitation, you can ignore this email.";
        Mail::raw(strtr($body, $variables), function ($message) use ($user, $subject, $variables): void {
            $message->to((string) $user->email, (string) ($user->display_name ?: $user->email))->subject(strtr($subject, $variables));
        });
    }
    private function sendPlatformPasswordResetEmail(PlatformUser $user, string $token): void
    {
        $template = NotificationTemplate::query()
            ->whereNull('tenant_id')
            ->where('channel', 'email')
            ->where('code', 'platform_user.password_reset')
            ->where('status', 'active')
            ->latest('id')
            ->first();

        $resetUrl = rtrim((string) env('FRONTEND_URL', 'http://localhost:5173'), '/') . '/auth/password/reset?email=' . rawurlencode((string) $user->email) . '&token=' . rawurlencode($token);
        $variables = [
            '{{display_name}}' => (string) ($user->display_name ?: trim($user->first_name . ' ' . ($user->last_name ?? ''))),
            '{{email}}' => (string) $user->email,
            '{{reset_url}}' => $resetUrl,
            '{{app_name}}' => (string) config('app.name'),
        ];
        $subject = $template?->subject ?: 'Reset your {{app_name}} password';
        $body = $template?->body ?: "Hello {{display_name}},\n\nA password reset was requested for your {{app_name}} account.\nReset it here: {{reset_url}}\n\nIf you did not request this, you can ignore this email.";

        Mail::raw(strtr($body, $variables), function ($message) use ($user, $subject, $variables): void {
            $message->to((string) $user->email, (string) ($user->display_name ?: $user->email))
                ->subject(strtr($subject, $variables));
        });
    }
    public function show(string $uuid): mixed
    {
        $user = $this->findUser($uuid);
        if (! $user) return ApiResponse::error('Platform user not found.', 404, null, 'PLATFORM_USER_NOT_FOUND');
        return ApiResponse::success(['user' => $this->loadUser($user, true)], 'Platform user fetched.');
    }

    public function update(UpdatePlatformUserRequest $request, string $uuid): mixed
    {
        $user = $this->findUser($uuid);
        if (! $user) return ApiResponse::error('Platform user not found.', 404, null, 'PLATFORM_USER_NOT_FOUND');
        $input = $request->validated();
        $this->fillUser($user, $input)->save();
        if (array_key_exists('role_uuids', $input) || array_key_exists('role_ids', $input)) $this->syncUserRoles($user, $input);
        if (array_key_exists('team_uuids', $input) || array_key_exists('team_ids', $input)) $this->syncUserTeams($user, $input);
        return ApiResponse::success(['user' => $this->loadUser($user)], 'Platform user updated successfully.');
    }

    public function destroy(Request $request, string $uuid): mixed
    {
        $user = $this->findUser($uuid);
        if (! $user) return ApiResponse::error('Platform user not found.', 404, null, 'PLATFORM_USER_NOT_FOUND');
        $user->delete();
        ActivityLogger::record($request, 'platform_user.deleted', $user, 'Platform user deleted.');
        return ApiResponse::success(null, 'Platform user deleted successfully.');
    }

    public function restore(Request $request, string $uuid): mixed
    {
        $user = PlatformUser::withTrashed()->where('uuid', $uuid)->first();
        if (! $user) return ApiResponse::error('Platform user not found.', 404, null, 'PLATFORM_USER_NOT_FOUND');
        $user->restore();
        ActivityLogger::record($request, 'platform_user.restored', $user, 'Platform user restored.');
        return ApiResponse::success(['user' => $this->loadUser($user)], 'Platform user restored successfully.');
    }

    public function suspend(Request $request, string $uuid): mixed
    {
        $user = $this->findUser($uuid);
        if (! $user) return ApiResponse::error('Platform user not found.', 404, null, 'PLATFORM_USER_NOT_FOUND');
        $user->forceFill(['status' => 'suspended'])->save();
        if ($request->boolean('notify_user')) SendPlatformSuspensionEmail::dispatch($user, $request->input('reason'), $request->input('effective_until'));
        $user->tokens()->delete();
        ActivityLogger::record($request, 'platform_user.suspended', $user, 'Platform user suspended.', [
            'reason' => $request->input('reason'),
            'effective_until' => $request->input('effective_until'),
            'revoke_sessions' => $request->boolean('revoke_sessions', true),
            'notify_user' => $request->boolean('notify_user', false),
        ]);
        return ApiResponse::success(['user' => $this->loadUser($user)], 'Platform user suspended successfully.');
    }

    public function activate(Request $request, string $uuid): mixed
    {
        $user = $this->findUser($uuid);
        if (! $user) return ApiResponse::error('Platform user not found.', 404, null, 'PLATFORM_USER_NOT_FOUND');
        $user->forceFill(['status' => 'active'])->save();
        ActivityLogger::record($request, 'platform_user.activated', $user, 'Platform user activated.', [
            'audit_reason' => $request->input('audit_reason', $request->input('reason')),
        ]);
        return ApiResponse::success(['user' => $this->loadUser($user)], 'Platform user activated successfully.');
    }

    public function resetPassword(Request $request, string $uuid): mixed
    {
        $user = $this->findUser($uuid);
        if (! $user) return ApiResponse::error('Platform user not found.', 404, null, 'PLATFORM_USER_NOT_FOUND');
        $token = Str::random(64);
        PasswordResetToken::updateOrCreate(
            ['email' => $user->email],
            ['token' => json_encode(['hash' => Hash::make($token), 'targets' => [['platform', $user->id]]]), 'created_at' => now()]
        );
        $this->sendPlatformPasswordResetEmail($user, $token);
        ActivityLogger::record($request, 'platform_user.password_reset_requested', $user, 'Platform password reset instructions generated.', [
            'audit_reason' => $request->input('audit_reason', $request->input('reason')),
        ]);
        $data = ['sent' => true];
        if (app()->environment('local')) $data['reset_token'] = $token;
        return ApiResponse::success($data, 'Password reset instructions sent.');
    }

    public function forceLogout(Request $request, string $uuid): mixed
    {
        $user = $this->findUser($uuid);
        if (! $user) return ApiResponse::error('Platform user not found.', 404, null, 'PLATFORM_USER_NOT_FOUND');
        $user->tokens()->delete();
        ActivityLogger::record($request, 'platform_user.force_logout', $user, 'All platform user sessions were revoked.', [
            'audit_reason' => $request->input('audit_reason', $request->input('reason')),
        ]);
        return ApiResponse::success(null, 'Platform user logged out from all sessions.');
    }

    public function requireTwoFactor(Request $request, string $uuid): mixed
    {
        $user = $this->findUser($uuid);
        if (! $user) return ApiResponse::error('Platform user not found.', 404, null, 'PLATFORM_USER_NOT_FOUND');
        $attributes = ['two_factor_required' => true];
        if (! $user->two_factor_enabled) {
            $attributes['two_factor_secret'] = null;
            $attributes['two_factor_recovery_codes'] = null;
            $attributes['two_factor_confirmed_at'] = null;
        }
        $user->forceFill($attributes)->save();
        $user->tokens()->delete();
        ActivityLogger::record($request, 'platform_user.two_factor_required', $user, 'Two-factor authentication is now required for this user.', [
            'enforcement_date' => $request->input('enforcement_date'),
            'notify_user' => $request->boolean('notify_user', false),
        ]);
        return ApiResponse::success(['user' => $this->loadUser($user)], 'Two-factor authentication is now required.');
    }
    public function roles(string $uuid): mixed
    {
        $user = $this->findUser($uuid);
        if (! $user) return ApiResponse::error('Platform user not found.', 404, null, 'PLATFORM_USER_NOT_FOUND');
        return ApiResponse::success(['roles' => $user->roles()->orderBy('name')->get()], 'Platform user roles fetched.');
    }

    public function syncRoles(SyncPlatformUserRolesRequest $request, string $uuid): mixed
    {
        $user = $this->findUser($uuid);
        if (! $user) return ApiResponse::error('Platform user not found.', 404, null, 'PLATFORM_USER_NOT_FOUND');
        $user->roles()->sync($this->idsFromInput($request->validated(), 'role_uuids', 'role_ids', PlatformRole::class));
        return ApiResponse::success(['roles' => $user->roles()->orderBy('name')->get()], 'Platform user roles synchronized.');
    }

    public function teams(string $uuid): mixed
    {
        $user = $this->findUser($uuid);
        if (! $user) return ApiResponse::error('Platform user not found.', 404, null, 'PLATFORM_USER_NOT_FOUND');
        return ApiResponse::success(['teams' => $user->teams()->with(['department', 'lead', 'assistantLead'])->withPivot(['joined_at', 'status'])->orderBy('name')->get()], 'Platform user teams fetched.');
    }

    public function syncTeams(SyncPlatformUserTeamsRequest $request, string $uuid): mixed
    {
        $user = $this->findUser($uuid);
        if (! $user) return ApiResponse::error('Platform user not found.', 404, null, 'PLATFORM_USER_NOT_FOUND');
        $user->teams()->sync($this->idsFromInput($request->validated(), 'team_uuids', 'team_ids', PlatformTeam::class));
        return ApiResponse::success(['teams' => $user->teams()->with(['department', 'lead', 'assistantLead'])->withPivot(['joined_at', 'status'])->orderBy('name')->get()], 'Platform user teams synchronized.');
    }

    public function permissions(string $uuid): mixed
    {
        $user = $this->findUser($uuid);
        if (! $user) return ApiResponse::error('Platform user not found.', 404, null, 'PLATFORM_USER_NOT_FOUND');
        return ApiResponse::success(['permissions' => $user->permissions()->with(['roles'])->orderBy('module')->orderBy('name')->get()], 'Direct platform permissions fetched.');
    }

    public function syncPermissions(SyncPlatformUserPermissionsRequest $request, string $uuid): mixed
    {
        $user = $this->findUser($uuid);
        if (! $user) return ApiResponse::error('Platform user not found.', 404, null, 'PLATFORM_USER_NOT_FOUND');
        $user->permissions()->sync($this->idsFromInput($request->validated(), 'permission_uuids', 'permission_ids', PlatformPermission::class));
        return ApiResponse::success(['permissions' => $user->permissions()->with(['roles'])->orderBy('module')->orderBy('name')->get()], 'Direct platform permissions synchronized.');
    }

    public function usersActivity(string $uuid): mixed
    {
        $user = $this->findUser($uuid);
        if (! $user) return ApiResponse::error('Platform user not found.', 404, null, 'PLATFORM_USER_NOT_FOUND');
        $activity = ActivityLog::where('actor_platform_user_id', $user->id)->latest('created_at')->limit(100)->get();
        return ApiResponse::success($activity, 'Platform user activity fetched.');
    }

    public function export(ExportPlatformUsersRequest $request): mixed
    {
        $users = PlatformUser::orderBy('created_at')->get(self::EXPORT_COLUMNS);
        $handle = fopen('php://temp', 'w+'); fputcsv($handle, self::EXPORT_COLUMNS);
        foreach ($users as $user) fputcsv($handle, $user->only(self::EXPORT_COLUMNS));
        rewind($handle); $csv = stream_get_contents($handle); fclose($handle);
        $filename = 'platform-users-'.now()->format('YmdHis').'-'.Str::random(6).'.csv';
        $path = 'platform-exports/'.$filename;
        Storage::disk(config('filesystems.default'))->put($path, $csv);
        return ApiResponse::success(['export' => ['filename' => $filename, 'path' => $path, 'size' => strlen($csv), 'disk' => config('filesystems.default')]], 'Platform user export created.');
    }

    private function saveUser(array $input): PlatformUser
    {
        $user = new PlatformUser();
        $this->fillUser($user, $input);
        $user->password = Hash::make($input['password'] ?? Str::random(24));
        $user->save();
        if (array_key_exists('role_uuids', $input) || array_key_exists('role_ids', $input)) $this->syncUserRoles($user, $input);
        if (array_key_exists('team_uuids', $input) || array_key_exists('team_ids', $input)) $this->syncUserTeams($user, $input);
        return $user;
    }

    private function fillUser(PlatformUser $user, array $input): PlatformUser
    {
        $data = collect($input)->only(['first_name', 'last_name', 'email', 'employee_code', 'mobile', 'timezone', 'locale', 'two_factor_enabled', 'status', 'profile_photo_file_id'])->all();
        if (array_key_exists('display_name', $input)) $data['display_name'] = $input['display_name'];
        elseif (! $user->exists) $data['display_name'] = trim($input['first_name'].' '.($input['last_name'] ?? ''));

        if (array_key_exists('designation_uuid', $input)) $data['designation_id'] = $input['designation_uuid'] ? PlatformDesignation::where('uuid', $input['designation_uuid'])->value('id') : null;
        elseif (array_key_exists('designation', $input)) $data['designation_id'] = $input['designation'];

        if (array_key_exists('department_uuid', $input)) $data['department_id'] = $input['department_uuid'] ? PlatformDepartment::where('uuid', $input['department_uuid'])->value('id') : null;
        elseif (array_key_exists('department', $input)) $data['department_id'] = $input['department'];

        if (array_key_exists('manager_uuid', $input)) $data['manager_id'] = $input['manager_uuid'] ? PlatformUser::where('uuid', $input['manager_uuid'])->value('id') : null;
        elseif (array_key_exists('manager_id', $input)) $data['manager_id'] = $input['manager_id'];

        return $user->forceFill($data);
    }

    private function loadUser(PlatformUser $user, bool $full = false): PlatformUser
    {
        $loaded = $user->fresh()->load(['roles', 'teams', 'permissions', 'department', 'designation', 'manager', 'subordinates', 'profilePhotoFile'])->loadCount(['permissions as direct_permissions_count']);
        return $this->decorateUser($loaded);
    }

    private function decorateUser(PlatformUser $user): PlatformUser
    {
        $file = $user->profilePhotoFile;
        if (! $file) {
            $user->setAttribute('profile_photo_url', $user->profile_photo);
            return $user;
        }
        $disk = Storage::disk($file->disk);
        try {
            $url = $file->visibility === 'public' ? $disk->url($file->path) : $disk->temporaryUrl($file->path, now()->addMinutes(10));
        } catch (\Throwable) {
            $url = $disk->url($file->path);
        }
        if (app()->bound('request')) {
            $parts = parse_url($url);
            if (is_array($parts) && isset($parts['path'])) {
                $url = request()->getSchemeAndHttpHost().$parts['path'].(isset($parts['query']) ? '?'.$parts['query'] : '');
            }
        }
        $user->setAttribute('profile_photo_url', $url);
        return $user;
    }

    private function findUser(string $uuid): ?PlatformUser { return PlatformUser::where('uuid', $uuid)->first(); }

    private function idsFromInput(array $input, string $uuidKey, string $idKey, string $model): array
    {
        if (array_key_exists($uuidKey, $input)) return $model::whereIn('uuid', $input[$uuidKey] ?? [])->pluck('id')->all();
        return $input[$idKey] ?? [];
    }

    private function syncUserRoles(PlatformUser $user, array $input): void { $user->roles()->sync($this->idsFromInput($input, 'role_uuids', 'role_ids', PlatformRole::class)); }
    private function syncUserTeams(PlatformUser $user, array $input): void { $user->teams()->sync($this->idsFromInput($input, 'team_uuids', 'team_ids', PlatformTeam::class)); }
}
