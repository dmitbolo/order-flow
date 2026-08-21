<?php

namespace App\DTO\Stock;

use App\Enums\StockMovementType;
use App\Models\Order;
use App\Models\User;

final readonly class StockMovementContext
{
    private function __construct(
        public StockMovementType $type,
        public ?Order $order,
        public ?User $actor,
        public ?string $comment,
    ) {}

    public static function orderCreated(Order $order, User $actor): self
    {
        return new self(StockMovementType::OrderCreated, $order, $actor, null);
    }

    public static function orderCanceled(Order $order, User $actor): self
    {
        return new self(StockMovementType::OrderCanceled, $order, $actor, null);
    }

    public static function manualAdjustment(?User $actor, ?string $comment): self
    {
        return new self(StockMovementType::ManualAdjustment, null, $actor, $comment);
    }
}
