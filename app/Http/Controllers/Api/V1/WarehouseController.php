<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\WarehouseResource;
use App\Models\Warehouse;
use App\QueryFilters\StartsWithFilter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class WarehouseController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $perPage = max(1, min((int) $request->input('per_page', 15), 100));

        $warehouses = QueryBuilder::for(Warehouse::query()->where('is_active', true))
            ->allowedFilters(
                AllowedFilter::custom('name', new StartsWithFilter('warehouses.name')),
                AllowedFilter::custom('code', new StartsWithFilter('warehouses.code')),
            )
            ->allowedSorts('name', 'code', 'created_at')
            ->defaultSort('name')
            ->paginate($perPage)
            ->withQueryString();

        return WarehouseResource::collection($warehouses);
    }

    public function show(int $id): WarehouseResource
    {
        $warehouse = Warehouse::query()
            ->where('is_active', true)
            ->findOrFail($id);

        return new WarehouseResource($warehouse);
    }
}
