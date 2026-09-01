<?php

use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->activeWarehouse = Warehouse::factory()->create([
        'name' => 'Central Warehouse',
        'code' => 'CENTRAL',
    ]);
    $this->inactiveWarehouse = Warehouse::factory()->create([
        'name' => 'Central Inactive Warehouse',
        'code' => 'ZINACTIVE',
        'is_active' => false,
    ]);
});

test('a guest cannot access warehouse endpoints', function () {
    $this->getJson('/api/v1/warehouses')->assertUnauthorized();
    $this->getJson("/api/v1/warehouses/{$this->activeWarehouse->id}")->assertUnauthorized();
    $this->getJson("/api/v1/warehouses/{$this->activeWarehouse->id}/products")->assertUnauthorized();
});

test('it lists only active warehouses with filtering sorting and pagination', function () {
    Warehouse::factory()->create([
        'name' => 'North Warehouse',
        'code' => 'NORTH',
    ]);

    $response = $this->actingAs($this->user)
        ->getJson('/api/v1/warehouses?filter[name]=Cen&sort=-code&per_page=1');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $this->activeWarehouse->id)
        ->assertJsonPath('meta.per_page', 1);
});

test('starts-with filters treat SQL wildcard characters literally', function (
    string $filter,
    string $matchingName,
    string $nonMatchingName,
) {
    $matchingWarehouse = Warehouse::factory()->create(['name' => $matchingName]);
    Warehouse::factory()->create(['name' => $nonMatchingName]);

    $query = http_build_query([
        'filter' => ['name' => $filter],
    ]);

    $this->actingAs($this->user)
        ->getJson("/api/v1/warehouses?{$query}")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $matchingWarehouse->id);
})->with([
    'percent' => ['A%', 'A% Warehouse', 'Apple Warehouse'],
    'underscore' => ['A_', 'A_ Warehouse', 'AB Warehouse'],
    'escape character' => ['A!', 'A! Warehouse', 'A Warehouse'],
]);

test('it returns an active warehouse and hides inactive or missing warehouses', function () {
    $this->actingAs($this->user)
        ->getJson("/api/v1/warehouses/{$this->activeWarehouse->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $this->activeWarehouse->id);

    $this->actingAs($this->user)
        ->getJson("/api/v1/warehouses/{$this->inactiveWarehouse->id}")
        ->assertNotFound();

    $this->actingAs($this->user)
        ->getJson('/api/v1/warehouses/999999')
        ->assertNotFound();
});

test('it rejects unallowed warehouse filters and sorts', function () {
    $this->actingAs($this->user)
        ->getJson('/api/v1/warehouses?filter[is_active]=1')
        ->assertBadRequest();

    $this->actingAs($this->user)
        ->getJson('/api/v1/warehouses?sort=is_active')
        ->assertBadRequest();
});

test('it lists only active products of an active warehouse with pivot data', function () {
    $availableProduct = Product::factory()->create([
        'name' => 'Apple Juice',
        'sku' => 'APPLE-001',
    ]);
    $inactiveProduct = Product::factory()->create([
        'sku' => 'APPLE-INACTIVE',
        'is_active' => false,
    ]);
    $otherWarehouseProduct = Product::factory()->create();
    $otherWarehouse = Warehouse::factory()->create();

    WarehouseProduct::factory()->create([
        'warehouse_id' => $this->activeWarehouse->id,
        'product_id' => $availableProduct->id,
        'price' => 199,
        'stock_quantity' => 7,
    ]);
    WarehouseProduct::factory()->create([
        'warehouse_id' => $this->activeWarehouse->id,
        'product_id' => $inactiveProduct->id,
    ]);
    WarehouseProduct::factory()->create([
        'warehouse_id' => $otherWarehouse->id,
        'product_id' => $otherWarehouseProduct->id,
    ]);

    $response = $this->actingAs($this->user)
        ->getJson("/api/v1/warehouses/{$this->activeWarehouse->id}/products?filter[sku]=APPLE&sort=-stock_quantity");

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $availableProduct->id)
        ->assertJsonPath('data.0.price', 199)
        ->assertJsonPath('data.0.stock_quantity', 7);
});

test('it hides products for inactive and missing warehouses', function () {
    $this->actingAs($this->user)
        ->getJson("/api/v1/warehouses/{$this->inactiveWarehouse->id}/products")
        ->assertNotFound();

    $this->actingAs($this->user)
        ->getJson('/api/v1/warehouses/999999/products')
        ->assertNotFound();
});

test('it rejects unallowed product filters and sorts', function () {
    $url = "/api/v1/warehouses/{$this->activeWarehouse->id}/products";

    $this->actingAs($this->user)->getJson("{$url}?filter[is_active]=1")->assertBadRequest();
    $this->actingAs($this->user)->getJson("{$url}?sort=description")->assertBadRequest();
});
