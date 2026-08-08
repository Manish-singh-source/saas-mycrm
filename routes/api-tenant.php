<?php

use App\Http\Controllers\Tenant\TenantApiTokenController;
use App\Http\Controllers\Tenant\TenantPermissionController;
use App\Http\Controllers\Tenant\TenantRoleController;
use App\Http\Controllers\Tenant\TenantAuthController;
use App\Http\Controllers\Tenant\TenantHealthController;
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