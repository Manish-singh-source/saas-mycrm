<?php

namespace Database\Seeders;

use App\Models\PlatformUser;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PlatformDummyStaffSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $now = now();
            $password = Hash::make('123456789');
            $staff = [
                ['employee_code' => 'PL-DUMMY-0001', 'first_name' => 'Aarav', 'last_name' => 'Mehta', 'email' => 'aarav.mehta.platform@example.com', 'mobile' => '+919810010001', 'department' => 'PL-DEPT-EXEC', 'designation' => 'PL-DES-COO', 'manager' => null, 'roles' => ['platform_admin']],
                ['employee_code' => 'PL-DUMMY-0002', 'first_name' => 'Nisha', 'last_name' => 'Rao', 'email' => 'nisha.rao.platform@example.com', 'mobile' => '+919810010002', 'department' => 'PL-DEPT-CS', 'designation' => 'PL-DES-DIR-CS', 'manager' => 'aarav.mehta.platform@example.com', 'roles' => ['customer_success_manager', 'operations_analyst']],
                ['employee_code' => 'PL-DUMMY-0003', 'first_name' => 'Kabir', 'last_name' => 'Kapoor', 'email' => 'kabir.kapoor.platform@example.com', 'mobile' => '+919810010003', 'department' => 'PL-DEPT-SUPPORT', 'designation' => 'PL-DES-SUP-MGR', 'manager' => 'aarav.mehta.platform@example.com', 'roles' => ['support_manager']],
                ['employee_code' => 'PL-DUMMY-0004', 'first_name' => 'Priya', 'last_name' => 'Sharma', 'email' => 'priya.sharma.platform@example.com', 'mobile' => '+919810010004', 'department' => 'PL-DEPT-FIN', 'designation' => 'PL-DES-FIN-MGR', 'manager' => 'aarav.mehta.platform@example.com', 'roles' => ['billing_manager']],
                ['employee_code' => 'PL-DUMMY-0005', 'first_name' => 'Rohan', 'last_name' => 'Iyer', 'email' => 'rohan.iyer.platform@example.com', 'mobile' => '+919810010005', 'department' => 'PL-DEPT-ENG', 'designation' => 'PL-DES-ENG-MGR', 'manager' => 'aarav.mehta.platform@example.com', 'roles' => ['platform_admin', 'operations_analyst']],
                ['employee_code' => 'PL-DUMMY-0006', 'first_name' => 'Meera', 'last_name' => 'Nair', 'email' => 'meera.nair.platform@example.com', 'mobile' => '+919810010006', 'department' => 'PL-DEPT-SALES', 'designation' => 'PL-DES-SALES-MGR', 'manager' => 'aarav.mehta.platform@example.com', 'roles' => ['customer_success_manager']],
                ['employee_code' => 'PL-DUMMY-0007', 'first_name' => 'Dev', 'last_name' => 'Malhotra', 'email' => 'dev.malhotra.platform@example.com', 'mobile' => '+919810010007', 'department' => 'PL-DEPT-COMP', 'designation' => 'PL-DES-COMP-OFF', 'manager' => 'aarav.mehta.platform@example.com', 'roles' => ['readonly_auditor', 'operations_analyst']],
                ['employee_code' => 'PL-DUMMY-0008', 'first_name' => 'Ananya', 'last_name' => 'Sen', 'email' => 'ananya.sen.platform@example.com', 'mobile' => '+919810010008', 'department' => 'PL-DEPT-CS', 'designation' => 'PL-DES-CS-SPEC', 'manager' => 'nisha.rao.platform@example.com', 'roles' => ['customer_success_manager']],
                ['employee_code' => 'PL-DUMMY-0009', 'first_name' => 'Vikram', 'last_name' => 'Sethi', 'email' => 'vikram.sethi.platform@example.com', 'mobile' => '+919810010009', 'department' => 'PL-DEPT-CS', 'designation' => 'PL-DES-CS-SPEC', 'manager' => 'nisha.rao.platform@example.com', 'roles' => ['customer_success_manager']],
                ['employee_code' => 'PL-DUMMY-0010', 'first_name' => 'Ishita', 'last_name' => 'Bose', 'email' => 'ishita.bose.platform@example.com', 'mobile' => '+919810010010', 'department' => 'PL-DEPT-SUPPORT', 'designation' => 'PL-DES-SUP-SPEC', 'manager' => 'kabir.kapoor.platform@example.com', 'roles' => ['support_manager']],
                ['employee_code' => 'PL-DUMMY-0011', 'first_name' => 'Farhan', 'last_name' => 'Khan', 'email' => 'farhan.khan.platform@example.com', 'mobile' => '+919810010011', 'department' => 'PL-DEPT-SUPPORT', 'designation' => 'PL-DES-SUP-SPEC', 'manager' => 'kabir.kapoor.platform@example.com', 'roles' => ['support_manager']],
                ['employee_code' => 'PL-DUMMY-0012', 'first_name' => 'Sneha', 'last_name' => 'Patel', 'email' => 'sneha.patel.platform@example.com', 'mobile' => '+919810010012', 'department' => 'PL-DEPT-FIN', 'designation' => 'PL-DES-BILL-SPEC', 'manager' => 'priya.sharma.platform@example.com', 'roles' => ['billing_manager']],
                ['employee_code' => 'PL-DUMMY-0013', 'first_name' => 'Arjun', 'last_name' => 'Menon', 'email' => 'arjun.menon.platform@example.com', 'mobile' => '+919810010013', 'department' => 'PL-DEPT-OPS', 'designation' => 'PL-DES-OPS-ANL', 'manager' => 'aarav.mehta.platform@example.com', 'roles' => ['operations_analyst', 'readonly_auditor']],
                ['employee_code' => 'PL-DUMMY-0014', 'first_name' => 'Tara', 'last_name' => 'Dutta', 'email' => 'tara.dutta.platform@example.com', 'mobile' => '+919810010014', 'department' => 'PL-DEPT-CONTENT', 'designation' => 'PL-DES-CONT-STR', 'manager' => 'nisha.rao.platform@example.com', 'roles' => ['content_manager']],
            ];

            $departmentIds = DB::table('platform_departments')->pluck('id', 'code')->all();
            $designationIds = DB::table('platform_designations')->pluck('id', 'code')->all();

            foreach ($staff as $person) {
                DB::table('platform_users')->upsert([[
                    'uuid' => (string) Str::uuid(),
                    'employee_code' => $person['employee_code'],
                    'first_name' => $person['first_name'],
                    'last_name' => $person['last_name'],
                    'display_name' => $person['first_name'].' '.$person['last_name'],
                    'email' => $person['email'],
                    'mobile' => $person['mobile'],
                    'password' => $password,
                    'designation_id' => $designationIds[$person['designation']] ?? null,
                    'department_id' => $departmentIds[$person['department']] ?? null,
                    'timezone' => 'Asia/Kolkata',
                    'locale' => 'en',
                    'email_verified_at' => $now,
                    'status' => 'active',
                    'deleted_at' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]], ['email'], ['employee_code', 'first_name', 'last_name', 'display_name', 'mobile', 'password', 'designation_id', 'department_id', 'timezone', 'locale', 'email_verified_at', 'status', 'deleted_at', 'updated_at']);
            }

            $userIds = DB::table('platform_users')->whereIn('email', array_column($staff, 'email'))->pluck('id', 'email')->all();

            foreach ($staff as $person) {
                DB::table('platform_users')->where('email', $person['email'])->update([
                    'manager_id' => $person['manager'] ? ($userIds[$person['manager']] ?? null) : null,
                    'updated_at' => $now,
                ]);
            }

            $roleIds = DB::table('platform_roles')->where('guard_name', 'platform')->pluck('id', 'name')->all();
            $roleRows = [];
            foreach ($staff as $person) {
                foreach ($person['roles'] as $roleName) {
                    if (isset($userIds[$person['email']], $roleIds[$roleName])) {
                        $roleRows[] = ['role_id' => $roleIds[$roleName], 'model_id' => $userIds[$person['email']], 'model_type' => PlatformUser::class];
                    }
                }
            }

            DB::table('platform_model_has_roles')->insertOrIgnore($roleRows);

            $departmentManagers = [
                'PL-DEPT-EXEC' => 'aarav.mehta.platform@example.com',
                'PL-DEPT-OPS' => 'arjun.menon.platform@example.com',
                'PL-DEPT-CS' => 'nisha.rao.platform@example.com',
                'PL-DEPT-SUPPORT' => 'kabir.kapoor.platform@example.com',
                'PL-DEPT-FIN' => 'priya.sharma.platform@example.com',
                'PL-DEPT-ENG' => 'rohan.iyer.platform@example.com',
                'PL-DEPT-SALES' => 'meera.nair.platform@example.com',
                'PL-DEPT-COMP' => 'dev.malhotra.platform@example.com',
                'PL-DEPT-CONTENT' => 'tara.dutta.platform@example.com',
            ];

            foreach ($departmentManagers as $departmentCode => $managerEmail) {
                DB::table('platform_departments')->where('code', $departmentCode)->update([
                    'platform_manager_user_id' => $userIds[$managerEmail] ?? null,
                    'updated_at' => $now,
                ]);
            }
        });
    }
}
