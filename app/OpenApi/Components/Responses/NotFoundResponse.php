<?php

namespace App\OpenApi\Components\Responses;

use OpenApi\Attributes as OA;

#[OA\Response(
    response: 'NotFoundError',
    description: 'The requested resource was not found.',
    content: new OA\JsonContent(
        required: ['status', 'error_code', 'message'],
        properties: [
            new OA\Property(property: 'status', type: 'string', example: 'error'),
            new OA\Property(property: 'error_code', type: 'string', example: 'RESOURCE_NOT_FOUND'),
            new OA\Property(property: 'message', type: 'string', example: 'Запрашиваемая запись не найдена.'),
        ],
        type: 'object',
    ),
)]
class NotFoundResponse {}
