<?php

use App\Enums\OrderStatus;
use App\Enums\StockMovementType;
use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Widgets\LowStockPositions;
use App\Filament\Widgets\OrderStatsOverview;
use App\Filament\Widgets\RecentOrders;
use App\Filament\Widgets\RecentStockMovements;
use App\Models\Order;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseProduct;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->actingAs($this->admin);
});

test('the admin panel registers the operational dashboard widgets', function () {
    $widgets = Filament::getPanel('admin')->getWidgets();

    expect($widgets)
        ->toContain(OrderStatsOverview::class)
        ->toContain(RecentOrders::class)
        ->toContain(LowStockPositions::class)
        ->toContain(RecentStockMovements::class);
});

test('order statistics show current operational metrics', function () {
    Order::factory()->create(['status' => OrderStatus::Pending]);
    Order::factory()->create(['status' => OrderStatus::Processing]);
    Order::factory()->create([
        'status' => OrderStatus::Completed,
        'total_amount' => 12_345,
        'updated_at' => now(),
    ]);

    $component = Livewire::test(OrderStatsOverview::class)
        ->assertSee('Ожидают обработки')
        ->assertSee('В обработке')
        ->assertSee('Завершены сегодня')
        ->assertSee('Сумма завершённых сегодня')
        ->assertSee('123,45');

    foreach ([OrderStatus::Pending, OrderStatus::Processing, OrderStatus::Completed] as $status) {
        $component->assertSee(OrderResource::getUrl('index', [
            'filters' => [
                'status' => [
                    'value' => $status->value,
                ],
            ],
        ]), escape: false);
    }
});

test('recent orders widget displays the latest orders', function () {
    $orders = Order::factory()->count(3)->create();

    Livewire::test(RecentOrders::class)
        ->assertCanSeeTableRecords($orders);
});

test('low stock widget only displays positions at or below the configured threshold', function () {
    config()->set('inventory.low_stock_threshold', 5);

    $warehouse = Warehouse::factory()->create();
    $lowStockProduct = Product::factory()->create();
    $healthyStockProduct = Product::factory()->create();
    $lowStockPosition = WarehouseProduct::factory()->create([
        'warehouse_id' => $warehouse->id,
        'product_id' => $lowStockProduct->id,
        'stock_quantity' => 5,
    ]);
    $healthyStockPosition = WarehouseProduct::factory()->create([
        'warehouse_id' => $warehouse->id,
        'product_id' => $healthyStockProduct->id,
        'stock_quantity' => 6,
    ]);

    Livewire::test(LowStockPositions::class)
        ->assertCanSeeTableRecords([$lowStockPosition])
        ->assertCanNotSeeTableRecords([$healthyStockPosition]);
});

test('recent stock movements widget displays journal entries', function () {
    $warehouse = Warehouse::factory()->create();
    $product = Product::factory()->create();
    $movement = StockMovement::query()->create([
        'warehouse_id' => $warehouse->id,
        'product_id' => $product->id,
        'actor_id' => $this->admin->id,
        'type' => StockMovementType::ManualAdjustment,
        'quantity_change' => 3,
        'quantity_before' => 0,
        'quantity_after' => 3,
        'comment' => 'Dashboard test',
    ]);

    Livewire::test(RecentStockMovements::class)
        ->assertCanSeeTableRecords([$movement]);
});
