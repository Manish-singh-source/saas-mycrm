<?php

use App\Http\Controllers\Tenant\TenantApiTokenController;
use App\Http\Controllers\Tenant\TenantPermissionController;
use App\Http\Controllers\Tenant\TenantRoleController;
use App\Http\Controllers\Tenant\TenantAuthController;
use App\Http\Controllers\Tenant\TenantHealthController;
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
    Route::get('/profile/api-tokens', [TenantApiTokenController::class, 'index'])->name('profile.api-tokens.index');
    Route::post('/profile/api-tokens', [TenantApiTokenController::class, 'store'])->name('profile.api-tokens.store');
    Route::post('/profile/api-tokens/{token_uuid}/rotate', [TenantApiTokenController::class, 'rotate'])->name('profile.api-tokens.rotate');
    Route::post('/profile/api-tokens/{token_uuid}/revoke', [TenantApiTokenController::class, 'revoke'])->name('profile.api-tokens.revoke');
});