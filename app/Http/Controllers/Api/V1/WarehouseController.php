<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\WarehouseResource;
use App\Models\Warehouse;

class WarehouseController extends Controller
{
    public function show(Warehouse $warehouse)
    {
        return new WarehouseResource($warehouse);
    }
}
