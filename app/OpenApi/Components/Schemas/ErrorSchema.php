<?php

namespace App\OpenApi\Components\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ApiError',
    required: ['status', 'error_code', 'message'],
    properties: [
        new OA\Property(property: 'status', type: 'string', example: 'error'),
        new OA\Property(property: 'error_code', type: 'string'),
        new OA\Property(property: 'message', type: 'string'),
    ],
)]
#[OA\Schema(
    schema: 'ValidationError',
    required: ['status', 'error_code', 'message', 'errors'],
    properties: [
        new OA\Property(property: 'status', type: 'string', example: 'error'),
        new OA\Property(property: 'error_code', type: 'string', example: 'VALIDATION_ERROR'),
        new OA\Property(property: 'message', type: 'string', example: 'Переданные данные не прошли валидацию.'),
        new OA\Property(
            property: 'errors',
            type: 'object',
            additionalProperties: new OA\AdditionalProperties(
                type: 'array',
                items: new OA\Items(type: 'string'),
            ),
        ),
    ],
)]
class ErrorSchema {}
