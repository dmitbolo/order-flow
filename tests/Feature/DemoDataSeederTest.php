<?php

use App\Enums\OrderStatus;
use App\Enums\StockMovementType;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseProduct;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;

uses(DatabaseMigrations::class);

test('the demo seeder creates a coherent repeatable dataset', function () {
    $this->travelTo(Carbon::create(2026, 9, 1, 12, 0, 0, 'Europe/Moscow'));
    Queue::fake();

    $this->seed(DemoDataSeeder::class);

    $expectedCounts = [
        'users' => 4,
        'warehouses' => 3,
        'products' => 8,
        'warehouse_products' => 16,
        'orders' => 12,
        'order_items' => 19,
        'stock_movements' => 38,
    ];

    expect(demoDatasetCounts())->toBe($expectedCounts)
        ->and(User::query()->where('email', 'test@example.com')->sole()->is_admin)->toBeTrue()
        ->and(Hash::check('password', User::query()->where('email', 'test@example.com')->sole()->password))->toBeTrue();

    foreach ([
        OrderStatus::Pending->value => 3,
        OrderStatus::Processing->value => 2,
        OrderStatus::Canceled->value => 2,
        OrderStatus::Completed->value => 5,
    ] as $status => $count) {
        expect(Order::query()->where('status', $status)->count())->toBe($count);
    }

    $completedToday = Order::query()
        ->where('status', OrderStatus::Completed)
        ->where('updated_at', '>=', today())
        ->where('updated_at', '<', today()->addDay());

    expect((clone $completedToday)->count())->toBe(3)
        ->and((int) (clone $completedToday)->sum('total_amount'))->toBe(11_950_000);

    $expectedStock = collect([
        'KZN-01|PRN-XP-365B' => 4,
        'KZN-01|RFID-USB-01' => 0,
        'KZN-01|SCN-ZEB-2278' => 6,
        'MSK-01|DRW-ATOL-410' => 30,
        'MSK-01|LBL-58X40-700' => 90,
        'MSK-01|POS-ATOL-S10' => 4,
        'MSK-01|PRN-XP-365B' => 14,
        'MSK-01|RFID-USB-01' => 3,
        'MSK-01|SCL-MER-326' => 9,
        'MSK-01|SCN-HW-1250G' => 4,
        'MSK-01|SCN-ZEB-2278' => 21,
        'SPB-01|DRW-ATOL-410' => 5,
        'SPB-01|LBL-58X40-700' => 59,
        'SPB-01|PRN-XP-365B' => 10,
        'SPB-01|SCL-MER-326' => 2,
        'SPB-01|SCN-ZEB-2278' => 10,
    ])->sortKeys()->all();
    $actualStock = WarehouseProduct::query()
        ->with(['warehouse:id,code', 'product:id,sku'])
        ->get()
        ->mapWithKeys(fn (WarehouseProduct $position): array => [
            "{$position->warehouse->code}|{$position->product->sku}" => $position->stock_quantity,
        ])
        ->sortKeys()
        ->all();

    expect($actualStock)->toBe($expectedStock)
        ->and(StockMovement::query()->where('type', StockMovementType::InitialBalance)->count())->toBe(15)
        ->and(StockMovement::query()->where('type', StockMovementType::OrderCreated)->count())->toBe(19)
        ->and(StockMovement::query()->where('type', StockMovementType::OrderCanceled)->count())->toBe(3)
        ->and(StockMovement::query()->where('type', StockMovementType::ManualAdjustment)->count())->toBe(1)
        ->and(WarehouseProduct::query()
            ->whereRelation('warehouse', 'is_active', true)
            ->whereRelation('product', 'is_active', true)
            ->where('stock_quantity', '<=', 10)
            ->count())->toBe(7);

    Order::query()->with('items')->each(function (Order $order): void {
        $calculatedTotal = (int) $order->items->sum(
            fn (OrderItem $item): int => $item->price * $item->quantity,
        );

        expect($order->total_amount)->toBe($calculatedTotal);
    });

    StockMovement::query()->each(function (StockMovement $movement): void {
        expect($movement->quantity_change)->not->toBe(0)
            ->and($movement->quantity_after)->toBe($movement->quantity_before + $movement->quantity_change)
            ->and($movement->quantity_before)->toBeGreaterThanOrEqual(0)
            ->and($movement->quantity_after)->toBeGreaterThanOrEqual(0);
    });

    WarehouseProduct::query()->each(function (WarehouseProduct $position): void {
        $movements = StockMovement::query()
            ->where('warehouse_id', $position->warehouse_id)
            ->where('product_id', $position->product_id)
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        expect((int) $movements->sum('quantity_change'))->toBe($position->stock_quantity);

        if ($movements->isNotEmpty()) {
            expect($movements->last()->quantity_after)->toBe($position->stock_quantity);
        }
    });

    $this->seed(DemoDataSeeder::class);

    expect(demoDatasetCounts())->toBe($expectedCounts);
    Queue::assertNothingPushed();
});

/** @return array<string, int> */
function demoDatasetCounts(): array
{
    return [
        'users' => User::query()->count(),
        'warehouses' => Warehouse::query()->count(),
        'products' => Product::query()->count(),
        'warehouse_products' => WarehouseProduct::query()->count(),
        'orders' => Order::query()->count(),
        'order_items' => OrderItem::query()->count(),
        'stock_movements' => StockMovement::query()->count(),
    ];
}
