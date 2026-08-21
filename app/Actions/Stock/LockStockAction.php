<?php

namespace App\Actions\Stock;

use App\DTO\Stock\LockedStock;
use App\Exceptions\Warehouses\InsufficientStockException;
use App\Exceptions\Warehouses\ProductNotAttachedException;
use App\Models\Warehouse;
use App\Models\WarehouseProduct;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use LogicException;

class LockStockAction
{
    /**
     * @param  array<int, int>  $quantityChanges  [product_id => signed quantity]
     *
     * @throws InsufficientStockException|ProductNotAttachedException
     */
    public function execute(Warehouse $warehouse, array $quantityChanges): LockedStock
    {
        if (DB::transactionLevel() === 0) {
            throw new LogicException('Stock must be locked inside a database transaction.');
        }

        $normalizedChanges = $this->normalizeChanges($quantityChanges);
        $positions = WarehouseProduct::query()
            ->where('warehouse_id', $warehouse->id)
            ->whereIn('product_id', array_keys($normalizedChanges))
            ->orderBy('product_id')
            ->lockForUpdate()
            ->get()
            ->keyBy('product_id');
        $snapshot = [];

        foreach ($normalizedChanges as $productId => $normalizedChange) {
            $position = $positions->get($productId);

            if (! $position) {
                throw new ProductNotAttachedException($productId);
            }

            if ($position->stock_quantity + $normalizedChange < 0) {
                throw new InsufficientStockException($productId, abs($normalizedChange), $position->stock_quantity);
            }

            $snapshot[$productId] = [
                'change' => $normalizedChange,
                'quantity' => $position->stock_quantity,
                'price' => $position->price,
            ];
        }

        return new LockedStock($warehouse, $snapshot);
    }

    /**
     * @param  array<int|string, mixed>  $quantityChanges
     * @return array<int, int>
     */
    private function normalizeChanges(array $quantityChanges): array
    {
        if ($quantityChanges === []) {
            throw new InvalidArgumentException('At least one stock movement is required.');
        }

        $normalized = [];

        foreach ($quantityChanges as $productId => $quantityChange) {
            if (filter_var($productId, FILTER_VALIDATE_INT) === false || (int) $productId < 1) {
                throw new InvalidArgumentException('Product identifiers must be positive integers.');
            }

            if (filter_var($quantityChange, FILTER_VALIDATE_INT) === false || (int) $quantityChange === 0) {
                throw new InvalidArgumentException('Stock quantity changes must be non-zero integers.');
            }

            $normalized[(int) $productId] = (int) $quantityChange;
        }

        ksort($normalized);

        return $normalized;
    }
}
