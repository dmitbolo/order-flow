<?php

use App\Actions\Orders\TransitionOrderStatusAction;
use App\Enums\OrderStatus;
use App\Exceptions\Orders\InvalidOrderStatusTransitionException;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('an order follows the processing and completed status sequence', function () {
    $order = Order::factory()->create(['status' => OrderStatus::Pending]);
    $action = app(TransitionOrderStatusAction::class);

    $processingOrder = $action->execute($order, OrderStatus::Processing);
    $completedOrder = $action->execute($processingOrder, OrderStatus::Completed);

    expect($completedOrder->status)->toBe(OrderStatus::Completed);
});

test('an order cannot skip the processing status', function () {
    $order = Order::factory()->create(['status' => OrderStatus::Pending]);

    expect(fn () => app(TransitionOrderStatusAction::class)->execute($order, OrderStatus::Completed))
        ->toThrow(InvalidOrderStatusTransitionException::class);

    expect($order->fresh()->status)->toBe(OrderStatus::Pending);
});

test('a terminal order cannot transition to another status', function (OrderStatus $status) {
    $order = Order::factory()->create(['status' => $status]);

    expect(fn () => app(TransitionOrderStatusAction::class)->execute($order, OrderStatus::Processing))
        ->toThrow(InvalidOrderStatusTransitionException::class);
})->with([OrderStatus::Canceled, OrderStatus::Completed]);
