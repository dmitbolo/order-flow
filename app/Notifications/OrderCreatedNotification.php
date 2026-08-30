<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderCreatedNotification extends Notification
{
    public function __construct(
        public readonly int $orderId,
        public readonly int $totalAmount,
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Заказ №{$this->orderId} создан")
            ->greeting('Заказ успешно создан')
            ->line("Сумма заказа: {$this->totalAmount}.")
            ->line('Мы сообщим вам об изменении его статуса.');
    }
}
