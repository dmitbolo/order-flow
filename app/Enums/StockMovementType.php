<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum StockMovementType: string implements HasColor, HasLabel
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

    public function getLabel(): string
    {
        return $this->label();
    }

    public function getColor(): string
    {
        return match ($this) {
            self::InitialBalance => 'gray',
            self::ManualAdjustment => 'info',
            self::OrderCreated => 'danger',
            self::OrderCanceled => 'success',
        };
    }
}
