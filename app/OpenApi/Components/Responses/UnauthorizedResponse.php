<?php

namespace App\OpenApi\Components\Responses;

use OpenApi\Attributes as OA;

#[OA\Response(
    response: 'UnauthorizedError',
    description: 'Authentication is required.',
    content: new OA\JsonContent(ref: '#/components/schemas/ApiError'),
)]
class UnauthorizedResponse {}
