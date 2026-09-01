<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'warehouse_id' => Warehouse::factory(),
            'status' => $this->faker->randomElement([OrderStatus::Pending, OrderStatus::Canceled, OrderStatus::Completed, OrderStatus::Processing]),
            'total_amount' => 0, // Recalculated from related order items.
            'notes' => $this->faker->optional()->sentence(),
        ];
    }
}
