<?php

namespace App\Enums;

enum OrderStatus: string
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
}
