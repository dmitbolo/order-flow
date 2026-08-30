<?php

namespace App\Listeners;

use App\Events\OrderCreated;
use App\Jobs\CheckLowStock;
use App\Jobs\SendOrderCreatedNotification;

class DispatchOrderCreatedJobs
{
    public function handle(OrderCreated $event): void
    {
        SendOrderCreatedNotification::dispatch(
            orderId: $event->orderId,
            warehouseId: $event->warehouseId,
        );

        CheckLowStock::dispatch(
            orderId: $event->orderId,
            warehouseId: $event->warehouseId,
            productIds: array_values(array_unique($event->productIds)),
        );
    }
}
