<?php

namespace App\Jobs;

use App\Jobs\Concerns\TracksJobExecution;
use App\Models\User;
use App\Models\WarehouseProduct;
use App\Notifications\LowStockDetectedNotification;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Throwable;

class CheckLowStock implements ShouldBeUnique, ShouldQueue
{
    use Queueable, TracksJobExecution;

    public int $tries = 5;

    public int $timeout = 20;

    public int $uniqueFor = 300;

    public bool $failOnTimeout = true;

    public readonly float $dispatchedAt;

    /** @var list<int> */
    public readonly array $productIds;

    /**
     * @param  list<int>  $productIds
     */
    public function __construct(
        public readonly int $orderId,
        public readonly int $warehouseId,
        array $productIds,
    ) {
        $this->productIds = array_values(array_unique($productIds));
        $this->dispatchedAt = microtime(true);
        $this->onQueue('inventory')->afterCommit();
    }

    /** @return list<int> */
    public function backoff(): array
    {
        return [5, 15, 30, 60];
    }

    public function handle(): void
    {
        $this->startTracking();

        $threshold = (int) config('inventory.low_stock_threshold');
        $lowStockProductIds = array_values(
            WarehouseProduct::query()
                ->where('warehouse_id', $this->warehouseId)
                ->whereIn('product_id', $this->productIds)
                ->where('stock_quantity', '<=', $threshold)
                ->orderBy('product_id')
                ->pluck('product_id')
                ->map(static fn (mixed $productId): int => (int) $productId)
                ->all(),
        );
        $notificationResult = $this->sendLowStockNotifications($lowStockProductIds, $threshold);

        $this->logJobSucceeded([
            ...$this->logContext(),
            'low_stock_product_ids' => $lowStockProductIds,
            ...$notificationResult,
        ]);
    }

    public function uniqueId(): string
    {
        return "order:{$this->orderId}:warehouse:{$this->warehouseId}";
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

    /** @return array<string, int|float|string|list<int>> */
    private function logContext(): array
    {
        return [
            'job' => self::class,
            'queue' => 'inventory',
            'order_id' => $this->orderId,
            'warehouse_id' => $this->warehouseId,
            'product_ids' => $this->productIds,
            'dispatched_at' => $this->dispatchedAt,
        ];
    }

    /**
     * @param  list<int>  $productIds
     * @return array{
     *     notified_product_ids: list<int>,
     *     cooldown_product_ids: list<int>,
     *     recipients_count: int
     * }
     */
    private function sendLowStockNotifications(array $productIds, int $threshold): array
    {
        if ($productIds === []) {
            return [
                'notified_product_ids' => [],
                'cooldown_product_ids' => [],
                'recipients_count' => 0,
            ];
        }

        $admins = User::query()->where('is_admin', true)->get();

        if ($admins->isEmpty()) {
            return [
                'notified_product_ids' => [],
                'cooldown_product_ids' => [],
                'recipients_count' => 0,
            ];
        }

        $notifiedProductIds = $this->reserveNotificationCooldowns($productIds);
        $cooldownProductIds = array_values(array_diff($productIds, $notifiedProductIds));

        if ($notifiedProductIds === []) {
            return [
                'notified_product_ids' => [],
                'cooldown_product_ids' => $cooldownProductIds,
                'recipients_count' => 0,
            ];
        }

        try {
            Notification::send($admins, new LowStockDetectedNotification(
                warehouseId: $this->warehouseId,
                productIds: $notifiedProductIds,
                threshold: $threshold,
            ));
        } catch (Throwable $exception) {
            $this->releaseNotificationCooldowns($notifiedProductIds);

            throw $exception;
        }

        return [
            'notified_product_ids' => $notifiedProductIds,
            'cooldown_product_ids' => $cooldownProductIds,
            'recipients_count' => $admins->count(),
        ];
    }

    /**
     * @param  list<int>  $productIds
     * @return list<int>
     */
    private function reserveNotificationCooldowns(array $productIds): array
    {
        $ttl = max(1, (int) config('inventory.low_stock_notification_cooldown_seconds'));
        $reservedProductIds = [];

        foreach ($productIds as $productId) {
            if (Cache::add($this->notificationCooldownKey($productId), true, $ttl)) {
                $reservedProductIds[] = $productId;
            }
        }

        return $reservedProductIds;
    }

    /** @param list<int> $productIds */
    private function releaseNotificationCooldowns(array $productIds): void
    {
        foreach ($productIds as $productId) {
            Cache::forget($this->notificationCooldownKey($productId));
        }
    }

    private function notificationCooldownKey(int $productId): string
    {
        return "inventory:low-stock:warehouse:{$this->warehouseId}:product:{$productId}";
    }
}
