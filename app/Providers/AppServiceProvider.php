<?php

namespace App\Providers;

use App\Tenancy\TenantContext;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(TenantContext::class, fn (): TenantContext => new TenantContext());
    }

    public function boot(): void
    {
        //
    }
}