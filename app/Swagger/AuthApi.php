<?php

namespace App\Swagger;

use OpenApi\Attributes as OA;

final class AuthApi
{
    #[OA\Get(path: '/api/auth/v1/tenants/plans', summary: 'List public plans', tags: ['Authentication'], responses: [new OA\Response(response: 200, description: 'Public plans fetched')])]
    public function plans(): void {}

    #[OA\Post(path: '/api/auth/v1/tenants/register', summary: 'Register a tenant', tags: ['Authentication'], requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(type: 'object', required: ['organization_name', 'slug', 'company_size', 'default_currency', 'default_timezone', 'owner', 'office'], properties: [
        new OA\Property(property: 'organization_name', type: 'string'),
        new OA\Property(property: 'slug', type: 'string'),
        new OA\Property(property: 'company_size', type: 'string'),
        new OA\Property(property: 'default_currency', type: 'string'),
        new OA\Property(property: 'default_timezone', type: 'string'),
        new OA\Property(property: 'owner', type: 'object', properties: [
            new OA\Property(property: 'first_name', type: 'string'),
            new OA\Property(property: 'last_name', type: 'string'),
            new OA\Property(property: 'display_name', type: 'string'),
            new OA\Property(property: 'email', type: 'string', format: 'email'),
            new OA\Property(property: 'password', type: 'string', format: 'password'),
            new OA\Property(property: 'password_confirmation', type: 'string', format: 'password'),
        ]),
        new OA\Property(property: 'office', type: 'object', properties: [new OA\Property(property: 'office_name', type: 'string')]),
    ])), responses: [new OA\Response(response: 201, description: 'Tenant registered'), new OA\Response(response: 422, description: 'Validation error')])]
    public function register(): void {}

    #[OA\Post(path: '/api/auth/v1/tenants/register/payment/confirm', summary: 'Confirm registration payment', tags: ['Authentication'], requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(type: 'object', required: ['tenant_uuid', 'razorpay_order_id', 'razorpay_payment_id', 'razorpay_signature'], properties: [
        new OA\Property(property: 'tenant_uuid', type: 'string', format: 'uuid'),
        new OA\Property(property: 'razorpay_order_id', type: 'string'),
        new OA\Property(property: 'razorpay_payment_id', type: 'string'),
        new OA\Property(property: 'razorpay_signature', type: 'string'),
    ])), responses: [new OA\Response(response: 200, description: 'Payment verified'), new OA\Response(response: 422, description: 'Validation error')])]
    public function confirmPayment(): void {}

    #[OA\Get(path: '/api/auth/v1/announcements', summary: 'List published announcements', tags: ['Authentication'], responses: [new OA\Response(response: 200, description: 'Published announcements fetched')])]
    public function announcements(): void {}

    #[OA\Get(path: '/api/auth/v1/legal/{document_type}', summary: 'Get a public legal document', tags: ['Authentication'], parameters: [new OA\Parameter(name: 'document_type', in: 'path', required: true, schema: new OA\Schema(type: 'string'))], responses: [new OA\Response(response: 200, description: 'Legal document fetched'), new OA\Response(response: 404, description: 'Document not found')])]
    public function legal(): void {}

    #[OA\Post(path: '/api/auth/v1/accounts/discover', summary: 'Discover accounts by email', tags: ['Authentication'], requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(type: 'object', required: ['email'], properties: [
        new OA\Property(property: 'email', type: 'string', format: 'email'),
        new OA\Property(property: 'device_name', type: 'string'),
    ])), responses: [new OA\Response(response: 200, description: 'Accounts discovered'), new OA\Response(response: 422, description: 'Validation error')])]
    public function discover(): void {}

    #[OA\Post(path: '/api/auth/v1/accounts/login', summary: 'Login to an account', tags: ['Authentication'], requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(type: 'object', required: ['email', 'discovery_token', 'account_ref', 'password'], properties: [
        new OA\Property(property: 'email', type: 'string', format: 'email'),
        new OA\Property(property: 'discovery_token', type: 'string'),
        new OA\Property(property: 'account_ref', type: 'string'),
        new OA\Property(property: 'password', type: 'string', format: 'password'),
        new OA\Property(property: 'remember', type: 'boolean'),
        new OA\Property(property: 'device_name', type: 'string'),
    ])), responses: [new OA\Response(response: 200, description: 'Login successful'), new OA\Response(response: 401, description: 'Invalid credentials')])]
    public function login(): void {}

    #[OA\Post(path: '/api/auth/v1/accounts/login/2fa', summary: 'Verify two-factor login', tags: ['Authentication'], requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(type: 'object', required: ['challenge_token', 'code'], properties: [
        new OA\Property(property: 'challenge_token', type: 'string'),
        new OA\Property(property: 'code', type: 'string', minLength: 6, maxLength: 6),
        new OA\Property(property: 'remember_device', type: 'boolean'),
        new OA\Property(property: 'device_name', type: 'string'),
    ])), responses: [new OA\Response(response: 200, description: 'Two-factor verification successful'), new OA\Response(response: 422, description: 'Invalid code')])]
    public function twoFactor(): void {}

    #[OA\Post(path: '/api/auth/v1/password/forgot', summary: 'Request password reset', tags: ['Authentication'], requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(type: 'object', required: ['email'], properties: [new OA\Property(property: 'email', type: 'string', format: 'email'), new OA\Property(property: 'account_ref', type: 'string'), new OA\Property(property: 'discovery_token', type: 'string')])), responses: [new OA\Response(response: 200, description: 'Reset instructions requested')])]
    public function forgotPassword(): void {}

    #[OA\Post(path: '/api/auth/v1/password/reset', summary: 'Reset password', tags: ['Authentication'], requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(type: 'object', required: ['email', 'token', 'password', 'password_confirmation'], properties: [
        new OA\Property(property: 'email', type: 'string', format: 'email'),
        new OA\Property(property: 'token', type: 'string'),
        new OA\Property(property: 'password', type: 'string', format: 'password'),
        new OA\Property(property: 'password_confirmation', type: 'string', format: 'password'),
    ])), responses: [new OA\Response(response: 200, description: 'Password reset successfully'), new OA\Response(response: 422, description: 'Invalid reset token')])]
    public function resetPassword(): void {}

    #[OA\Get(path: '/api/auth/v1/me', summary: 'Get current session', tags: ['Authentication'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Current session fetched'), new OA\Response(response: 401, description: 'Unauthenticated')])]
    public function me(): void {}

    #[OA\Post(path: '/api/auth/v1/logout', summary: 'Logout current session', tags: ['Authentication'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Logged out successfully'), new OA\Response(response: 401, description: 'Unauthenticated')])]
    public function logout(): void {}

    #[OA\Post(path: '/api/auth/v1/refresh', summary: 'Refresh access token', tags: ['Authentication'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Token refreshed'), new OA\Response(response: 401, description: 'Unauthenticated')])]
    public function refresh(): void {}
}
