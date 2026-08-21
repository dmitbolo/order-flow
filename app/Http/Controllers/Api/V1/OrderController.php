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
use OpenApi\Attributes as OA;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use Symfony\Component\HttpFoundation\Response;

class OrderController extends Controller
{
    #[OA\Get(
        path: '/orders', summary: 'List current user orders', security: [['sanctum' => []]], tags: ['Orders'],
        parameters: [
            new OA\Parameter(name: 'filter[status]', in: 'query', schema: new OA\Schema(type: 'string', example: 'processing', enum: ['pending', 'processing', 'canceled', 'completed'])),
            new OA\Parameter(name: 'filter[warehouse_id]', in: 'query', schema: new OA\Schema(type: 'integer', example: 5)),
            new OA\Parameter(name: 'include', description: 'Separate values with commas. Available: warehouse, items.', in: 'query', schema: new OA\Schema(type: 'string', example: 'warehouse,items')),
            new OA\Parameter(name: 'sort', description: 'Available: id, total_amount, created_at. Use - for descending.', in: 'query', schema: new OA\Schema(type: 'string', example: '-created_at')),
            new OA\Parameter(name: 'page', description: 'Page number.', in: 'query', schema: new OA\Schema(type: 'integer', example: 1, default: 1, minimum: 1)),
            new OA\Parameter(name: 'per_page', description: 'Items per page.', in: 'query', schema: new OA\Schema(type: 'integer', example: 15, default: 15, maximum: 100, minimum: 1)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Orders', content: new OA\JsonContent(properties: [new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Order')), new OA\Property(property: 'links', ref: '#/components/schemas/PaginationLinks'), new OA\Property(property: 'meta', ref: '#/components/schemas/PaginationMeta')], type: 'object')),
            new OA\Response(ref: '#/components/responses/BadRequestError', response: 400),
            new OA\Response(ref: '#/components/responses/UnauthorizedError', response: 401),
        ],
    )]
    public function index(Request $request): AnonymousResourceCollection
    {
        $perPage = min((int) $request->input('per_page', 15), 100)
                |> (fn ($x) => max(1, $x));

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

    #[OA\Get(
        path: '/orders/{id}', summary: 'Get a current user order', security: [['sanctum' => []]], tags: ['Orders'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'include', in: 'query', description: 'Separate values with commas. Available: warehouse, items.', schema: new OA\Schema(type: 'string', example: 'warehouse,items')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Order', content: new OA\JsonContent(type: 'object', properties: [new OA\Property(property: 'data', ref: '#/components/schemas/Order')])),
            new OA\Response(ref: '#/components/responses/BadRequestError', response: 400),
            new OA\Response(ref: '#/components/responses/UnauthorizedError', response: 401),
            new OA\Response(ref: '#/components/responses/NotFoundError', response: 404),
        ],
    )]
    public function show(int $id): OrderResource
    {
        $order = QueryBuilder::for(Order::class)
            ->where('user_id', auth()->id())
            ->allowedIncludes('warehouse', 'items')
            ->findOrFail($id);

        return OrderResource::make($order);
    }

    #[OA\Post(
        path: '/orders', summary: 'Create an order and reserve stock', security: [['sanctum' => []]], requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/CreateOrderRequest')),
        tags: ['Orders'],
        responses: [
            new OA\Response(response: 201, description: 'Order created', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string'), new OA\Property(property: 'data', ref: '#/components/schemas/Order')], type: 'object')),
            new OA\Response(ref: '#/components/responses/UnauthorizedError', response: 401),
            new OA\Response(response: 422, description: 'Validation error, insufficient stock, or product unavailable', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(ref: '#/components/responses/NotFoundError', response: 404),
        ],
    )]
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
    #[OA\Post(
        path: '/orders/{id}/cancel', summary: 'Cancel a pending order and restore stock', security: [['sanctum' => []]], tags: ['Orders'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Order cancelled', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string'), new OA\Property(property: 'data', ref: '#/components/schemas/Order')], type: 'object')),
            new OA\Response(ref: '#/components/responses/UnauthorizedError', response: 401),
            new OA\Response(ref: '#/components/responses/NotFoundError', response: 404),
            new OA\Response(response: 422, description: 'Order cannot be cancelled', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        ],
    )]
    public function cancel(Request $request, int $id, CancelOrderAction $action): JsonResponse
    {
        $order = $request->user()->orders()->findOrFail($id);

        $canceledOrder = $action->execute($order, $request->user());

        return response()->json([
            'message' => 'Заказ успешно отменен',
            'data' => new OrderResource($canceledOrder),
        ], Response::HTTP_OK);
    }
}
