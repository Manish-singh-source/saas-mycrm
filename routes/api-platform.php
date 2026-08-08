<?php

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