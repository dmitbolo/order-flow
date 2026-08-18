<?php

namespace App\OpenApi\Components\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'OrderItem',
    required: ['product_id', 'quantity', 'price'],
    properties: [
        new OA\Property(property: 'product_id', type: 'integer', example: 1),
        new OA\Property(property: 'quantity', type: 'integer', example: 2),
        new OA\Property(property: 'price', type: 'integer', description: 'Price in kopecks.', example: 19900),
    ],
)]
#[OA\Schema(
    schema: 'Order',
    required: ['id', 'status', 'total_amount', 'created_at'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'status', type: 'string', enum: ['pending', 'processing', 'canceled', 'completed']),
        new OA\Property(property: 'total_amount', type: 'integer', description: 'Total in kopecks.', example: 39800),
        new OA\Property(property: 'warehouse', ref: '#/components/schemas/Warehouse', description: 'Included only when requested with include=warehouse.'),
        new OA\Property(property: 'items', type: 'array', description: 'Included only when requested with include=items.', items: new OA\Items(ref: '#/components/schemas/OrderItem')),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
    ],
)]
class OrderSchema {}
