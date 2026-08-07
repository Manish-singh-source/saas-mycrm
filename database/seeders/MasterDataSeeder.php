<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\SeedsRecords;
use Illuminate\Database\Seeder;

class MasterDataSeeder extends Seeder
{
    use SeedsRecords;

    public function run(): void
    {
        $indiaId = $this->seedRecord('countries', ['iso2' => 'IN'], ['name' => 'India', 'iso3' => 'IND', 'phone_code' => '+91', 'currency_code' => 'INR', 'status' => 'active']);
        $usaId = $this->seedRecord('countries', ['iso2' => 'US'], ['name' => 'United States', 'iso3' => 'USA', 'phone_code' => '+1', 'currency_code' => 'USD', 'status' => 'active']);

        $gujaratId = $this->seedRecord('states', ['country_id' => $indiaId, 'code' => 'GJ'], ['name' => 'Gujarat', 'status' => 'active']);
        $maharashtraId = $this->seedRecord('states', ['country_id' => $indiaId, 'code' => 'MH'], ['name' => 'Maharashtra', 'status' => 'active']);
        $californiaId = $this->seedRecord('states', ['country_id' => $usaId, 'code' => 'CA'], ['name' => 'California', 'status' => 'active']);

        foreach ([
            [$indiaId, $gujaratId, 'Ahmedabad'], [$indiaId, $gujaratId, 'Surat'], [$indiaId, $maharashtraId, 'Mumbai'], [$usaId, $californiaId, 'San Francisco'],
        ] as [$countryId, $stateId, $name]) {
            $this->seedRecord('cities', ['country_id' => $countryId, 'state_id' => $stateId, 'name' => $name], ['status' => 'active']);
        }

        foreach ([
            'private_limited' => 'Private Limited Company', 'llp' => 'Limited Liability Partnership', 'partnership' => 'Partnership Firm', 'proprietorship' => 'Proprietorship', 'enterprise' => 'Enterprise',
        ] as $code => $name) {
            $this->seedRecord('business_types', ['code' => $code], ['name' => $name, 'status' => 'active']);
        }

        foreach ([
            'technology' => 'Technology', 'professional_services' => 'Professional Services', 'manufacturing' => 'Manufacturing', 'retail' => 'Retail', 'finance' => 'Finance', 'healthcare' => 'Healthcare', 'education' => 'Education',
        ] as $code => $name) {
            $this->seedRecord('industries', ['code' => $code], ['name' => $name, 'status' => 'active']);
        }
    }
}