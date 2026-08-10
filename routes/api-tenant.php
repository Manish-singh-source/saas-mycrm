<?php

use App\Http\Controllers\Tenant\TenantApiTokenController;
use App\Http\Controllers\Tenant\TenantClientController;
use App\Http\Controllers\Tenant\TenantDashboardController;
use App\Http\Controllers\Tenant\TenantLeadController;
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

Route::get('/health', TenantHealthController::class)->name('health');

Route::middleware('tenant.context')->prefix('auth')->name('auth.')->group(function (): void {
    Route::post('/forgot-password', [TenantAuthController::class, 'forgotPassword'])->name('forgot-password');
    Route::post('/reset-password', [TenantAuthController::class, 'resetPassword'])->name('reset-password');
});

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


    Route::get('/navigation/sidebar', [TenantDashboardController::class, 'sidebar'])->name('navigation.sidebar');
    Route::get('/dashboard/summary', [TenantDashboardController::class, 'summary'])->middleware('tenant.permission:dashboard.view')->name('dashboard.summary');
    Route::get('/dashboard/charts/{chart}', [TenantDashboardController::class, 'chart'])->middleware('tenant.permission:dashboard.view')->name('dashboard.charts');
    Route::get('/dashboard/recent-activities', [TenantDashboardController::class, 'recentActivities'])->middleware('tenant.permission:activity_log.view')->name('dashboard.recent-activities');
    Route::get('/dashboard/widgets', [TenantDashboardController::class, 'widgets'])->middleware('tenant.permission:dashboard.view')->name('dashboard.widgets');
    Route::put('/dashboard/widgets', [TenantDashboardController::class, 'updateWidgets'])->middleware('tenant.permission:dashboard.customize')->name('dashboard.widgets.update');
    Route::post('/dashboard/export', [TenantDashboardController::class, 'export'])->middleware('tenant.permission:dashboard.view')->name('dashboard.export');
    Route::get('/dashboard/{widget}', [TenantDashboardController::class, 'table'])->whereIn('widget', ['my-tasks', 'upcoming-events', 'recent-leads', 'overdue-invoices'])->middleware('tenant.permission:dashboard.view')->name('dashboard.widgets.tables');
    Route::prefix('access-control')->name('access-control.')->group(function (): void {
        Route::get('/roles', [TenantRoleController::class, 'index'])->middleware('tenant.permission:role.view')->name('roles.index');
        Route::post('/roles', [TenantRoleController::class, 'store'])->middleware('tenant.permission:role.create')->name('roles.store');
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
    Route::get('/vendors/{vendor_uuid}/addresses', [TenantVendorController::class, 'addresses'])->middleware('tenant.permission:vendor.view')->name('vendors.addresses.index');
    Route::post('/vendors/{vendor_uuid}/addresses', [TenantVendorController::class, 'storeAddress'])->middleware('tenant.permission:vendor.edit')->name('vendors.addresses.store');
    Route::match(['put', 'patch'], '/vendors/{vendor_uuid}/addresses/{address_id}', [TenantVendorController::class, 'updateAddress'])->whereNumber('address_id')->middleware('tenant.permission:vendor.edit')->name('vendors.addresses.update');
    Route::get('/vendors/{vendor_uuid}/bank-accounts', [TenantVendorController::class, 'bankAccounts'])->middleware('tenant.permission:finance.bank_account.view')->name('vendors.bank-accounts.index');
    Route::post('/vendors/{vendor_uuid}/bank-accounts', [TenantVendorController::class, 'storeBankAccount'])->middleware('tenant.permission:finance.bank_account.create')->name('vendors.bank-accounts.store');
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
    Route::get('/leads/{lead_uuid}/activities', [TenantLeadController::class, 'activities'])->middleware('tenant.permission:lead.view')->name('leads.activities.index');
    Route::post('/leads/{lead_uuid}/activities', [TenantLeadController::class, 'storeActivity'])->middleware('tenant.permission:lead.edit')->name('leads.activities.store');
    Route::match(['put', 'patch'], '/leads/{lead_uuid}/activities/{activity_uuid}', [TenantLeadController::class, 'updateActivity'])->middleware('tenant.permission:lead.edit')->name('leads.activities.update');
    Route::get('/leads/{lead_uuid}/activity', [TenantLeadController::class, 'activity'])->middleware('tenant.permission:activity_log.view')->name('leads.activity');
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
    Route::get('/profile/api-tokens', [TenantApiTokenController::class, 'index'])->name('profile.api-tokens.index');
    Route::post('/profile/api-tokens', [TenantApiTokenController::class, 'store'])->name('profile.api-tokens.store');
    Route::post('/profile/api-tokens/{token_uuid}/rotate', [TenantApiTokenController::class, 'rotate'])->name('profile.api-tokens.rotate');
    Route::post('/profile/api-tokens/{token_uuid}/revoke', [TenantApiTokenController::class, 'revoke'])->name('profile.api-tokens.revoke');
});




