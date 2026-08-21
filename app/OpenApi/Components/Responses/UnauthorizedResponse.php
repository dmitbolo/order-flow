<?php

namespace App\OpenApi\Components\Responses;

use OpenApi\Attributes as OA;

#[OA\Response(
    response: 'UnauthorizedError',
    description: 'Authentication is required.',
    content: new OA\JsonContent(
        required: ['status', 'error_code', 'message'],
        properties: [
            new OA\Property(property: 'status', type: 'string', example: 'error'),
            new OA\Property(property: 'error_code', type: 'string', example: 'UNAUTHENTICATED'),
            new OA\Property(property: 'message', type: 'string', example: 'Необходима авторизация.'),
        ],
        type: 'object',
    ),
)]
class UnauthorizedResponse {}
