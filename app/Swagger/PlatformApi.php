<?php

namespace App\Swagger;

use OpenApi\Attributes as OA;

final class PlatformApi
{
    #[OA\Get(path: '/api/platform/v1/health', summary: 'Platform API health check', tags: ['Platform Health'], responses: [new OA\Response(response: 200, description: 'Platform API is ready')])]
    public function health(): void {}

    #[OA\Post(path: '/api/platform/v1/verify-email/resend', summary: 'Resend platform email verification', tags: ['2FA System'], requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(type: 'object', required: ['email', 'password'], properties: [
        new OA\Property(property: 'email', type: 'string', format: 'email'),
        new OA\Property(property: 'password', type: 'string', format: 'password'),
    ])), responses: [new OA\Response(response: 200, description: 'Verification email queued'), new OA\Response(response: 401, description: 'Invalid credentials')])]
    public function resendVerification(): void {}

    #[OA\Post(path: '/api/platform/v1/2fa/enable', summary: 'Start platform two-factor setup', tags: ['2FA System'], requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(type: 'object', required: ['email', 'password'], properties: [
        new OA\Property(property: 'email', type: 'string', format: 'email'),
        new OA\Property(property: 'password', type: 'string', format: 'password'),
    ])), responses: [new OA\Response(response: 200, description: '2FA setup started'), new OA\Response(response: 401, description: 'Invalid credentials')])]
    public function enableTwoFactor(): void {}

    #[OA\Post(path: '/api/platform/v1/2fa/confirm', summary: 'Confirm platform two-factor setup', tags: ['2FA System'], requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(type: 'object', required: ['setup_token', 'code'], properties: [
        new OA\Property(property: 'setup_token', type: 'string'),
        new OA\Property(property: 'code', type: 'string', minLength: 6, maxLength: 6),
    ])), responses: [new OA\Response(response: 200, description: '2FA enabled'), new OA\Response(response: 422, description: 'Invalid two-factor code')])]
    public function confirmTwoFactor(): void {}

    #[OA\Post(path: '/api/platform/v1/2fa/disable', summary: 'Disable platform two-factor authentication', tags: ['2FA System'], requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(type: 'object', required: ['email', 'password'], properties: [
        new OA\Property(property: 'email', type: 'string', format: 'email'),
        new OA\Property(property: 'password', type: 'string', format: 'password'),
    ])), responses: [new OA\Response(response: 200, description: '2FA disabled'), new OA\Response(response: 401, description: 'Invalid credentials')])]
    public function disableTwoFactor(): void {}

    #[OA\Get(path: '/api/platform/v1/settings/preferences', summary: 'Get platform preferences', tags: ['Platform Settings'], parameters: [
        new OA\Parameter(name: 'email', in: 'query', required: true, schema: new OA\Schema(type: 'string', format: 'email')),
        new OA\Parameter(name: 'password', in: 'query', required: true, schema: new OA\Schema(type: 'string', format: 'password')),
        new OA\Parameter(name: 'group', in: 'query', schema: new OA\Schema(type: 'string', maxLength: 100)),
        new OA\Parameter(name: 'key', in: 'query', schema: new OA\Schema(type: 'string', maxLength: 150)),
    ], responses: [new OA\Response(response: 200, description: 'Platform preferences fetched'), new OA\Response(response: 401, description: 'Invalid credentials')])]
    public function preferences(): void {}

    #[OA\Put(path: '/api/platform/v1/settings/preferences', summary: 'Update platform preferences', tags: ['Platform Settings'], requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(type: 'object', required: ['email', 'password', 'preferences'], properties: [
        new OA\Property(property: 'email', type: 'string', format: 'email'),
        new OA\Property(property: 'password', type: 'string', format: 'password'),
        new OA\Property(property: 'preferences', type: 'object', additionalProperties: new OA\AdditionalProperties(type: 'object')),
    ])), responses: [new OA\Response(response: 200, description: 'Platform preferences updated'), new OA\Response(response: 401, description: 'Invalid credentials')])]
    public function updatePreferences(): void {}
}
