<?php

namespace App\Actions\Orders;

use App\Actions\Stock\ApplyStockMovementAction;
use App\Actions\Stock\LockStockAction;
use App\DTO\Stock\StockMovementContext;
use App\Enums\OrderStatus;
use App\Exceptions\Orders\OrderCannotBeCanceledException;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CancelOrderAction
{
    public function __construct(
        private readonly LockStockAction $lockStock,
        private readonly ApplyStockMovementAction $applyMovement,
    ) {}

    /**
     * @throws \Throwable
     * @throws OrderCannotBeCanceledException
     */
    public function execute(Order $order, User $actor): Order
    {
        return DB::transaction(function () use ($order, $actor) {
            $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->id);

            if ($lockedOrder->status !== OrderStatus::Pending) {
                throw new OrderCannotBeCanceledException;
            }

            // Prevent deadlocks by always locking rows in the same order.
            $items = $lockedOrder->items()
                ->select('product_id', DB::raw('SUM(quantity) as quantity'))
                ->groupBy('product_id')
                ->orderBy('product_id')
                ->get();

            $quantities = $items->pluck('quantity', 'product_id')->map(fn ($quantity) => (int) $quantity)->all();

            $lockedStock = $this->lockStock->execute($lockedOrder->warehouse, $quantities);

            $this->applyMovement->execute(
                $lockedStock,
                StockMovementContext::orderCanceled($lockedOrder, $actor),
            );

            $lockedOrder->update([
                'status' => OrderStatus::Canceled,
            ]);

            return $lockedOrder->fresh(['warehouse', 'items']);
        });
    }
}
