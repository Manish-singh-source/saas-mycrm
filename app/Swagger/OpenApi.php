<?php

namespace App\Swagger;

use OpenApi\Attributes as OA;

#[OA\Info(version: '1.0.0', title: 'Dummy CRM API', description: 'OpenAPI documentation for the Dummy CRM backend')]
#[OA\Server(url: 'http://127.0.0.1:8000', description: 'Local development server')]
#[OA\SecurityScheme(securityScheme: 'bearerAuth', type: 'http', scheme: 'bearer', bearerFormat: 'Sanctum token')]
final class OpenApi
{
}
