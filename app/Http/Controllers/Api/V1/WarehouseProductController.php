<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ProductResource;
use App\Models\Warehouse;
use App\QueryFilters\StartsWithFilter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OpenApi\Attributes as OA;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;

class WarehouseProductController extends Controller
{
    /**
     * List products available at a specific warehouse.
     * URL: GET /api/v1/warehouses/1/products
     */
    #[OA\Get(
        path: '/warehouses/{id}/products',
        summary: 'List active products available at a warehouse',
        security: [['sanctum' => []]],
        tags: ['Warehouses'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'filter[name]', in: 'query', schema: new OA\Schema(type: 'string', example: 'Apple')),
            new OA\Parameter(name: 'filter[sku]', in: 'query', schema: new OA\Schema(type: 'string', example: 'APPLE')),
            new OA\Parameter(name: 'sort', description: 'Available: name, price, stock_quantity. Use - for descending.', in: 'query', schema: new OA\Schema(type: 'string', example: '-stock_quantity')),
            new OA\Parameter(name: 'page', description: 'Page number.', in: 'query', schema: new OA\Schema(type: 'integer', minimum: 1, default: 1, example: 1)),
            new OA\Parameter(name: 'per_page', description: 'Items per page. Values are normalized to the range from 1 to 100.', in: 'query', schema: new OA\Schema(type: 'integer', default: 10, example: 10)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Warehouse products', content: new OA\JsonContent(required: ['data', 'links', 'meta'], properties: [new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/WarehouseProduct')), new OA\Property(property: 'links', ref: '#/components/schemas/PaginationLinks'), new OA\Property(property: 'meta', ref: '#/components/schemas/PaginationMeta')], type: 'object')),
            new OA\Response(response: 400, ref: '#/components/responses/BadRequestError'),
            new OA\Response(ref: '#/components/responses/UnauthorizedError', response: 401),
            new OA\Response(ref: '#/components/responses/NotFoundError', response: 404),
        ],
    )]
    public function index(Request $request, int $id): AnonymousResourceCollection
    {
        $warehouse = Warehouse::query()
            ->where('is_active', true)
            ->findOrFail($id);

        $baseQuery = $warehouse->products()
            ->where('products.is_active', true)
            ->select(['products.id', 'products.name', 'products.sku', 'products.description']);

        $products = QueryBuilder::for($baseQuery)
            ->allowedFilters(
                AllowedFilter::custom('name', new StartsWithFilter('products.name'))->delimiter(''),
                AllowedFilter::custom('sku', new StartsWithFilter('products.sku'))->delimiter(''),
            )
            ->allowedSorts(
                AllowedSort::field('name', 'products.name'),
                AllowedSort::field('price', 'warehouse_product.price'),
                AllowedSort::field('stock_quantity', 'warehouse_product.stock_quantity'),
            )
            ->paginate($this->perPage($request, default: 10))
            ->withQueryString();

        return ProductResource::collection($products);
    }
}
