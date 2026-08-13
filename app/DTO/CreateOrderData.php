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
        return new self(
            warehouseId: (int) $request->validated('warehouse_id'),
            items: array_map(
                fn (array $item) => OrderItemData::fromArray($item),
                $request->validated('items')
            ),
            notes: $request->validated('notes'),
        );
    }

    public function getItemsWithQuantities(): array
    {
        return collect($this->items)
            ->pluck('quantity', 'productId')
            ->sortKeys()
            ->toArray();
    }
}
