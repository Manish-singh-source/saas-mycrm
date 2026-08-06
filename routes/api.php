<?php

use Illuminate\Support\Facades\Route;

Route::prefix('platform/v1')
    ->as('api.platform.v1.')
    ->group(base_path('routes/api-platform.php'));

Route::prefix('tenant/v1')
    ->as('api.tenant.v1.')
    ->group(base_path('routes/api-tenant.php'));