<?php

namespace App\DTO;

use InvalidArgumentException;

readonly class OrderItemData
{
    public function __construct(
        public int $productId,
        public int $quantity,
    ) {
        if ($productId < 1) {
            throw new InvalidArgumentException('The product identifier must be a positive integer.');
        }

        if ($quantity < 1) {
            throw new InvalidArgumentException('The product quantity must be a positive integer.');
        }
    }

    /**
     * @param  array{product_id: int|string, quantity: int|string}  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            productId: (int) $data['product_id'],
            quantity: (int) $data['quantity'],
        );
    }
}
