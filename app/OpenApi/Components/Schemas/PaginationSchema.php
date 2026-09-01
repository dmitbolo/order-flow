<?php

namespace App\OpenApi\Components\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'PaginationLinks',
    required: ['first', 'last', 'prev', 'next'],
    properties: [
        new OA\Property(property: 'first', type: 'string', format: 'uri', nullable: true),
        new OA\Property(property: 'last', type: 'string', format: 'uri', nullable: true),
        new OA\Property(property: 'prev', type: 'string', format: 'uri', nullable: true),
        new OA\Property(property: 'next', type: 'string', format: 'uri', nullable: true),
    ],
)]
#[OA\Schema(
    schema: 'PaginationMetaLink',
    required: ['url', 'label', 'active'],
    properties: [
        new OA\Property(property: 'url', type: 'string', format: 'uri', nullable: true),
        new OA\Property(property: 'label', type: 'string', example: '1'),
        new OA\Property(property: 'page', type: 'integer', example: 1, nullable: true),
        new OA\Property(property: 'active', type: 'boolean', example: true),
    ],
)]
#[OA\Schema(
    schema: 'PaginationMeta',
    required: ['current_page', 'from', 'last_page', 'links', 'path', 'per_page', 'to', 'total'],
    properties: [
        new OA\Property(property: 'current_page', type: 'integer', example: 1),
        new OA\Property(property: 'from', type: 'integer', example: 1, nullable: true),
        new OA\Property(property: 'last_page', type: 'integer', example: 3),
        new OA\Property(
            property: 'links',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/PaginationMetaLink'),
            example: [
                ['url' => null, 'label' => 'Назад', 'page' => null, 'active' => false],
                ['url' => 'http://localhost/api/v1/stock-movements?page=1', 'label' => '1', 'page' => 1, 'active' => true],
                ['url' => 'http://localhost/api/v1/stock-movements?page=2', 'label' => 'Вперёд', 'page' => 2, 'active' => false],
            ],
        ),
        new OA\Property(property: 'path', type: 'string', format: 'uri', example: 'http://localhost/api/v1/stock-movements'),
        new OA\Property(property: 'per_page', type: 'integer', example: 15),
        new OA\Property(property: 'to', type: 'integer', example: 15, nullable: true),
        new OA\Property(property: 'total', type: 'integer', example: 42),
    ],
)]
class PaginationSchema {}
