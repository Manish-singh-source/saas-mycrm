<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\SeedsRecords;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DemoTenantFoundationSeeder extends Seeder
{
    use SeedsRecords;

    public const TENANT_SLUG = 'demo-technofra-crm';
    public const OWNER_EMAIL = 'owner.demo@example.test';

    public function run(): void
    {
        $countryId = (int) DB::table('countries')->where('iso2', 'IN')->value('id');
        $stateId = (int) DB::table('states')->where('country_id', $countryId)->where('code', 'GJ')->value('id');
        $cityId = (int) DB::table('cities')->where('state_id', $stateId)->where('name', 'Ahmedabad')->value('id');
        $businessTypeId = (int) DB::table('business_types')->where('code', 'private_limited')->value('id');
        $industryId = (int) DB::table('industries')->where('code', 'it_services')->value('id');

        $tenantId = $this->seedRecord('tenants', ['slug' => self::TENANT_SLUG], [
            'organization_name' => 'Technofra Demo CRM',
            'legal_name' => 'Technofra Demo CRM Private Limited',
            'display_name' => 'Technofra Demo',
            'organization_code' => 'DEMO-CRM',
            'business_type_id' => $businessTypeId ?: null,
            'industry_id' => $industryId ?: null,
            'company_size' => 'small',
            'default_currency' => 'INR',
            'default_timezone' => 'Asia/Kolkata',
            'onboarded_at' => now()->subDays(20),
            'trial_ends_at' => now()->addDays(10),
            'status' => 'active',
        ], true);

        $officeId = $this->seedRecord('tenant_offices', ['tenant_id' => $tenantId, 'office_code' => 'HO'], [
            'office_name' => 'Head Office',
            'office_type' => 'head_office',
            'is_head_office' => true,
            'is_default' => true,
            'address_line_1' => 'C G Road',
            'country_id' => $countryId ?: null,
            'state_id' => $stateId ?: null,
            'city_id' => $cityId ?: null,
            'postal_code' => '380009',
            'contact_person' => 'Demo Owner',
            'contact_email' => self::OWNER_EMAIL,
            'contact_phone' => '+919999000001',
            'timezone' => 'Asia/Kolkata',
            'working_hours' => json_encode(['mon_fri' => '09:30-18:30']),
            'status' => 'active',
        ], true);

        $this->seedRecord('users', ['tenant_id' => $tenantId, 'email' => self::OWNER_EMAIL], [
            'default_office_id' => $officeId,
            'employee_code' => 'EMP-DEMO-001',
            'first_name' => 'Demo',
            'last_name' => 'Owner',
            'display_name' => 'Demo Owner',
            'mobile' => '+919999000001',
            'password' => Hash::make('Password@123'),
            'timezone' => 'Asia/Kolkata',
            'locale' => 'en',
            'email_verified_at' => now(),
            'account_type' => 'owner',
            'status' => 'active',
        ], true);
    }
}
