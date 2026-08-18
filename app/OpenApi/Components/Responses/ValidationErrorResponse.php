<?php

namespace App\OpenApi\Components\Responses;

use OpenApi\Attributes as OA;

#[OA\Response(
    response: 'ValidationError',
    description: 'The request could not be validated.',
    content: new OA\JsonContent(ref: '#/components/schemas/ApiError'),
)]
class ValidationErrorResponse {}
