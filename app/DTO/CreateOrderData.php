<?php

namespace App\DTO;

use App\Http\Requests\Api\V1\CreateOrderRequest;
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
            if ($item->productId < 1 || $item->quantity < 1) {
                throw new InvalidArgumentException('Product identifiers and quantities must be positive integers.');
            }

            if (isset($productIds[$item->productId])) {
                throw new InvalidArgumentException('Products in an order must be unique.');
            }

            $productIds[$item->productId] = true;
        }
    }

    public static function fromRequest(CreateOrderRequest $request): self
    {
        /** @var array{warehouse_id: int|string, items: list<array{product_id: int|string, quantity: int|string}>, notes?: string|null} $validated */
        $validated = $request->validated();

        return new self(
            warehouseId: (int) $validated['warehouse_id'],
            items: array_map(
                fn (array $item) => OrderItemData::fromArray($item),
                $validated['items']
            ),
            notes: $validated['notes'] ?? null,
        );
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
