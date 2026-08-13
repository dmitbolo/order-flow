<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ProductResource;
use App\Models\Warehouse;
use App\QueryFilters\StartsWithFilter;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;

class WarehouseProductController extends Controller
{
    /**
     * Получить список товаров конкретного склада.
     * URL: GET /api/v1/warehouses/1/products
     */
    public function index(Request $request, int $id)
    {
        $warehouse = Warehouse::query()
            ->where('is_active', true)
            ->findOrFail($id);

        $baseQuery = $warehouse->products()
            ->where('products.is_active', true)
            ->select(['products.id', 'products.name', 'products.sku', 'products.description']);

        $perPage = max(1, min((int) $request->input('per_page', 10), 100));

        $products = QueryBuilder::for($baseQuery)
            ->allowedFilters(
                AllowedFilter::custom('name', new StartsWithFilter('products.name')),
                AllowedFilter::custom('sku', new StartsWithFilter('products.sku')),
            )
            ->allowedSorts(
                AllowedSort::field('name', 'products.name'),
                AllowedSort::field('price', 'warehouse_product.price'),
                AllowedSort::field('stock_quantity', 'warehouse_product.stock_quantity'),
            )
            ->paginate($perPage)
            ->withQueryString();

        return ProductResource::collection($products);
    }
}
