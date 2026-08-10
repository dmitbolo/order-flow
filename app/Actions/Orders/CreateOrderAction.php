<?php

namespace App\Actions\Orders;

use App\DTO\CreateOrderData;
use App\Models\Order;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Throwable;

class CreateOrderAction
{
    /**
     * @throws Throwable
     */
    public function execute(User $user, CreateOrderData $data): Order
    {
        return DB::transaction(function () use ($user, $data) {
            /** @var Warehouse $warehouse */
            $warehouse = Warehouse::where('is_active', true)->findOrFail($data->warehouseId);

            $prices = $warehouse->decrementProductStocks($data->getItemsWithQuantities());

            $order = Order::createFromData($user, $warehouse, $data, $prices);

            return $order->load(['warehouse', 'items']);
        });
    }
}
