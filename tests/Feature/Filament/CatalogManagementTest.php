<?php

use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\ProductResource;
use App\Filament\Resources\Warehouses\Pages\CreateWarehouse;
use App\Filament\Resources\Warehouses\WarehouseResource;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->admin()->create());
});

test('product sku is validated for uniqueness', function () {
    Product::factory()->create(['sku' => 'DUPLICATE-SKU']);

    Livewire::test(CreateProduct::class)
        ->fillForm([
            'name' => 'Duplicate product',
            'sku' => 'DUPLICATE-SKU',
            'is_active' => true,
        ])
        ->call('create')
        ->assertHasFormErrors(['sku' => 'unique']);
});

test('warehouse code is validated for uniqueness and inactive warehouses can be created', function () {
    Warehouse::factory()->create(['code' => 'DUPLICATE']);

    Livewire::test(CreateWarehouse::class)
        ->fillForm([
            'name' => 'Duplicate warehouse',
            'code' => 'DUPLICATE',
            'is_active' => false,
        ])
        ->call('create')
        ->assertHasFormErrors(['code' => 'unique']);

    Livewire::test(CreateWarehouse::class)
        ->fillForm([
            'name' => 'Inactive warehouse',
            'code' => 'INACTIVE',
            'is_active' => false,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('warehouses', [
        'code' => 'INACTIVE',
        'is_active' => false,
    ]);
});

test('products and warehouses cannot be deleted through their resources', function () {
    $product = Product::factory()->create();
    $warehouse = Warehouse::factory()->create();

    expect(ProductResource::canDelete($product))->toBeFalse()
        ->and(ProductResource::canDeleteAny())->toBeFalse()
        ->and(WarehouseResource::canDelete($warehouse))->toBeFalse()
        ->and(WarehouseResource::canDeleteAny())->toBeFalse();
});
