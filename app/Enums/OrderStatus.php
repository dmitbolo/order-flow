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

    // Человекочитаемые названия статусов для UI или ответов API
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
