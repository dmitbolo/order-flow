<?php

namespace App\Actions\Orders;

use App\Actions\Stock\ApplyStockMovementAction;
use App\Actions\Stock\LockStockAction;
use App\DTO\CreateOrderData;
use App\DTO\Stock\StockMovementContext;
use App\Events\OrderCreated;
use App\Exceptions\Products\ProductUnavailableException;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Throwable;

class CreateOrderAction
{
    public function __construct(
        private readonly LockStockAction $lockStock,
        private readonly ApplyStockMovementAction $applyMovement,
    ) {}

    /**
     * @throws Throwable
     */
    public function execute(User $user, CreateOrderData $data, ?User $actor = null): Order
    {
        return DB::transaction(function () use ($user, $data, $actor) {
            /** @var Warehouse $warehouse */
            $warehouse = Warehouse::query()
                ->where('is_active', true)
                ->sharedLock()
                ->findOrFail($data->warehouseId);

            $quantities = $data->getItemsWithQuantities();
            $productIds = array_keys($quantities);

            $this->lockAvailableProducts($productIds);

            $quantityChanges = array_map(
                static fn (int $quantity): int => -$quantity,
                $quantities,
            );

            $lockedStock = $this->lockStock->execute($warehouse, $quantityChanges);

            $order = Order::createFromData($user, $warehouse, $data, $lockedStock->prices());

            $this->applyMovement->execute(
                $lockedStock,
                StockMovementContext::orderCreated($order, $actor ?? $user),
            );

            OrderCreated::dispatch(
                orderId: $order->id,
                warehouseId: $warehouse->id,
                productIds: $productIds,
            );

            return $order->load(['warehouse', 'items']);
        });
    }

    /**
     * @param  list<int>  $productIds
     */
    private function lockAvailableProducts(array $productIds): void
    {
        $products = Product::query()
            ->whereIn('id', $productIds)
            ->orderBy('id')
            ->sharedLock()
            ->get(['id', 'is_active'])
            ->keyBy('id');

        foreach ($productIds as $productId) {
            $product = $products->get($productId);

            if (! $product?->is_active) {
                throw new ProductUnavailableException($productId);
            }
        }
    }
}
