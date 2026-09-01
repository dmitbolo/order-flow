<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Warehouse;
use App\Models\WarehouseProduct;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WarehouseProduct>
 */
class WarehouseProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'warehouse_id' => Warehouse::factory(),
            'product_id' => Product::factory(),
            'price' => $this->faker->numberBetween(1000, 500000), // Price from 10.00 to 5000.00 in minor currency units.
            'stock_quantity' => $this->faker->numberBetween(0, 150),
        ];
    }
}
