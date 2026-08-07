<?php

use App\Http\Controllers\Platform\PlatformApiTokenController;
use App\Http\Controllers\Platform\PlatformAuthController;
use App\Http\Controllers\Platform\PlatformHealthController;
use Illuminate\Support\Facades\Route;

Route::get('/health', PlatformHealthController::class)->name('health');

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

    Route::get('/api-tokens', [PlatformApiTokenController::class, 'index'])->name('api-tokens.index');
    Route::post('/api-tokens', [PlatformApiTokenController::class, 'store'])->name('api-tokens.store');
    Route::get('/api-tokens/{token_uuid}', [PlatformApiTokenController::class, 'show'])->name('api-tokens.show');
    Route::post('/api-tokens/{token_uuid}/rotate', [PlatformApiTokenController::class, 'rotate'])->name('api-tokens.rotate');
    Route::post('/api-tokens/{token_uuid}/revoke', [PlatformApiTokenController::class, 'revoke'])->name('api-tokens.revoke');
});