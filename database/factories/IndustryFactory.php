<?php

namespace Database\Factories;

use App\Models\Industry;
use Illuminate\Database\Eloquent\Factories\Factory;

class IndustryFactory extends Factory
{
    protected $model = Industry::class;

    public function definition(): array
    {
        return ['name' => fake()->jobTitle(), 'code' => fake()->unique()->slug(2), 'description' => fake()->optional()->sentence(), 'status' => 'active', 'sort_order' => fake()->numberBetween(1, 100)];
    }
}