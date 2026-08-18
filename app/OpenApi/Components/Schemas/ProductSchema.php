<?php

namespace App\OpenApi\Components\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'WarehouseProduct',
    required: ['id', 'name', 'sku', 'price', 'stock_quantity'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'Apple juice'),
        new OA\Property(property: 'sku', type: 'string', example: 'APPLE-001'),
        new OA\Property(property: 'description', type: 'string', nullable: true),
        new OA\Property(property: 'price', type: 'integer', description: 'Price in kopecks.', example: 19900),
        new OA\Property(property: 'stock_quantity', type: 'integer', example: 7),
    ],
)]
class ProductSchema {}
