<?php

use App\Http\Controllers\LocationController;
use App\Http\Controllers\MasterDataController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:api-common')->prefix('common/v1')->group(function (): void {
    Route::get('locations/countries', [LocationController::class, 'countries']);
    Route::get('locations/states', [LocationController::class, 'states']);
    Route::get('locations/cities', [LocationController::class, 'cities']);

    Route::get('business-types', [MasterDataController::class, 'businessTypes']);
    Route::get('industries', [MasterDataController::class, 'industries']);
    Route::get('currencies', [MasterDataController::class, 'currencies']);
    Route::get('languages', [MasterDataController::class, 'languages']);
    Route::get('timezones', [MasterDataController::class, 'timezones']);
    Route::get('dateformats', [MasterDataController::class, 'dateFormats']);
    Route::get('timeformats', [MasterDataController::class, 'timeFormats']);
});

Route::prefix('auth/v1')->group(function (): void {
    // Notifications & Legal Documents
    Route::get('announcements', [App\Http\Controllers\PlatformAnnouncementController::class, 'publicIndex']);
    Route::get('legal/{document_type}', [App\Http\Controllers\PlatformLegalDocumentController::class, 'publicDocument']);
    
    // Tenant Registration
    Route::get('tenants/plans', [App\Http\Controllers\TenantRegistrationController::class, 'plans'])->middleware('throttle:api-common');
    Route::post('tenants/register', [App\Http\Controllers\TenantRegistrationController::class, 'store'])->middleware('throttle:api-auth');
    Route::post('tenants/register/payment/confirm', [App\Http\Controllers\TenantRegistrationController::class, 'confirmPayment'])->middleware('throttle:api-auth');
    
    // Authentication
    Route::post('accounts/discover', [App\Http\Controllers\UnifiedAuthController::class, 'discover'])->middleware('throttle:api-auth');
    Route::post('accounts/login', [App\Http\Controllers\UnifiedAuthController::class, 'login'])->middleware('throttle:api-auth');
    Route::post('accounts/login/2fa', [App\Http\Controllers\UnifiedAuthController::class, 'verifyTwoFactor'])->middleware('throttle:api-auth');
    Route::post('password/forgot', [App\Http\Controllers\PasswordRecoveryController::class, 'forgot'])->middleware('throttle:api-password-forgot');
    Route::post('password/reset', [App\Http\Controllers\PasswordRecoveryController::class, 'reset'])->middleware('throttle:api-password-reset');
    Route::middleware(['auth:sanctum', 'throttle:api-authenticated'])->group(function (): void {
        Route::get('me', [App\Http\Controllers\UnifiedAuthController::class, 'me']);
        Route::post('logout', [App\Http\Controllers\UnifiedAuthController::class, 'logout']);
        Route::post('refresh', [App\Http\Controllers\UnifiedAuthController::class, 'refresh']);
    });
});

Route::prefix('platform/v1')->group(base_path('routes/api-platform.php'));
Route::prefix('tenant/v1')->group(base_path('routes/api-tenant.php'));
