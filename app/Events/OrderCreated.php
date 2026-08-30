<?php

namespace App\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderCreated implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    /**
     * @param  list<int>  $productIds
     */
    public function __construct(
        public readonly int $orderId,
        public readonly int $warehouseId,
        public readonly array $productIds,
    ) {}
}
