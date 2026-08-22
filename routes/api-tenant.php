<?php

use App\Http\Controllers\Tenant\TenantApiTokenController;
use App\Http\Controllers\Tenant\TenantBusinessController;
use App\Http\Controllers\Tenant\TenantClientController;
use App\Http\Controllers\Tenant\TenantDashboardController;
use App\Http\Controllers\Tenant\TenantEngagementController;
use App\Http\Controllers\Tenant\TenantHrmsController;
use App\Http\Controllers\Tenant\TenantLeadController;
use App\Http\Controllers\Tenant\TenantOperationsController;
use App\Http\Controllers\Tenant\TenantPermissionController;
use App\Http\Controllers\Tenant\TenantRoleController;
use App\Http\Controllers\Tenant\TenantAuthController;
use App\Http\Controllers\Tenant\TenantHealthController;
use App\Http\Controllers\Tenant\TenantStaffController;
use App\Http\Controllers\Tenant\TenantTeamController;
use App\Http\Controllers\Tenant\TenantUserController;
use App\Http\Controllers\Tenant\TenantVendorController;
use App\Http\Controllers\Shared\SharedPrimitiveController;
use Illuminate\Support\Facades\Route;

// Tenant surface health check for uptime and deployment validation.
Route::get('/health', TenantHealthController::class)->name('health');

// Password recovery before authentication, still scoped by tenant context.
Route::middleware('tenant.context')->prefix('auth')->name('auth.')->group(function (): void {
    Route::post('/forgot-password', [TenantAuthController::class, 'forgotPassword'])->name('forgot-password');
    Route::post('/reset-password', [TenantAuthController::class, 'resetPassword'])->name('reset-password');
});

// All tenant-admin endpoints require tenant context and a valid Sanctum token.
Route::middleware(['tenant.context', 'auth:sanctum', 'tenant.token'])->group(function (): void {
    Route::prefix('auth')->name('auth.')->group(function (): void {
        Route::post('/logout', [TenantAuthController::class, 'logout'])->name('logout');
        Route::post('/refresh', [TenantAuthController::class, 'refresh'])->name('refresh');
        Route::get('/me', [TenantAuthController::class, 'me'])->name('me');
        Route::post('/verify-email/resend', [TenantAuthController::class, 'resendVerification'])->name('verify-email.resend');
        Route::post('/2fa/enable', [TenantAuthController::class, 'enable2fa'])->name('2fa.enable');
        Route::post('/2fa/confirm', [TenantAuthController::class, 'confirm2fa'])->name('2fa.confirm');
        Route::post('/2fa/disable', [TenantAuthController::class, 'disable2fa'])->name('2fa.disable');
    });

    Route::get('/profile', [TenantAuthController::class, 'profile'])->name('profile.show');
    Route::match(['put', 'patch'], '/profile', [TenantAuthController::class, 'updateProfile'])->name('profile.update');
    Route::put('/profile/password', [TenantAuthController::class, 'changePassword'])->name('profile.password');
    Route::get('/profile/preferences', [TenantAuthController::class, 'preferences'])->name('profile.preferences.show');
    Route::put('/profile/preferences', [TenantAuthController::class, 'updatePreferences'])->name('profile.preferences.update');
    Route::get('/profile/sessions', [TenantAuthController::class, 'sessions'])->name('profile.sessions.index');
    Route::delete('/profile/sessions/{session_id}', [TenantAuthController::class, 'revokeSession'])->whereNumber('session_id')->name('profile.sessions.revoke');


    // Dashboard endpoints aligned with the phase-0 dashboard requirements.
    Route::get('/navigation/sidebar', [TenantDashboardController::class, 'sidebar'])->name('navigation.sidebar');
    Route::get('/dashboard/summary', [TenantDashboardController::class, 'summary'])->middleware('tenant.permission:dashboard.view')->name('dashboard.summary');
    Route::get('/dashboard/charts/{chart}', [TenantDashboardController::class, 'chart'])->middleware('tenant.permission:dashboard.view')->name('dashboard.charts');
    Route::get('/dashboard/recent-activities', [TenantDashboardController::class, 'recentActivities'])->middleware('tenant.permission:activity_log.view')->name('dashboard.recent-activities');
    Route::get('/dashboard/widgets', [TenantDashboardController::class, 'widgets'])->middleware('tenant.permission:dashboard.view')->name('dashboard.widgets');
    Route::put('/dashboard/widgets', [TenantDashboardController::class, 'updateWidgets'])->middleware('tenant.permission:dashboard.customize')->name('dashboard.widgets.update');
    Route::post('/dashboard/export', [TenantDashboardController::class, 'export'])->middleware('tenant.permission:dashboard.view')->name('dashboard.export');
    Route::get('/dashboard/{widget}', [TenantDashboardController::class, 'table'])->whereIn('widget', ['my-tasks', 'upcoming-events', 'recent-leads', 'overdue-invoices', 'recent-activities'])->middleware('tenant.permission:dashboard.view')->name('dashboard.widgets.tables');

    // Access control, teams, and staff administration.
    Route::prefix('access-control')->name('access-control.')->group(function (): void {
        Route::get('/roles', [TenantRoleController::class, 'index'])->middleware('tenant.permission:role.view')->name('roles.index');
        Route::post('/roles', [TenantRoleController::class, 'store'])->middleware('tenant.permission:role.create')->name('roles.store');
        Route::delete('/roles/bulk', [TenantRoleController::class, 'bulkDestroy'])->middleware('tenant.permission:role.delete')->name('roles.bulk-destroy');
        Route::get('/roles/{role_uuid}', [TenantRoleController::class, 'show'])->middleware('tenant.permission:role.view')->name('roles.show');
        Route::match(['put', 'patch'], '/roles/{role_uuid}', [TenantRoleController::class, 'update'])->middleware('tenant.permission:role.edit')->name('roles.update');
        Route::delete('/roles/{role_uuid}', [TenantRoleController::class, 'destroy'])->middleware('tenant.permission:role.delete')->name('roles.destroy');
        Route::post('/roles/{role_uuid}/clone', [TenantRoleController::class, 'clone'])->middleware('tenant.permission:role.create')->name('roles.clone');
        Route::post('/roles/{role_uuid}/activate', [TenantRoleController::class, 'activate'])->middleware('tenant.permission:role.edit')->name('roles.activate');
        Route::post('/roles/{role_uuid}/deactivate', [TenantRoleController::class, 'deactivate'])->middleware('tenant.permission:role.edit')->name('roles.deactivate');
        Route::get('/roles/{role_uuid}/permissions', [TenantRoleController::class, 'permissions'])->middleware('tenant.permission:role.view')->name('roles.permissions');
        Route::put('/roles/{role_uuid}/permissions', [TenantRoleController::class, 'syncPermissions'])->middleware('tenant.permission:role.assign_permissions')->name('roles.permissions.sync');
        Route::get('/roles/{role_uuid}/users', [TenantRoleController::class, 'users'])->middleware('tenant.permission:role.view')->name('roles.users');
        Route::post('/roles/{role_uuid}/users', [TenantRoleController::class, 'assignUsers'])->middleware('tenant.permission:role.edit')->name('roles.users.assign');
        Route::delete('/roles/{role_uuid}/users/{user_uuid}', [TenantRoleController::class, 'removeUser'])->middleware('tenant.permission:role.edit')->name('roles.users.remove');

        Route::get('/permissions/grouped', [TenantPermissionController::class, 'grouped'])->middleware('tenant.permission:permission.view')->name('permissions.grouped');
        Route::get('/permissions', [TenantPermissionController::class, 'index'])->middleware('tenant.permission:permission.view')->name('permissions.index');
        Route::get('/permissions/{permission_uuid}', [TenantPermissionController::class, 'show'])->middleware('tenant.permission:permission.view')->name('permissions.show');
    });

    // Teams and team roles.
    Route::get('/team-roles', [TenantTeamController::class, 'teamRoles'])->middleware('tenant.permission:team.view')->name('team-roles.index');
    Route::post('/team-roles', [TenantTeamController::class, 'storeTeamRole'])->middleware('tenant.permission:team.create')->name('team-roles.store');
    Route::match(['put', 'patch'], '/team-roles/{team_role_uuid}', [TenantTeamController::class, 'updateTeamRole'])->middleware('tenant.permission:team.edit')->name('team-roles.update');
    Route::delete('/team-roles/{team_role_uuid}', [TenantTeamController::class, 'deleteTeamRole'])->middleware('tenant.permission:team.delete')->name('team-roles.destroy');
    Route::post('/teams/export', [TenantTeamController::class, 'export'])->middleware('tenant.permission:team.view')->name('teams.export');
    Route::get('/teams', [TenantTeamController::class, 'index'])->middleware('tenant.permission:team.view')->name('teams.index');
    Route::post('/teams', [TenantTeamController::class, 'store'])->middleware('tenant.permission:team.create')->name('teams.store');
    Route::get('/teams/{team_uuid}', [TenantTeamController::class, 'show'])->middleware('tenant.permission:team.view')->name('teams.show');
    Route::match(['put', 'patch'], '/teams/{team_uuid}', [TenantTeamController::class, 'update'])->middleware('tenant.permission:team.edit')->name('teams.update');
    Route::delete('/teams/{team_uuid}', [TenantTeamController::class, 'destroy'])->middleware('tenant.permission:team.delete')->name('teams.destroy');
    Route::get('/teams/{team_uuid}/members', [TenantTeamController::class, 'members'])->middleware('tenant.permission:team.view')->name('teams.members.index');
    Route::post('/teams/{team_uuid}/members', [TenantTeamController::class, 'addMembers'])->middleware('tenant.permission:team.assign')->name('teams.members.store');
    Route::match(['put', 'patch'], '/teams/{team_uuid}/members/{member_uuid}', [TenantTeamController::class, 'updateMember'])->middleware('tenant.permission:team.assign')->name('teams.members.update');
    Route::delete('/teams/{team_uuid}/members/{member_uuid}', [TenantTeamController::class, 'removeMember'])->middleware('tenant.permission:team.assign')->name('teams.members.destroy');
    Route::get('/teams/{team_uuid}/permissions', [TenantTeamController::class, 'permissions'])->middleware('tenant.permission:team.view')->name('teams.permissions');
    Route::put('/teams/{team_uuid}/permissions', [TenantTeamController::class, 'syncPermissions'])->middleware('tenant.permission:team.edit')->name('teams.permissions.sync');
    Route::get('/teams/{team_uuid}/settings', [TenantTeamController::class, 'settings'])->middleware('tenant.permission:team.view')->name('teams.settings');
    Route::put('/teams/{team_uuid}/settings', [TenantTeamController::class, 'updateSettings'])->middleware('tenant.permission:team.edit')->name('teams.settings.update');
    Route::get('/teams/{team_uuid}/assignments', [TenantTeamController::class, 'assignments'])->middleware('tenant.permission:team.view')->name('teams.assignments');
    Route::post('/teams/{team_uuid}/assignments', [TenantTeamController::class, 'createAssignment'])->middleware('tenant.permission:team.assign')->name('teams.assignments.store');
    Route::delete('/teams/{team_uuid}/assignments/{assignment_id}', [TenantTeamController::class, 'releaseAssignment'])->whereNumber('assignment_id')->middleware('tenant.permission:team.assign')->name('teams.assignments.destroy');

    // User-facing staff records.
    Route::get('/users', [TenantUserController::class, 'index'])->middleware('tenant.permission:staff.view')->name('users.index');
    Route::post('/users/invite', [TenantUserController::class, 'invite'])->middleware('tenant.permission:staff.create')->name('users.invite');
    Route::get('/users/{user_uuid}', [TenantUserController::class, 'show'])->middleware('tenant.permission:staff.view')->name('users.show');
    Route::match(['put', 'patch'], '/users/{user_uuid}', [TenantUserController::class, 'update'])->middleware('tenant.permission:staff.edit')->name('users.update');
    Route::put('/users/{user_uuid}/roles', [TenantUserController::class, 'syncUserRoles'])->middleware('tenant.permission:role.edit')->name('users.roles.sync');
    Route::post('/users/{user_uuid}/suspend', [TenantUserController::class, 'suspend'])->middleware('tenant.permission:staff.edit')->name('users.suspend');
    Route::post('/users/{user_uuid}/activate', [TenantUserController::class, 'activate'])->middleware('tenant.permission:staff.edit')->name('users.activate');
    Route::post('/users/{user_uuid}/reset-password', [TenantUserController::class, 'resetPassword'])->middleware('tenant.permission:staff.edit')->name('users.reset-password');

    Route::get('/staff/dashboard', [TenantStaffController::class, 'dashboard'])->middleware('tenant.permission:staff.view')->name('staff.dashboard');
    Route::get('/staff/grid', [TenantStaffController::class, 'grid'])->middleware('tenant.permission:staff.view')->name('staff.grid');
    Route::post('/staff/import', [TenantStaffController::class, 'import'])->middleware('tenant.permission:staff.import')->name('staff.import');
    Route::post('/staff/export', [TenantStaffController::class, 'export'])->middleware('tenant.permission:staff.export')->name('staff.export');
    Route::get('/staff', [TenantStaffController::class, 'index'])->middleware('tenant.permission:staff.view')->name('staff.index');
    Route::post('/staff', [TenantStaffController::class, 'store'])->middleware('tenant.permission:staff.create')->name('staff.store');
    Route::get('/staff/{staff_uuid}', [TenantStaffController::class, 'show'])->middleware('tenant.permission:staff.view')->name('staff.show');
    Route::match(['put', 'patch'], '/staff/{staff_uuid}', [TenantStaffController::class, 'update'])->middleware('tenant.permission:staff.edit')->name('staff.update');
    Route::delete('/staff/{staff_uuid}', [TenantStaffController::class, 'destroy'])->middleware('tenant.permission:staff.delete')->name('staff.destroy');
    Route::post('/staff/{staff_uuid}/restore', [TenantStaffController::class, 'restore'])->middleware('tenant.permission:staff.edit')->name('staff.restore');
    Route::get('/staff/{staff_uuid}/activity', [TenantStaffController::class, 'activity'])->middleware('tenant.permission:activity_log.view')->name('staff.activity');
    Route::get('/staff/{staff_uuid}/tabs/{tab}', [TenantStaffController::class, 'tab'])->whereIn('tab', ['user-access', 'teams', 'documents', 'bank-details', 'salary-structure', 'leave-history', 'attendance', 'payroll', 'projects-tasks', 'assets', 'certifications', 'appraisals', 'training', 'notes', 'files'])->middleware('tenant.permission:staff.view')->name('staff.tabs.show');
    Route::get('/staff/{staff_uuid}/bank-accounts', [TenantStaffController::class, 'childIndex'])->defaults('resource', 'bank-accounts')->middleware('tenant.permission:staff.manage_bank')->name('staff.bank-accounts.index');
    Route::post('/staff/{staff_uuid}/bank-accounts', [TenantStaffController::class, 'childStore'])->defaults('resource', 'bank-accounts')->middleware('tenant.permission:staff.manage_bank')->name('staff.bank-accounts.store');
    Route::match(['put', 'patch'], '/staff/{staff_uuid}/bank-accounts/{id}', [TenantStaffController::class, 'childUpdate'])->whereNumber('id')->defaults('resource', 'bank-accounts')->middleware('tenant.permission:staff.manage_bank')->name('staff.bank-accounts.update');
    Route::delete('/staff/{staff_uuid}/bank-accounts/{id}', [TenantStaffController::class, 'childDelete'])->whereNumber('id')->defaults('resource', 'bank-accounts')->middleware('tenant.permission:staff.manage_bank')->name('staff.bank-accounts.destroy');
    Route::get('/staff/{staff_uuid}/salary-structures', [TenantStaffController::class, 'childIndex'])->defaults('resource', 'salary-structures')->middleware('tenant.permission:staff.manage_salary')->name('staff.salary-structures.index');
    Route::post('/staff/{staff_uuid}/salary-structures', [TenantStaffController::class, 'childStore'])->defaults('resource', 'salary-structures')->middleware('tenant.permission:staff.manage_salary')->name('staff.salary-structures.store');
    Route::match(['put', 'patch'], '/staff/{staff_uuid}/salary-structures/{id}', [TenantStaffController::class, 'childUpdate'])->whereNumber('id')->defaults('resource', 'salary-structures')->middleware('tenant.permission:staff.manage_salary')->name('staff.salary-structures.update');

    foreach (['employment-history', 'documents', 'emergency-contacts', 'assets', 'certifications', 'appraisals', 'training'] as $resource) {
        Route::get('/staff/{staff_uuid}/'.$resource, [TenantStaffController::class, 'childIndex'])->defaults('resource', $resource)->middleware('tenant.permission:staff.view')->name('staff.'.$resource.'.index');
        Route::post('/staff/{staff_uuid}/'.$resource, [TenantStaffController::class, 'childStore'])->defaults('resource', $resource)->middleware('tenant.permission:staff.edit')->name('staff.'.$resource.'.store');
        Route::delete('/staff/{staff_uuid}/'.$resource.'/{id}', [TenantStaffController::class, 'childDelete'])->whereNumber('id')->defaults('resource', $resource)->middleware('tenant.permission:staff.edit')->name('staff.'.$resource.'.destroy');
    }

    // HRMS: attendance, leave, payroll, and holidays.
    Route::get('/attendance/dashboard', [TenantHrmsController::class, 'attendanceDashboard'])->middleware('tenant.permission:attendance.view')->name('attendance.dashboard');
    Route::get('/attendance/daily', [TenantHrmsController::class, 'attendanceDaily'])->middleware('tenant.permission:attendance.view')->name('attendance.daily');
    Route::get('/attendance/monthly', [TenantHrmsController::class, 'attendanceMonthly'])->middleware('tenant.permission:attendance.view')->name('attendance.monthly');
    Route::post('/attendance/check-in', [TenantHrmsController::class, 'checkIn'])->name('attendance.check-in');
    Route::post('/attendance/check-out', [TenantHrmsController::class, 'checkOut'])->name('attendance.check-out');
    Route::post('/attendance/records', [TenantHrmsController::class, 'storeAttendanceRecord'])->middleware('tenant.permission:attendance.create')->name('attendance.records.store');
    Route::get('/attendance/records/{record_id}', [TenantHrmsController::class, 'showAttendanceRecord'])->whereNumber('record_id')->middleware('tenant.permission:attendance.view')->name('attendance.records.show');
    Route::match(['put', 'patch'], '/attendance/records/{record_id}', [TenantHrmsController::class, 'updateAttendanceRecord'])->whereNumber('record_id')->middleware('tenant.permission:attendance.edit')->name('attendance.records.update');
    Route::post('/attendance/import', [TenantHrmsController::class, 'importAttendance'])->middleware('tenant.permission:attendance.edit')->name('attendance.import');
    Route::post('/attendance/export', [TenantHrmsController::class, 'exportAttendance'])->middleware('tenant.permission:attendance.export')->name('attendance.export');
    Route::get('/attendance/requests', [TenantHrmsController::class, 'attendanceRequests'])->middleware('tenant.permission:attendance.view')->name('attendance.requests.index');
    Route::post('/attendance/requests', [TenantHrmsController::class, 'storeAttendanceRequest'])->name('attendance.requests.store');
    Route::get('/attendance/requests/{request_uuid}', [TenantHrmsController::class, 'showAttendanceRequest'])->middleware('tenant.permission:attendance.view')->name('attendance.requests.show');
    Route::post('/attendance/requests/{request_uuid}/approve', [TenantHrmsController::class, 'approveAttendanceRequest'])->middleware('tenant.permission:attendance.approve')->name('attendance.requests.approve');
    Route::post('/attendance/requests/{request_uuid}/reject', [TenantHrmsController::class, 'rejectAttendanceRequest'])->middleware('tenant.permission:attendance.approve')->name('attendance.requests.reject');

    Route::get('/leave/dashboard', [TenantHrmsController::class, 'leaveDashboard'])->middleware('tenant.permission:leave.view')->name('leave.dashboard');
    Route::get('/leave/requests', [TenantHrmsController::class, 'leaveRequests'])->middleware('tenant.permission:leave.view')->name('leave.requests.index');
    Route::post('/leave/requests', [TenantHrmsController::class, 'storeLeaveRequest'])->name('leave.requests.store');
    Route::get('/leave/requests/{request_id}', [TenantHrmsController::class, 'showLeaveRequest'])->middleware('tenant.permission:leave.view')->name('leave.requests.show');
    Route::post('/leave/requests/{request_id}/approve', [TenantHrmsController::class, 'approveLeaveRequest'])->middleware('tenant.permission:leave.approve')->name('leave.requests.approve');
    Route::post('/leave/requests/{request_id}/reject', [TenantHrmsController::class, 'rejectLeaveRequest'])->middleware('tenant.permission:leave.approve')->name('leave.requests.reject');
    Route::post('/leave/requests/{request_id}/cancel', [TenantHrmsController::class, 'cancelLeaveRequest'])->name('leave.requests.cancel');
    Route::get('/leave/balances', [TenantHrmsController::class, 'leaveBalances'])->middleware('tenant.permission:leave.view')->name('leave.balances.index');
    Route::post('/leave/balances/adjust', [TenantHrmsController::class, 'adjustLeaveBalance'])->middleware('tenant.permission:leave.approve')->name('leave.balances.adjust');
    Route::get('/leave/calendar', [TenantHrmsController::class, 'leaveCalendar'])->middleware('tenant.permission:leave.view')->name('leave.calendar');
    Route::get('/leave/types', [TenantHrmsController::class, 'leaveTypes'])->middleware('tenant.permission:setting.view')->name('leave.types.index');
    Route::post('/leave/types', [TenantHrmsController::class, 'leaveTypes'])->middleware('tenant.permission:setting.edit')->name('leave.types.store');

    Route::get('/payroll/dashboard', [TenantHrmsController::class, 'payrollDashboard'])->middleware('tenant.permission:payroll.view')->name('payroll.dashboard');
    Route::get('/payroll/cycles', [TenantHrmsController::class, 'payrollCycles'])->middleware('tenant.permission:payroll.view')->name('payroll.cycles.index');
    Route::post('/payroll/cycles', [TenantHrmsController::class, 'storePayrollCycle'])->middleware('tenant.permission:payroll.generate')->name('payroll.cycles.store');
    Route::get('/payroll/cycles/{cycle_uuid}', [TenantHrmsController::class, 'showPayrollCycle'])->middleware('tenant.permission:payroll.view')->name('payroll.cycles.show');
    Route::match(['put', 'patch'], '/payroll/cycles/{cycle_uuid}', [TenantHrmsController::class, 'updatePayrollCycle'])->middleware('tenant.permission:payroll.generate')->name('payroll.cycles.update');
    Route::post('/payroll/cycles/{cycle_uuid}/generate-preview', [TenantHrmsController::class, 'payrollPreview'])->middleware('tenant.permission:payroll.generate')->name('payroll.cycles.preview');
    Route::post('/payroll/cycles/{cycle_uuid}/generate', [TenantHrmsController::class, 'generatePayroll'])->middleware('tenant.permission:payroll.generate')->name('payroll.cycles.generate');
    foreach (['submit', 'approve', 'lock', 'reopen'] as $action) {
        Route::post('/payroll/cycles/{cycle_uuid}/'.$action, [TenantHrmsController::class, 'payrollCycleAction'])->defaults('action', $action)->middleware('tenant.permission:payroll.approve')->name('payroll.cycles.'.$action);
    }
    Route::get('/payroll/payrolls', [TenantHrmsController::class, 'payrolls'])->middleware('tenant.permission:payroll.view')->name('payroll.payrolls.index');
    Route::get('/payroll/payrolls/{payroll_uuid}', [TenantHrmsController::class, 'showPayroll'])->middleware('tenant.permission:payroll.view')->name('payroll.payrolls.show');
    Route::match(['put', 'patch'], '/payroll/payrolls/{payroll_uuid}', [TenantHrmsController::class, 'updatePayroll'])->middleware('tenant.permission:payroll.generate')->name('payroll.payrolls.update');
    Route::get('/payroll/payrolls/{payroll_uuid}/items', [TenantHrmsController::class, 'payrollItems'])->middleware('tenant.permission:payroll.view')->name('payroll.payrolls.items');
    Route::get('/payroll/payslips', [TenantHrmsController::class, 'payslips'])->middleware('tenant.permission:payroll.view')->name('payroll.payslips.index');
    Route::post('/payroll/payslips/generate', [TenantHrmsController::class, 'generatePayslips'])->middleware('tenant.permission:payroll.generate')->name('payroll.payslips.generate');
    Route::post('/payroll/payslips/email', [TenantHrmsController::class, 'emailPayslips'])->middleware('tenant.permission:payroll.generate')->name('payroll.payslips.email');
    Route::get('/payroll/payslips/{payslip_id}/download', [TenantHrmsController::class, 'downloadPayslip'])->whereNumber('payslip_id')->middleware('tenant.permission:payroll.view')->name('payroll.payslips.download');
    Route::match(['get', 'post'], '/payroll/component-types', [TenantHrmsController::class, 'componentTypes'])->middleware('tenant.permission:payroll.manage_settings')->name('payroll.component-types');
    Route::match(['get', 'post'], '/payroll/components', [TenantHrmsController::class, 'components'])->middleware('tenant.permission:payroll.manage_settings')->name('payroll.components');
    Route::match(['put', 'patch'], '/payroll/components/{component_id}', [TenantHrmsController::class, 'updateComponent'])->whereNumber('component_id')->middleware('tenant.permission:payroll.manage_settings')->name('payroll.components.update');
    Route::match(['get', 'post'], '/payroll/component-assignments', [TenantHrmsController::class, 'componentAssignments'])->middleware('tenant.permission:payroll.manage_settings')->name('payroll.component-assignments');
    Route::match(['get', 'post'], '/payroll/loans', [TenantHrmsController::class, 'loans'])->middleware('tenant.permission:payroll.manage_settings')->name('payroll.loans');
    Route::match(['put', 'patch'], '/payroll/loans/{loan_id}', [TenantHrmsController::class, 'updateLoan'])->whereNumber('loan_id')->middleware('tenant.permission:payroll.manage_settings')->name('payroll.loans.update');
    Route::match(['get', 'post'], '/payroll/reimbursements', [TenantHrmsController::class, 'reimbursements'])->middleware('tenant.permission:payroll.view')->name('payroll.reimbursements');
    Route::post('/payroll/reimbursements/{reimbursement_id}/approve', [TenantHrmsController::class, 'approveReimbursement'])->whereNumber('reimbursement_id')->middleware('tenant.permission:payroll.approve')->name('payroll.reimbursements.approve');
    Route::match(['get', 'post'], '/payroll/bank-transfers', [TenantHrmsController::class, 'bankTransfers'])->middleware('tenant.permission:payroll.view')->name('payroll.bank-transfers');
    Route::post('/payroll/bank-transfers/{transfer_id}/mark-paid', [TenantHrmsController::class, 'markTransferPaid'])->whereNumber('transfer_id')->middleware('tenant.permission:payroll.generate')->name('payroll.bank-transfers.mark-paid');
    Route::match(['get', 'post'], '/payroll/tax-slabs', [TenantHrmsController::class, 'taxSlabs'])->middleware('tenant.permission:payroll.manage_settings')->name('payroll.tax-slabs');
    Route::match(['get', 'put'], '/payroll/pf-settings', [TenantHrmsController::class, 'pfSettings'])->middleware('tenant.permission:payroll.manage_settings')->name('payroll.pf-settings');
    Route::match(['get', 'put'], '/payroll/esi-settings', [TenantHrmsController::class, 'esiSettings'])->middleware('tenant.permission:payroll.manage_settings')->name('payroll.esi-settings');
    Route::post('/payroll/export', [TenantHrmsController::class, 'exportPayroll'])->middleware('tenant.permission:payroll.export')->name('payroll.export');

    Route::get('/holidays', [TenantHrmsController::class, 'holidays'])->middleware('tenant.permission:holiday.view')->name('holidays.index');
    Route::post('/holidays', [TenantHrmsController::class, 'storeHoliday'])->middleware('tenant.permission:holiday.create')->name('holidays.store');
    Route::get('/holidays/{holiday_uuid}', [TenantHrmsController::class, 'showHoliday'])->middleware('tenant.permission:holiday.view')->name('holidays.show');
    Route::match(['put', 'patch'], '/holidays/{holiday_uuid}', [TenantHrmsController::class, 'updateHoliday'])->middleware('tenant.permission:holiday.edit')->name('holidays.update');
    Route::delete('/holidays/{holiday_uuid}', [TenantHrmsController::class, 'deleteHoliday'])->middleware('tenant.permission:holiday.delete')->name('holidays.destroy');
    Route::post('/holidays/{holiday_uuid}/duplicate-next-year', [TenantHrmsController::class, 'duplicateHoliday'])->middleware('tenant.permission:holiday.create')->name('holidays.duplicate-next-year');
    Route::post('/holidays/import', [TenantHrmsController::class, 'importHolidays'])->middleware('tenant.permission:holiday.create')->name('holidays.import');
    Route::post('/holidays/export', [TenantHrmsController::class, 'exportHolidays'])->middleware('tenant.permission:holiday.view')->name('holidays.export');
    Route::match(['get', 'post'], '/holiday-calendars', [TenantHrmsController::class, 'holidayCalendars'])->middleware('tenant.permission:holiday.view')->name('holiday-calendars.index-store');
    Route::get('/holiday-calendars/{calendar_uuid}', [TenantHrmsController::class, 'showHolidayCalendar'])->middleware('tenant.permission:holiday.view')->name('holiday-calendars.show');
    Route::match(['put', 'patch'], '/holiday-calendars/{calendar_uuid}', [TenantHrmsController::class, 'updateHolidayCalendar'])->middleware('tenant.permission:holiday.edit')->name('holiday-calendars.update');
    Route::delete('/holiday-calendars/{calendar_uuid}', [TenantHrmsController::class, 'deleteHolidayCalendar'])->middleware('tenant.permission:holiday.delete')->name('holiday-calendars.destroy');
    Route::match(['get', 'post'], '/holiday-groups', [TenantHrmsController::class, 'holidayGroups'])->middleware('tenant.permission:holiday.view')->name('holiday-groups.index-store');
    Route::match(['put', 'patch'], '/holiday-groups/{group_uuid}', [TenantHrmsController::class, 'updateHolidayGroup'])->middleware('tenant.permission:holiday.edit')->name('holiday-groups.update');
    Route::match(['get', 'post'], '/holiday-groups/{group_uuid}/members', [TenantHrmsController::class, 'holidayGroupMembers'])->middleware('tenant.permission:holiday.view')->name('holiday-groups.members');
    Route::delete('/holiday-groups/{group_uuid}/members/{staff_uuid}', [TenantHrmsController::class, 'deleteHolidayGroupMember'])->middleware('tenant.permission:holiday.edit')->name('holiday-groups.members.destroy');
    // CRM: clients, vendors, leads, renewals, and related records.
    Route::post('/clients/import', [TenantClientController::class, 'import'])->middleware('tenant.permission:client.import')->name('clients.import');
    Route::post('/clients/export', [TenantClientController::class, 'export'])->middleware('tenant.permission:client.export')->name('clients.export');
    Route::post('/clients/merge', [TenantClientController::class, 'merge'])->middleware('tenant.permission:client.merge')->name('clients.merge');
    Route::get('/clients', [TenantClientController::class, 'index'])->middleware('tenant.permission:client.view')->name('clients.index');
    Route::post('/clients', [TenantClientController::class, 'store'])->middleware('tenant.permission:client.create')->name('clients.store');
    Route::get('/clients/{client_uuid}', [TenantClientController::class, 'show'])->middleware('tenant.permission:client.view')->name('clients.show');
    Route::match(['put', 'patch'], '/clients/{client_uuid}', [TenantClientController::class, 'update'])->middleware('tenant.permission:client.edit')->name('clients.update');
    Route::delete('/clients/{client_uuid}', [TenantClientController::class, 'destroy'])->middleware('tenant.permission:client.delete')->name('clients.destroy');
    Route::post('/clients/{client_uuid}/restore', [TenantClientController::class, 'restore'])->middleware('tenant.permission:client.edit')->name('clients.restore');
    Route::get('/clients/{client_uuid}/contacts', [TenantClientController::class, 'contacts'])->middleware('tenant.permission:client.view')->name('clients.contacts.index');
    Route::post('/clients/{client_uuid}/contacts', [TenantClientController::class, 'storeContact'])->middleware('tenant.permission:client.edit')->name('clients.contacts.store');
    Route::match(['put', 'patch'], '/clients/{client_uuid}/contacts/{contact_uuid}', [TenantClientController::class, 'updateContact'])->middleware('tenant.permission:client.edit')->name('clients.contacts.update');
    Route::delete('/clients/{client_uuid}/contacts/{contact_uuid}', [TenantClientController::class, 'deleteContact'])->middleware('tenant.permission:client.edit')->name('clients.contacts.destroy');
    Route::get('/clients/{client_uuid}/addresses', [TenantClientController::class, 'addresses'])->middleware('tenant.permission:client.view')->name('clients.addresses.index');
    Route::post('/clients/{client_uuid}/addresses', [TenantClientController::class, 'storeAddress'])->middleware('tenant.permission:client.edit')->name('clients.addresses.store');
    Route::match(['put', 'patch'], '/clients/{client_uuid}/addresses/{address_id}', [TenantClientController::class, 'updateAddress'])->whereNumber('address_id')->middleware('tenant.permission:client.edit')->name('clients.addresses.update');
    Route::delete('/clients/{client_uuid}/addresses/{address_id}', [TenantClientController::class, 'deleteAddress'])->whereNumber('address_id')->middleware('tenant.permission:client.edit')->name('clients.addresses.destroy');
    Route::get('/clients/{client_uuid}/{resource}', [TenantClientController::class, 'related'])->whereIn('resource', ['projects', 'invoices', 'payments', 'renewals', 'issues'])->middleware('tenant.permission:client.view')->name('clients.related');
    Route::get('/clients/{client_uuid}/activity', [TenantClientController::class, 'activity'])->middleware('tenant.permission:activity_log.view')->name('clients.activity');

    Route::post('/vendors/import', [TenantVendorController::class, 'import'])->middleware('tenant.permission:vendor.import')->name('vendors.import');
    Route::post('/vendors/export', [TenantVendorController::class, 'export'])->middleware('tenant.permission:vendor.export')->name('vendors.export');
    Route::get('/vendors', [TenantVendorController::class, 'index'])->middleware('tenant.permission:vendor.view')->name('vendors.index');
    Route::post('/vendors', [TenantVendorController::class, 'store'])->middleware('tenant.permission:vendor.create')->name('vendors.store');
    Route::get('/vendors/{vendor_uuid}', [TenantVendorController::class, 'show'])->middleware('tenant.permission:vendor.view')->name('vendors.show');
    Route::match(['put', 'patch'], '/vendors/{vendor_uuid}', [TenantVendorController::class, 'update'])->middleware('tenant.permission:vendor.edit')->name('vendors.update');
    Route::delete('/vendors/{vendor_uuid}', [TenantVendorController::class, 'destroy'])->middleware('tenant.permission:vendor.delete')->name('vendors.destroy');
    Route::get('/vendors/{vendor_uuid}/contacts', [TenantVendorController::class, 'contacts'])->middleware('tenant.permission:vendor.view')->name('vendors.contacts.index');
    Route::post('/vendors/{vendor_uuid}/contacts', [TenantVendorController::class, 'storeContact'])->middleware('tenant.permission:vendor.edit')->name('vendors.contacts.store');
    Route::match(['put', 'patch'], '/vendors/{vendor_uuid}/contacts/{contact_uuid}', [TenantVendorController::class, 'updateContact'])->middleware('tenant.permission:vendor.edit')->name('vendors.contacts.update');
    Route::delete('/vendors/{vendor_uuid}/contacts/{contact_uuid}', [TenantVendorController::class, 'deleteContact'])->middleware('tenant.permission:vendor.edit')->name('vendors.contacts.destroy');
    Route::get('/vendors/{vendor_uuid}/addresses', [TenantVendorController::class, 'addresses'])->middleware('tenant.permission:vendor.view')->name('vendors.addresses.index');
    Route::post('/vendors/{vendor_uuid}/addresses', [TenantVendorController::class, 'storeAddress'])->middleware('tenant.permission:vendor.edit')->name('vendors.addresses.store');
    Route::match(['put', 'patch'], '/vendors/{vendor_uuid}/addresses/{address_id}', [TenantVendorController::class, 'updateAddress'])->whereNumber('address_id')->middleware('tenant.permission:vendor.edit')->name('vendors.addresses.update');
    Route::delete('/vendors/{vendor_uuid}/addresses/{address_id}', [TenantVendorController::class, 'deleteAddress'])->whereNumber('address_id')->middleware('tenant.permission:vendor.edit')->name('vendors.addresses.destroy');
    Route::get('/vendors/{vendor_uuid}/bank-accounts', [TenantVendorController::class, 'bankAccounts'])->middleware('tenant.permission:finance.bank_account.view')->name('vendors.bank-accounts.index');
    Route::post('/vendors/{vendor_uuid}/bank-accounts', [TenantVendorController::class, 'storeBankAccount'])->middleware('tenant.permission:finance.bank_account.create')->name('vendors.bank-accounts.store');
    Route::match(['put', 'patch'], '/vendors/{vendor_uuid}/bank-accounts/{account_id}', [TenantVendorController::class, 'updateBankAccount'])->whereNumber('account_id')->middleware('tenant.permission:finance.bank_account.edit')->name('vendors.bank-accounts.update');
    Route::delete('/vendors/{vendor_uuid}/bank-accounts/{account_id}', [TenantVendorController::class, 'deleteBankAccount'])->whereNumber('account_id')->middleware('tenant.permission:finance.bank_account.delete')->name('vendors.bank-accounts.destroy');
    Route::get('/vendors/{vendor_uuid}/{resource}', [TenantVendorController::class, 'related'])->whereIn('resource', ['expenses', 'renewals'])->middleware('tenant.permission:vendor.view')->name('vendors.related');
    Route::get('/vendors/{vendor_uuid}/activity', [TenantVendorController::class, 'activity'])->middleware('tenant.permission:activity_log.view')->name('vendors.activity');

    Route::get('/leads/dashboard', [TenantLeadController::class, 'dashboard'])->middleware('tenant.permission:lead.view')->name('leads.dashboard');
    Route::get('/leads/kanban', [TenantLeadController::class, 'kanban'])->middleware('tenant.permission:lead.view')->name('leads.kanban');
    Route::post('/leads/import', [TenantLeadController::class, 'import'])->middleware('tenant.permission:lead.import')->name('leads.import');
    Route::post('/leads/export', [TenantLeadController::class, 'export'])->middleware('tenant.permission:lead.export')->name('leads.export');
    Route::post('/leads/merge', [TenantLeadController::class, 'merge'])->middleware('tenant.permission:lead.edit')->name('leads.merge');
    Route::get('/leads', [TenantLeadController::class, 'index'])->middleware('tenant.permission:lead.view')->name('leads.index');
    Route::post('/leads', [TenantLeadController::class, 'store'])->middleware('tenant.permission:lead.create')->name('leads.store');
    Route::get('/leads/{lead_uuid}', [TenantLeadController::class, 'show'])->middleware('tenant.permission:lead.view')->name('leads.show');
    Route::match(['put', 'patch'], '/leads/{lead_uuid}', [TenantLeadController::class, 'update'])->middleware('tenant.permission:lead.edit')->name('leads.update');
    Route::delete('/leads/{lead_uuid}', [TenantLeadController::class, 'destroy'])->middleware('tenant.permission:lead.delete')->name('leads.destroy');
    Route::post('/leads/{lead_uuid}/duplicate', [TenantLeadController::class, 'duplicate'])->middleware('tenant.permission:lead.create')->name('leads.duplicate');
    Route::post('/leads/{lead_uuid}/convert', [TenantLeadController::class, 'convert'])->middleware('tenant.permission:lead.convert')->name('leads.convert');
    Route::post('/leads/{lead_uuid}/mark-lost', [TenantLeadController::class, 'markLost'])->middleware('tenant.permission:lead.edit')->name('leads.mark-lost');
    Route::get('/leads/{lead_uuid}/contacts', [TenantLeadController::class, 'contacts'])->middleware('tenant.permission:lead.view')->name('leads.contacts.index');
    Route::post('/leads/{lead_uuid}/contacts', [TenantLeadController::class, 'storeContact'])->middleware('tenant.permission:lead.edit')->name('leads.contacts.store');
    Route::match(['put', 'patch'], '/leads/{lead_uuid}/contacts/{contact_uuid}', [TenantLeadController::class, 'updateContact'])->middleware('tenant.permission:lead.edit')->name('leads.contacts.update');
    Route::delete('/leads/{lead_uuid}/contacts/{contact_uuid}', [TenantLeadController::class, 'deleteContact'])->middleware('tenant.permission:lead.edit')->name('leads.contacts.destroy');
    Route::get('/leads/{lead_uuid}/addresses', [TenantLeadController::class, 'addresses'])->middleware('tenant.permission:lead.view')->name('leads.addresses.index');
    Route::post('/leads/{lead_uuid}/addresses', [TenantLeadController::class, 'storeAddress'])->middleware('tenant.permission:lead.edit')->name('leads.addresses.store');
    Route::match(['put', 'patch'], '/leads/{lead_uuid}/addresses/{address_id}', [TenantLeadController::class, 'updateAddress'])->whereNumber('address_id')->middleware('tenant.permission:lead.edit')->name('leads.addresses.update');
    Route::delete('/leads/{lead_uuid}/addresses/{address_id}', [TenantLeadController::class, 'deleteAddress'])->whereNumber('address_id')->middleware('tenant.permission:lead.edit')->name('leads.addresses.destroy');
    Route::get('/leads/{lead_uuid}/activities', [TenantLeadController::class, 'activities'])->middleware('tenant.permission:lead.view')->name('leads.activities.index');
    Route::post('/leads/{lead_uuid}/activities', [TenantLeadController::class, 'storeActivity'])->middleware('tenant.permission:lead.edit')->name('leads.activities.store');
    Route::match(['put', 'patch'], '/leads/{lead_uuid}/activities/{activity_uuid}', [TenantLeadController::class, 'updateActivity'])->middleware('tenant.permission:lead.edit')->name('leads.activities.update');
    Route::get('/leads/{lead_uuid}/activity', [TenantLeadController::class, 'activity'])->middleware('tenant.permission:activity_log.view')->name('leads.activity');

    // Projects, tasks, issues, and workspace planning.
    Route::get('/projects/dashboard', [TenantOperationsController::class, 'projectDashboard'])->middleware('tenant.permission:project.view')->name('projects.dashboard');
    Route::get('/projects/kanban', [TenantOperationsController::class, 'projectKanban'])->middleware('tenant.permission:project.view')->name('projects.kanban');
    Route::get('/projects/gantt', [TenantOperationsController::class, 'projectGantt'])->middleware('tenant.permission:project.view')->name('projects.gantt');
    Route::get('/projects/calendar', [TenantOperationsController::class, 'projectCalendar'])->middleware('tenant.permission:project.view')->name('projects.calendar');
    Route::post('/projects/export', [TenantOperationsController::class, 'exportProjects'])->middleware('tenant.permission:project.view')->name('projects.export');
    Route::get('/projects', [TenantOperationsController::class, 'projects'])->middleware('tenant.permission:project.view')->name('projects.index');
    Route::post('/projects', [TenantOperationsController::class, 'storeProject'])->middleware('tenant.permission:project.create')->name('projects.store');
    Route::get('/projects/{project_uuid}', [TenantOperationsController::class, 'showProject'])->middleware('tenant.permission:project.view')->name('projects.show');
    Route::match(['put', 'patch'], '/projects/{project_uuid}', [TenantOperationsController::class, 'updateProject'])->middleware('tenant.permission:project.edit')->name('projects.update');
    Route::delete('/projects/{project_uuid}', [TenantOperationsController::class, 'archiveProject'])->middleware('tenant.permission:project.delete')->name('projects.destroy');
    Route::post('/projects/{project_uuid}/archive', [TenantOperationsController::class, 'archiveProject'])->middleware('tenant.permission:project.archive')->name('projects.archive');
    foreach (['members', 'phases', 'milestones', 'time-logs', 'expenses'] as $resource) {
        Route::get('/projects/{project_uuid}/'.$resource, [TenantOperationsController::class, 'projectChildren'])->defaults('resource', $resource)->middleware('tenant.permission:project.view')->name('projects.'.$resource.'.index');
        Route::post('/projects/{project_uuid}/'.$resource, [TenantOperationsController::class, 'storeProjectChild'])->defaults('resource', $resource)->middleware('tenant.permission:project.edit')->name('projects.'.$resource.'.store');
        Route::match(['put', 'patch'], '/projects/{project_uuid}/'.$resource.'/{id}', [TenantOperationsController::class, 'updateProjectChild'])->whereNumber('id')->defaults('resource', $resource)->middleware('tenant.permission:project.edit')->name('projects.'.$resource.'.update');
        Route::delete('/projects/{project_uuid}/'.$resource.'/{id}', [TenantOperationsController::class, 'deleteProjectChild'])->whereNumber('id')->defaults('resource', $resource)->middleware('tenant.permission:project.edit')->name('projects.'.$resource.'.destroy');
    }
    Route::post('/projects/{project_uuid}/milestones/{milestone_id}/complete', [TenantOperationsController::class, 'completeMilestone'])->whereNumber('milestone_id')->middleware('tenant.permission:project.edit')->name('projects.milestones.complete');
    Route::get('/projects/{project_uuid}/tasks', [TenantOperationsController::class, 'projectTasks'])->middleware('tenant.permission:task.view')->name('projects.tasks.index');
    Route::post('/projects/{project_uuid}/tasks', [TenantOperationsController::class, 'storeProjectTask'])->middleware('tenant.permission:task.create')->name('projects.tasks.store');

    Route::get('/tasks/dashboard', [TenantOperationsController::class, 'taskDashboard'])->middleware('tenant.permission:task.view')->name('tasks.dashboard');
    Route::get('/tasks/kanban', [TenantOperationsController::class, 'taskKanban'])->middleware('tenant.permission:task.view')->name('tasks.kanban');
    Route::get('/tasks/calendar', [TenantOperationsController::class, 'taskCalendar'])->middleware('tenant.permission:task.view')->name('tasks.calendar');
    Route::get('/tasks/my', [TenantOperationsController::class, 'tasks'])->defaults('scope', 'my')->middleware('tenant.permission:task.view')->name('tasks.my');
    Route::get('/tasks/team', [TenantOperationsController::class, 'tasks'])->defaults('scope', 'team')->middleware('tenant.permission:task.view')->name('tasks.team');
    Route::post('/tasks/bulk/update', [TenantOperationsController::class, 'bulkUpdateTasks'])->middleware('tenant.permission:task.edit')->name('tasks.bulk.update');
    Route::post('/tasks/export', [TenantOperationsController::class, 'exportTasks'])->middleware('tenant.permission:task.view')->name('tasks.export');
    Route::get('/tasks', [TenantOperationsController::class, 'tasks'])->middleware('tenant.permission:task.view')->name('tasks.index');
    Route::post('/tasks', [TenantOperationsController::class, 'storeTask'])->middleware('tenant.permission:task.create')->name('tasks.store');
    Route::get('/tasks/{task_uuid}', [TenantOperationsController::class, 'showTask'])->middleware('tenant.permission:task.view')->name('tasks.show');
    Route::match(['put', 'patch'], '/tasks/{task_uuid}', [TenantOperationsController::class, 'updateTask'])->middleware('tenant.permission:task.edit')->name('tasks.update');
    Route::delete('/tasks/{task_uuid}', [TenantOperationsController::class, 'archiveTask'])->middleware('tenant.permission:task.delete')->name('tasks.destroy');
    Route::post('/tasks/{task_uuid}/assign', [TenantOperationsController::class, 'assignTask'])->middleware('tenant.permission:task.assign')->name('tasks.assign');
    Route::post('/tasks/{task_uuid}/status', [TenantOperationsController::class, 'taskStatus'])->middleware('tenant.permission:task.edit')->name('tasks.status');
    Route::post('/tasks/{task_uuid}/complete', [TenantOperationsController::class, 'completeTask'])->middleware('tenant.permission:task.edit')->name('tasks.complete');
    Route::post('/tasks/{task_uuid}/clone', [TenantOperationsController::class, 'cloneTask'])->middleware('tenant.permission:task.create')->name('tasks.clone');
    foreach (['checklists', 'comments', 'dependencies', 'watchers', 'time-logs'] as $resource) {
        Route::get('/tasks/{task_uuid}/'.$resource, [TenantOperationsController::class, 'taskChildren'])->defaults('resource', $resource)->middleware('tenant.permission:task.view')->name('tasks.'.$resource.'.index');
        Route::post('/tasks/{task_uuid}/'.$resource, [TenantOperationsController::class, 'storeTaskChild'])->defaults('resource', $resource)->middleware('tenant.permission:task.edit')->name('tasks.'.$resource.'.store');
    }
    Route::post('/tasks/{task_uuid}/checklists/{checklist_id}/items', [TenantOperationsController::class, 'addChecklistItem'])->whereNumber('checklist_id')->middleware('tenant.permission:task.edit')->name('tasks.checklists.items.store');
    Route::match(['put', 'patch'], '/tasks/{task_uuid}/checklist-items/{item_id}', [TenantOperationsController::class, 'updateChecklistItem'])->whereNumber('item_id')->middleware('tenant.permission:task.edit')->name('tasks.checklist-items.update');
    Route::post('/tasks/{task_uuid}/checklist-items/{item_id}/complete', [TenantOperationsController::class, 'completeChecklistItem'])->whereNumber('item_id')->middleware('tenant.permission:task.edit')->name('tasks.checklist-items.complete');
    Route::match(['put', 'patch'], '/tasks/{task_uuid}/{resource}/{id}', [TenantOperationsController::class, 'updateTaskChild'])->whereIn('resource', ['comments', 'time-logs'])->whereNumber('id')->middleware('tenant.permission:task.edit')->name('tasks.children.update');
    Route::delete('/tasks/{task_uuid}/{resource}/{id}', [TenantOperationsController::class, 'deleteTaskChild'])->whereIn('resource', ['comments', 'dependencies'])->whereNumber('id')->middleware('tenant.permission:task.edit')->name('tasks.children.destroy');
    Route::delete('/tasks/{task_uuid}/watchers/{user_uuid}', [TenantOperationsController::class, 'deleteWatcher'])->middleware('tenant.permission:task.edit')->name('tasks.watchers.destroy');

    // My list: todo and calendar utilities.
    Route::get('/todo-lists/dashboard', [TenantOperationsController::class, 'todoDashboard'])->middleware('tenant.permission:todo.view')->name('todo-lists.dashboard');
    Route::get('/todo-lists/kanban', [TenantOperationsController::class, 'todoKanban'])->middleware('tenant.permission:todo.view')->name('todo-lists.kanban');
    Route::get('/todo-lists/calendar', [TenantOperationsController::class, 'todoCalendar'])->middleware('tenant.permission:todo.view')->name('todo-lists.calendar');
    Route::post('/todo-lists/export', [TenantOperationsController::class, 'exportTodo'])->middleware('tenant.permission:todo.view')->name('todo-lists.export');
    Route::get('/todo-lists', [TenantOperationsController::class, 'todoLists'])->middleware('tenant.permission:todo.view')->name('todo-lists.index');
    Route::post('/todo-lists', [TenantOperationsController::class, 'storeTodo'])->middleware('tenant.permission:todo.create')->name('todo-lists.store');
    Route::get('/todo-lists/{todo_list_uuid}', [TenantOperationsController::class, 'showTodo'])->middleware('tenant.permission:todo.view')->name('todo-lists.show');
    Route::match(['put', 'patch'], '/todo-lists/{todo_list_uuid}', [TenantOperationsController::class, 'updateTodo'])->middleware('tenant.permission:todo.edit')->name('todo-lists.update');
    Route::delete('/todo-lists/{todo_list_uuid}', [TenantOperationsController::class, 'archiveTodo'])->middleware('tenant.permission:todo.delete')->name('todo-lists.destroy');
    Route::get('/todo-lists/{todo_list_uuid}/tasks', [TenantOperationsController::class, 'todoTasks'])->middleware('tenant.permission:todo.view')->name('todo-lists.tasks');

    Route::get('/issues/dashboard', [TenantOperationsController::class, 'issueDashboard'])->middleware('tenant.permission:issue.view')->name('issues.dashboard');
    Route::get('/issues/kanban', [TenantOperationsController::class, 'issueKanban'])->middleware('tenant.permission:issue.view')->name('issues.kanban');
    Route::post('/issues/export', [TenantOperationsController::class, 'exportIssues'])->middleware('tenant.permission:issue.view')->name('issues.export');
    Route::get('/issues', [TenantOperationsController::class, 'issues'])->middleware('tenant.permission:issue.view')->name('issues.index');
    Route::post('/issues', [TenantOperationsController::class, 'storeIssue'])->middleware('tenant.permission:issue.create')->name('issues.store');
    Route::get('/issues/{issue_uuid}', [TenantOperationsController::class, 'showIssue'])->middleware('tenant.permission:issue.view')->name('issues.show');
    Route::match(['put', 'patch'], '/issues/{issue_uuid}', [TenantOperationsController::class, 'updateIssue'])->middleware('tenant.permission:issue.edit')->name('issues.update');
    Route::delete('/issues/{issue_uuid}', [TenantOperationsController::class, 'archiveIssue'])->middleware('tenant.permission:issue.delete')->name('issues.destroy');
    Route::post('/issues/{issue_uuid}/assign', [TenantOperationsController::class, 'assignIssue'])->middleware('tenant.permission:issue.assign')->name('issues.assign');
    Route::post('/issues/{issue_uuid}/status', [TenantOperationsController::class, 'issueStatus'])->middleware('tenant.permission:issue.edit')->name('issues.status');
    Route::post('/issues/{issue_uuid}/resolve', [TenantOperationsController::class, 'resolveIssue'])->middleware('tenant.permission:issue.close')->name('issues.resolve');
    Route::post('/issues/{issue_uuid}/close', [TenantOperationsController::class, 'closeIssue'])->middleware('tenant.permission:issue.close')->name('issues.close');
    Route::post('/issues/{issue_uuid}/reopen', [TenantOperationsController::class, 'reopenIssue'])->middleware('tenant.permission:issue.close')->name('issues.reopen');
    Route::get('/issues/{issue_uuid}/time-logs', [TenantOperationsController::class, 'issueTimeLogs'])->middleware('tenant.permission:issue.view')->name('issues.time-logs.index');
    Route::post('/issues/{issue_uuid}/time-logs', [TenantOperationsController::class, 'storeIssueTimeLog'])->middleware('tenant.permission:task.log_time')->name('issues.time-logs.store');
    Route::post('/issues/{issue_uuid}/create-task', [TenantOperationsController::class, 'createIssueTask'])->middleware('tenant.permission:task.create')->name('issues.create-task');
    Route::get('/issues/{issue_uuid}/activity', [TenantOperationsController::class, 'issueActivity'])->middleware('tenant.permission:activity_log.view')->name('issues.activity');

    Route::get('/renewals/dashboard', [TenantOperationsController::class, 'renewalDashboard'])->middleware('tenant.permission:renewal.view')->name('renewals.dashboard');
    Route::get('/renewals/calendar', [TenantOperationsController::class, 'renewalCalendar'])->middleware('tenant.permission:renewal.view')->name('renewals.calendar');
    Route::get('/client-renewals', [TenantOperationsController::class, 'renewals'])->defaults('renewal_type', 'client')->middleware('tenant.permission:renewal.view')->name('renewals.clients');
    Route::get('/vendor-renewals', [TenantOperationsController::class, 'renewals'])->defaults('renewal_type', 'vendor')->middleware('tenant.permission:renewal.view')->name('renewals.vendors');
    Route::post('/renewals/export', [TenantOperationsController::class, 'exportRenewals'])->middleware('tenant.permission:renewal.view')->name('renewals.export');
    Route::get('/renewals', [TenantOperationsController::class, 'renewals'])->middleware('tenant.permission:renewal.view')->name('renewals.index');
    Route::post('/renewals', [TenantOperationsController::class, 'storeRenewal'])->middleware('tenant.permission:renewal.create')->name('renewals.store');
    Route::get('/renewals/{renewal_uuid}', [TenantOperationsController::class, 'showRenewal'])->middleware('tenant.permission:renewal.view')->name('renewals.show');
    Route::match(['put', 'patch'], '/renewals/{renewal_uuid}', [TenantOperationsController::class, 'updateRenewal'])->middleware('tenant.permission:renewal.edit')->name('renewals.update');
    Route::delete('/renewals/{renewal_uuid}', [TenantOperationsController::class, 'archiveRenewal'])->middleware('tenant.permission:renewal.delete')->name('renewals.destroy');
    Route::post('/renewals/{renewal_uuid}/renew', [TenantOperationsController::class, 'renewRenewal'])->middleware('tenant.permission:renewal.renew')->name('renewals.renew');
    Route::post('/renewals/{renewal_uuid}/cancel', [TenantOperationsController::class, 'cancelRenewal'])->middleware('tenant.permission:renewal.edit')->name('renewals.cancel');
    foreach (['items', 'reminders'] as $resource) {
        Route::get('/renewals/{renewal_uuid}/'.$resource, [TenantOperationsController::class, 'renewalChildren'])->defaults('resource', $resource)->middleware('tenant.permission:renewal.view')->name('renewals.'.$resource.'.index');
        Route::post('/renewals/{renewal_uuid}/'.$resource, [TenantOperationsController::class, 'storeRenewalChild'])->defaults('resource', $resource)->middleware('tenant.permission:renewal.edit')->name('renewals.'.$resource.'.store');
        Route::match(['put', 'patch'], '/renewals/{renewal_uuid}/'.$resource.'/{id}', [TenantOperationsController::class, 'updateRenewalChild'])->whereNumber('id')->defaults('resource', $resource)->middleware('tenant.permission:renewal.edit')->name('renewals.'.$resource.'.update');
    }
    Route::delete('/renewals/{renewal_uuid}/items/{id}', [TenantOperationsController::class, 'deleteRenewalChild'])->whereNumber('id')->defaults('resource', 'items')->middleware('tenant.permission:renewal.edit')->name('renewals.items.destroy');
    Route::get('/renewals/{renewal_uuid}/history', [TenantOperationsController::class, 'renewalChildren'])->defaults('resource', 'history')->middleware('tenant.permission:renewal.view')->name('renewals.history');
    Route::post('/renewals/{renewal_uuid}/send-reminder', [TenantOperationsController::class, 'sendRenewalReminder'])->middleware('tenant.permission:renewal.edit')->name('renewals.send-reminder');

    Route::get('/calendars', [TenantOperationsController::class, 'calendars'])->middleware('tenant.permission:calendar.view')->name('calendars.index');
    Route::post('/calendars', [TenantOperationsController::class, 'storeCalendar'])->middleware('tenant.permission:calendar.create')->name('calendars.store');
    Route::get('/calendars/{calendar_uuid}', [TenantOperationsController::class, 'showCalendar'])->middleware('tenant.permission:calendar.view')->name('calendars.show');
    Route::match(['put', 'patch'], '/calendars/{calendar_uuid}', [TenantOperationsController::class, 'updateCalendar'])->middleware('tenant.permission:calendar.edit')->name('calendars.update');
    Route::delete('/calendars/{calendar_uuid}', [TenantOperationsController::class, 'deleteCalendar'])->middleware('tenant.permission:calendar.delete')->name('calendars.destroy');
    Route::get('/calendar-events', [TenantOperationsController::class, 'events'])->middleware('tenant.permission:calendar.view')->name('calendar-events.index');
    Route::post('/calendar-events', [TenantOperationsController::class, 'storeEvent'])->middleware('tenant.permission:calendar.create')->name('calendar-events.store');
    Route::get('/calendar-events/{event_uuid}', [TenantOperationsController::class, 'showEvent'])->middleware('tenant.permission:calendar.view')->name('calendar-events.show');
    Route::match(['put', 'patch'], '/calendar-events/{event_uuid}', [TenantOperationsController::class, 'updateEvent'])->middleware('tenant.permission:calendar.edit')->name('calendar-events.update');
    Route::delete('/calendar-events/{event_uuid}', [TenantOperationsController::class, 'deleteEvent'])->middleware('tenant.permission:calendar.delete')->name('calendar-events.destroy');
    Route::post('/calendar-events/{event_uuid}/reschedule', [TenantOperationsController::class, 'rescheduleEvent'])->middleware('tenant.permission:calendar.edit')->name('calendar-events.reschedule');
    foreach (['attendees', 'reminders'] as $resource) {
        Route::get('/calendar-events/{event_uuid}/'.$resource, [TenantOperationsController::class, 'eventChildren'])->defaults('resource', $resource)->middleware('tenant.permission:calendar.view')->name('calendar-events.'.$resource.'.index');
        Route::post('/calendar-events/{event_uuid}/'.$resource, [TenantOperationsController::class, 'storeEventChild'])->defaults('resource', $resource)->middleware('tenant.permission:calendar.edit')->name('calendar-events.'.$resource.'.store');
    }
    Route::match(['put', 'patch'], '/calendar-events/{event_uuid}/attendees/{id}', [TenantOperationsController::class, 'updateEventChild'])->whereNumber('id')->defaults('resource', 'attendees')->middleware('tenant.permission:calendar.edit')->name('calendar-events.attendees.update');
    Route::post('/calendar-events/{event_uuid}/video-meeting', [TenantOperationsController::class, 'videoMeeting'])->middleware('tenant.permission:calendar.edit')->name('calendar-events.video-meeting');
    Route::post('/calendar-events/{event_uuid}/room-booking', [TenantOperationsController::class, 'roomBooking'])->middleware('tenant.permission:calendar.edit')->name('calendar-events.room-booking');
    Route::get('/meeting-rooms', [TenantOperationsController::class, 'meetingRooms'])->middleware('tenant.permission:calendar.view')->name('meeting-rooms.index');
    Route::post('/meeting-rooms', [TenantOperationsController::class, 'storeMeetingRoom'])->middleware('tenant.permission:calendar.manage_team')->name('meeting-rooms.store');
    Route::match(['put', 'patch'], '/meeting-rooms/{room_id}', [TenantOperationsController::class, 'updateMeetingRoom'])->whereNumber('room_id')->middleware('tenant.permission:calendar.manage_team')->name('meeting-rooms.update');

    Route::get('/finance/dashboard', [TenantBusinessController::class, 'financeDashboard'])->middleware('tenant.permission:finance.invoice.view')->name('finance.dashboard');
    Route::post('/invoices/export', [TenantBusinessController::class, 'exportInvoices'])->middleware('tenant.permission:finance.invoice.view')->name('invoices.export');
    Route::get('/invoices', [TenantBusinessController::class, 'invoices'])->middleware('tenant.permission:finance.invoice.view')->name('invoices.index');
    Route::post('/invoices', [TenantBusinessController::class, 'storeInvoice'])->middleware('tenant.permission:finance.invoice.create')->name('invoices.store');
    Route::get('/invoices/{invoice_uuid}', [TenantBusinessController::class, 'showInvoice'])->middleware('tenant.permission:finance.invoice.view')->name('invoices.show');
    Route::match(['put', 'patch'], '/invoices/{invoice_uuid}', [TenantBusinessController::class, 'updateInvoice'])->middleware('tenant.permission:finance.invoice.edit')->name('invoices.update');
    Route::post('/invoices/{invoice_uuid}/items', [TenantBusinessController::class, 'addInvoiceItem'])->middleware('tenant.permission:finance.invoice.edit')->name('invoices.items.store');
    Route::match(['put', 'patch'], '/invoices/{invoice_uuid}/items/{item_id}', [TenantBusinessController::class, 'updateInvoiceItem'])->whereNumber('item_id')->middleware('tenant.permission:finance.invoice.edit')->name('invoices.items.update');
    Route::delete('/invoices/{invoice_uuid}/items/{item_id}', [TenantBusinessController::class, 'deleteInvoiceItem'])->whereNumber('item_id')->middleware('tenant.permission:finance.invoice.edit')->name('invoices.items.destroy');
    Route::post('/invoices/{invoice_uuid}/send', [TenantBusinessController::class, 'sendInvoice'])->middleware('tenant.permission:finance.invoice.send')->name('invoices.send');
    Route::post('/invoices/{invoice_uuid}/cancel', [TenantBusinessController::class, 'cancelInvoice'])->middleware('tenant.permission:finance.invoice.edit')->name('invoices.cancel');
    Route::get('/invoices/{invoice_uuid}/pdf', [TenantBusinessController::class, 'invoicePdf'])->middleware('tenant.permission:finance.invoice.view')->name('invoices.pdf');
    Route::post('/payments/export', [TenantBusinessController::class, 'exportPayments'])->middleware('tenant.permission:finance.payment.view')->name('payments.export');
    Route::get('/payments', [TenantBusinessController::class, 'payments'])->middleware('tenant.permission:finance.payment.view')->name('payments.index');
    Route::post('/payments', [TenantBusinessController::class, 'storePayment'])->middleware('tenant.permission:finance.payment.create')->name('payments.store');
    Route::get('/payments/{payment_uuid}', [TenantBusinessController::class, 'showPayment'])->middleware('tenant.permission:finance.payment.view')->name('payments.show');
    Route::post('/payments/{payment_uuid}/void', [TenantBusinessController::class, 'voidPayment'])->middleware('tenant.permission:finance.payment.edit')->name('payments.void');
    Route::get('/payments/{payment_uuid}/receipt', [TenantBusinessController::class, 'paymentReceipt'])->middleware('tenant.permission:finance.payment.view')->name('payments.receipt');
    Route::post('/expenses/export', [TenantBusinessController::class, 'exportExpenses'])->middleware('tenant.permission:finance.expense.view')->name('expenses.export');
    Route::get('/expenses', [TenantBusinessController::class, 'expenses'])->middleware('tenant.permission:finance.expense.view')->name('expenses.index');
    Route::post('/expenses', [TenantBusinessController::class, 'storeExpense'])->middleware('tenant.permission:finance.expense.create')->name('expenses.store');
    Route::get('/expenses/{expense_uuid}', [TenantBusinessController::class, 'showExpense'])->middleware('tenant.permission:finance.expense.view')->name('expenses.show');
    Route::match(['put', 'patch'], '/expenses/{expense_uuid}', [TenantBusinessController::class, 'updateExpense'])->middleware('tenant.permission:finance.expense.edit')->name('expenses.update');
    Route::post('/expenses/{expense_uuid}/approve', [TenantBusinessController::class, 'approveExpense'])->middleware('tenant.permission:finance.expense.approve')->name('expenses.approve');
    Route::post('/expenses/{expense_uuid}/reject', [TenantBusinessController::class, 'rejectExpense'])->middleware('tenant.permission:finance.expense.approve')->name('expenses.reject');
    Route::get('/bank-accounts', [TenantBusinessController::class, 'bankAccounts'])->middleware('tenant.permission:finance.bank_account.view')->name('bank-accounts.index');
    Route::post('/bank-accounts', [TenantBusinessController::class, 'bankAccounts'])->middleware('tenant.permission:finance.bank_account.edit')->name('bank-accounts.store');
    Route::match(['put', 'patch'], '/bank-accounts/{account_id}', [TenantBusinessController::class, 'updateBankAccount'])->whereNumber('account_id')->middleware('tenant.permission:finance.bank_account.edit')->name('bank-accounts.update');
    Route::delete('/bank-accounts/{account_id}', [TenantBusinessController::class, 'deleteBankAccount'])->whereNumber('account_id')->middleware('tenant.permission:finance.bank_account.edit')->name('bank-accounts.destroy');
    Route::post('/bank-accounts/{account_id}/set-primary', [TenantBusinessController::class, 'setPrimaryBankAccount'])->whereNumber('account_id')->middleware('tenant.permission:finance.bank_account.edit')->name('bank-accounts.primary');
    Route::get('/documents/dashboard', [TenantBusinessController::class, 'documentDashboard'])->middleware('tenant.permission:document.view')->name('documents.dashboard');
    Route::get('/document-folders', [TenantBusinessController::class, 'folders'])->middleware('tenant.permission:document.view')->name('document-folders.index');
    Route::post('/document-folders', [TenantBusinessController::class, 'folders'])->middleware('tenant.permission:document.upload')->name('document-folders.store');
    Route::post('/document-folders/{folder_uuid}/files', [TenantBusinessController::class, 'attachFileToFolder'])->middleware('tenant.permission:document.upload')->name('document-folders.files.store');
    // Finance, reports, and operational settings.
    Route::get('/reports/dashboard', [TenantBusinessController::class, 'reportsDashboard'])->middleware('tenant.permission:report.view')->name('reports.dashboard');
    Route::get('/reports/custom', [TenantBusinessController::class, 'customReports'])->middleware('tenant.permission:report.view')->name('reports.custom.index');
    Route::post('/reports/custom', [TenantBusinessController::class, 'storeCustomReport'])->middleware('tenant.permission:report.edit')->name('reports.custom.store');
    Route::post('/reports/custom/{report_uuid}/run', [TenantBusinessController::class, 'runCustomReport'])->middleware('tenant.permission:report.view')->name('reports.custom.run');
    Route::post('/reports/{report_code}/export', [TenantBusinessController::class, 'exportReport'])->middleware('tenant.permission:report.export')->name('reports.export');
    Route::get('/reports/{report_code}', [TenantBusinessController::class, 'report'])->middleware('tenant.permission:report.view')->name('reports.show');
    foreach (['general', 'company', 'branding', 'localization', 'communication', 'security', 'storage', 'hr', 'crm', 'integrations'] as $group) {
        Route::get('/settings/'.$group, [TenantBusinessController::class, 'settingsGroup'])->defaults('group', $group)->middleware('tenant.permission:setting.view')->name('settings.'.$group);
        Route::match(['put', 'patch'], '/settings/'.$group, [TenantBusinessController::class, 'settingsGroup'])->defaults('group', $group)->middleware('tenant.permission:setting.edit')->name('settings.'.$group.'.update');
    }
    Route::get('/settings/lookups', [TenantBusinessController::class, 'settingsLookups'])->middleware('tenant.permission:setting.view')->name('settings.lookups');
    Route::put('/settings/lookups/reorder', [TenantBusinessController::class, 'reorderLookups'])->middleware('tenant.permission:setting.edit')->name('settings.lookups.reorder');
    Route::delete('/settings/lookups/{lookup_uuid}', [TenantBusinessController::class, 'deleteLookup'])->middleware('tenant.permission:setting.edit')->name('settings.lookups.delete');
    Route::get('/settings/notification-templates', [TenantBusinessController::class, 'notificationTemplates'])->middleware('tenant.permission:setting.view')->name('settings.templates');
    Route::post('/settings/notification-templates', [TenantBusinessController::class, 'notificationTemplates'])->middleware('tenant.permission:setting.edit')->name('settings.templates.store');
    Route::match(['put', 'patch'], '/settings/notification-templates/{template_uuid}', [TenantBusinessController::class, 'updateNotificationTemplate'])->middleware('tenant.permission:setting.edit')->name('settings.templates.update');
    Route::post('/settings/notification-templates/{template_uuid}/test-send', [TenantBusinessController::class, 'testNotificationTemplate'])->middleware('tenant.permission:setting.edit')->name('settings.templates.test');
    Route::get('/settings/backups/runs', [TenantBusinessController::class, 'backupRuns'])->middleware('tenant.permission:setting.view')->name('settings.backups.runs');
    Route::post('/settings/backups/run', [TenantBusinessController::class, 'runBackup'])->middleware('tenant.permission:setting.edit')->name('settings.backups.run');
    Route::post('/settings/backups/restore', [TenantBusinessController::class, 'restoreBackup'])->middleware('tenant.permission:setting.edit')->name('settings.backups.restore');
    Route::get('/integrations/providers', [TenantBusinessController::class, 'providers'])->middleware('tenant.permission:setting.view')->name('integrations.providers');
    Route::get('/integrations', [TenantBusinessController::class, 'integrations'])->middleware('tenant.permission:setting.view')->name('integrations.index');
    Route::post('/integrations', [TenantBusinessController::class, 'integrations'])->middleware('tenant.permission:setting.edit')->name('integrations.store');
    Route::get('/integrations/{integration_uuid}', [TenantBusinessController::class, 'showIntegration'])->whereUuid('integration_uuid')->middleware('tenant.permission:setting.view')->name('integrations.show');
    Route::match(['put', 'patch'], '/integrations/{integration_uuid}', [TenantBusinessController::class, 'updateIntegration'])->whereUuid('integration_uuid')->middleware('tenant.permission:setting.edit')->name('integrations.update');
    Route::post('/integrations/{integration_uuid}/credentials/rotate', [TenantBusinessController::class, 'rotateCredentials'])->whereUuid('integration_uuid')->middleware('tenant.permission:setting.edit')->name('integrations.credentials.rotate');
    Route::post('/integrations/{integration_uuid}/disconnect', [TenantBusinessController::class, 'disconnectIntegration'])->whereUuid('integration_uuid')->middleware('tenant.permission:setting.edit')->name('integrations.disconnect');
    Route::get('/integrations/webhooks', [TenantBusinessController::class, 'webhooks'])->middleware('tenant.permission:setting.view')->name('integrations.webhooks');
    Route::get('/integrations/sync-jobs', [TenantBusinessController::class, 'syncJobs'])->middleware('tenant.permission:setting.view')->name('integrations.sync-jobs');
    Route::post('/integrations/sync-jobs/{job_id}/retry', [TenantBusinessController::class, 'retrySyncJob'])->whereNumber('job_id')->middleware('tenant.permission:setting.edit')->name('integrations.sync-jobs.retry');
    Route::get('/integrations/{integration_uuid}/field-mappings', [TenantBusinessController::class, 'mappings'])->whereUuid('integration_uuid')->middleware('tenant.permission:setting.view')->name('integrations.mappings');
    Route::put('/integrations/{integration_uuid}/field-mappings', [TenantBusinessController::class, 'replaceMappings'])->whereUuid('integration_uuid')->middleware('tenant.permission:setting.edit')->name('integrations.mappings.replace');
    Route::get('/integrations/{integration_uuid}/rate-limits', [TenantBusinessController::class, 'rateLimits'])->whereUuid('integration_uuid')->middleware('tenant.permission:setting.view')->name('integrations.rate-limits');
    Route::get('/audit/{type}', [TenantBusinessController::class, 'audit'])->whereIn('type', ['activity-logs', 'login-history', 'system-api-logs', 'data-changes'])->middleware('tenant.permission:audit_log.view')->name('audit.index');
    Route::get('/audit/activity-logs/{activity_id}/compare', [TenantBusinessController::class, 'compareAudit'])->whereNumber('activity_id')->middleware('tenant.permission:audit_log.view')->name('audit.compare');
    Route::post('/audit/export', [TenantBusinessController::class, 'exportAudit'])->middleware('tenant.permission:audit_log.view')->name('audit.export');
    Route::get('/business/selectors', [TenantBusinessController::class, 'selectors'])->name('business.selectors');
    Route::get('/files', [SharedPrimitiveController::class, 'files'])->middleware('tenant.permission:document.view')->name('files.index');
    Route::post('/files', [SharedPrimitiveController::class, 'upload'])->middleware('tenant.permission:document.upload')->name('files.store');
    Route::get('/files/{file_uuid}', [SharedPrimitiveController::class, 'file'])->middleware('tenant.permission:document.view')->name('files.show');
    Route::get('/files/{file_uuid}/download', [SharedPrimitiveController::class, 'download'])->middleware('tenant.permission:document.view')->name('files.download');
    Route::delete('/files/{file_uuid}', [SharedPrimitiveController::class, 'deleteFile'])->middleware('tenant.permission:document.delete')->name('files.destroy');
    Route::get('/attachments', [SharedPrimitiveController::class, 'attachments'])->middleware('tenant.permission:document.view')->name('attachments.index');
    Route::post('/attachments', [SharedPrimitiveController::class, 'attach'])->middleware('tenant.permission:document.upload')->name('attachments.store');
    Route::delete('/attachments/{attachment_id}', [SharedPrimitiveController::class, 'detach'])->whereNumber('attachment_id')->middleware('tenant.permission:document.delete')->name('attachments.destroy');
    Route::get('/notes', [SharedPrimitiveController::class, 'notes'])->middleware('tenant.permission:document.view')->name('notes.index');
    Route::post('/notes', [SharedPrimitiveController::class, 'createNote'])->middleware('tenant.permission:document.upload')->name('notes.store');
    Route::match(['put', 'patch'], '/notes/{note_uuid}', [SharedPrimitiveController::class, 'updateNote'])->middleware('tenant.permission:document.upload')->name('notes.update');
    Route::delete('/notes/{note_uuid}', [SharedPrimitiveController::class, 'deleteNote'])->middleware('tenant.permission:document.delete')->name('notes.destroy');
    Route::get('/activity-logs', [SharedPrimitiveController::class, 'activityLogs'])->middleware('tenant.permission:audit_log.view')->name('activity-logs.index');
    Route::get('/activity-logs/{activity_id}/compare', [SharedPrimitiveController::class, 'activityCompare'])->whereNumber('activity_id')->middleware('tenant.permission:audit_log.view')->name('activity-logs.compare');
    Route::get('/tags', [SharedPrimitiveController::class, 'tags'])->middleware('tenant.permission:setting.view')->name('tags.index');
    Route::get('/lookups', [SharedPrimitiveController::class, 'lookups'])->middleware('tenant.permission:setting.view')->name('lookups.index');
    Route::post('/tags', [SharedPrimitiveController::class, 'createTag'])->middleware('tenant.permission:setting.edit')->name('tags.store');
    Route::match(['put', 'patch'], '/tags/{tag_uuid}', [SharedPrimitiveController::class, 'updateTag'])->middleware('tenant.permission:setting.edit')->name('tags.update');
    Route::delete('/tags/{tag_uuid}', [SharedPrimitiveController::class, 'deleteTag'])->middleware('tenant.permission:setting.edit')->name('tags.destroy');
    Route::post('/taggables', [SharedPrimitiveController::class, 'tagRecord'])->middleware('tenant.permission:document.upload')->name('taggables.store');
    Route::delete('/taggables', [SharedPrimitiveController::class, 'untagRecord'])->middleware('tenant.permission:document.delete')->name('taggables.destroy');
    Route::get('/custom-fields', [SharedPrimitiveController::class, 'customFields'])->middleware('tenant.permission:setting.view')->name('custom-fields.index');
    Route::post('/custom-fields', [SharedPrimitiveController::class, 'createCustomField'])->middleware('tenant.permission:setting.edit')->name('custom-fields.store');
    Route::match(['put', 'patch'], '/custom-fields/{field_uuid}', [SharedPrimitiveController::class, 'updateCustomField'])->middleware('tenant.permission:setting.edit')->name('custom-fields.update');
    Route::delete('/custom-fields/{field_uuid}', [SharedPrimitiveController::class, 'deleteCustomField'])->middleware('tenant.permission:setting.edit')->name('custom-fields.destroy');
    Route::get('/custom-field-values', [SharedPrimitiveController::class, 'customFieldValues'])->middleware('tenant.permission:setting.view')->name('custom-field-values.index');
    Route::put('/custom-field-values', [SharedPrimitiveController::class, 'replaceCustomFieldValues'])->middleware('tenant.permission:setting.edit')->name('custom-field-values.replace');
    Route::get('/reminders', [SharedPrimitiveController::class, 'reminders'])->middleware('tenant.permission:document.view')->name('reminders.index');
    Route::post('/reminders', [SharedPrimitiveController::class, 'createReminder'])->middleware('tenant.permission:document.upload')->name('reminders.store');
    Route::match(['put', 'patch'], '/reminders/{reminder_uuid}', [SharedPrimitiveController::class, 'updateReminder'])->middleware('tenant.permission:document.upload')->name('reminders.update');
    Route::delete('/reminders/{reminder_uuid}', [SharedPrimitiveController::class, 'deleteReminder'])->middleware('tenant.permission:document.delete')->name('reminders.destroy');
    // Workspace tools, notifications, and help center.
    Route::get('/notifications', [TenantEngagementController::class, 'notifications'])->middleware('tenant.permission:notification.view')->name('notifications.index');
    Route::get('/notifications/unread-count', [TenantEngagementController::class, 'unreadCount'])->middleware('tenant.permission:notification.view')->name('notifications.unread-count');
    Route::post('/notifications/bulk/read', [TenantEngagementController::class, 'bulkRead'])->middleware('tenant.permission:notification.manage')->name('notifications.bulk.read');
    Route::post('/notifications/{notification_id}/read', [TenantEngagementController::class, 'markRead'])->middleware('tenant.permission:notification.manage')->name('notifications.read');
    Route::post('/notifications/{notification_id}/unread', [TenantEngagementController::class, 'markUnread'])->middleware('tenant.permission:notification.manage')->name('notifications.unread');
    Route::delete('/notifications/{notification_id}', [TenantEngagementController::class, 'deleteNotification'])->middleware('tenant.permission:notification.manage')->name('notifications.delete');
    Route::get('/communication/logs', [TenantEngagementController::class, 'communicationLogs'])->middleware('tenant.permission:setting.view')->name('communication.logs');
    Route::post('/communication/email', [TenantEngagementController::class, 'sendEmail'])->name('communication.email');
    Route::post('/communication/logs/{log_uuid}/retry', [TenantEngagementController::class, 'retryCommunication'])->middleware('tenant.permission:setting.edit')->name('communication.logs.retry');
    Route::get('/help/articles', [TenantEngagementController::class, 'helpArticles'])->name('help.articles');
    Route::get('/help/articles/{slug}', [TenantEngagementController::class, 'helpArticle'])->name('help.articles.show');
    Route::get('/help/faqs', [TenantEngagementController::class, 'faqs'])->name('help.faqs');
    Route::get('/help/release-notes', [TenantEngagementController::class, 'releaseNotes'])->name('help.release-notes');
    Route::post('/help/contact-support', [TenantEngagementController::class, 'contactSupport'])->name('help.contact-support');
    Route::get('/help/system-status', [TenantEngagementController::class, 'systemStatus'])->name('help.system-status');
    Route::get('/profile/api-tokens', [TenantApiTokenController::class, 'index'])->name('profile.api-tokens.index');
    Route::post('/profile/api-tokens', [TenantApiTokenController::class, 'store'])->name('profile.api-tokens.store');
    Route::post('/profile/api-tokens/{token_uuid}/rotate', [TenantApiTokenController::class, 'rotate'])->name('profile.api-tokens.rotate');
    Route::post('/profile/api-tokens/{token_uuid}/revoke', [TenantApiTokenController::class, 'revoke'])->name('profile.api-tokens.revoke');
});



