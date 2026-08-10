<?php

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->otherUser = User::factory()->create();
    $this->warehouse = Warehouse::factory()->create();

    $this->pendingOrder = Order::factory()->create([
        'user_id' => $this->user->id,
        'warehouse_id' => $this->warehouse->id,
        'status' => OrderStatus::Pending,
        'total_amount' => 500.00,
        'created_at' => now()->subDays(2),
    ]);

    $this->completedOrder = Order::factory()->create([
        'user_id' => $this->user->id,
        'warehouse_id' => $this->warehouse->id,
        'status' => OrderStatus::Completed,
        'total_amount' => 1500.00,
        'created_at' => now(), // Самый свежий заказ
    ]);

    $this->foreignOrder = Order::factory()->create([
        'user_id' => $this->otherUser->id,
    ]);
});

test('it returns only the authenticated users orders sorted by newest by default', function () {
    $response = $this->actingAs($this->user)
        ->getJson('/api/v1/orders');

    $response->assertStatus(Response::HTTP_OK)
        ->assertJsonCount(2, 'data');

    // Проверяем defaultSort('-created_at'): первым должен идти Completed, так как он новее
    expect($response->json('data.0.id'))->toBe($this->completedOrder->id)
        ->and($response->json('data.1.id'))->toBe($this->pendingOrder->id);

    // Убеждаемся, что чужой заказ не утек в ответ
    $idsInResponse = collect($response->json('data'))->pluck('id');
    expect($idsInResponse)->not->toContain($this->foreignOrder->id);
});

test('it filters orders correctly by allowed fields', function (string $queryParam, int $expectedCount, string $orderProperty) {
    $expectedOrderId = $this->{$orderProperty}->id;

    $finalQueryParam = str_replace('{warehouse_id}', $this->warehouse->id, $queryParam);

    $response = $this->actingAs($this->user)
        ->getJson("/api/v1/orders?{$finalQueryParam}");

    $response->assertStatus(Response::HTTP_OK)
        ->assertJsonCount($expectedCount, 'data')
        ->assertJsonPath('data.0.id', $expectedOrderId);
    })->with([
        'filter by status' => [
            'queryParam' => 'filter[status]=' . OrderStatus::Pending->value,
            'expectedCount' => 1,
            'orderProperty' => 'pendingOrder'
        ],
        'filter by warehouse_id' => [
            'queryParam' => 'filter[warehouse_id]={warehouse_id}',
            'expectedCount' => 2,
            'orderProperty' => 'completedOrder'
        ]
    ]);

test('it sorts orders correctly by allowed fields', function (string $sortParam, string $expectedFirstOrderProperty) {
    $firstExpectedOrderId = $this->{$expectedFirstOrderProperty}->id;

    $response = $this->actingAs($this->user)
        ->getJson("/api/v1/orders?sort={$sortParam}");

    $response->assertStatus(Response::HTTP_OK);

    expect($response->json('data.0.id'))->toBe($firstExpectedOrderId);
    })->with([
        'sort by id ascending'             => ['id', 'pendingOrder'],
        'sort by id descending'            => ['-id', 'completedOrder'],
        'sort by total_amount ascending'   => ['total_amount', 'pendingOrder'],
        'sort by total_amount descending'  => ['-total_amount', 'completedOrder'],
        'sort by created_at ascending'     => ['created_at', 'pendingOrder'],
        'sort by created_at descending'    => ['-created_at', 'completedOrder'],
    ]);

test('it limits per_page parameter to maximum 100', function () {
    Order::factory()->count(110)->create(['user_id' => $this->user->id]);

    $response = $this->actingAs($this->user)
        ->getJson('/api/v1/orders?per_page=500');

    $response->assertStatus(Response::HTTP_OK);
    $response->assertJsonCount(100, 'data');
    expect($response->json('meta.per_page'))->toBe(100);
});

test('it shows a specific order belonging to the user', function () {
    $response = $this->actingAs($this->user)
        ->getJson("/api/v1/orders/{$this->pendingOrder->id}");

    $response->assertStatus(Response::HTTP_OK)
        ->assertJsonPath('data.id', $this->pendingOrder->id);
});

test('it loads allowed includes for a single order', function () {
    $response = $this->actingAs($this->user)
        ->getJson("/api/v1/orders/{$this->pendingOrder->id}?include=warehouse");

    $response->assertStatus(Response::HTTP_OK)
        ->assertJsonStructure([
            'data' => [
                'id',
                'warehouse' => [
                    'id',
                ],
            ]
        ]);

    expect($response->json('data.warehouse.id'))->toBe($this->warehouse->id);
});

test('it returns 400 when trying to include an unallowed relation', function () {
    $response = $this->actingAs($this->user)
        ->getJson("/api/v1/orders/{$this->pendingOrder->id}?include=unallowedRelation");

    $response->assertStatus(Response::HTTP_BAD_REQUEST);
});

test('it returns 404 when trying to view another users order', function () {
    $response = $this->actingAs($this->user)
        ->getJson("/api/v1/orders/{$this->foreignOrder->id}");

    // Так как используется findOrFail внутри области видимости user_id,
    // чужой заказ вернет честный 404 Not Found вместо 403, скрывая факт его существования
    $response->assertStatus(Response::HTTP_NOT_FOUND);
});
