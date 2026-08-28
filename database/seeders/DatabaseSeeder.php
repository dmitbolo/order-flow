<?php

namespace Database\Seeders;

use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseProduct;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->admin()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $warehouses = Warehouse::factory()->count(3)->create();
        $products = Product::factory()->count(10)->create();

        foreach ($warehouses as $warehouse) {
            foreach ($products as $product) {
                $warehouseProduct = WarehouseProduct::factory()->create([
                    'warehouse_id' => $warehouse->id,
                    'product_id' => $product->id,
                ]);

                if ($warehouseProduct->stock_quantity > 0) {
                    StockMovement::create([
                        'warehouse_id' => $warehouse->id,
                        'product_id' => $product->id,
                        'type' => StockMovementType::InitialBalance,
                        'quantity_change' => $warehouseProduct->stock_quantity,
                        'quantity_before' => 0,
                        'quantity_after' => $warehouseProduct->stock_quantity,
                        'comment' => 'Начальный остаток',
                    ]);
                }
            }
        }
    }
}
