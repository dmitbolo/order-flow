<?php

namespace App\OpenApi\Components\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Warehouse',
    required: ['id', 'name', 'code', 'is_active'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'Central warehouse'),
        new OA\Property(property: 'code', type: 'string', example: 'CENTRAL'),
        new OA\Property(property: 'is_active', type: 'boolean', example: true),
    ],
)]
class WarehouseSchema {}
