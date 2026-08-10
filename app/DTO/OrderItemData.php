<?php

namespace App\DTO;

readonly class OrderItemData
{
    public function __construct(
        public int $productId,
        public int $quantity,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            productId: (int) $data['product_id'],
            quantity: (int) $data['quantity'],
        );
    }
}
