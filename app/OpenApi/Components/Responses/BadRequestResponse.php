<?php

namespace App\OpenApi\Components\Responses;

use OpenApi\Attributes as OA;

#[OA\Response(
    response: 'BadRequestError',
    description: 'An unsupported filter, sort, or include parameter was supplied.',
    content: new OA\JsonContent(ref: '#/components/schemas/ApiError'),
)]
class BadRequestResponse {}
