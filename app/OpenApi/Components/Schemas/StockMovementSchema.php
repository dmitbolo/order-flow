<?php

namespace App\OpenApi\Components\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'StockMovement',
    required: [
        'id',
        'warehouse',
        'product',
        'order_id',
        'type',
        'type_label',
        'quantity_change',
        'quantity_before',
        'quantity_after',
        'comment',
        'created_at',
    ],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'warehouse', required: ['id', 'name'], properties: [
            new OA\Property(property: 'id', type: 'integer', example: 1),
            new OA\Property(property: 'name', type: 'string', example: 'Основной склад'),
        ], type: 'object'),
        new OA\Property(property: 'product', required: ['id', 'name', 'sku'], properties: [
            new OA\Property(property: 'id', type: 'integer', example: 10),
            new OA\Property(property: 'name', type: 'string', example: 'Товар'),
            new OA\Property(property: 'sku', type: 'string', example: 'SKU-001'),
        ], type: 'object'),
        new OA\Property(property: 'order_id', type: 'integer', example: 42, nullable: true),
        new OA\Property(property: 'type', type: 'string', enum: ['initial_balance', 'manual_adjustment', 'order_created', 'order_canceled']),
        new OA\Property(property: 'type_label', type: 'string', example: 'Списание по заказу'),
        new OA\Property(property: 'quantity_change', type: 'integer', example: -2),
        new OA\Property(property: 'quantity_before', type: 'integer', example: 10),
        new OA\Property(property: 'quantity_after', type: 'integer', example: 8),
        new OA\Property(property: 'comment', type: 'string', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
    ],
)]
class StockMovementSchema {}
