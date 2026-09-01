<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\WarehouseResource;
use App\Models\Warehouse;
use App\QueryFilters\StartsWithFilter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OpenApi\Attributes as OA;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class WarehouseController extends Controller
{
    #[OA\Get(
        path: '/warehouses',
        summary: 'List active warehouses',
        security: [['sanctum' => []]],
        tags: ['Warehouses'],
        parameters: [
            new OA\Parameter(name: 'filter[name]', in: 'query', schema: new OA\Schema(type: 'string', example: 'Moscow')),
            new OA\Parameter(name: 'filter[code]', in: 'query', schema: new OA\Schema(type: 'string', example: 'MSK')),
            new OA\Parameter(name: 'sort', description: 'Available: name, code, created_at. Use - for descending.', in: 'query', schema: new OA\Schema(type: 'string', example: 'name')),
            new OA\Parameter(name: 'page', description: 'Page number.', in: 'query', schema: new OA\Schema(type: 'integer', minimum: 1, default: 1, example: 1)),
            new OA\Parameter(name: 'per_page', description: 'Items per page. Values are normalized to the range from 1 to 100.', in: 'query', schema: new OA\Schema(type: 'integer', default: 15, example: 15)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Active warehouses', content: new OA\JsonContent(required: ['data', 'links', 'meta'], properties: [new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Warehouse')), new OA\Property(property: 'links', ref: '#/components/schemas/PaginationLinks'), new OA\Property(property: 'meta', ref: '#/components/schemas/PaginationMeta')], type: 'object')),
            new OA\Response(response: 400, ref: '#/components/responses/BadRequestError'),
            new OA\Response(ref: '#/components/responses/UnauthorizedError', response: 401),
        ],
    )]
    public function index(Request $request): AnonymousResourceCollection
    {
        $warehouses = QueryBuilder::for(Warehouse::query()->where('is_active', true))
            ->allowedFilters(
                AllowedFilter::custom('name', new StartsWithFilter('warehouses.name'))->delimiter(''),
                AllowedFilter::custom('code', new StartsWithFilter('warehouses.code'))->delimiter(''),
            )
            ->allowedSorts('name', 'code', 'created_at')
            ->defaultSort('name')
            ->paginate($this->perPage($request, default: 15))
            ->withQueryString();

        return WarehouseResource::collection($warehouses);
    }

    #[OA\Get(
        path: '/warehouses/{id}',
        summary: 'Get an active warehouse',
        security: [['sanctum' => []]],
        tags: ['Warehouses'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Warehouse', content: new OA\JsonContent(required: ['data'], properties: [new OA\Property(property: 'data', ref: '#/components/schemas/Warehouse')], type: 'object')),
            new OA\Response(ref: '#/components/responses/UnauthorizedError', response: 401),
            new OA\Response(ref: '#/components/responses/NotFoundError', response: 404),
        ],
    )]
    public function show(int $id): WarehouseResource
    {
        $warehouse = Warehouse::query()
            ->where('is_active', true)
            ->findOrFail($id);

        return new WarehouseResource($warehouse);
    }
}
