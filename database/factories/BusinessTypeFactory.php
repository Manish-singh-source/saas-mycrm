<?php

namespace Database\Factories;

use App\Models\BusinessType;
use Illuminate\Database\Eloquent\Factories\Factory;

class BusinessTypeFactory extends Factory
{
    protected $model = BusinessType::class;

    public function definition(): array
    {
        return ['name' => fake()->company(), 'code' => fake()->unique()->slug(2), 'description' => fake()->optional()->sentence(), 'status' => 'active', 'sort_order' => fake()->numberBetween(1, 100)];
    }
}