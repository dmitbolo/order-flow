<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\StockMovementResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OpenApi\Attributes as OA;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;

class StockMovementController extends Controller
{
    #[OA\Get(
        path: '/stock-movements',
        summary: 'List stock movements performed by the current user',
        security: [['sanctum' => []]],
        tags: ['Stock movements'],
        parameters: [
            new OA\Parameter(name: 'filter[warehouse_id]', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'filter[product_id]', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'filter[order_id]', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'filter[type]', in: 'query', schema: new OA\Schema(type: 'string', enum: ['initial_balance', 'manual_adjustment', 'order_created', 'order_canceled'])),
            new OA\Parameter(name: 'sort', description: 'Available: created_at, quantity_change, quantity_after. Use - for descending.', in: 'query', schema: new OA\Schema(type: 'string', example: '-created_at')),
            new OA\Parameter(name: 'page', description: 'Page number.', in: 'query', schema: new OA\Schema(type: 'integer', default: 1, minimum: 1)),
            new OA\Parameter(name: 'per_page', description: 'Items per page.', in: 'query', schema: new OA\Schema(type: 'integer', default: 20, maximum: 100, minimum: 1)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Stock movements', content: new OA\JsonContent(properties: [new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/StockMovement')), new OA\Property(property: 'links', ref: '#/components/schemas/PaginationLinks'), new OA\Property(property: 'meta', ref: '#/components/schemas/PaginationMeta')], type: 'object')),
            new OA\Response(ref: '#/components/responses/BadRequestError', response: 400),
            new OA\Response(ref: '#/components/responses/UnauthorizedError', response: 401),
        ],
    )]
    public function index(Request $request): AnonymousResourceCollection
    {
        $perPage = max(1, min((int) $request->input('per_page', 20), 100));

        $movements = QueryBuilder::for($request->user()->stockMovements()->with(['warehouse', 'product']))
            ->allowedFilters(
                AllowedFilter::exact('warehouse_id'),
                AllowedFilter::exact('product_id'),
                AllowedFilter::exact('order_id'),
                AllowedFilter::exact('type'),
            )
            ->allowedSorts(
                AllowedSort::field('created_at'),
                AllowedSort::field('quantity_change'),
                AllowedSort::field('quantity_after'),
            )
            ->defaultSort('-created_at')
            ->paginate($perPage)
            ->withQueryString();

        return StockMovementResource::collection($movements);
    }
}
