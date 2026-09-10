<?php

namespace Database\Factories;

use App\Models\Currency;
use Illuminate\Database\Eloquent\Factories\Factory;

class CurrencyFactory extends Factory
{
    protected $model = Currency::class;

    public function definition(): array
    {
        return [
            'name' => fake()->currencyCode(),
            'code' => fake()->unique()->regexify('[A-Z]{3}'),
            'symbol' => fake()->optional()->randomElement(['$', 'EUR', 'GBP', 'INR', 'JPY']),
            'decimal_places' => 2,
            'status' => 'active',
            'sort_order' => fake()->numberBetween(1, 200),
        ];
    }
}