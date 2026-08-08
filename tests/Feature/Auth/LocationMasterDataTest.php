<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LocationMasterDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_common_location_dropdowns_return_active_master_data(): void
    {
        $countryId = DB::table('countries')->insertGetId([
            'name' => 'India',
            'iso2' => 'IN',
            'iso3' => 'IND',
            'phone_code' => '+91',
            'currency_code' => 'INR',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $stateId = DB::table('states')->insertGetId([
            'country_id' => $countryId,
            'name' => 'Maharashtra',
            'code' => 'MH',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('cities')->insert([
            'country_id' => $countryId,
            'state_id' => $stateId,
            'name' => 'Mumbai',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->getJson('/api/common/v1/locations/countries')
            ->assertOk()
            ->assertJsonPath('data.countries.0.iso2', 'IN');

        $this->getJson('/api/common/v1/locations/states?country_id='.$countryId)
            ->assertOk()
            ->assertJsonPath('data.states.0.code', 'MH');

        $this->getJson('/api/common/v1/locations/cities?state_id='.$stateId.'&country_id='.$countryId)
            ->assertOk()
            ->assertJsonPath('data.cities.0.name', 'Mumbai');
    }
}
