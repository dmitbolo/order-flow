<?php

namespace App\Jobs;

use App\Jobs\Concerns\TracksJobExecution;
use App\Models\Order;
use App\Notifications\OrderCreatedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Notification;
use Throwable;

class SendOrderCreatedNotification implements ShouldQueue
{
    use Queueable, TracksJobExecution;

    public int $tries = 3;

    public int $timeout = 30;

    public bool $failOnTimeout = true;

    public readonly float $dispatchedAt;

    public function __construct(
        public readonly int $orderId,
        public readonly int $warehouseId,
    ) {
        $this->dispatchedAt = microtime(true);
        $this->onQueue('notifications')->afterCommit();
    }

    /** @return list<int> */
    public function backoff(): array
    {
        return [30, 120, 300];
    }

    public function handle(): void
    {
        $this->startTracking();

        $order = Order::query()->with('user')->findOrFail($this->orderId);

        Notification::send($order->user, new OrderCreatedNotification(
            orderId: $order->id,
            totalAmount: $order->total_amount,
        ));

        $this->logJobSucceeded($this->logContext());
    }

    /** @return list<string> */
    public function tags(): array
    {
        return [
            'order:'.$this->orderId,
            'warehouse:'.$this->warehouseId,
        ];
    }

    public function failed(Throwable $exception): void
    {
        $this->logJobFailed($exception, $this->logContext());
    }

    /** @return array<string, int|float|string> */
    private function logContext(): array
    {
        return [
            'job' => self::class,
            'queue' => 'notifications',
            'order_id' => $this->orderId,
            'warehouse_id' => $this->warehouseId,
            'dispatched_at' => $this->dispatchedAt,
        ];
    }
}
