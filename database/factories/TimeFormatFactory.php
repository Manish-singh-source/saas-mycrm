<?php

namespace Database\Factories;

use App\Models\TimeFormat;
use Illuminate\Database\Eloquent\Factories\Factory;

class TimeFormatFactory extends Factory
{
    protected $model = TimeFormat::class;

    public function definition(): array
    {
        return ['name' => fake()->words(2, true), 'code' => fake()->unique()->slug(2), 'format' => 'H:i', 'example' => now()->format('H:i'), 'status' => 'active', 'sort_order' => fake()->numberBetween(1, 50)];
    }
}