<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class PlatformMonitoringServiceSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $services = [
            ['name' => 'MySQL Database', 'code' => 'database', 'service_type' => 'database', 'status' => 'active', 'check_interval_seconds' => 60, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Application Cache', 'code' => 'cache', 'service_type' => 'cache', 'status' => 'active', 'check_interval_seconds' => 60, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Database Queue Worker', 'code' => 'queue', 'service_type' => 'queue', 'status' => 'active', 'check_interval_seconds' => 60, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Laravel Scheduler', 'code' => 'scheduler', 'service_type' => 'scheduler', 'status' => 'active', 'check_interval_seconds' => 60, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Local File Storage', 'code' => 'storage', 'service_type' => 'storage', 'status' => 'active', 'check_interval_seconds' => 300, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'SMTP Mail Transport', 'code' => 'mail', 'service_type' => 'mail', 'status' => 'active', 'check_interval_seconds' => 300, 'created_at' => $now, 'updated_at' => $now],
        ];

        DB::table('monitoring_services')->upsert(
            $services,
            ['code'],
            ['name', 'service_type', 'status', 'check_interval_seconds', 'updated_at']
        );
    }
}