<?php

namespace App\Actions\Stock;

use App\DTO\Stock\StockMovementContext;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;

class AdjustStockAction
{
    public function __construct(
        private readonly LockStockAction $lockStock,
        private readonly ApplyStockMovementAction $applyMovement,
    ) {}

    public function execute(
        Warehouse $warehouse,
        int $productId,
        int $quantityChange,
        ?User $actor,
        ?string $comment = null,
    ): void {
        DB::transaction(function () use ($warehouse, $productId, $quantityChange, $actor, $comment): void {
            $stock = $this->lockStock->execute($warehouse, [$productId => $quantityChange]);

            $this->applyMovement->execute(
                $stock,
                StockMovementContext::manualAdjustment($actor, $comment),
            );
        });
    }
}
