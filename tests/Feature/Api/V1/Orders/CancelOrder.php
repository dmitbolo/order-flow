<?php

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->warehouse = Warehouse::factory()->create();
    $this->product = Product::factory()->create();

    $this->warehouseProduct = WarehouseProduct::factory()->create([
        'warehouse_id' => $this->warehouse->id,
        'product_id' => $this->product->id,
        'stock_quantity' => 10,
    ]);
});

it('successfully cancels a pending order via api', function () {
    $order = Order::factory()->create([
        'user_id' => $this->user->id,
        'status' => OrderStatus::Pending,
        'warehouse_id' => $this->warehouse->id,
    ]);

    $order->items()->create([
        'product_id' => $this->product->id,
        'quantity' => 3,
        'price' => 100,
    ]);

    $response = $this->actingAs($this->user, 'sanctum')
        ->postJson("/api/v1/orders/{$order->id}/cancel");

    $response->assertStatus(Response::HTTP_OK)
        ->assertJson([
            'message' => 'Заказ успешно отменен',
        ])
        // Улучшено: проверяем точную структуру ответа из OrderResource
        ->assertJsonStructure([
            'message',
            'data' => [
                'id',
                'status',
                'total_amount',
                'warehouse',
                'items',
                'created_at',
            ]
        ]);

    expect($response->json('data.status'))->toBe(OrderStatus::Canceled->value);

    $this->assertDatabaseHas('orders', [
        'id' => $order->id,
        'status' => OrderStatus::Canceled,
    ]);

    $this->warehouseProduct->refresh();
    expect($this->warehouseProduct->stock_quantity)->toBe(13);
});

it('returns 404 if a user tries to cancel someone else order', function () {
    $anotherUser = User::factory()->create();

    $order = Order::factory()->create([
        'user_id' => $anotherUser->id,
        'status' => OrderStatus::Pending,
        'warehouse_id' => $this->warehouse->id,
    ]);

    $response = $this->actingAs($this->user, 'sanctum')
        ->postJson("/api/v1/orders/{$order->id}/cancel");

    $response->assertStatus(Response::HTTP_NOT_FOUND);

    $this->assertDatabaseHas('orders', [
        'id' => $order->id,
        'status' => OrderStatus::Pending,
    ]);
});

it('returns 401 unauthenticated if the user is not logged in', function () {
    $order = Order::factory()->create([
        'user_id' => $this->user->id,
        'status' => OrderStatus::Pending,
    ]);

    $response = $this->postJson("/api/v1/orders/{$order->id}/cancel");

    $response->assertStatus(Response::HTTP_UNAUTHORIZED);
});

it('handles custom business exceptions and passes them through response', function () {
    $order = Order::factory()->create([
        'user_id' => $this->user->id,
        'status' => OrderStatus::Canceled,
        'warehouse_id' => $this->warehouse->id,
    ]);

    $response = $this->actingAs($this->user, 'sanctum')
        ->postJson("/api/v1/orders/{$order->id}/cancel");

    $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
});

it('rolls back database changes if an unexpected error occurs during cancellation via HTTP', function () {
    $order = Order::factory()->create([
        'user_id' => $this->user->id,
        'status' => OrderStatus::Pending,
        'warehouse_id' => $this->warehouse->id,
    ]);

    $order->items()->create([
        'product_id' => $this->product->id,
        'quantity' => 5,
        'price' => 100,
    ]);

    Order::updating(function () {
        throw new \RuntimeException('Simulated database crash');
    });

    $response = $this->actingAs($this->user, 'sanctum')
        ->postJson("/api/v1/orders/{$order->id}/cancel");

    $response->assertStatus(Response::HTTP_INTERNAL_SERVER_ERROR);

    Order::flushEventListeners();

    $actualStockInDb = DB::table('warehouse_product')
        ->where('warehouse_id', $this->warehouse->id)
        ->where('product_id', $this->product->id)
        ->value('stock_quantity');

    expect($actualStockInDb)->toBe(10);
});

it('prevents double stock restoration on consecutive cancel requests', function () {
    $order = Order::factory()->create([
        'user_id' => $this->user->id,
        'status' => OrderStatus::Pending,
        'warehouse_id' => $this->warehouse->id,
    ]);

    $order->items()->create([
        'product_id' => $this->product->id,
        'quantity' => 3,
        'price' => 100,
    ]);

    $response1 = $this->actingAs($this->user, 'sanctum')
        ->postJson("/api/v1/orders/{$order->id}/cancel");
    $response1->assertStatus(Response::HTTP_OK);

    $response2 = $this->actingAs($this->user, 'sanctum')
        ->postJson("/api/v1/orders/{$order->id}/cancel");
    $response2->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);

    $this->warehouseProduct->refresh();
    expect($this->warehouseProduct->stock_quantity)->toBe(13);
});
