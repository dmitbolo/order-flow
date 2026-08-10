<?php

namespace App\Actions\Orders;

use App\Enums\OrderStatus;
use App\Exceptions\Orders\OrderCannotBeCanceledException;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

class CancelOrderAction
{
    /**
     * @throws \Throwable
     * @throws OrderCannotBeCanceledException
     */
    public function execute(Order $order): Order
    {
        if ($order->status !== OrderStatus::Pending) {
            throw new OrderCannotBeCanceledException;
        }

        return DB::transaction(function () use ($order) {
            $order = Order::where('id', $order->id)->lockForUpdate()->first();

            if ($order->status !== OrderStatus::Pending) {
                throw new OrderCannotBeCanceledException;
            }

            // Prevent deadlocks by always locking rows in the same order.
            $items = $order->items()
                ->select('product_id', DB::raw('SUM(quantity) as quantity'))
                ->groupBy('product_id')
                ->orderBy('product_id')
                ->get();

            $order->warehouse->incrementProductStocks($items);

            $order->update([
                'status' => OrderStatus::Canceled,
            ]);

            return $order->fresh(['warehouse', 'items']);
        });
    }
}
