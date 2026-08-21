<?php

namespace App\OpenApi\Components\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'CreateOrderItemRequest',
    required: ['product_id', 'quantity'],
    properties: [
        new OA\Property(property: 'product_id', type: 'integer', example: 1),
        new OA\Property(property: 'quantity', type: 'integer', example: 2, minimum: 1),
    ],
)]
#[OA\Schema(
    schema: 'CreateOrderRequest',
    required: ['warehouse_id', 'items'],
    properties: [
        new OA\Property(property: 'warehouse_id', type: 'integer', example: 1),
        new OA\Property(property: 'notes', type: 'string', example: 'Deliver before 18:00.', nullable: true, maxLength: 1000),
        new OA\Property(property: 'items', description: 'Every product_id must occur only once.', type: 'array', items: new OA\Items(ref: '#/components/schemas/CreateOrderItemRequest'), minItems: 1),
    ],
)]
class CreateOrderSchema {}
