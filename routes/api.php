<?php

use App\Http\Controllers\Common\LocationController;
use App\Http\Controllers\Shared\SignedFileDownloadController;
use App\Http\Controllers\Auth\TenantRegistrationController;
use App\Http\Controllers\Auth\UnifiedAuthController;
use Illuminate\Support\Facades\Route;

// Shared endpoints used by both platform and tenant surfaces.
Route::get('/files/signed-download/{file_uuid}', SignedFileDownloadController::class)->name('api.files.signed-download')->middleware('signed');

// Public common lookups.
Route::prefix('common/v1')
    ->as('api.common.v1.')
    ->group(function (): void {
        Route::get('/locations/countries', [LocationController::class, 'countries'])->name('locations.countries');
        Route::get('/locations/states', [LocationController::class, 'states'])->name('locations.states');
        Route::get('/locations/cities', [LocationController::class, 'cities'])->name('locations.cities');
    });

// Unified login, discovery, password reset, and tenant registration flows.
Route::prefix('auth/v1')
    ->as('api.auth.v1.')
    ->group(function (): void {
        Route::get('/tenants/plans', [TenantRegistrationController::class, 'plans'])->name('tenants.plans');
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

// Platform admin API surface.
Route::prefix('platform/v1')
    ->as('api.platform.v1.')
    ->group(base_path('routes/api-platform.php'));

// Tenant admin API surface.
Route::prefix('tenant/v1')
    ->as('api.tenant.v1.')
    ->group(base_path('routes/api-tenant.php'));
