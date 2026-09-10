<?php

namespace Database\Factories;

use App\Models\Country;
use App\Models\State;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<State>
 */
class StateFactory extends Factory
{
    protected $model = State::class;

    public function definition(): array
    {
        return [
            'country_id' => Country::factory(),
            'name' => fake()->state(),
            'code' => fake()->optional()->bothify('??-##'),
            'status' => 'active',
            'sort_order' => fake()->numberBetween(1, 999),
        ];
    }
}