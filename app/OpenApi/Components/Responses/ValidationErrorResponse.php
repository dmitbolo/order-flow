<?php

namespace App\OpenApi\Components\Responses;

use OpenApi\Attributes as OA;

#[OA\Response(
    response: 'ValidationError',
    description: 'The request could not be validated.',
    content: new OA\MediaType(
        mediaType: 'application/json',
        schema: new OA\Schema(ref: '#/components/schemas/ValidationError'),
        example: [
            'status' => 'error',
            'error_code' => 'VALIDATION_ERROR',
            'message' => 'Переданные данные не прошли валидацию.',
            'errors' => [
                'email' => ['Неверный логин или пароль'],
            ],
        ],
    ),
)]
class ValidationErrorResponse {}
