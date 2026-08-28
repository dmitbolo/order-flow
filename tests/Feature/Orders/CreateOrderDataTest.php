<?php

use App\DTO\CreateOrderData;
use App\DTO\OrderItemData;

test('order data rejects duplicate products', function () {
    expect(fn () => new CreateOrderData(
        warehouseId: 1,
        items: [
            new OrderItemData(productId: 1, quantity: 1),
            new OrderItemData(productId: 1, quantity: 2),
        ],
    ))->toThrow(InvalidArgumentException::class);
});

test('order data rejects empty items and non-positive identifiers or quantities', function () {
    expect(fn () => new CreateOrderData(warehouseId: 1, items: []))
        ->toThrow(InvalidArgumentException::class);

    expect(fn () => new CreateOrderData(
        warehouseId: 1,
        items: [new OrderItemData(productId: 1, quantity: 0)],
    ))->toThrow(InvalidArgumentException::class);
});
