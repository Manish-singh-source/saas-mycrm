<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class PlatformDummyOrganizationSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PlatformDummyRoleSeeder::class,
            PlatformDummyDepartmentSeeder::class,
            PlatformDummyDesignationSeeder::class,
            PlatformDummyStaffSeeder::class,
            PlatformDummyTeamRoleSeeder::class,
            PlatformDummyTeamSeeder::class,
        ]);
    }
}
