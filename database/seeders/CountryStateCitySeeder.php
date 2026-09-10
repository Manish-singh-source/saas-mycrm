<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CountryStateCitySeeder extends Seeder
{
    public function run(): void
    {
        $basePath = base_path('vendor/nnjeim/world/resources/json');

        $countries = $this->readJson($basePath . '/countries.json');
        $states = $this->readJson($basePath . '/states.json');
        $cities = $this->readJson($basePath . '/cities.json');

        $now = now();

        DB::transaction(function () use ($countries, $states, $cities, $now): void {
            DB::table('countries')->upsert(
                array_map(fn (array $country): array => [
                    'id' => $country['id'],
                    'name' => $country['name'],
                    'iso2' => $country['iso2'],
                    'iso3' => $country['iso3'] ?? null,
                    'phone_code' => $country['phone_code'] ?? null,
                    'currency_code' => $country['currency'] ?? null,
                    'status' => 'active',
                    'sort_order' => $country['id'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ], $countries),
                ['id'],
                ['name', 'iso2', 'iso3', 'phone_code', 'currency_code', 'status', 'sort_order', 'updated_at'],
            );

            foreach (array_chunk($states, 1000) as $chunk) {
                DB::table('states')->upsert(
                    array_map(fn (array $state): array => [
                        'id' => $state['id'],
                        'country_id' => $state['country_id'],
                        'name' => $state['name'],
                        'code' => $state['state_code'] ?? null,
                        'status' => 'active',
                        'sort_order' => $state['id'],
                        'created_at' => $now,
                        'updated_at' => $now,
                    ], $chunk),
                    ['id'],
                    ['country_id', 'name', 'code', 'status', 'sort_order', 'updated_at'],
                );
            }

            foreach (array_chunk($cities, 1000) as $chunk) {
                DB::table('cities')->upsert(
                    array_map(fn (array $city): array => [
                        'id' => $city['id'],
                        'state_id' => $city['state_id'],
                        'name' => $city['name'],
                        'status' => 'active',
                        'sort_order' => $city['id'],
                        'created_at' => $now,
                        'updated_at' => $now,
                    ], $chunk),
                    ['id'],
                    ['state_id', 'name', 'status', 'sort_order', 'updated_at'],
                );
            }
        });
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function readJson(string $path): array
    {
        if (! is_file($path)) {
            throw new RuntimeException("World dataset not found: {$path}");
        }

        $data = json_decode(
            file_get_contents($path),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );

        if (! is_array($data)) {
            throw new RuntimeException("World dataset is invalid: {$path}");
        }

        return $data;
    }
}