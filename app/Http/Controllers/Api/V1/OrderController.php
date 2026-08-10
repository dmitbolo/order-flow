<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Orders\CancelOrderAction;
use App\Actions\Orders\CreateOrderAction;
use App\DTO\CreateOrderData;
use App\Exceptions\Orders\OrderCannotBeCanceledException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CreateOrderRequest;
use App\Http\Resources\Api\V1\OrderResource;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use Symfony\Component\HttpFoundation\Response;

class OrderController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $perPage = min((int)$request->input('per_page', 15), 100)
                |> (fn($x) => max(1, $x));

        $orders = QueryBuilder::for(
            $request->user()->orders()->getQuery()
        )
            ->allowedFilters(
                AllowedFilter::exact('status'),
                AllowedFilter::exact('warehouse_id'),
            )
            ->allowedIncludes('warehouse', 'items')
            ->allowedSorts('id', 'total_amount', 'created_at')
            ->defaultSort('-created_at')
            ->paginate($perPage)
            ->withQueryString();

        return OrderResource::collection($orders);
    }

    public function show(int $id): OrderResource
    {
        $order = QueryBuilder::for(Order::class)
            ->where('user_id', auth()->id())
            ->allowedIncludes('warehouse', 'items')
            ->findOrFail($id);

        return OrderResource::make($order);
    }

    public function store(CreateOrderRequest $request, CreateOrderAction $action): JsonResponse
    {
        $dto = CreateOrderData::fromRequest($request);

        $order = $action->execute(
            user: $request->user(),
            data: $dto
        );

        return response()->json([
            'message' => 'Заказ успешно создан',
            'data' => new OrderResource($order),
        ], Response::HTTP_CREATED);
    }

    /**
     * @throws \Throwable
     * @throws OrderCannotBeCanceledException
     */
    public function cancel(Request $request, int $id, CancelOrderAction $action): JsonResponse
    {
        $order = $request->user()->orders()->findOrFail($id);

        $canceledOrder = $action->execute($order);

        return response()->json([
            'message' => 'Заказ успешно отменен',
            'data' => new OrderResource($canceledOrder),
        ], Response::HTTP_OK);
    }
}
