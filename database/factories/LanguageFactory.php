<?php

namespace Database\Factories;

use App\Models\Language;
use Illuminate\Database\Eloquent\Factories\Factory;

class LanguageFactory extends Factory
{
    protected $model = Language::class;

    public function definition(): array
    {
        return ['name' => fake()->words(2, true), 'code' => fake()->unique()->regexify('[a-z]{2}'), 'iso3' => fake()->optional()->regexify('[a-z]{3}'), 'native_name' => fake()->optional()->words(2, true), 'status' => 'active', 'sort_order' => fake()->numberBetween(1, 200)];
    }
}