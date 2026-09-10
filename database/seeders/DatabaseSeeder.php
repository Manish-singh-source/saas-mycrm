<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        
        $this->call(CountryStateCitySeeder::class);
        $this->call(MasterDataSeeder::class);
        
        $this->call([
            PlatformCatalogSeeder::class,
            PlatformPermissionMapSeeder::class,
            TenantPermissionMapSeeder::class,
            PlatformSuperAdminSeeder::class,
            PlatformKnowledgeBaseSeeder::class,
            PlatformLegalDocumentSeeder::class,
            PlatformMonitoringServiceSeeder::class,
            IntegrationProviderSeeder::class,
        ]);
    }
}
