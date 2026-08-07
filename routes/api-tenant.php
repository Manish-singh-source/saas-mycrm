<?php

use App\Http\Controllers\Tenant\TenantApiTokenController;
use App\Http\Controllers\Tenant\TenantAuthController;
use App\Http\Controllers\Tenant\TenantHealthController;
use Illuminate\Support\Facades\Route;

Route::get('/health', TenantHealthController::class)->name('health');

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

    Route::get('/profile/api-tokens', [TenantApiTokenController::class, 'index'])->name('profile.api-tokens.index');
    Route::post('/profile/api-tokens', [TenantApiTokenController::class, 'store'])->name('profile.api-tokens.store');
    Route::post('/profile/api-tokens/{token_uuid}/rotate', [TenantApiTokenController::class, 'rotate'])->name('profile.api-tokens.rotate');
    Route::post('/profile/api-tokens/{token_uuid}/revoke', [TenantApiTokenController::class, 'revoke'])->name('profile.api-tokens.revoke');
});