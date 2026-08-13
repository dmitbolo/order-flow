<?php

namespace Database\Factories;

use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Warehouse>
 */
class WarehouseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Склад '.$this->faker->city(),
            'code' => strtoupper($this->faker->unique()->lexify('???-???')),
            'address' => $this->faker->address(),
            'is_active' => true,
        ];
    }
}
