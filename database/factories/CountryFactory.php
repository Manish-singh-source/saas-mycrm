<?php

namespace Database\Factories;

use App\Models\Country;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Country>
 */
class CountryFactory extends Factory
{
    protected $model = Country::class;

    public function definition(): array
    {
        return [
            'name' => fake()->country(),
            'iso2' => fake()->unique()->regexify('[A-Z]{2}'),
            'iso3' => fake()->unique()->regexify('[A-Z]{3}'),
            'phone_code' => (string) fake()->numberBetween(1, 999),
            'currency_code' => fake()->optional()->currencyCode(),
            'status' => 'active',
            'sort_order' => fake()->numberBetween(1, 999),
        ];
    }
}