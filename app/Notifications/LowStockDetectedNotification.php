<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LowStockDetectedNotification extends Notification
{
    /**
     * @param  list<int>  $productIds
     */
    public function __construct(
        public readonly int $warehouseId,
        public readonly array $productIds,
        public readonly int $threshold,
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Обнаружены критические остатки товаров')
            ->error()
            ->line("Склад: {$this->warehouseId}.")
            ->line('Товары: '.implode(', ', $this->productIds).'.')
            ->line("Порог критического остатка: {$this->threshold}.");
    }
}
