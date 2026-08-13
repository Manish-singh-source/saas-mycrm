<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Shared\BaseApiController;
use App\Mail\PasswordResetInstructions;
use App\Models\PlatformPermission;
use App\Models\PlatformRole;
use App\Models\PlatformUser;
use App\Services\Platform\PlatformAdminService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PlatformStaffController extends BaseApiController
{
    public function __construct(private readonly PlatformAdminService $admin) {}

    public function index(Request $request)
    {
        $query = PlatformUser::query()->withCount(['platformRoles as roles_count']);
        if ($request->filled('search')) {
            $search = '%'.$request->string('search').'%';
            $query->where(fn ($q) => $q->where('display_name', 'like', $search)->orWhere('email', 'like', $search)->orWhere('employee_code', 'like', $search));
        }
        foreach (['status', 'department'] as $field) if ($request->filled('filter.'.$field)) $query->where($field, $request->input('filter.'.$field));
        $paginator = $query->latest('id')->paginate((int) $request->integer('per_page', 25));

        return $this->list($paginator->items(), $paginator);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $password = $data['password'] ?? Str::password(16);
        $user = DB::transaction(function () use ($request, $data, $password) {
            $user = PlatformUser::query()->create([...$data, 'uuid' => (string) Str::uuid(), 'password' => Hash::make($password), 'display_name' => $data['display_name'] ?? trim($data['first_name'].' '.($data['last_name'] ?? '')), 'status' => $data['status'] ?? 'active']);
            if (($roleUuids = $this->requestedUuids($request, 'role_uuids', 'role_ids')) !== null) {
                $this->syncUserRoles($request, $user, $roleUuids);
            }
            if (($teamUuids = $this->requestedUuids($request, 'team_uuids', 'team_ids')) !== null) {
                $this->syncUserTeams($request, $user, $teamUuids);
            }
            $this->admin->audit($request, 'platform_user_created', PlatformUser::class, $user->id, null, $user->fresh()->toArray());
            return $user->fresh();
        });

        return $this->success([...$this->staffPayload($user), 'temporary_password' => app()->isLocal() ? $password : null], 'Platform staff created.', 201);
    }

    public function invite(Request $request)
    {
        $response = $this->store($request);
        $email = $request->input('email');
        $token = Str::random(64);
        DB::table('password_reset_tokens')->updateOrInsert(['email' => 'platform:'.$email], ['token' => Hash::make($token), 'created_at' => now()]);
        Mail::to($email)->send(new PasswordResetInstructions($email, $token, url('/reset-password?surface=platform&email='.urlencode($email).'&token='.urlencode($token)), 'platform'));

        return $response;
    }

    public function show(string $platform_user_uuid)
    {
        $user = PlatformUser::query()->where('uuid', $platform_user_uuid)->firstOrFail();
        return $this->success($this->staffPayload($user));
    }

    public function update(Request $request, string $platform_user_uuid)
    {
        $user = PlatformUser::query()->where('uuid', $platform_user_uuid)->firstOrFail();
        $old = $user->toArray();
        $data = $this->validated($request, $user);
        unset($data['password']);
        $user = DB::transaction(function () use ($request, $user, $old, $data) {
            $user->fill($data)->save();
            if (($roleUuids = $this->requestedUuids($request, 'role_uuids', 'role_ids')) !== null) {
                $this->syncUserRoles($request, $user, $roleUuids);
            }
            if (($teamUuids = $this->requestedUuids($request, 'team_uuids', 'team_ids')) !== null) {
                $this->syncUserTeams($request, $user, $teamUuids);
            }
            $this->admin->audit($request, 'platform_user_updated', PlatformUser::class, $user->id, $old, $user->fresh()->toArray());
            return $user->fresh();
        });
        return $this->success($this->staffPayload($user), 'Platform staff updated.');
    }

    public function destroy(Request $request, string $platform_user_uuid)
    {
        $user = PlatformUser::query()->where('uuid', $platform_user_uuid)->firstOrFail();
        $old = $user->toArray(); $user->delete();
        $this->admin->audit($request, 'platform_user_deleted', PlatformUser::class, $user->id, $old);
        return $this->success(null, 'Platform staff deleted.');
    }

    public function restore(Request $request, string $platform_user_uuid)
    {
        $user = PlatformUser::withTrashed()->where('uuid', $platform_user_uuid)->firstOrFail();
        $old = $user->toArray(); $user->restore();
        $this->admin->audit($request, 'platform_user_restored', PlatformUser::class, $user->id, $old, $user->fresh()->toArray());
        return $this->success(['user' => $user->fresh()], 'Platform staff restored.');
    }

    public function suspend(Request $request, string $platform_user_uuid) { return $this->status($request, $platform_user_uuid, 'suspended', 'platform_user_suspended'); }
    public function activate(Request $request, string $platform_user_uuid) { return $this->status($request, $platform_user_uuid, 'active', 'platform_user_activated'); }

    public function resetPassword(Request $request, string $platform_user_uuid)
    {
        $user = PlatformUser::query()->where('uuid', $platform_user_uuid)->firstOrFail();
        $token = Str::random(64);
        DB::table('password_reset_tokens')->updateOrInsert(['email' => 'platform:'.$user->email], ['token' => Hash::make($token), 'created_at' => now()]);
        Mail::to($user->email)->send(new PasswordResetInstructions($user->email, $token, url('/reset-password?surface=platform&email='.urlencode($user->email).'&token='.urlencode($token)), 'platform'));
        $this->admin->audit($request, 'platform_user_password_reset_requested', PlatformUser::class, $user->id);
        return $this->success(['sent' => true, 'reset_token' => app()->isLocal() ? $token : null], 'Password reset email sent.');
    }

    public function forceLogout(Request $request, string $platform_user_uuid)
    {
        $user = PlatformUser::query()->where('uuid', $platform_user_uuid)->firstOrFail();
        $user->tokens()->delete();
        $this->admin->audit($request, 'platform_user_force_logout', PlatformUser::class, $user->id);
        return $this->success(['revoked' => true]);
    }

    public function require2fa(Request $request, string $platform_user_uuid)
    {
        $user = PlatformUser::query()->where('uuid', $platform_user_uuid)->firstOrFail();
        $old = $user->toArray();
        $user->forceFill(['two_factor_required' => true])->save();
        $user->tokens()->delete();
        $this->admin->audit($request, 'platform_user_require_2fa', PlatformUser::class, $user->id, $old, $user->fresh()->toArray());
        return $this->success(['required' => true], '2FA required.');
    }

    public function roles(string $platform_user_uuid) { return $this->success(['roles' => PlatformUser::query()->where('uuid', $platform_user_uuid)->firstOrFail()->platformRoles()->get()]); }

    public function teams(string $platform_user_uuid)
    {
        $user = PlatformUser::query()->where('uuid', $platform_user_uuid)->firstOrFail();
        return $this->success(['teams' => $this->userTeams($user)]);
    }

    public function syncRoles(Request $request, string $platform_user_uuid)
    {
        $data = $request->validate([
            'role_uuids' => ['nullable', 'array'],
            'role_uuids.*' => ['uuid'],
            'role_ids' => ['nullable', 'array'],
            'role_ids.*' => ['uuid'],
        ]);
        $user = PlatformUser::query()->where('uuid', $platform_user_uuid)->firstOrFail();
        $this->syncUserRoles($request, $user, $data['role_uuids'] ?? $data['role_ids'] ?? []);
        return $this->success(['roles' => $user->platformRoles()->get()]);
    }

    public function syncTeams(Request $request, string $platform_user_uuid)
    {
        $data = $request->validate([
            'team_uuids' => ['nullable', 'array'],
            'team_uuids.*' => ['uuid'],
            'team_ids' => ['nullable', 'array'],
            'team_ids.*' => ['uuid'],
        ]);
        $user = PlatformUser::query()->where('uuid', $platform_user_uuid)->firstOrFail();
        $this->syncUserTeams($request, $user, $data['team_uuids'] ?? $data['team_ids'] ?? []);
        return $this->success(['teams' => $this->userTeams($user)]);
    }

    public function permissions(string $platform_user_uuid) { return $this->success(['permissions' => PlatformUser::query()->where('uuid', $platform_user_uuid)->firstOrFail()->platformDirectPermissions()->get()]); }

    public function syncPermissions(Request $request, string $platform_user_uuid)
    {
        $data = $request->validate(['permission_uuids' => ['required', 'array'], 'permission_uuids.*' => ['uuid']]);
        $user = PlatformUser::query()->where('uuid', $platform_user_uuid)->firstOrFail();
        $ids = PlatformPermission::query()->whereIn('uuid', $data['permission_uuids'])->pluck('id')->all();
        $user->platformDirectPermissions()->syncWithPivotValues($ids, ['model_type' => PlatformUser::class]);
        $this->admin->audit($request, 'platform_user_permissions_synced', PlatformUser::class, $user->id, null, ['permission_ids' => $ids]);
        return $this->success(['permissions' => $user->platformDirectPermissions()->get()]);
    }

    public function activity(string $platform_user_uuid)
    {
        $user = PlatformUser::query()->where('uuid', $platform_user_uuid)->firstOrFail();
        return $this->success(['activity' => DB::table('activity_logs')->where('actor_platform_user_id', $user->id)->latest('id')->limit(100)->get()]);
    }

    public function export()
    {
        $rows = PlatformUser::query()->get(['uuid', 'employee_code', 'display_name', 'email', 'status', 'created_at']);
        $csv = "uuid,employee_code,display_name,email,status,created_at\n";
        foreach ($rows as $row) $csv .= implode(',', array_map(fn ($v) => '"'.str_replace('"', '""', (string) $v).'"', $row->toArray()))."\n";
        $path = 'platform/exports/platform-users-'.now()->format('YmdHis').'.csv';
        Storage::disk(config('filesystems.default', 'local'))->put($path, $csv);
        return $this->success(['export' => ['filename' => basename($path), 'size_bytes' => strlen($csv)]], 'Platform staff export created.', 201);
    }

    private function validated(Request $request, ?PlatformUser $user = null): array
    {
        $data = $request->validate([
            'employee_code' => ['nullable', 'string', 'max:50', Rule::unique('platform_users')->ignore($user?->id)],
            'first_name' => [$user ? 'sometimes' : 'required', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'display_name' => ['nullable', 'string', 'max:200'],
            'email' => [$user ? 'sometimes' : 'required', 'email', 'max:150', Rule::unique('platform_users')->ignore($user?->id)],
            'mobile' => ['nullable', 'string', 'max:20'],
            'password' => ['nullable', 'string', 'min:8'],
            'profile_photo_file_id' => ['nullable'],
            'designation' => ['nullable', 'string', 'max:100'],
            'department' => ['nullable', 'string', 'max:100'],
            'timezone' => ['nullable', 'string', 'max:100'],
            'locale' => ['nullable', 'string', 'max:20'],
            'two_factor_enabled' => ['nullable', 'boolean'],
            'status' => ['nullable', Rule::in(['active', 'inactive', 'suspended'])],
        ]);
        if (array_key_exists('profile_photo_file_id', $data)) {
            $data['profile_photo_file_id'] = $this->fileId($data['profile_photo_file_id']);
        }
        return $data;
    }

    private function staffPayload(PlatformUser $user): array
    {
        return [
            'user' => $user,
            'roles' => $user->platformRoles()->get(),
            'teams' => $this->userTeams($user),
            'permissions' => $user->platformDirectPermissions()->get(),
        ];
    }

    private function requestedUuids(Request $request, string $primary, string $alias): ?array
    {
        if (! $request->has($primary) && ! $request->has($alias)) {
            return null;
        }
        $values = $request->input($primary, $request->input($alias, []));
        if (! is_array($values)) {
            $values = array_map('trim', explode(',', (string) $values));
        }
        return array_values(array_filter($values));
    }

    private function fileId($value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_numeric($value)) {
            return (int) $value;
        }
        return DB::table('files')->where('uuid', $value)->value('id');
    }

    private function syncUserRoles(Request $request, PlatformUser $user, array $roleUuids): void
    {
        $ids = PlatformRole::query()->whereIn('uuid', $roleUuids)->pluck('id')->all();
        $user->platformRoles()->syncWithPivotValues($ids, ['model_type' => PlatformUser::class]);
        $this->admin->audit($request, 'platform_user_roles_synced', PlatformUser::class, $user->id, null, ['role_ids' => $ids]);
    }

    private function syncUserTeams(Request $request, PlatformUser $user, array $teamUuids): void
    {
        $teamIds = DB::table('platform_teams')
            ->whereIn('uuid', $teamUuids)
            ->whereNull('deleted_at')
            ->pluck('id')
            ->all();

        $membership = DB::table('platform_team_members')->where('platform_user_id', $user->id);
        if ($teamIds === []) {
            $membership->delete();
        } else {
            $membership->whereNotIn('platform_team_id', $teamIds)->delete();
        }

        foreach ($teamIds as $teamId) {
            DB::table('platform_team_members')->updateOrInsert(
                ['platform_team_id' => $teamId, 'platform_user_id' => $user->id],
                ['status' => 'active', 'joined_at' => now()->toDateString(), 'updated_at' => now(), 'created_at' => now()]
            );
        }

        $this->admin->audit($request, 'platform_user_teams_synced', PlatformUser::class, $user->id, null, ['team_ids' => $teamIds]);
    }

    private function userTeams(PlatformUser $user)
    {
        return DB::table('platform_team_members')
            ->join('platform_teams', 'platform_teams.id', '=', 'platform_team_members.platform_team_id')
            ->where('platform_team_members.platform_user_id', $user->id)
            ->whereNull('platform_teams.deleted_at')
            ->orderBy('platform_teams.name')
            ->get([
                'platform_teams.uuid',
                'platform_teams.name',
                'platform_teams.code',
                'platform_teams.status',
                'platform_team_members.joined_at',
                'platform_team_members.status as membership_status',
            ]);
    }

    private function status(Request $request, string $uuid, string $status, string $event)
    {
        $user = PlatformUser::query()->where('uuid', $uuid)->firstOrFail();
        $old = $user->toArray();
        $user->forceFill(['status' => $status])->save();
        if ($status !== 'active') $user->tokens()->delete();
        $this->admin->audit($request, $event, PlatformUser::class, $user->id, $old, $user->fresh()->toArray());
        return $this->success(['user' => $user->fresh()], 'Platform staff status updated.');
    }
}
