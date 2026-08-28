<?php

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();

    $this->warehouse = Warehouse::factory()->create(['is_active' => true]);

    $this->product1 = Product::factory()->create();
    $this->product2 = Product::factory()->create();

    WarehouseProduct::factory()->create([
        'warehouse_id' => $this->warehouse->id,
        'product_id' => $this->product1->id,
        'price' => 100.00,
        'stock_quantity' => 10,
    ]);

    WarehouseProduct::factory()->create([
        'warehouse_id' => $this->warehouse->id,
        'product_id' => $this->product2->id,
        'price' => 250.00,
        'stock_quantity' => 5,
    ]);
});

test('it successfully creates an order through api endpoint', function () {
    $payload = [
        'warehouse_id' => $this->warehouse->id,
        'notes' => 'API test order note',
        'items' => [
            ['product_id' => $this->product1->id, 'quantity' => 2],
            ['product_id' => $this->product2->id, 'quantity' => 1],
        ],
    ];

    $response = $this->actingAs($this->user)
        ->postJson('/api/v1/orders', $payload);

    $response->assertStatus(Response::HTTP_CREATED)
        ->assertJsonStructure([
            'message',
            'data' => [
                'id',
                'total_amount',
                'status',
                'warehouse',
                'items',
            ],
        ])
        ->assertJsonPath('message', 'Заказ успешно создан')
        ->assertJsonPath('data.total_amount', 450)
        ->assertJsonPath('data.status', OrderStatus::Pending);

    $this->assertDatabaseHas('orders', [
        'user_id' => $this->user->id,
        'warehouse_id' => $this->warehouse->id,
        'total_amount' => 450,
        'notes' => 'API test order note',
    ]);

    $this->assertDatabaseHas('warehouse_product', [
        'warehouse_id' => $this->warehouse->id,
        'product_id' => $this->product1->id,
        'stock_quantity' => 8,
    ]);

    $this->assertDatabaseHas('stock_movements', [
        'warehouse_id' => $this->warehouse->id,
        'product_id' => $this->product1->id,
        'order_id' => $response->json('data.id'),
        'actor_id' => $this->user->id,
        'type' => 'order_created',
        'quantity_change' => -2,
        'quantity_before' => 10,
        'quantity_after' => 8,
    ]);

    expect(StockMovement::where('order_id', $response->json('data.id'))->count())->toBe(2);
});

test('it fails request validation when missing required fields', function () {
    $payload = [];

    $response = $this->actingAs($this->user)
        ->postJson('/api/v1/orders', $payload);

    $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
        ->assertJsonValidationErrors([
            'warehouse_id' => 'Укажите склад для оформления заказа.',
            'items' => 'Заказ должен содержать хотя бы один товар.',
        ]);
});

test('it fails when warehouse does not exist or is inactive', function () {
    $inactiveWarehouse = Warehouse::factory()->create(['is_active' => false]);

    $payload = [
        'warehouse_id' => $inactiveWarehouse->id,
        'items' => [
            ['product_id' => $this->product1->id, 'quantity' => 1],
        ],
    ];

    $response = $this->actingAs($this->user)
        ->postJson('/api/v1/orders', $payload);

    // Если склад inactive, экшен выбросит ModelNotFoundException,
    // что Laravel автоматически превратит в HTTP 404
    $response->assertStatus(Response::HTTP_NOT_FOUND);
});

test('it fails validation when product is not attached to the warehouse', function () {
    $unattachedProduct = Product::factory()->create();

    $payload = [
        'warehouse_id' => $this->warehouse->id,
        'items' => [
            ['product_id' => $unattachedProduct->id, 'quantity' => 1],
        ],
    ];

    $response = $this->actingAs($this->user)
        ->postJson('/api/v1/orders', $payload);

    $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
        ->assertJson([
            'status' => 'error',
            'error_code' => 'PRODUCT_NOT_ATTACHED_TO_WAREHOUSE',
            'message' => "Товар ID {$unattachedProduct->id} не привязан к данному складу.",
        ]);
});

test('it rejects an inactive product attached to the warehouse', function () {
    $inactiveProduct = Product::factory()->create(['is_active' => false]);
    WarehouseProduct::factory()->create([
        'warehouse_id' => $this->warehouse->id,
        'product_id' => $inactiveProduct->id,
        'stock_quantity' => 10,
    ]);

    $response = $this->actingAs($this->user)->postJson('/api/v1/orders', [
        'warehouse_id' => $this->warehouse->id,
        'items' => [
            ['product_id' => $inactiveProduct->id, 'quantity' => 1],
        ],
    ]);

    $response->assertUnprocessable()
        ->assertJsonPath('error_code', 'PRODUCT_UNAVAILABLE');

    expect(Order::query()->count())->toBe(0);
});

test('a guest cannot create an order', function () {
    $this->postJson('/api/v1/orders', [
        'warehouse_id' => $this->warehouse->id,
        'items' => [['product_id' => $this->product1->id, 'quantity' => 1]],
    ])->assertUnauthorized();
});

test('it rejects duplicate products and does not change stock', function () {
    $response = $this->actingAs($this->user)->postJson('/api/v1/orders', [
        'warehouse_id' => $this->warehouse->id,
        'items' => [
            ['product_id' => $this->product1->id, 'quantity' => 6],
            ['product_id' => $this->product1->id, 'quantity' => 6],
        ],
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['items.0.product_id', 'items.1.product_id']);

    expect(Order::count())->toBe(0)
        ->and(StockMovement::query()->count())->toBe(0);
    $this->assertDatabaseHas('warehouse_product', [
        'warehouse_id' => $this->warehouse->id,
        'product_id' => $this->product1->id,
        'stock_quantity' => 10,
    ]);
});

test('it validates referenced ids, item quantity, and notes length', function () {
    $this->actingAs($this->user)->postJson('/api/v1/orders', [
        'warehouse_id' => 999_999,
        'notes' => str_repeat('a', 1001),
        'items' => [['product_id' => 999_999, 'quantity' => 0]],
    ])->assertUnprocessable()->assertJsonValidationErrors([
        'warehouse_id', 'notes', 'items.0.product_id', 'items.0.quantity',
    ]);
});

test('it fails when requested quantity exceeds stock availability', function () {
    $payload = [
        'warehouse_id' => $this->warehouse->id,
        'items' => [
            ['product_id' => $this->product1->id, 'quantity' => 11], // Available: 10
        ],
    ];

    $response = $this->actingAs($this->user)
        ->postJson('/api/v1/orders', $payload);

    $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);

    $response->assertJson([
        'status' => 'error',
        'error_code' => 'INSUFFICIENT_STOCK',
    ]);

    $response->assertJsonStructure(['message']);

    $this->assertDatabaseHas('warehouse_product', [
        'warehouse_id' => $this->warehouse->id,
        'product_id' => $this->product1->id,
        'stock_quantity' => 10,
    ]);
});

test('it rolls back the entire transaction and keeps database intact if any item fails', function () {
    $payload = [
        'warehouse_id' => $this->warehouse->id,
        'items' => [
            ['product_id' => $this->product1->id, 'quantity' => 2],
            // InsufficientStockException
            ['product_id' => $this->product2->id, 'quantity' => 100],
        ],
    ];

    $response = $this->actingAs($this->user)
        ->postJson('/api/v1/orders', $payload);

    $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
        ->assertJson(['status' => 'error']);

    expect(Order::count())->toBe(0);

    $this->assertDatabaseHas('warehouse_product', [
        'warehouse_id' => $this->warehouse->id,
        'product_id' => $this->product1->id,
        'stock_quantity' => 10, // Осталось исходное количество
    ]);

    $this->assertDatabaseHas('warehouse_product', [
        'warehouse_id' => $this->warehouse->id,
        'product_id' => $this->product2->id,
        'stock_quantity' => 5,
    ]);
});
