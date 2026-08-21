<?php

namespace App\DTO;

use App\Http\Requests\Api\V1\CreateOrderRequest;

readonly class CreateOrderData
{
    /**
     * @param  OrderItemData[]  $items
     */
    public function __construct(
        public int $warehouseId,
        public array $items,
        public ?string $notes = null,
    ) {}

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
        return collect($this->items)
            ->pluck('quantity', 'productId')
            ->sortKeys()
            ->toArray();
    }
}
