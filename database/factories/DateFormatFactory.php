<?php

namespace Database\Factories;

use App\Models\DateFormat;
use Illuminate\Database\Eloquent\Factories\Factory;

class DateFormatFactory extends Factory
{
    protected $model = DateFormat::class;

    public function definition(): array
    {
        return ['name' => fake()->words(2, true), 'code' => fake()->unique()->slug(2), 'format' => 'Y-m-d', 'example' => now()->format('Y-m-d'), 'status' => 'active', 'sort_order' => fake()->numberBetween(1, 50)];
    }
}