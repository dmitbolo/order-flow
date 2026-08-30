<?php

use App\Actions\Orders\CreateOrderAction;
use App\DTO\CreateOrderData;
use App\DTO\OrderItemData;
use App\Events\OrderCreated;
use App\Jobs\CheckLowStock;
use App\Jobs\SendOrderCreatedNotification;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseProduct;
use App\Notifications\LowStockDetectedNotification;
use App\Notifications\OrderCreatedNotification;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;

uses(DatabaseMigrations::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->warehouse = Warehouse::factory()->create();
    $this->product = Product::factory()->create();
    $this->position = WarehouseProduct::factory()->create([
        'warehouse_id' => $this->warehouse->id,
        'product_id' => $this->product->id,
        'price' => 100,
        'stock_quantity' => 10,
    ]);
    $this->orderData = new CreateOrderData(
        warehouseId: $this->warehouse->id,
        items: [new OrderItemData(productId: $this->product->id, quantity: 1)],
    );
});

test('order created event is dispatched only after the outer transaction commits', function () {
    Event::fake([OrderCreated::class]);
    DB::beginTransaction();

    try {
        $order = app(CreateOrderAction::class)->execute(
            user: $this->user,
            data: $this->orderData,
        );

        expect($order->exists)->toBeTrue();
        Event::assertNotDispatched(OrderCreated::class);

        DB::commit();
    } finally {
        if (DB::transactionLevel() > 0) {
            DB::rollBack();
        }
    }

    Event::assertDispatched(OrderCreated::class, fn (OrderCreated $event): bool => $event->orderId === $order->id
        && $event->productIds === [$this->product->id]);
});

test('order created event is discarded when the outer transaction rolls back', function () {
    Event::fake([OrderCreated::class]);

    expect(fn () => DB::transaction(function (): void {
        app(CreateOrderAction::class)->execute(
            user: $this->user,
            data: $this->orderData,
        );

        throw new RuntimeException('Force outer rollback');
    }))->toThrow(RuntimeException::class, 'Force outer rollback');

    Event::assertNotDispatched(OrderCreated::class);
    expect(Order::query()->count())->toBe(0);
});

test('order event routes one job to each queue and keeps operation id in Laravel context', function () {
    Queue::fake();

    $response = $this->actingAs($this->user)
        ->withHeader('X-Operation-ID', 'checkout-123')
        ->postJson('/api/v1/orders', [
            'warehouse_id' => $this->warehouse->id,
            'items' => [['product_id' => $this->product->id, 'quantity' => 1]],
        ]);

    $response->assertCreated()->assertHeader('X-Operation-ID', 'checkout-123');
    expect(Context::get('operation_id'))->toBe('checkout-123');

    Queue::assertPushedOn('notifications', SendOrderCreatedNotification::class, fn ($job): bool => $job->afterCommit === true);
    Queue::assertPushedOn('inventory', CheckLowStock::class, fn ($job): bool => $job->afterCommit === true
        && $job->productIds === [$this->product->id]);
    Queue::assertPushed(SendOrderCreatedNotification::class, 1);
    Queue::assertPushed(CheckLowStock::class, 1);
});

test('duplicate low stock jobs for the same order are not queued', function () {
    Queue::fake();

    CheckLowStock::dispatch(1, $this->warehouse->id, [$this->product->id]);
    CheckLowStock::dispatch(1, $this->warehouse->id, [$this->product->id]);

    Queue::assertPushed(CheckLowStock::class, 1);
});

test('low stock job sends one aggregated notification for all critical products', function () {
    Notification::fake();
    Log::spy();
    $admin = User::factory()->admin()->create();
    $secondProduct = Product::factory()->create();
    $healthyProduct = Product::factory()->create();
    WarehouseProduct::factory()->create([
        'warehouse_id' => $this->warehouse->id,
        'product_id' => $secondProduct->id,
        'stock_quantity' => 5,
    ]);
    WarehouseProduct::factory()->create([
        'warehouse_id' => $this->warehouse->id,
        'product_id' => $healthyProduct->id,
        'stock_quantity' => 20,
    ]);
    $job = new CheckLowStock(
        orderId: 1,
        warehouseId: $this->warehouse->id,
        productIds: [$this->product->id, $secondProduct->id, $healthyProduct->id],
    );

    $job->handle();

    Notification::assertSentTo($admin, LowStockDetectedNotification::class, fn ($notification): bool => $notification->productIds === [$this->product->id, $secondProduct->id]
    );
    Notification::assertSentToTimes($admin, LowStockDetectedNotification::class, 1);
});

test('successful job execution writes a short structured log', function () {
    Notification::fake();
    Log::spy();
    Context::add('operation_id', 'op-success');
    $order = Order::factory()->create([
        'user_id' => $this->user->id,
        'warehouse_id' => $this->warehouse->id,
        'total_amount' => 100,
    ]);
    $job = new SendOrderCreatedNotification(
        orderId: $order->id,
        warehouseId: $this->warehouse->id,
    );

    $job->handle();

    Notification::assertSentTo($this->user, OrderCreatedNotification::class);
    expect(Context::get('operation_id'))->toBe('op-success');
    Log::shouldHaveReceived('info')->once()->withArgs(fn (string $message, array $context): bool => $message === 'queue_job_succeeded'
        && $context['queue'] === 'notifications'
        && $context['attempt'] === 1
        && $context['order_id'] === $order->id
        && $context['warehouse_id'] === $this->warehouse->id
        && is_int($context['execution_ms'])
        && is_int($context['total_duration_ms'])
        && $context['total_duration_ms'] >= $context['execution_ms']
    );
});

test('final job failure writes a detailed structured log', function () {
    Log::spy();
    Context::add('operation_id', 'op-failed');
    $exception = new RuntimeException('SMTP unavailable');
    $job = new SendOrderCreatedNotification(
        orderId: 123,
        warehouseId: $this->warehouse->id,
    );

    $job->failed($exception);

    Log::shouldHaveReceived('error')->once()->withArgs(fn (string $message, array $context): bool => $message === 'queue_job_failed'
        && $context['queue'] === 'notifications'
        && $context['attempt'] === 1
        && $context['order_id'] === 123
        && $context['warehouse_id'] === $this->warehouse->id
        && $context['exception_class'] === RuntimeException::class
        && $context['exception_message'] === 'SMTP unavailable'
        && $context['exception'] === $exception
        && ! array_key_exists('exception_trace', $context)
        && ! array_key_exists('execution_ms', $context)
        && is_int($context['total_duration_ms'])
    );
});

test('notification failure cannot roll back an already committed order', function () {
    Queue::fake();

    $response = $this->actingAs($this->user)->postJson('/api/v1/orders', [
        'warehouse_id' => $this->warehouse->id,
        'items' => [['product_id' => $this->product->id, 'quantity' => 1]],
    ])->assertCreated();
    $orderId = (int) $response->json('data.id');
    $job = Queue::pushed(SendOrderCreatedNotification::class)->first();

    Notification::shouldReceive('send')
        ->once()
        ->andThrow(new RuntimeException('SMTP unavailable'));

    expect(fn () => $job->handle())->toThrow(RuntimeException::class, 'SMTP unavailable');
    $this->assertDatabaseHas('orders', ['id' => $orderId]);
});
