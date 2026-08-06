<?php

use App\Http\Controllers\Tenant\TenantHealthController;
use Illuminate\Support\Facades\Route;

Route::get('/health', TenantHealthController::class)->name('health');
