<?php

namespace App\Actions\Orders;

use App\Actions\Stock\ApplyStockMovementAction;
use App\Actions\Stock\LockStockAction;
use App\DTO\CreateOrderData;
use App\DTO\Stock\StockMovementContext;
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
            $warehouse = Warehouse::where('is_active', true)->findOrFail($data->warehouseId);

            $quantities = $data->getItemsWithQuantities();
            $productIds = array_keys($quantities);
            $unavailableProductId = Product::query()
                ->whereIn('id', $productIds)
                ->where('is_active', false)
                ->orderBy('id')
                ->value('id');

            if ($unavailableProductId !== null) {
                throw new ProductUnavailableException((int) $unavailableProductId);
            }

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

            return $order->load(['warehouse', 'items']);
        });
    }
}
