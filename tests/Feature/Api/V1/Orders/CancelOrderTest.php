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

        ->assertJsonStructure([
            'message',
            'data' => [
                'id',
                'status',
                'total_amount',
                'warehouse',
                'items',
                'created_at',
            ],
        ]);

    expect($response->json('data.status'))->toBe(OrderStatus::Canceled->value);

    $this->assertDatabaseHas('orders', [
        'id' => $order->id,
        'status' => OrderStatus::Canceled,
    ]);

    $this->warehouseProduct->refresh();
    expect($this->warehouseProduct->stock_quantity)->toBe(13);

    $this->assertDatabaseHas('stock_movements', [
        'warehouse_id' => $this->warehouse->id,
        'product_id' => $this->product->id,
        'order_id' => $order->id,
        'actor_id' => $this->user->id,
        'type' => 'order_canceled',
        'quantity_change' => 3,
        'quantity_before' => 10,
        'quantity_after' => 13,
    ]);
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

it('cannot cancel a non-pending order', function (OrderStatus $status) {
    $order = Order::factory()->create([
        'user_id' => $this->user->id,
        'status' => $status,
        'warehouse_id' => $this->warehouse->id,
    ]);

    $this->actingAs($this->user, 'sanctum')
        ->postJson("/api/v1/orders/{$order->id}/cancel")
        ->assertUnprocessable()
        ->assertJsonPath('error_code', 'ORDER_CANNOT_BE_CANCELED');
})->with([OrderStatus::Processing, OrderStatus::Canceled, OrderStatus::Completed]);

it('returns 404 when cancelling an order that does not exist', function () {
    $this->actingAs($this->user, 'sanctum')
        ->postJson('/api/v1/orders/999999/cancel')
        ->assertNotFound();
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
        throw new RuntimeException('Simulated database crash');
    });

    try {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/v1/orders/{$order->id}/cancel");

        $response->assertStatus(Response::HTTP_INTERNAL_SERVER_ERROR);
    } finally {
        Order::flushEventListeners();
    }

    $actualStockInDb = DB::table('warehouse_product')
        ->where('warehouse_id', $this->warehouse->id)
        ->where('product_id', $this->product->id)
        ->value('stock_quantity');

    expect($actualStockInDb)->toBe(10);

    $this->assertDatabaseMissing('stock_movements', [
        'order_id' => $order->id,
        'type' => 'order_canceled',
    ]);
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
