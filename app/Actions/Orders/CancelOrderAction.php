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
        if ($order->status !== OrderStatus::Pending) {
            throw new OrderCannotBeCanceledException;
        }

        return DB::transaction(function () use ($order, $actor) {
            $order = Order::query()->lockForUpdate()->findOrFail($order->id);

            if ($order->status !== OrderStatus::Pending) {
                throw new OrderCannotBeCanceledException;
            }

            // Prevent deadlocks by always locking rows in the same order.
            $items = $order->items()
                ->select('product_id', DB::raw('SUM(quantity) as quantity'))
                ->groupBy('product_id')
                ->orderBy('product_id')
                ->get();

            $quantities = $items->pluck('quantity', 'product_id')->map(fn ($quantity) => (int) $quantity)->all();

            $lockedStock = $this->lockStock->execute($order->warehouse, $quantities);

            $this->applyMovement->execute(
                $lockedStock,
                StockMovementContext::orderCanceled($order, $actor),
            );

            $order->update([
                'status' => OrderStatus::Canceled,
            ]);

            return $order->fresh(['warehouse', 'items']);
        });
    }
}
