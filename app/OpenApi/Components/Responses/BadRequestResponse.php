<?php

namespace App\OpenApi\Components\Responses;

use OpenApi\Attributes as OA;

#[OA\Response(
    response: 'BadRequestError',
    description: 'An unsupported filter, sort, or include parameter was supplied.',
    content: new OA\JsonContent(
        required: ['status', 'error_code', 'message'],
        properties: [
            new OA\Property(property: 'status', type: 'string', example: 'error'),
            new OA\Property(property: 'error_code', type: 'string', example: 'INVALID_QUERY_PARAMETER'),
            new OA\Property(property: 'message', type: 'string', example: 'Requested include(s) `user` are not allowed.'),
        ],
        type: 'object',
    ),
)]
class BadRequestResponse {}
