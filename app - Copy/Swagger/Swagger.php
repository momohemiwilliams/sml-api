<?php

namespace App\Swagger;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: "1.0.0",
    title: "SMLARS API",
    description: "API Documentation for SMLARS Application"
)]
#[OA\Server(
    url: L5_SWAGGER_CONST_HOST,
    description: "API Server"
)]
#[OA\Contact(
    email: "aboajah.emmanuel@fmdqgroup.com"
)]
#[OA\License(
    name: "Apache 2.0",
    url: "http://www.apache.org/licenses/LICENSE-2.0.html"
)]
#[OA\SecurityScheme(
    securityScheme: "bearerAuth",
    type: "http",
    scheme: "bearer"
)]
class Swagger
{
}
