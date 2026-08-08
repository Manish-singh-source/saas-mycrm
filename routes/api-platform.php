<?php

use App\Http\Controllers\Platform\PlatformDashboardController;
use App\Http\Controllers\Platform\PlatformStaffController;
use App\Http\Controllers\Platform\PlatformTeamController;
use App\Http\Controllers\Platform\PlatformTenantController;
use App\Http\Controllers\Platform\PlatformApiTokenController;
use App\Http\Controllers\Platform\PlatformPermissionController;
use App\Http\Controllers\Platform\PlatformRoleController;
use App\Http\Controllers\Platform\PlatformAuthController;
use App\Http\Controllers\Platform\PlatformHealthController;
use App\Http\Controllers\Shared\SharedPrimitiveController;
use Illuminate\Support\Facades\Route;

Route::get('/health', PlatformHealthController::class)->name('health');

Route::prefix('auth')->name('auth.')->group(function (): void {
    Route::post('/forgot-password', [PlatformAuthController::class, 'forgotPassword'])->name('forgot-password');
    Route::post('/reset-password', [PlatformAuthController::class, 'resetPassword'])->name('reset-password');
});

Route::middleware(['auth:sanctum', 'platform.token'])->group(function (): void {
    Route::prefix('auth')->name('auth.')->group(function (): void {
        Route::post('/logout', [PlatformAuthController::class, 'logout'])->name('logout');
        Route::post('/refresh', [PlatformAuthController::class, 'refresh'])->name('refresh');
        Route::get('/me', [PlatformAuthController::class, 'me'])->name('me');
        Route::post('/verify-email/resend', [PlatformAuthController::class, 'resendVerification'])->name('verify-email.resend');
        Route::post('/2fa/enable', [PlatformAuthController::class, 'enable2fa'])->name('2fa.enable');
        Route::post('/2fa/confirm', [PlatformAuthController::class, 'confirm2fa'])->name('2fa.confirm');
        Route::post('/2fa/disable', [PlatformAuthController::class, 'disable2fa'])->name('2fa.disable');
    });

    Route::get('/profile', [PlatformAuthController::class, 'profile'])->name('profile.show');
    Route::match(['put', 'patch'], '/profile', [PlatformAuthController::class, 'updateProfile'])->name('profile.update');
    Route::put('/profile/password', [PlatformAuthController::class, 'changePassword'])->name('profile.password');
    Route::get('/settings/preferences', [PlatformAuthController::class, 'preferences'])->name('settings.preferences.show');
    Route::put('/settings/preferences', [PlatformAuthController::class, 'updatePreferences'])->name('settings.preferences.update');
    Route::get('/profile/sessions', [PlatformAuthController::class, 'sessions'])->name('profile.sessions.index');
    Route::delete('/profile/sessions/{session_id}', [PlatformAuthController::class, 'revokeSession'])->whereNumber('session_id')->name('profile.sessions.revoke');



    Route::prefix('dashboard')->name('dashboard.')->group(function (): void {
        Route::get('/summary', [PlatformDashboardController::class, 'summary'])->middleware('platform.permission:dashboard.view')->name('summary');
        Route::get('/charts', [PlatformDashboardController::class, 'charts'])->middleware('platform.permission:dashboard.view')->name('charts');
        Route::get('/recent', [PlatformDashboardController::class, 'recent'])->middleware('platform.permission:dashboard.view')->name('recent');
        Route::get('/alerts', [PlatformDashboardController::class, 'alerts'])->middleware('platform.permission:dashboard.view')->name('alerts');
        Route::post('/export', [PlatformDashboardController::class, 'export'])->middleware('platform.permission:dashboard.view')->name('export');
    });

    Route::post('/platform-users/export', [PlatformStaffController::class, 'export'])->middleware('platform.permission:platform_user.view')->name('platform-users.export');
    Route::post('/platform-users/invite', [PlatformStaffController::class, 'invite'])->middleware('platform.permission:platform_user.create')->name('platform-users.invite');
    Route::get('/platform-users', [PlatformStaffController::class, 'index'])->middleware('platform.permission:platform_user.view')->name('platform-users.index');
    Route::post('/platform-users', [PlatformStaffController::class, 'store'])->middleware('platform.permission:platform_user.create')->name('platform-users.store');
    Route::get('/platform-users/{platform_user_uuid}', [PlatformStaffController::class, 'show'])->middleware('platform.permission:platform_user.view')->name('platform-users.show');
    Route::match(['put', 'patch'], '/platform-users/{platform_user_uuid}', [PlatformStaffController::class, 'update'])->middleware('platform.permission:platform_user.edit')->name('platform-users.update');
    Route::delete('/platform-users/{platform_user_uuid}', [PlatformStaffController::class, 'destroy'])->middleware('platform.permission:platform_user.delete')->name('platform-users.destroy');
    Route::post('/platform-users/{platform_user_uuid}/restore', [PlatformStaffController::class, 'restore'])->middleware('platform.permission:platform_user.edit')->name('platform-users.restore');
    Route::post('/platform-users/{platform_user_uuid}/suspend', [PlatformStaffController::class, 'suspend'])->middleware('platform.permission:platform_user.suspend')->name('platform-users.suspend');
    Route::post('/platform-users/{platform_user_uuid}/activate', [PlatformStaffController::class, 'activate'])->middleware('platform.permission:platform_user.edit')->name('platform-users.activate');
    Route::post('/platform-users/{platform_user_uuid}/reset-password', [PlatformStaffController::class, 'resetPassword'])->middleware('platform.permission:platform_user.edit')->name('platform-users.reset-password');
    Route::post('/platform-users/{platform_user_uuid}/force-logout', [PlatformStaffController::class, 'forceLogout'])->middleware('platform.permission:platform_user.edit')->name('platform-users.force-logout');
    Route::post('/platform-users/{platform_user_uuid}/require-2fa', [PlatformStaffController::class, 'require2fa'])->middleware('platform.permission:platform_user.edit')->name('platform-users.require-2fa');
    Route::get('/platform-users/{platform_user_uuid}/roles', [PlatformStaffController::class, 'roles'])->middleware('platform.permission:platform_user.view')->name('platform-users.roles');
    Route::put('/platform-users/{platform_user_uuid}/roles', [PlatformStaffController::class, 'syncRoles'])->middleware('platform.permission:platform_user.edit')->name('platform-users.roles.sync');
    Route::get('/platform-users/{platform_user_uuid}/permissions', [PlatformStaffController::class, 'permissions'])->middleware('platform.permission:platform_user.view')->name('platform-users.permissions');
    Route::put('/platform-users/{platform_user_uuid}/permissions', [PlatformStaffController::class, 'syncPermissions'])->middleware('platform.permission:platform_user.edit')->name('platform-users.permissions.sync');
    Route::get('/platform-users/{platform_user_uuid}/activity', [PlatformStaffController::class, 'activity'])->middleware('platform.permission:audit_log.view')->name('platform-users.activity');

    Route::get('/platform-teams', [PlatformTeamController::class, 'index'])->middleware('platform.permission:platform_team.view')->name('platform-teams.index');
    Route::post('/platform-teams', [PlatformTeamController::class, 'store'])->middleware('platform.permission:platform_team.create')->name('platform-teams.store');
    Route::get('/platform-teams/{team_uuid}', [PlatformTeamController::class, 'show'])->middleware('platform.permission:platform_team.view')->name('platform-teams.show');
    Route::match(['put', 'patch'], '/platform-teams/{team_uuid}', [PlatformTeamController::class, 'update'])->middleware('platform.permission:platform_team.edit')->name('platform-teams.update');
    Route::delete('/platform-teams/{team_uuid}', [PlatformTeamController::class, 'destroy'])->middleware('platform.permission:platform_team.delete')->name('platform-teams.destroy');
    Route::get('/platform-teams/{team_uuid}/members', [PlatformTeamController::class, 'members'])->middleware('platform.permission:platform_team.view')->name('platform-teams.members');
    Route::post('/platform-teams/{team_uuid}/members', [PlatformTeamController::class, 'addMember'])->middleware('platform.permission:platform_team.assign')->name('platform-teams.members.store');
    Route::match(['put', 'patch'], '/platform-teams/{team_uuid}/members/{member_id}', [PlatformTeamController::class, 'updateMember'])->whereNumber('member_id')->middleware('platform.permission:platform_team.assign')->name('platform-teams.members.update');
    Route::delete('/platform-teams/{team_uuid}/members/{member_id}', [PlatformTeamController::class, 'removeMember'])->whereNumber('member_id')->middleware('platform.permission:platform_team.assign')->name('platform-teams.members.destroy');
    Route::get('/platform-teams/{team_uuid}/assignments', [PlatformTeamController::class, 'assignments'])->middleware('platform.permission:platform_team.view')->name('platform-teams.assignments');
    Route::post('/platform-teams/{team_uuid}/assignments', [PlatformTeamController::class, 'assign'])->middleware('platform.permission:platform_team.assign')->name('platform-teams.assignments.store');
    Route::delete('/platform-teams/{team_uuid}/assignments/{assignment_id}', [PlatformTeamController::class, 'releaseAssignment'])->whereNumber('assignment_id')->middleware('platform.permission:platform_team.assign')->name('platform-teams.assignments.destroy');
    Route::get('/platform-team-roles', [PlatformTeamController::class, 'teamRoles'])->middleware('platform.permission:platform_team.view')->name('platform-team-roles.index');
    Route::post('/platform-team-roles', [PlatformTeamController::class, 'createTeamRole'])->middleware('platform.permission:platform_team.create')->name('platform-team-roles.store');
    Route::match(['put', 'patch'], '/platform-team-roles/{role_uuid}', [PlatformTeamController::class, 'updateTeamRole'])->middleware('platform.permission:platform_team.edit')->name('platform-team-roles.update');

    Route::get('/tenants', [PlatformTenantController::class, 'index'])->middleware('platform.permission:tenant.view')->name('tenants.index');
    Route::post('/tenants', [PlatformTenantController::class, 'store'])->middleware('platform.permission:tenant.create')->name('tenants.store');
    Route::get('/tenants/{tenant_uuid}', [PlatformTenantController::class, 'show'])->middleware('platform.permission:tenant.view')->name('tenants.show');
    Route::match(['put', 'patch'], '/tenants/{tenant_uuid}', [PlatformTenantController::class, 'update'])->middleware('platform.permission:tenant.edit')->name('tenants.update');
    Route::delete('/tenants/{tenant_uuid}', [PlatformTenantController::class, 'destroy'])->middleware('platform.permission:tenant.delete')->name('tenants.destroy');
    Route::post('/tenants/{tenant_uuid}/restore', [PlatformTenantController::class, 'restore'])->middleware('platform.permission:tenant.edit')->name('tenants.restore');
    Route::post('/tenants/{tenant_uuid}/activate', [PlatformTenantController::class, 'activate'])->middleware('platform.permission:tenant.activate')->name('tenants.activate');
    Route::post('/tenants/{tenant_uuid}/suspend', [PlatformTenantController::class, 'suspend'])->middleware('platform.permission:tenant.suspend')->name('tenants.suspend');
    Route::post('/tenants/{tenant_uuid}/reactivate', [PlatformTenantController::class, 'reactivate'])->middleware('platform.permission:tenant.activate')->name('tenants.reactivate');
    Route::post('/tenants/{tenant_uuid}/archive', [PlatformTenantController::class, 'archive'])->middleware('platform.permission:tenant.delete')->name('tenants.archive');
    Route::post('/tenants/{tenant_uuid}/extend-trial', [PlatformTenantController::class, 'extendTrial'])->middleware('platform.permission:subscription.edit')->name('tenants.extend-trial');
    Route::post('/tenants/{tenant_uuid}/impersonate', [PlatformTenantController::class, 'remoteLogin'])->middleware('platform.permission:tenant.impersonate')->name('tenants.impersonate');
    Route::delete('/tenants/{tenant_uuid}/impersonate/{session_uuid}', [PlatformTenantController::class, 'endRemoteLogin'])->middleware('platform.permission:tenant.impersonate')->name('tenants.impersonate.end');
    Route::get('/tenants/{tenant_uuid}/{tab}', [PlatformTenantController::class, 'tab'])->whereIn('tab', ['users','offices','subscription','billing','usage','modules','settings','integrations','security','support','files','activity'])->middleware('platform.permission:tenant.view')->name('tenants.tab');
    Route::put('/tenants/{tenant_uuid}/modules', [PlatformTenantController::class, 'moduleOverrides'])->middleware('platform.permission:module.edit')->name('tenants.modules.update');
    Route::prefix('access-control')->name('access-control.')->group(function (): void {
        Route::get('/roles', [PlatformRoleController::class, 'index'])->middleware('platform.permission:platform_role.view')->name('roles.index');
        Route::post('/roles', [PlatformRoleController::class, 'store'])->middleware('platform.permission:platform_role.create')->name('roles.store');
        Route::get('/roles/{role_uuid}', [PlatformRoleController::class, 'show'])->middleware('platform.permission:platform_role.view')->name('roles.show');
        Route::match(['put', 'patch'], '/roles/{role_uuid}', [PlatformRoleController::class, 'update'])->middleware('platform.permission:platform_role.edit')->name('roles.update');
        Route::delete('/roles/{role_uuid}', [PlatformRoleController::class, 'destroy'])->middleware('platform.permission:platform_role.delete')->name('roles.destroy');
        Route::post('/roles/{role_uuid}/clone', [PlatformRoleController::class, 'clone'])->middleware('platform.permission:platform_role.create')->name('roles.clone');
        Route::post('/roles/{role_uuid}/activate', [PlatformRoleController::class, 'activate'])->middleware('platform.permission:platform_role.edit')->name('roles.activate');
        Route::post('/roles/{role_uuid}/deactivate', [PlatformRoleController::class, 'deactivate'])->middleware('platform.permission:platform_role.edit')->name('roles.deactivate');
        Route::get('/roles/{role_uuid}/permissions', [PlatformRoleController::class, 'permissions'])->middleware('platform.permission:platform_role.view')->name('roles.permissions');
        Route::put('/roles/{role_uuid}/permissions', [PlatformRoleController::class, 'syncPermissions'])->middleware('platform.permission:platform_role.edit')->name('roles.permissions.sync');
        Route::get('/roles/{role_uuid}/users', [PlatformRoleController::class, 'users'])->middleware('platform.permission:platform_role.view')->name('roles.users');
        Route::post('/roles/{role_uuid}/users', [PlatformRoleController::class, 'assignUsers'])->middleware('platform.permission:platform_role.edit')->name('roles.users.assign');
        Route::delete('/roles/{role_uuid}/users/{platform_user_uuid}', [PlatformRoleController::class, 'removeUser'])->middleware('platform.permission:platform_role.edit')->name('roles.users.remove');

        Route::get('/permissions/grouped', [PlatformPermissionController::class, 'grouped'])->middleware('platform.permission:platform_permission.view')->name('permissions.grouped');
        Route::get('/permissions', [PlatformPermissionController::class, 'index'])->middleware('platform.permission:platform_permission.view')->name('permissions.index');
        Route::post('/permissions', [PlatformPermissionController::class, 'store'])->middleware('platform.permission:platform_permission.create')->name('permissions.store');
        Route::get('/permissions/{permission_uuid}', [PlatformPermissionController::class, 'show'])->middleware('platform.permission:platform_permission.view')->name('permissions.show');
        Route::match(['put', 'patch'], '/permissions/{permission_uuid}', [PlatformPermissionController::class, 'update'])->middleware('platform.permission:platform_permission.edit')->name('permissions.update');
        Route::delete('/permissions/{permission_uuid}', [PlatformPermissionController::class, 'destroy'])->middleware('platform.permission:platform_permission.delete')->name('permissions.destroy');
    });

    Route::get('/files', [SharedPrimitiveController::class, 'files'])->middleware('platform.permission:document.view')->name('files.index');
    Route::post('/files', [SharedPrimitiveController::class, 'upload'])->middleware('platform.permission:document.upload')->name('files.store');
    Route::get('/files/{file_uuid}', [SharedPrimitiveController::class, 'file'])->middleware('platform.permission:document.view')->name('files.show');
    Route::get('/files/{file_uuid}/download', [SharedPrimitiveController::class, 'download'])->middleware('platform.permission:document.view')->name('files.download');
    Route::delete('/files/{file_uuid}', [SharedPrimitiveController::class, 'deleteFile'])->middleware('platform.permission:document.delete')->name('files.destroy');
    Route::get('/attachments', [SharedPrimitiveController::class, 'attachments'])->middleware('platform.permission:document.view')->name('attachments.index');
    Route::post('/attachments', [SharedPrimitiveController::class, 'attach'])->middleware('platform.permission:document.upload')->name('attachments.store');
    Route::delete('/attachments/{attachment_id}', [SharedPrimitiveController::class, 'detach'])->whereNumber('attachment_id')->middleware('platform.permission:document.delete')->name('attachments.destroy');
    Route::get('/notes', [SharedPrimitiveController::class, 'notes'])->middleware('platform.permission:document.view')->name('notes.index');
    Route::post('/notes', [SharedPrimitiveController::class, 'createNote'])->middleware('platform.permission:document.upload')->name('notes.store');
    Route::match(['put', 'patch'], '/notes/{note_uuid}', [SharedPrimitiveController::class, 'updateNote'])->middleware('platform.permission:document.upload')->name('notes.update');
    Route::delete('/notes/{note_uuid}', [SharedPrimitiveController::class, 'deleteNote'])->middleware('platform.permission:document.delete')->name('notes.destroy');
    Route::get('/activity-logs', [SharedPrimitiveController::class, 'activityLogs'])->middleware('platform.permission:audit_log.view')->name('activity-logs.index');
    Route::get('/activity-logs/{activity_id}/compare', [SharedPrimitiveController::class, 'activityCompare'])->whereNumber('activity_id')->middleware('platform.permission:audit_log.view')->name('activity-logs.compare');
    Route::get('/api-tokens', [PlatformApiTokenController::class, 'index'])->name('api-tokens.index');
    Route::post('/api-tokens', [PlatformApiTokenController::class, 'store'])->name('api-tokens.store');
    Route::get('/api-tokens/{token_uuid}', [PlatformApiTokenController::class, 'show'])->name('api-tokens.show');
    Route::post('/api-tokens/{token_uuid}/rotate', [PlatformApiTokenController::class, 'rotate'])->name('api-tokens.rotate');
    Route::post('/api-tokens/{token_uuid}/revoke', [PlatformApiTokenController::class, 'revoke'])->name('api-tokens.revoke');
});