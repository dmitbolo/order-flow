<?php

namespace App\Jobs;

use App\Jobs\Concerns\TracksJobExecution;
use App\Models\User;
use App\Models\WarehouseProduct;
use App\Notifications\LowStockDetectedNotification;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
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
        $lowStockPositions = WarehouseProduct::query()
            ->where('warehouse_id', $this->warehouseId)
            ->whereIn('product_id', $this->productIds)
            ->where('stock_quantity', '<=', $threshold)
            ->orderBy('product_id')
            ->get();
        $lowStockProductIds = array_values(
            $lowStockPositions
                ->map(static fn (WarehouseProduct $position): int => $position->product_id)
                ->all(),
        );
        $recipientsCount = 0;

        if ($lowStockProductIds !== []) {
            $admins = User::query()->where('is_admin', true)->get();
            $recipientsCount = $admins->count();

            Notification::send($admins, new LowStockDetectedNotification(
                warehouseId: $this->warehouseId,
                productIds: $lowStockProductIds,
                threshold: $threshold,
            ));
        }

        $this->logJobSucceeded([
            ...$this->logContext(),
            'low_stock_product_ids' => $lowStockProductIds,
            'recipients_count' => $recipientsCount,
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
}
