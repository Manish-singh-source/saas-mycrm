<?php

namespace Database\Factories;

use App\Models\Timezone;
use Illuminate\Database\Eloquent\Factories\Factory;

class TimezoneFactory extends Factory
{
    protected $model = Timezone::class;

    public function definition(): array
    {
        $identifier = fake()->randomElement(\DateTimeZone::listIdentifiers());
        return ['name' => $identifier, 'identifier' => $identifier, 'utc_offset' => '+00:00', 'status' => 'active', 'sort_order' => fake()->numberBetween(1, 500)];
    }
}