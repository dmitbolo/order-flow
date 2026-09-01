<?php

namespace App\Actions\Orders;

use App\Enums\OrderStatus;
use App\Exceptions\Orders\InvalidOrderStatusTransitionException;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

class TransitionOrderStatusAction
{
    /**
     * @throws InvalidOrderStatusTransitionException
     * @throws \Throwable
     */
    public function execute(Order $order, OrderStatus $targetStatus): Order
    {
        return DB::transaction(function () use ($order, $targetStatus): Order {
            $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->id);

            if (! $lockedOrder->status->canTransitionTo($targetStatus)) {
                throw new InvalidOrderStatusTransitionException($lockedOrder->status, $targetStatus);
            }

            $lockedOrder->update(['status' => $targetStatus]);

            return $lockedOrder->fresh(['warehouse', 'items']);
        });
    }
}
