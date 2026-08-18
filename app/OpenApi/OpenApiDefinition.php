<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\OpenApi(
    info: new OA\Info(
        version: '1.0.0',
        description: 'API for warehouse catalogues and order management.',
        title: 'Order Flow API',
    ),
    servers: [new OA\Server(url: '/api/v1', description: 'API v1')],
)]
#[OA\SecurityScheme(
    securityScheme: 'sanctum',
    type: 'http',
    description: 'Sanctum personal access token.',
    bearerFormat: 'API token',
    scheme: 'bearer',
)]
class OpenApiDefinition {}
