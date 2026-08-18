<?php

namespace App\OpenApi\Components\Responses;

use OpenApi\Attributes as OA;

#[OA\Response(
    response: 'NotFoundError',
    description: 'The requested resource was not found.',
    content: new OA\JsonContent(ref: '#/components/schemas/ApiError'),
)]
class NotFoundResponse {}
