<?php

namespace App\OpenApi\Components\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'User',
    required: ['name', 'email', 'created_at'],
    properties: [
        new OA\Property(property: 'name', type: 'string', example: 'Jane Doe'),
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'test@example.com'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
    ],
)]
class UserSchema {}
