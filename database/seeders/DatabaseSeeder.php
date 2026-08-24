<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            MasterDataSeeder::class,
            PlatformPermissionMapSeeder::class,
            TenantPermissionMapSeeder::class,
            PlatformRoleSeeder::class,
            PlatformSuperAdminSeeder::class,
            Phase1PlatformSeeder::class,
            TenantLookupSeeder::class,
            BillingCatalogSeeder::class,
            Phase2PlatformCatalogSeeder::class,
            Phase3PlatformConfigurationSeeder::class,
            DemoTenantFoundationSeeder::class,
            DemoTenantSettingsSeeder::class,
            TenantIntegrationProviderSeeder::class,
            TenantRoleSeeder::class,
            DemoRelationalDataSeeder::class,
        ]);
    }
}


