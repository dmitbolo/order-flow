<?php

use App\Actions\Stock\AdjustStockAction;
use App\Enums\StockMovementType;
use App\Exceptions\Warehouses\InsufficientStockException;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseProduct;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

test('an authenticated user can only filter their own stock movements', function () {
    $user = User::factory()->create();
    $warehouse = Warehouse::factory()->create();
    $product = Product::factory()->create();
    $anotherProduct = Product::factory()->create();

    $movement = StockMovement::create([
        'warehouse_id' => $warehouse->id,
        'product_id' => $product->id,
        'actor_id' => $user->id,
        'type' => StockMovementType::InitialBalance,
        'quantity_change' => 12,
        'quantity_before' => 0,
        'quantity_after' => 12,
    ]);

    StockMovement::create([
        'warehouse_id' => $warehouse->id,
        'product_id' => $anotherProduct->id,
        'actor_id' => User::factory()->create()->id,
        'type' => StockMovementType::InitialBalance,
        'quantity_change' => 4,
        'quantity_before' => 0,
        'quantity_after' => 4,
    ]);

    $this->actingAs($user)
        ->getJson("/api/v1/stock-movements?filter[product_id]={$product->id}")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $movement->id)
        ->assertJsonPath('data.0.type', 'initial_balance')
        ->assertJsonPath('data.0.quantity_change', 12)
        ->assertJsonPath('data.0.quantity_after', 12)
        ->assertJsonPath('meta.links.0.label', 'Назад')
        ->assertJsonPath('meta.links.2.label', 'Вперёд');
});

test('a guest cannot view stock movements', function () {
    $this->getJson('/api/v1/stock-movements')
        ->assertUnauthorized()
        ->assertJsonPath('error_code', 'UNAUTHENTICATED');
});

test('a manual adjustment changes the quantity and creates a journal entry', function () {
    $user = User::factory()->create();
    $warehouse = Warehouse::factory()->create();
    $product = Product::factory()->create();

    WarehouseProduct::factory()->create([
        'warehouse_id' => $warehouse->id,
        'product_id' => $product->id,
        'stock_quantity' => 7,
    ]);

    app(AdjustStockAction::class)->execute($warehouse, $product->id, -2, $user);

    $this->assertDatabaseHas('warehouse_product', [
        'warehouse_id' => $warehouse->id,
        'product_id' => $product->id,
        'stock_quantity' => 5,
    ]);
    $this->assertDatabaseHas('stock_movements', [
        'warehouse_id' => $warehouse->id,
        'product_id' => $product->id,
        'actor_id' => $user->id,
        'type' => 'manual_adjustment',
        'quantity_change' => -2,
        'quantity_before' => 7,
        'quantity_after' => 5,
    ]);
});

test('a manual adjustment cannot make stock negative', function () {
    $warehouse = Warehouse::factory()->create();
    $product = Product::factory()->create();

    $position = WarehouseProduct::factory()->create([
        'warehouse_id' => $warehouse->id,
        'product_id' => $product->id,
        'stock_quantity' => 2,
    ]);

    expect(fn () => app(AdjustStockAction::class)->execute($warehouse, $product->id, -3, null))
        ->toThrow(InsufficientStockException::class);

    expect($position->fresh()->stock_quantity)->toBe(2)
        ->and(StockMovement::query()->count())->toBe(0);
});

test('a zero manual adjustment is rejected', function () {
    $warehouse = Warehouse::factory()->create();
    $product = Product::factory()->create();

    WarehouseProduct::factory()->create([
        'warehouse_id' => $warehouse->id,
        'product_id' => $product->id,
    ]);

    expect(fn () => app(AdjustStockAction::class)->execute($warehouse, $product->id, 0, null))
        ->toThrow(InvalidArgumentException::class);
});

test('stock quantity cannot be changed through mass assignment', function () {
    $warehouse = Warehouse::factory()->create();
    $product = Product::factory()->create();

    $position = WarehouseProduct::query()->create([
        'warehouse_id' => $warehouse->id,
        'product_id' => $product->id,
        'price' => 100,
        'stock_quantity' => 12,
    ]);

    expect($position->refresh()->stock_quantity)->toBe(0);
});

test('the database rejects a negative stock quantity', function () {
    $warehouse = Warehouse::factory()->create();
    $product = Product::factory()->create();

    $position = WarehouseProduct::factory()->create([
        'warehouse_id' => $warehouse->id,
        'product_id' => $product->id,
        'stock_quantity' => 1,
    ]);

    expect(fn () => DB::table('warehouse_product')->where('id', $position->id)->update(['stock_quantity' => -1]))
        ->toThrow(QueryException::class);
});

test('the database rejects an inconsistent stock movement', function () {
    $warehouse = Warehouse::factory()->create();
    $product = Product::factory()->create();

    expect(fn () => DB::table('stock_movements')->insert([
        'warehouse_id' => $warehouse->id,
        'product_id' => $product->id,
        'type' => StockMovementType::ManualAdjustment->value,
        'quantity_change' => -2,
        'quantity_before' => 10,
        'quantity_after' => 9,
    ]))->toThrow(QueryException::class);
});
