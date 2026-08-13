<?php

namespace App\Http\Controllers\Tenant;

use App\Models\User;
use App\Services\Tenant\TenantWorkspaceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class TenantStaffController extends BaseTenantController
{
    private const CHILDREN = [
        'employment-history' => ['table' => 'staff_employment_history', 'permission' => 'staff.edit', 'fields' => ['department_id', 'designation_id', 'office_id', 'effective_from', 'effective_to']],
        'bank-accounts' => ['table' => 'staff_bank_accounts', 'permission' => 'staff.manage_bank', 'fields' => ['account_holder_name', 'bank_name', 'account_number', 'ifsc_code', 'is_primary']],
        'salary-structures' => ['table' => 'staff_salary_structures', 'permission' => 'staff.manage_salary', 'fields' => ['effective_from', 'effective_to', 'annual_ctc', 'monthly_gross', 'currency']],
        'documents' => ['table' => 'staff_documents', 'permission' => 'staff.edit', 'fields' => ['document_type_id', 'file_id', 'document_number', 'expiry_date']],
        'emergency-contacts' => ['table' => 'staff_emergency_contacts', 'permission' => 'staff.edit', 'fields' => ['name', 'relation', 'mobile', 'email', 'address']],
        'assets' => ['table' => 'staff_assets', 'permission' => 'staff.edit', 'fields' => ['asset_name', 'asset_code', 'issued_at', 'returned_at', 'status']],
        'certifications' => ['table' => 'staff_certifications', 'permission' => 'staff.edit', 'fields' => ['name', 'provider', 'issued_on', 'expires_on', 'file_id']],
        'appraisals' => ['table' => 'staff_appraisals', 'permission' => 'staff.edit', 'fields' => ['review_period', 'rating', 'reviewed_by', 'reviewed_at']],
        'training' => ['table' => 'staff_training', 'permission' => 'staff.edit', 'fields' => ['training_name', 'provider', 'started_on', 'completed_on', 'status']],
    ];

    public function __construct(private readonly TenantWorkspaceService $tenant) {}

    public function dashboard(): JsonResponse
    {
        $tenantId = app(\App\Tenancy\TenantContext::class)->id();

        return $this->success(['dashboard' => [
            'cards' => [
                'active_staff' => $this->tenant->count('staff', ['employment_status' => 'active']),
                'new_joiners_this_month' => $this->dateCount('staff', 'joining_date', now()->startOfMonth(), now()->endOfMonth()),
                'exits_this_month' => $this->dateCount('staff', 'exit_date', now()->startOfMonth(), now()->endOfMonth()),
                'pending_documents' => $this->tenant->count('staff_documents'),
                'pending_leave_approvals' => $this->tenant->count('leave_requests'),
                'present_today' => Schema::hasTable('attendance_records') ? DB::table('attendance_records')->where('tenant_id', $tenantId)->whereDate('attendance_date', now()->toDateString())->whereNotNull('check_in_at')->count() : 0,
            ],
            'charts' => [
                'department_headcount' => $this->grouped('staff', 'department_id'),
                'employment_status' => $this->grouped('staff', 'employment_status'),
                'attendance_trend' => $this->grouped('attendance_records', 'attendance_date'),
                'leave_usage' => $this->grouped('leave_requests', 'start_date'),
            ],
            'pending' => [
                'expiring_certifications' => $this->rows('staff_certifications', ['id', 'name', 'provider', 'expires_on'], 'expires_on', 10),
                'expiring_documents' => $this->rows('staff_documents', ['id', 'document_number', 'expiry_date'], 'expiry_date', 10),
            ],
        ]]);
    }

    public function index(Request $request): JsonResponse
    {
        $query = $this->staffQuery();
        foreach (['employment_status', 'employment_type'] as $field) {
            if ($request->filled('filter.' . $field)) {
                $query->where('staff.' . $field, $request->input('filter.' . $field));
            }
        }
        if ($request->filled('search')) {
            $query->where(fn($q) => $q->where('staff.display_name', 'like', '%' . $request->search . '%')->orWhere('staff.employee_code', 'like', '%' . $request->search . '%')->orWhere('staff.work_email', 'like', '%' . $request->search . '%'));
        }
        $page = $query->orderBy('staff.display_name')->paginate((int) $request->integer('per_page', 25));

        return $this->list($page->items(), $page);
    }

    public function grid(Request $request): JsonResponse
    {
        $page = $this->staffQuery()->orderBy('departments.name')->orderBy('teams.name')->orderBy('staff.display_name')->paginate((int) $request->integer('per_page', 50));

        return $this->list($page->items(), $page, 'OK', ['view' => 'grid']);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->staffData($request);
        return DB::transaction(function () use ($request, $data) {
            $id = DB::table('staff')->insertGetId([...$data, 'uuid' => (string) Str::uuid(), 'tenant_id' => app(\App\Tenancy\TenantContext::class)->id(), 'created_by' => $request->user()?->id, 'created_at' => now(), 'updated_at' => now()]);
            if ($request->boolean('create_user')) {
                $user = $this->createLoginUser($request, $id, $data);
                $this->syncLoginRoles($request, $user, $request->input('role_ids', []));
            }
            $staff = DB::table('staff')->where('id', $id)->first();
            $this->tenant->audit($request, 'tenant_staff_created', 'staff', $id, null, (array) $staff);

            return $this->success(['staff' => $this->staffPayload($staff)], 'Staff created.', 201);
        });
    }

    public function show(string $staff_uuid): JsonResponse
    {
        return $this->success(['staff' => $this->staffPayload($this->findStaff($staff_uuid), true)]);
    }

    public function update(Request $request, string $staff_uuid): JsonResponse
    {
        $staff = $this->findStaff($staff_uuid);
        $old = (array) $staff;
        $data = $this->staffData($request, true);
        DB::table('staff')->where('id', $staff->id)->update([...$data, 'updated_by' => $request->user()?->id, 'updated_at' => now()]);
        $new = (array) DB::table('staff')->where('id', $staff->id)->first();
        $this->tenant->audit($request, 'tenant_staff_updated', 'staff', $staff->id, $old, $new);

        return $this->success(['staff' => $this->staffPayload((object) $new)], 'Staff updated.');
    }

    public function destroy(Request $request, string $staff_uuid): JsonResponse
    {
        $staff = $this->findStaff($staff_uuid);
        DB::table('staff')->where('id', $staff->id)->update(['deleted_at' => now(), 'updated_by' => $request->user()?->id, 'updated_at' => now()]);
        DB::table('users')->where('tenant_id', app(\App\Tenancy\TenantContext::class)->id())->where('staff_id', $staff->id)->update(['status' => 'suspended', 'updated_at' => now()]);
        $this->tenant->audit($request, 'tenant_staff_archived', 'staff', $staff->id, (array) $staff, null);

        return $this->success(null, 'Staff archived.');
    }

    public function restore(Request $request, string $staff_uuid): JsonResponse
    {
        $staff = DB::table('staff')->where('tenant_id', app(\App\Tenancy\TenantContext::class)->id())->where('uuid', $staff_uuid)->first() ?: abort(404, 'Staff not found.');
        DB::table('staff')->where('id', $staff->id)->update(['deleted_at' => null, 'updated_by' => $request->user()?->id, 'updated_at' => now()]);
        $this->tenant->audit($request, 'tenant_staff_restored', 'staff', $staff->id, (array) $staff, ['deleted_at' => null]);

        return $this->success(['staff' => $this->staffPayload(DB::table('staff')->where('id', $staff->id)->first())], 'Staff restored.');
    }

    public function import(Request $request): JsonResponse
    {
        $data = $request->validate(['file_id' => ['nullable', 'string'], 'mapping' => ['nullable', 'array'], 'options' => ['nullable', 'array']]);
        if (! empty($data['file_id'])) {
            $data['file_id'] = $this->tenant->uuidToId('files', $data['file_id'], false);
        }

        return $this->success(['job' => $this->tenant->createJob($request, 'import', 'staff', $data)], 'Staff import queued.', 202);
    }

    public function export(Request $request): JsonResponse
    {
        return $this->success(['job' => $this->tenant->createJob($request, 'export', 'staff', $request->all())], 'Staff export queued.', 202);
    }

    public function childIndex(string $staff_uuid, string $resource): JsonResponse
    {
        $staff = $this->findStaff($staff_uuid);
        $meta = $this->childMeta($resource);
        $rows = DB::table($meta['table'])->where('tenant_id', app(\App\Tenancy\TenantContext::class)->id())->where('staff_id', $staff->id)->orderByDesc('id')->get()->map(fn($row) => $resource === 'bank-accounts' ? $this->tenant->bankPayload($row) : (array) $row)->all();

        return $this->success([str_replace('-', '_', $resource) => $rows]);
    }

    public function childStore(Request $request, string $staff_uuid, string $resource): JsonResponse
    {
        $staff = $this->findStaff($staff_uuid);
        $meta = $this->childMeta($resource);
        $data = $this->childData($request, $resource, $meta);
        $id = DB::table($meta['table'])->insertGetId([...$data, 'tenant_id' => app(\App\Tenancy\TenantContext::class)->id(), 'staff_id' => $staff->id, ...$this->timestampsFor($meta['table'])]);
        $row = DB::table($meta['table'])->where('id', $id)->first();
        $this->tenant->audit($request, 'tenant_staff_' . $resource . '_created', $meta['table'], $id, null, $resource === 'bank-accounts' ? ['masked' => true] : (array) $row);

        return $this->success([rtrim(str_replace('-', '_', $resource), 's') => $resource === 'bank-accounts' ? $this->tenant->bankPayload($row) : $row], ucfirst(str_replace('-', ' ', $resource)) . ' created.', 201);
    }

    public function childUpdate(Request $request, string $staff_uuid, string $resource, int $id): JsonResponse
    {
        $staff = $this->findStaff($staff_uuid);
        $meta = $this->childMeta($resource);
        $row = DB::table($meta['table'])->where('tenant_id', app(\App\Tenancy\TenantContext::class)->id())->where('staff_id', $staff->id)->where('id', $id)->first() ?: abort(404, 'Resource not found.');
        $data = $this->childData($request, $resource, $meta, true);
        DB::table($meta['table'])->where('id', $id)->update([...$data, ...$this->updatedAtFor($meta['table'])]);
        $new = DB::table($meta['table'])->where('id', $id)->first();
        $this->tenant->audit($request, 'tenant_staff_' . $resource . '_updated', $meta['table'], $id, $resource === 'bank-accounts' ? ['masked' => true] : (array) $row, $resource === 'bank-accounts' ? ['masked' => true] : (array) $new);

        return $this->success(['resource' => $resource === 'bank-accounts' ? $this->tenant->bankPayload($new) : $new], 'Staff child resource updated.');
    }

    public function childDelete(Request $request, string $staff_uuid, string $resource, int $id): JsonResponse
    {
        $staff = $this->findStaff($staff_uuid);
        $meta = $this->childMeta($resource);
        $row = DB::table($meta['table'])->where('tenant_id', app(\App\Tenancy\TenantContext::class)->id())->where('staff_id', $staff->id)->where('id', $id)->first() ?: abort(404, 'Resource not found.');
        DB::table($meta['table'])->where('id', $id)->delete();
        $this->tenant->audit($request, 'tenant_staff_' . $resource . '_deleted', $meta['table'], $id, $resource === 'bank-accounts' ? ['masked' => true] : (array) $row, null);

        return $this->success(null, 'Staff child resource deleted.');
    }

    public function activity(string $staff_uuid): JsonResponse
    {
        $staff = $this->findStaff($staff_uuid);

        return $this->success(['activities' => DB::table('activity_logs')->where('tenant_id', app(\App\Tenancy\TenantContext::class)->id())->where('subject_type', 'staff')->where('subject_id', $staff->id)->orderByDesc('created_at')->limit(50)->get()]);
    }

    public function tab(string $staff_uuid, string $tab): JsonResponse
    {
        $staff = $this->findStaff($staff_uuid);
        $tenantId = app(\App\Tenancy\TenantContext::class)->id();

        $rows = match ($tab) {
            'user-access' => [
                'users' => $this->tableRows('users', fn($q) => $q->where('staff_id', $staff->id), 25),
                'roles' => $this->userRolesForStaff($staff->id),
            ],
            'teams' => $this->joinedRows('team_members', fn($q) => $q
                ->leftJoin('teams', 'teams.id', '=', 'team_members.team_id')
                ->leftJoin('team_roles', 'team_roles.id', '=', 'team_members.team_role_id')
                ->where('team_members.staff_id', $staff->id)
                ->select('team_members.*', 'teams.uuid as team_uuid', 'teams.name as team_name', 'team_roles.name as team_role_name')),
            'documents' => $this->tableRows('staff_documents', fn($q) => $q->where('staff_id', $staff->id), 50),
            'bank-details' => $this->tableRows('staff_bank_accounts', fn($q) => $q->where('staff_id', $staff->id), 50)->map(fn($row) => $this->tenant->bankPayload($row))->all(),
            'salary-structure' => $this->tableRows('staff_salary_structures', fn($q) => $q->where('staff_id', $staff->id), 50),
            'leave-history' => $this->tableRows('leave_requests', fn($q) => $q->where('staff_id', $staff->id), 50),
            'attendance' => $this->tableRows('attendance_records', fn($q) => $q->where('staff_id', $staff->id), 50),
            'payroll' => $this->tableRows('payrolls', fn($q) => $q->where('staff_id', $staff->id), 50),
            'projects-tasks' => [
                'projects' => $this->projectRowsForStaff($staff->id),
                'tasks' => $this->taskRowsForStaff($staff->id),
            ],
            'assets' => $this->tableRows('staff_assets', fn($q) => $q->where('staff_id', $staff->id), 50),
            'certifications' => $this->tableRows('staff_certifications', fn($q) => $q->where('staff_id', $staff->id), 50),
            'appraisals' => $this->tableRows('staff_appraisals', fn($q) => $q->where('staff_id', $staff->id), 50),
            'training' => $this->tableRows('staff_training', fn($q) => $q->where('staff_id', $staff->id), 50),
            'notes' => $this->tableRows('notes', fn($q) => $q->where('notable_type', 'staff')->where('notable_id', $staff->id), 50),
            'files' => $this->staffFiles($tenantId, $staff->id),
            default => [],
        };

        return $this->success(['tab' => $tab, 'data' => $rows]);
    }

    private function staffData(Request $request, bool $partial = false): array
    {
        $data = $request->validate(['employee_code' => [$partial ? 'sometimes' : 'required', 'string', 'max:80'], 'first_name' => [$partial ? 'sometimes' : 'required', 'string', 'max:100'], 'last_name' => ['nullable', 'string', 'max:100'], 'display_name' => ['nullable', 'string', 'max:200'], 'personal_email' => ['nullable', 'email', 'max:150'], 'work_email' => ['nullable', 'email', 'max:150'], 'mobile' => ['nullable', 'string', 'max:20'], 'gender' => ['nullable', 'string', 'max:30'], 'date_of_birth' => ['nullable', 'date'], 'joining_date' => ['nullable', 'date'], 'exit_date' => ['nullable', 'date'], 'department_id' => ['nullable', 'string'], 'designation_id' => ['nullable', 'string'], 'office_id' => ['nullable', 'string'], 'primary_team_id' => ['nullable', 'string'], 'reporting_manager_id' => ['nullable', 'string'], 'employment_type' => ['nullable', 'string', 'max:50'], 'employment_status' => ['nullable', 'string', 'max:50']]);
        foreach (['department_id' => 'departments', 'designation_id' => 'designations', 'office_id' => 'tenant_offices', 'primary_team_id' => 'teams', 'reporting_manager_id' => 'users'] as $key => $table) {
            if (array_key_exists($key, $data)) {
                $data[$key] = $this->tenant->uuidToId($table, $data[$key], true);
            }
        }
        if (empty($data['display_name']) && isset($data['first_name'])) {
            $data['display_name'] = trim($data['first_name'] . ' ' . ($data['last_name'] ?? ''));
        }

        return $data;
    }

    private function createLoginUser(Request $request, int $staffId, array $staff): User
    {
        $email = $staff['work_email'] ?? $staff['personal_email'] ?? null;
        abort_if(! $email, 422, 'A work or personal email is required to create a login user.');
        $tenantId = app(\App\Tenancy\TenantContext::class)->id();
        abort_if(User::query()->where('tenant_id', $tenantId)->where('email', $email)->exists(), 409, 'Email already exists for this tenant.');
        $password = Str::password(16);

        return User::query()->create(['uuid' => (string) Str::uuid(), 'tenant_id' => $tenantId, 'staff_id' => $staffId, 'employee_code' => $staff['employee_code'] ?? null, 'first_name' => $staff['first_name'], 'last_name' => $staff['last_name'] ?? null, 'display_name' => $staff['display_name'], 'email' => $email, 'mobile' => $staff['mobile'] ?? null, 'password' => Hash::make($password), 'account_type' => 'staff', 'status' => 'invited', 'created_by' => $request->user()?->id]);
    }

    private function syncLoginRoles(Request $request, User $user, array $roleUuids): void
    {
        if ($roleUuids === []) {
            return;
        }

        $roleIds = DB::table('roles')->where('tenant_id', app(\App\Tenancy\TenantContext::class)->id())->whereIn('uuid', $roleUuids)->pluck('id')->all();
        foreach ($roleIds as $roleId) {
            DB::table('model_has_roles')->updateOrInsert(['tenant_id' => app(\App\Tenancy\TenantContext::class)->id(), 'role_id' => $roleId, 'model_id' => $user->id, 'model_type' => User::class], []);
        }
        $this->tenant->audit($request, 'tenant_staff_login_roles_synced', 'user', $user->id, null, ['role_ids' => $roleIds]);
    }
    private function staffPayload(object $staff, bool $detail = false): array
    {
        $payload = (array) $staff;
        $payload['login_user'] = DB::table('users')->where('tenant_id', app(\App\Tenancy\TenantContext::class)->id())->where('staff_id', $staff->id)->first(['uuid', 'display_name', 'email', 'status', 'account_type']);
        if ($detail) {
            foreach (array_keys(self::CHILDREN) as $resource) {
                if (in_array($resource, ['bank-accounts', 'salary-structures'], true)) {
                    continue;
                }
                $meta = self::CHILDREN[$resource];
                $payload[str_replace('-', '_', $resource)] = DB::table($meta['table'])->where('tenant_id', app(\App\Tenancy\TenantContext::class)->id())->where('staff_id', $staff->id)->orderByDesc('id')->get();
            }
        }

        return $payload;
    }

    private function findStaff(string $uuid): object
    {
        return DB::table('staff')->where('tenant_id', app(\App\Tenancy\TenantContext::class)->id())->where('uuid', $uuid)->whereNull('deleted_at')->first() ?: abort(404, 'Staff not found.');
    }

    private function staffQuery()
    {
        return DB::table('staff')->where('staff.tenant_id', app(\App\Tenancy\TenantContext::class)->id())->whereNull('staff.deleted_at')
            ->leftJoin('departments', 'departments.id', '=', 'staff.department_id')
            ->leftJoin('designations', 'designations.id', '=', 'staff.designation_id')
            ->leftJoin('tenant_offices', 'tenant_offices.id', '=', 'staff.office_id')
            ->leftJoin('teams', 'teams.id', '=', 'staff.primary_team_id')
            ->select('staff.*', 'departments.uuid as department_uuid', 'departments.name as department_name', 'designations.uuid as designation_uuid', 'designations.name as designation_name', 'tenant_offices.uuid as office_uuid', 'tenant_offices.name as office_name', 'teams.uuid as primary_team_uuid', 'teams.name as primary_team_name');
    }

    private function childMeta(string $resource): array
    {
        abort_unless(isset(self::CHILDREN[$resource]), 404, 'Staff child resource not found.');

        return self::CHILDREN[$resource];
    }

    private function childData(Request $request, string $resource, array $meta, bool $partial = false): array
    {
        $rules = [];
        foreach ($meta['fields'] as $field) {
            $rules[$field] = [$partial ? 'sometimes' : ($field === 'account_number' ? 'required' : 'nullable')];
        }
        $data = $request->validate($rules);
        foreach (['department_id' => 'departments', 'designation_id' => 'designations', 'office_id' => 'tenant_offices', 'document_type_id' => 'tenant_lookups', 'file_id' => 'files', 'reviewed_by' => 'users'] as $key => $table) {
            if (array_key_exists($key, $data)) {
                $data[$key] = $this->tenant->uuidToId($table, $data[$key], true);
            }
        }
        if ($resource === 'bank-accounts' && array_key_exists('account_number', $data)) {
            $data['account_number_encrypted'] = Crypt::encryptString((string) $data['account_number']);
            unset($data['account_number']);
        }

        return $data;
    }

    private function grouped(string $table, string $column): array
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return [];
        }

        return DB::table($table)->where('tenant_id', app(\App\Tenancy\TenantContext::class)->id())->selectRaw($column . ' as label, count(*) as total')->groupBy($column)->orderBy($column)->get()->all();
    }

    private function rows(string $table, array $columns, string $order, int $limit): array
    {
        if (! Schema::hasTable($table)) {
            return [];
        }

        return DB::table($table)->where('tenant_id', app(\App\Tenancy\TenantContext::class)->id())->orderBy($order)->limit($limit)->get($columns)->all();
    }

    private function tableRows(string $table, callable $scope, int $limit)
    {
        if (! Schema::hasTable($table)) {
            return collect([['implementation_placeholder' => true, 'message' => $table . ' table is not available yet.']]);
        }

        $query = DB::table($table)->where('tenant_id', app(\App\Tenancy\TenantContext::class)->id());
        $scope($query);

        return $query->orderByDesc(Schema::hasColumn($table, 'created_at') ? 'created_at' : 'id')->limit($limit)->get();
    }

    private function joinedRows(string $baseTable, callable $scope)
    {
        if (! Schema::hasTable($baseTable)) {
            return [['implementation_placeholder' => true, 'message' => $baseTable . ' table is not available yet.']];
        }

        $query = DB::table($baseTable)->where($baseTable . '.tenant_id', app(\App\Tenancy\TenantContext::class)->id());
        $scope($query);

        return $query->orderByDesc($baseTable . '.id')->limit(50)->get();
    }

    private function userRolesForStaff(int $staffId): array
    {
        if (! Schema::hasTable('users') || ! Schema::hasTable('roles') || ! Schema::hasTable('model_has_roles')) {
            return [];
        }

        return DB::table('users')
            ->join('model_has_roles', 'model_has_roles.model_id', '=', 'users.id')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('users.tenant_id', app(\App\Tenancy\TenantContext::class)->id())
            ->where('users.staff_id', $staffId)
            ->where('model_has_roles.model_type', User::class)
            ->get(['roles.uuid', 'roles.name', 'roles.display_name', 'roles.status'])
            ->all();
    }

    private function projectRowsForStaff(int $staffId): array
    {
        if (! Schema::hasTable('project_members') || ! Schema::hasTable('users')) {
            return [];
        }

        $userIds = DB::table('users')->where('tenant_id', app(\App\Tenancy\TenantContext::class)->id())->where('staff_id', $staffId)->pluck('id');
        if ($userIds->isEmpty()) {
            return [];
        }

        return DB::table('project_members')
            ->join('projects', 'projects.id', '=', 'project_members.project_id')
            ->where('project_members.tenant_id', app(\App\Tenancy\TenantContext::class)->id())
            ->whereIn('project_members.user_id', $userIds)
            ->orderByDesc('project_members.id')
            ->limit(50)
            ->get(['project_members.*', 'projects.uuid as project_uuid', 'projects.project_number', 'projects.name'])
            ->all();
    }

    private function taskRowsForStaff(int $staffId): array
    {
        if (! Schema::hasTable('tasks') || ! Schema::hasTable('users')) {
            return [];
        }

        $userIds = DB::table('users')->where('tenant_id', app(\App\Tenancy\TenantContext::class)->id())->where('staff_id', $staffId)->pluck('id');
        if ($userIds->isEmpty()) {
            return [];
        }

        return DB::table('tasks')
            ->where('tenant_id', app(\App\Tenancy\TenantContext::class)->id())
            ->whereIn('assigned_to', $userIds)
            ->orderByDesc('id')
            ->limit(50)
            ->get(['uuid', 'task_number', 'title', 'status_id', 'priority_id', 'due_at', 'progress'])
            ->all();
    }

    private function staffFiles(int $tenantId, int $staffId): array
    {
        if (! Schema::hasTable('attachments') || ! Schema::hasTable('files')) {
            return [['implementation_placeholder' => true, 'message' => 'attachments/files tables are not available yet.']];
        }

        return DB::table('attachments')
            ->join('files', 'files.id', '=', 'attachments.file_id')
            ->where('attachments.tenant_id', $tenantId)
            ->where('attachments.attachable_type', 'staff')
            ->where('attachments.attachable_id', $staffId)
            ->orderByDesc('attachments.id')
            ->limit(50)
            ->get(['attachments.*', 'files.uuid as file_uuid', 'files.original_name', 'files.mime_type', 'files.size_bytes'])
            ->all();
    }

    private function dateCount(string $table, string $column, \DateTimeInterface $from, \DateTimeInterface $to): int
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return 0;
        }

        return (int) DB::table($table)->where('tenant_id', app(\App\Tenancy\TenantContext::class)->id())->whereBetween($column, [$from, $to])->count();
    }

    private function timestampsFor(string $table): array
    {
        return Schema::hasColumn($table, 'created_at') ? ['created_at' => now(), 'updated_at' => now()] : [];
    }

    private function updatedAtFor(string $table): array
    {
        return Schema::hasColumn($table, 'updated_at') ? ['updated_at' => now()] : [];
    }
}
