<?php

namespace App\DTO\Stock;

use App\Models\Warehouse;
use InvalidArgumentException;

final readonly class LockedStock
{
    /**
     * @param  array<int, array{change: int, quantity: int, price: int}>  $positions
     */
    public function __construct(
        public Warehouse $warehouse,
        private array $positions,
    ) {
        if ($positions === []) {
            throw new InvalidArgumentException('Locked stock must contain at least one position.');
        }
    }

    /** @return array<int, int> [product_id => price] */
    public function prices(): array
    {
        return array_map(
            static fn (array $position): int => $position['price'],
            $this->positions,
        );
    }

    /** @return array<int, int> */
    public function changes(): array
    {
        return array_map(
            static fn (array $position): int => $position['change'],
            $this->positions,
        );
    }

    public function quantityBefore(int $productId): int
    {
        return $this->position($productId)['quantity'];
    }

    /** @return array{change: int, quantity: int, price: int} */
    private function position(int $productId): array
    {
        return $this->positions[$productId]
            ?? throw new InvalidArgumentException("Product {$productId} is not part of the locked stock.");
    }
}
