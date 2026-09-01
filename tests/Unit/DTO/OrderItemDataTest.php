<?php

use App\DTO\OrderItemData;

test('order item requires positive identifiers and quantities', function (int $productId, int $quantity) {
    expect(fn () => new OrderItemData($productId, $quantity))
        ->toThrow(InvalidArgumentException::class);
})->with([
    'non-positive product identifier' => [0, 1],
    'non-positive quantity' => [1, 0],
]);

test('order item is created from validated request data', function () {
    $item = OrderItemData::fromArray([
        'product_id' => '10',
        'quantity' => '2',
    ]);

    expect($item->productId)->toBe(10)
        ->and($item->quantity)->toBe(2);
});
