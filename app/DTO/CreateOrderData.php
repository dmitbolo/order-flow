<?php

namespace App\DTO;

use InvalidArgumentException;

readonly class CreateOrderData
{
    /**
     * @param  OrderItemData[]  $items
     */
    public function __construct(
        public int $warehouseId,
        public array $items,
        public ?string $notes = null,
    ) {
        if ($warehouseId < 1) {
            throw new InvalidArgumentException('The warehouse identifier must be a positive integer.');
        }

        if ($items === []) {
            throw new InvalidArgumentException('An order must contain at least one item.');
        }

        $productIds = [];

        foreach ($items as $item) {
            if (isset($productIds[$item->productId])) {
                throw new InvalidArgumentException('Products in an order must be unique.');
            }

            $productIds[$item->productId] = true;
        }
    }

    /**
     * @return array<int, int>
     */
    public function getItemsWithQuantities(): array
    {
        $quantities = [];

        foreach ($this->items as $item) {
            $quantities[$item->productId] = $item->quantity;
        }

        ksort($quantities);

        return $quantities;
    }
}
