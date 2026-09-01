<?php

namespace App\OpenApi\Components\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'LoginRequest',
    required: ['email', 'password'],
    properties: [
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'test@example.com'),
        new OA\Property(property: 'password', type: 'string', format: 'password', example: 'password'),
    ],
)]
#[OA\Schema(
    schema: 'LoginResponse',
    required: ['access_token', 'token_type'],
    properties: [
        new OA\Property(property: 'access_token', type: 'string', example: '1|personal-access-token'),
        new OA\Property(property: 'token_type', type: 'string', example: 'Bearer'),
    ],
)]
#[OA\Schema(
    schema: 'MessageResponse',
    required: ['message'],
    properties: [new OA\Property(property: 'message', type: 'string', example: 'Operation completed successfully.')],
)]
class AuthSchema {}
