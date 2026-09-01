<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum OrderStatus: string implements HasColor, HasLabel
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Canceled = 'canceled';
    case Completed = 'completed';

    public function canTransitionTo(self $target): bool
    {
        return match ($this) {
            self::Pending => $target === self::Processing,
            self::Processing => $target === self::Completed,
            self::Canceled, self::Completed => false,
        };
    }

    // Human-readable labels for UI and API responses.
    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Ожидает обработки',
            self::Processing => 'В обработке',
            self::Canceled => 'Отменен',
            self::Completed => 'Выполнен',
        };
    }

    public function getLabel(): string
    {
        return $this->label();
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Processing => 'info',
            self::Canceled => 'danger',
            self::Completed => 'success',
        };
    }
}
