<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\SeedsRecords;
use Illuminate\Database\Seeder;
use App\Models\PlatformUser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PlatformSuperAdminSeeder extends Seeder
{
    use SeedsRecords;


    public function run(): void
    {
        $email = (string) env('PLATFORM_SUPER_ADMIN_EMAIL', 'support@technofra.com');
        $password = (string) env('PLATFORM_SUPER_ADMIN_PASSWORD', 'Password@123');
        $now = now();

        $existing = DB::table('platform_users')->where('email', $email)->first();

        $values = [
            'employee_code' => 'SA-0001',
            'first_name' => 'Super',
            'last_name' => 'Admin',
            'display_name' => 'Super Admin',
            'designation' => 'Super Administrator',
            'department' => 'Platform',
            'timezone' => (string) env('APP_TIMEZONE', 'UTC'),
            'locale' => 'en',
            'email_verified_at' => $now,
            'two_factor_enabled' => false,
            'status' => 'active',
            'updated_at' => $now,
        ];

        if ($existing === null) {
            $userId = (int) DB::table('platform_users')->insertGetId(array_merge($values, [
                'uuid' => (string) Str::uuid(),
                'email' => $email,
                'password' => Hash::make($password),
                'created_at' => $now,
            ]));
        } else {
            DB::table('platform_users')->where('id', $existing->id)->update($values);
            $userId = (int) $existing->id;
        }

        $role = DB::table('platform_roles')
            ->where('name', 'super_admin')
            ->where('guard_name', 'platform')
            ->first();

        if ($role !== null) {
            $this->seedPivot('platform_model_has_roles', [
                'role_id' => (int) $role->id,
                'model_id' => $userId,
                'model_type' => PlatformUser::class,
            ]);
        }
    }
}