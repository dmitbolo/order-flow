<?php

namespace App\Actions\Stock;

use App\DTO\Stock\LockedStock;
use App\DTO\Stock\StockMovementContext;
use App\Exceptions\Warehouses\InsufficientStockException;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use LogicException;

class ApplyStockMovementAction
{
    public function execute(LockedStock $stock, StockMovementContext $context): void
    {
        if (DB::transactionLevel() === 0) {
            throw new LogicException('Stock movements must be applied inside a database transaction.');
        }

        if ($context->order && $stock->warehouse->id !== $context->order->warehouse_id) {
            throw new LogicException('The locked stock and order belong to different warehouses.');
        }

        $cases = [];
        $bindings = [];
        $movements = [];
        $createdAt = now();
        $quantityChanges = $stock->changes();

        foreach ($quantityChanges as $productId => $quantityChange) {
            $quantityBefore = $stock->quantityBefore($productId);
            $quantityAfter = $quantityBefore + $quantityChange;

            if ($quantityAfter < 0) {
                throw new InsufficientStockException($productId, abs($quantityChange), $quantityBefore);
            }

            $cases[] = 'WHEN product_id = ? THEN ?';
            $bindings[] = $productId;
            $bindings[] = $quantityAfter;

            $movements[] = [
                'warehouse_id' => $stock->warehouse->id,
                'product_id' => $productId,
                'order_id' => $context->order?->id,
                'actor_id' => $context->actor?->id,
                'type' => $context->type->value,
                'quantity_change' => $quantityChange,
                'quantity_before' => $quantityBefore,
                'quantity_after' => $quantityAfter,
                'comment' => $context->comment,
                'created_at' => $createdAt,
            ];
        }

        $productIds = array_keys($quantityChanges);
        $placeholders = implode(',', array_fill(0, count($productIds), '?'));
        $bindings[] = $stock->warehouse->id;
        array_push($bindings, ...$productIds);

        DB::statement(
            'UPDATE warehouse_product
            SET stock_quantity = CASE '.implode(' ', $cases).' ELSE stock_quantity END
            WHERE warehouse_id = ? AND product_id IN ('.$placeholders.')',
            $bindings,
        );

        StockMovement::insert($movements);
    }
}
