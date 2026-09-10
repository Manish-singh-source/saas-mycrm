<?php

namespace Database\Seeders;

use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class MasterDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedBusinessTypes();
        $this->seedIndustries();
        $this->seedCurrencies();
        $this->seedLanguages();
        $this->seedTimezones();
        $this->seedDateFormats();
        $this->seedTimeFormats();
    }

    private function seedBusinessTypes(): void
    {
        $values = [
            ['name' => 'Sole Proprietorship', 'code' => 'sole_proprietorship', 'description' => 'A business owned and operated by one individual.'],
            ['name' => 'Partnership', 'code' => 'partnership', 'description' => 'A business owned by two or more partners.'],
            ['name' => 'Limited Liability Partnership', 'code' => 'llp', 'description' => 'A partnership with limited liability protection.'],
            ['name' => 'Private Limited Company', 'code' => 'private_limited', 'description' => 'A privately held limited company.'],
            ['name' => 'Public Limited Company', 'code' => 'public_limited', 'description' => 'A company whose shares may be publicly traded.'],
            ['name' => 'Non-Profit Organization', 'code' => 'non_profit', 'description' => 'An organization established for a social or charitable purpose.'],
            ['name' => 'Government Entity', 'code' => 'government', 'description' => 'An entity operated by a government authority.'],
            ['name' => 'Cooperative', 'code' => 'cooperative', 'description' => 'An organization owned and operated by its members.'],
        ];

        $this->upsertStatic('business_types', $values);
    }

    private function seedIndustries(): void
    {
        $values = [
            ['name' => 'Information Technology', 'code' => 'information_technology'],
            ['name' => 'Healthcare', 'code' => 'healthcare'],
            ['name' => 'Finance and Banking', 'code' => 'finance_banking'],
            ['name' => 'Education', 'code' => 'education'],
            ['name' => 'Manufacturing', 'code' => 'manufacturing'],
            ['name' => 'Retail', 'code' => 'retail'],
            ['name' => 'Construction and Real Estate', 'code' => 'construction_real_estate'],
            ['name' => 'Transportation and Logistics', 'code' => 'transportation_logistics'],
            ['name' => 'Hospitality and Tourism', 'code' => 'hospitality_tourism'],
            ['name' => 'Media and Entertainment', 'code' => 'media_entertainment'],
            ['name' => 'Professional Services', 'code' => 'professional_services'],
            ['name' => 'Agriculture', 'code' => 'agriculture'],
            ['name' => 'Telecommunications', 'code' => 'telecommunications'],
            ['name' => 'Energy and Utilities', 'code' => 'energy_utilities'],
            ['name' => 'Government and Public Administration', 'code' => 'government_public_administration'],
        ];

        $this->upsertStatic('industries', $values);
    }

    private function seedCurrencies(): void
    {
        $currencies = $this->readJson('currencies.json');
        $rows = [];

        foreach ($currencies as $code => $currency) {
            $rows[] = [
                'name' => $currency['name'],
                'code' => $currency['code'] ?? $code,
                'symbol' => $currency['symbol'] ?? null,
                'decimal_places' => $currency['decimal_digits'] ?? 2,
            ];
        }

        $this->upsertRows('currencies', $rows, ['code'], ['name', 'symbol', 'decimal_places']);
    }

    private function seedLanguages(): void
    {
        $languages = $this->readJson('languages.json');
        $rows = [];

        foreach ($languages as $language) {
            $rows[] = [
                'name' => $language['name'],
                'code' => $language['code'],
                'iso3' => null,
                'native_name' => $language['name_native'] ?? null,
            ];
        }

        $this->upsertRows('languages', $rows, ['code'], ['name', 'iso3', 'native_name']);
    }

    private function seedTimezones(): void
    {
        $rows = [];

        foreach (DateTimeZone::listIdentifiers() as $identifier) {
            $zone = new DateTimeZone($identifier);
            $offset = $zone->getOffset(new DateTimeImmutable('now', $zone));
            $sign = $offset < 0 ? '-' : '+';
            $hours = intdiv(abs($offset), 3600);
            $minutes = intdiv(abs($offset) % 3600, 60);

            $rows[] = [
                'name' => $identifier,
                'identifier' => $identifier,
                'utc_offset' => sprintf('%s%02d:%02d', $sign, $hours, $minutes),
            ];
        }

        $this->upsertRows('timezones', $rows, ['identifier'], ['name', 'utc_offset']);
    }

    private function seedDateFormats(): void
    {
        $this->upsertStatic('date_formats', [
            ['name' => 'ISO 8601', 'code' => 'iso_8601', 'format' => 'Y-m-d', 'example' => '2026-08-29'],
            ['name' => 'Day/Month/Year', 'code' => 'dmy_slash', 'format' => 'd/m/Y', 'example' => '29/08/2026'],
            ['name' => 'Month/Day/Year', 'code' => 'mdy_slash', 'format' => 'm/d/Y', 'example' => '08/29/2026'],
            ['name' => 'Day-Month-Year', 'code' => 'dmy_dash', 'format' => 'd-m-Y', 'example' => '29-08-2026'],
            ['name' => 'Day Month Year', 'code' => 'd_m_y_long', 'format' => 'd M Y', 'example' => '29 Aug 2026'],
            ['name' => 'Day Month Year (Full)', 'code' => 'd_m_y_full', 'format' => 'd F Y', 'example' => '29 August 2026'],
            ['name' => 'Month Day, Year', 'code' => 'm_d_y_long', 'format' => 'M d, Y', 'example' => 'Aug 29, 2026'],
            ['name' => 'Month Day, Year (Full)', 'code' => 'm_d_y_full', 'format' => 'F d, Y', 'example' => 'August 29, 2026'],
        ]);
    }

    private function seedTimeFormats(): void
    {
        $this->upsertStatic('time_formats', [
            ['name' => '24-hour Time', 'code' => '24_hour', 'format' => 'H:i', 'example' => '14:30'],
            ['name' => '12-hour Time', 'code' => '12_hour', 'format' => 'h:i A', 'example' => '02:30 PM'],
            ['name' => '24-hour Time with Seconds', 'code' => '24_hour_seconds', 'format' => 'H:i:s', 'example' => '14:30:45'],
            ['name' => '12-hour Time with Seconds', 'code' => '12_hour_seconds', 'format' => 'h:i:s A', 'example' => '02:30:45 PM'],
        ]);
    }

    private function upsertStatic(string $table, array $values): void
    {
        $this->upsertRows($table, $values, ['code'], array_keys($values[0]));
    }

    private function upsertRows(string $table, array $rows, array $uniqueBy, array $columns): void
    {
        $now = now();

        foreach (array_chunk($rows, 500) as $chunk) {
            $chunk = array_map(fn (array $row, int $index): array => $row + [
                'status' => 'active',
                'sort_order' => $index + 1,
                'created_at' => $now,
                'updated_at' => $now,
            ], $chunk, array_keys($chunk));

            DB::table($table)->upsert(
                $chunk,
                $uniqueBy,
                array_merge($columns, ['status', 'sort_order', 'updated_at']),
            );
        }
    }

    private function readJson(string $filename): array
    {
        $path = base_path('vendor/nnjeim/world/resources/json/' . $filename);

        if (! is_file($path)) {
            throw new RuntimeException("World dataset not found: {$path}");
        }

        return json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
    }
}