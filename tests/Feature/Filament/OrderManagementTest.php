<?php

use App\Actions\Orders\CreateOrderAction;
use App\DTO\CreateOrderData;
use App\DTO\OrderItemData;
use App\Enums\OrderStatus;
use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Resources\Orders\Pages\CreateOrder;
use App\Filament\Resources\Orders\Pages\ViewOrder;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseProduct;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->customer = User::factory()->create();
    $this->warehouse = Warehouse::factory()->create(['is_active' => true]);
    $this->product = Product::factory()->create(['is_active' => true]);
    $this->position = WarehouseProduct::factory()->create([
        'warehouse_id' => $this->warehouse->id,
        'product_id' => $this->product->id,
        'price' => 125,
        'stock_quantity' => 10,
    ]);

    $this->actingAs($this->admin);
});

test('an administrator creates an order through the domain action', function () {
    Livewire::test(CreateOrder::class)
        ->fillForm([
            'user_id' => $this->customer->id,
            'warehouse_id' => $this->warehouse->id,
            'notes' => 'Created in Filament',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 2,
                ],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertNotified()
        ->assertRedirect();

    $order = Order::query()->sole();

    expect($order->user_id)->toBe($this->customer->id)
        ->and($order->warehouse_id)->toBe($this->warehouse->id)
        ->and($order->status)->toBe(OrderStatus::Pending)
        ->and($order->total_amount)->toBe(250)
        ->and($order->items()->sole()->price)->toBe(125)
        ->and($this->position->fresh()->stock_quantity)->toBe(8);

    $this->assertDatabaseHas('stock_movements', [
        'order_id' => $order->id,
        'actor_id' => $this->admin->id,
        'type' => 'order_created',
        'quantity_change' => -2,
        'quantity_before' => 10,
        'quantity_after' => 8,
    ]);
});

test('a failed admin order keeps the order and stock unchanged', function () {
    Livewire::test(CreateOrder::class)
        ->fillForm([
            'user_id' => $this->customer->id,
            'warehouse_id' => $this->warehouse->id,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 11,
                ],
            ],
        ])
        ->call('create')
        ->assertNotified();

    expect(Order::query()->count())->toBe(0)
        ->and(StockMovement::query()->count())->toBe(0)
        ->and($this->position->fresh()->stock_quantity)->toBe(10);
});

test('an administrator cancels a pending order and is recorded as the actor', function () {
    $order = app(CreateOrderAction::class)->execute(
        user: $this->customer,
        data: new CreateOrderData(
            warehouseId: $this->warehouse->id,
            items: [new OrderItemData($this->product->id, 2)],
        ),
        actor: $this->customer,
    );

    $component = Livewire::test(ViewOrder::class, ['record' => $order->id]);

    Model::preventLazyLoading();

    try {
        $component
            ->callAction('cancel')
            ->assertNotified();
    } finally {
        Model::preventLazyLoading(false);
    }

    expect($order->fresh()->status)->toBe(OrderStatus::Canceled)
        ->and($this->position->fresh()->stock_quantity)->toBe(10);

    $this->assertDatabaseHas('stock_movements', [
        'order_id' => $order->id,
        'actor_id' => $this->admin->id,
        'type' => 'order_canceled',
        'quantity_change' => 2,
    ]);
});

test('the order view query eager loads all infolist relationships', function () {
    $order = app(CreateOrderAction::class)->execute(
        user: $this->customer,
        data: new CreateOrderData(
            warehouseId: $this->warehouse->id,
            items: [new OrderItemData($this->product->id, 2)],
        ),
        actor: $this->admin,
    );

    $record = OrderResource::getRecordRouteBindingEloquentQuery()->findOrFail($order->id);

    expect($record->relationLoaded('user'))->toBeTrue()
        ->and($record->relationLoaded('warehouse'))->toBeTrue()
        ->and($record->relationLoaded('items'))->toBeTrue()
        ->and($record->items->every(
            fn (OrderItem $item): bool => $item->relationLoaded('product'),
        ))->toBeTrue()
        ->and($record->relationLoaded('stockMovements'))->toBeTrue()
        ->and($record->stockMovements->every(
            fn (StockMovement $movement): bool => $movement->relationLoaded('product'),
        ))->toBeTrue()
        ->and($record->stockMovements->every(
            fn (StockMovement $movement): bool => $movement->relationLoaded('actor'),
        ))->toBeTrue();
});

test('order records cannot be edited or deleted through the resource', function () {
    $order = Order::factory()->create();

    expect(OrderResource::canEdit($order))->toBeFalse()
        ->and(OrderResource::canDelete($order))->toBeFalse()
        ->and(OrderResource::canDeleteAny())->toBeFalse();
});
