<?php

use App\DTO\CreateOrderData;
use App\DTO\OrderItemData;

test('order data requires a positive warehouse identifier', function () {
    expect(fn () => new CreateOrderData(
        warehouseId: 0,
        items: [new OrderItemData(productId: 1, quantity: 1)],
    ))->toThrow(InvalidArgumentException::class);
});

test('order data requires at least one item', function () {
    expect(fn () => new CreateOrderData(warehouseId: 1, items: []))
        ->toThrow(InvalidArgumentException::class);
});

test('order data rejects duplicate products', function () {
    expect(fn () => new CreateOrderData(
        warehouseId: 1,
        items: [
            new OrderItemData(productId: 1, quantity: 1),
            new OrderItemData(productId: 1, quantity: 2),
        ],
    ))->toThrow(InvalidArgumentException::class);
});

test('order data returns quantities indexed and sorted by product identifier', function () {
    $data = new CreateOrderData(
        warehouseId: 1,
        items: [
            new OrderItemData(productId: 2, quantity: 3),
            new OrderItemData(productId: 1, quantity: 2),
        ],
    );

    expect($data->getItemsWithQuantities())->toBe([
        1 => 2,
        2 => 3,
    ]);
});
