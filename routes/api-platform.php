<?php

use App\Http\Controllers\Platform\PlatformHealthController;
use Illuminate\Support\Facades\Route;

Route::get('/health', PlatformHealthController::class)->name('health');
