<?php

namespace App\OpenApi\Components\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'CreateOrderItemRequest',
    required: ['product_id', 'quantity'],
    properties: [
        new OA\Property(property: 'product_id', type: 'integer', example: 1),
        new OA\Property(property: 'quantity', type: 'integer', minimum: 1, example: 2),
    ],
)]
#[OA\Schema(
    schema: 'CreateOrderRequest',
    required: ['warehouse_id', 'items'],
    properties: [
        new OA\Property(property: 'warehouse_id', type: 'integer', example: 1),
        new OA\Property(property: 'notes', type: 'string', nullable: true, maxLength: 1000, example: 'Deliver before 18:00.'),
        new OA\Property(property: 'items', type: 'array', minItems: 1, description: 'Every product_id must occur only once.', items: new OA\Items(ref: '#/components/schemas/CreateOrderItemRequest')),
    ],
)]
class CreateOrderSchema {}
