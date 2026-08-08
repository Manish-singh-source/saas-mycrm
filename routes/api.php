<?php

use App\Http\Controllers\Auth\TenantRegistrationController;
use App\Http\Controllers\Auth\UnifiedAuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth/v1')
    ->as('api.auth.v1.')
    ->group(function (): void {
        Route::post('/tenants/register', [TenantRegistrationController::class, 'store'])->name('tenants.register');
        Route::post('/accounts/discover', [UnifiedAuthController::class, 'discover'])->name('accounts.discover');
        Route::post('/accounts/login', [UnifiedAuthController::class, 'login'])->name('accounts.login');
        Route::post('/accounts/login/2fa', [UnifiedAuthController::class, 'verifyTwoFactor'])->name('accounts.login.2fa');
        Route::post('/password/forgot', [UnifiedAuthController::class, 'forgotPassword'])->name('password.forgot');
        Route::post('/password/reset', [UnifiedAuthController::class, 'resetPassword'])->name('password.reset');

        Route::middleware('auth:sanctum')->group(function (): void {
            Route::get('/me', [UnifiedAuthController::class, 'me'])->name('me');
            Route::post('/logout', [UnifiedAuthController::class, 'logout'])->name('logout');
        });
    });

Route::prefix('platform/v1')
    ->as('api.platform.v1.')
    ->group(base_path('routes/api-platform.php'));

Route::prefix('tenant/v1')
    ->as('api.tenant.v1.')
    ->group(base_path('routes/api-tenant.php'));