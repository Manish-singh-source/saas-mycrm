<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

class PlatformSuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $password = env('PLATFORM_SUPER_ADMIN_PASSWORD');
        if (! is_string($password) || $password === '') {
            throw new RuntimeException('PLATFORM_SUPER_ADMIN_PASSWORD must be set before seeding the platform super admin.');
        }

        DB::transaction(function () use ($password): void {
            $now = now();
            $roleId = DB::table('platform_roles')->where([
                'name' => 'super_admin', 'guard_name' => 'platform',
            ])->value('id');

            if ($roleId === null) {
                $roleId = DB::table('platform_roles')->insertGetId([
                    'uuid' => (string) Str::uuid(), 'name' => 'super_admin',
                    'display_name' => 'Super Admin', 'guard_name' => 'platform',
                    'description' => 'Full access to the platform.', 'is_system' => true,
                    'status' => 'active', 'created_at' => $now, 'updated_at' => $now,
                ]);
            }

            $email = env('PLATFORM_SUPER_ADMIN_EMAIL', 'admin@example.com');
            $userId = DB::table('platform_users')->where('email', $email)->value('id');
            $user = [
                'employee_code' => env('PLATFORM_SUPER_ADMIN_EMPLOYEE_CODE', 'PSA-0001'),
                'first_name' => env('PLATFORM_SUPER_ADMIN_FIRST_NAME', 'Super'),
                'last_name' => env('PLATFORM_SUPER_ADMIN_LAST_NAME', 'Admin'),
                'display_name' => env('PLATFORM_SUPER_ADMIN_DISPLAY_NAME', 'Super Admin'),
                'email' => $email, 'mobile' => env('PLATFORM_SUPER_ADMIN_MOBILE'),
                'password' => Hash::make($password),
                'timezone' => config('app.timezone', 'UTC'), 'locale' => 'en',
                'email_verified_at' => $now, 'status' => 'active', 'updated_at' => $now,
            ];

            if ($userId === null) {
                $user['uuid'] = (string) Str::uuid();
                $user['created_at'] = $now;
                $userId = DB::table('platform_users')->insertGetId($user);
            } else {
                DB::table('platform_users')->where('id', $userId)->update($user);
            }

            DB::table('platform_role_has_permissions')->insertOrIgnore(
                DB::table('platform_permissions')->pluck('id')->map(fn (int $permissionId): array => [
                    'role_id' => $roleId, 'permission_id' => $permissionId,
                ])->all(),
            );

            DB::table('platform_model_has_roles')->insertOrIgnore([
                'role_id' => $roleId, 'model_id' => $userId,
                'model_type' => 'App\\Models\\PlatformUser',
            ]);
        });
    }
}
