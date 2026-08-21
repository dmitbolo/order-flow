<?php

namespace App\Enums;

enum StockMovementType: string
{
    case InitialBalance = 'initial_balance';
    case ManualAdjustment = 'manual_adjustment';
    case OrderCreated = 'order_created';
    case OrderCanceled = 'order_canceled';

    public function label(): string
    {
        return match ($this) {
            self::InitialBalance => 'Начальный остаток',
            self::ManualAdjustment => 'Ручная корректировка',
            self::OrderCreated => 'Списание по заказу',
            self::OrderCanceled => 'Восстановление при отмене заказа',
        };
    }
}
