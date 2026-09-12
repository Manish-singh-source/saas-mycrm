<?php

namespace App\Swagger;

use OpenApi\Attributes as OA;

final class ZTenantApi
{
    #[OA\Get(path: '/api/tenant/v1/health', summary: 'GET health', tags: ['Tenant Health'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route1(): void {}

    #[OA\Post(path: '/api/tenant/v1/forgot-password', summary: 'POST forgot-password', tags: ['Tenant Auth & Profile'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route2(): void {}

    #[OA\Post(path: '/api/tenant/v1/reset-password', summary: 'POST reset-password', tags: ['Tenant Auth & Profile'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route3(): void {}

    #[OA\Post(path: '/api/tenant/v1/logout', summary: 'POST logout', tags: ['Tenant Auth & Profile'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route4(): void {}

    #[OA\Post(path: '/api/tenant/v1/refresh', summary: 'POST refresh', tags: ['Tenant Auth & Profile'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route5(): void {}

    #[OA\Get(path: '/api/tenant/v1/me', summary: 'GET me', tags: ['Tenant Auth & Profile'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route6(): void {}

    #[OA\Post(path: '/api/tenant/v1/verify-email/resend', summary: 'POST verify-email/resend', tags: ['Tenant Auth & Profile'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route7(): void {}

    #[OA\Post(path: '/api/tenant/v1/2fa/enable', summary: 'POST 2fa/enable', tags: ['Tenant Auth & Profile'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route8(): void {}

    #[OA\Post(path: '/api/tenant/v1/2fa/confirm', summary: 'POST 2fa/confirm', tags: ['Tenant Auth & Profile'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route9(): void {}

    #[OA\Post(path: '/api/tenant/v1/2fa/disable', summary: 'POST 2fa/disable', tags: ['Tenant Auth & Profile'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route10(): void {}

    #[OA\Get(path: '/api/tenant/v1/profile', summary: 'GET profile', tags: ['Tenant Auth & Profile'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route11(): void {}

    #[OA\Patch(path: '/api/tenant/v1/profile', summary: 'PATCH profile', tags: ['Tenant Auth & Profile'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route12(): void {}

    #[OA\Put(path: '/api/tenant/v1/profile', summary: 'PUT profile', tags: ['Tenant Auth & Profile'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route13(): void {}

    #[OA\Put(path: '/api/tenant/v1/profile/password', summary: 'PUT profile/password', tags: ['Tenant Auth & Profile'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route14(): void {}

    #[OA\Get(path: '/api/tenant/v1/profile/preferences', summary: 'GET profile/preferences', tags: ['Tenant Auth & Profile'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route15(): void {}

    #[OA\Put(path: '/api/tenant/v1/profile/preferences', summary: 'PUT profile/preferences', tags: ['Tenant Auth & Profile'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route16(): void {}

    #[OA\Get(path: '/api/tenant/v1/profile/sessions', summary: 'GET profile/sessions', tags: ['Tenant Auth & Profile'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route17(): void {}

    #[OA\Delete(path: '/api/tenant/v1/profile/sessions/{session_id}', summary: 'DELETE profile/sessions/{session_id}', tags: ['Tenant Auth & Profile'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route18(): void {}

    #[OA\Get(path: '/api/tenant/v1/permissions/grouped', summary: 'GET permissions/grouped', tags: ['Access Control'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route19(): void {}

    #[OA\Get(path: '/api/tenant/v1/permissions', summary: 'GET permissions', tags: ['Access Control'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route20(): void {}

    #[OA\Get(path: '/api/tenant/v1/permissions/{permission_uuid}', summary: 'GET permissions/{permission_uuid}', tags: ['Access Control'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route21(): void {}

    #[OA\Get(path: '/api/tenant/v1/roles', summary: 'GET roles', tags: ['Access Control'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route22(): void {}

    #[OA\Post(path: '/api/tenant/v1/roles', summary: 'POST roles', tags: ['Access Control'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route23(): void {}

    #[OA\Delete(path: '/api/tenant/v1/roles/bulk', summary: 'DELETE roles/bulk', tags: ['Access Control'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route24(): void {}

    #[OA\Get(path: '/api/tenant/v1/roles/{role_uuid}', summary: 'GET roles/{role_uuid}', tags: ['Access Control'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route25(): void {}

    #[OA\Patch(path: '/api/tenant/v1/roles/{role_uuid}', summary: 'PATCH roles/{role_uuid}', tags: ['Access Control'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route26(): void {}

    #[OA\Put(path: '/api/tenant/v1/roles/{role_uuid}', summary: 'PUT roles/{role_uuid}', tags: ['Access Control'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route27(): void {}

    #[OA\Delete(path: '/api/tenant/v1/roles/{role_uuid}', summary: 'DELETE roles/{role_uuid}', tags: ['Access Control'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route28(): void {}

    #[OA\Post(path: '/api/tenant/v1/roles/{role_uuid}/clone', summary: 'POST roles/{role_uuid}/clone', tags: ['Access Control'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route29(): void {}

    #[OA\Post(path: '/api/tenant/v1/roles/{role_uuid}/activate', summary: 'POST roles/{role_uuid}/activate', tags: ['Access Control'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route30(): void {}

    #[OA\Post(path: '/api/tenant/v1/roles/{role_uuid}/deactivate', summary: 'POST roles/{role_uuid}/deactivate', tags: ['Access Control'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route31(): void {}

    #[OA\Get(path: '/api/tenant/v1/roles/{role_uuid}/permissions', summary: 'GET roles/{role_uuid}/permissions', tags: ['Access Control'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route32(): void {}

    #[OA\Put(path: '/api/tenant/v1/roles/{role_uuid}/permissions', summary: 'PUT roles/{role_uuid}/permissions', tags: ['Access Control'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route33(): void {}

    #[OA\Get(path: '/api/tenant/v1/roles/{role_uuid}/users', summary: 'GET roles/{role_uuid}/users', tags: ['Access Control'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route34(): void {}

    #[OA\Post(path: '/api/tenant/v1/roles/{role_uuid}/users', summary: 'POST roles/{role_uuid}/users', tags: ['Access Control'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route35(): void {}

    #[OA\Put(path: '/api/tenant/v1/roles/{role_uuid}/users', summary: 'PUT roles/{role_uuid}/users', tags: ['Access Control'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route36(): void {}

    #[OA\Delete(path: '/api/tenant/v1/roles/{role_uuid}/users/{user_uuid}', summary: 'DELETE roles/{role_uuid}/users/{user_uuid}', tags: ['Access Control'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route37(): void {}

    #[OA\Get(path: '/api/tenant/v1/team-roles', summary: 'List tenant team roles', tags: ['Tenant Access Control'], security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'X-Tenant', in: 'header', required: true, description: 'Tenant UUID or slug.', schema: new OA\Schema(type: 'string')), new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 100, default: 50))], responses: [new OA\Response(response: 200, description: 'Team roles fetched successfully'), new OA\Response(response: 401, description: 'Unauthenticated'), new OA\Response(response: 403, description: 'Insufficient team permission')])]
    public function route38(): void {}

    #[OA\Post(path: '/api/tenant/v1/team-roles', summary: 'Create a tenant team role', tags: ['Tenant Access Control'], security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'X-Tenant', in: 'header', required: true, description: 'Tenant UUID or slug.', schema: new OA\Schema(type: 'string'))], requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(type: 'object', required: ['name', 'code'], properties: [
        new OA\Property(property: 'name', type: 'string', maxLength: 150),
        new OA\Property(property: 'code', type: 'string', maxLength: 80),
        new OA\Property(property: 'description', type: 'string', nullable: true),
        new OA\Property(property: 'permissions', type: 'array', nullable: true, items: new OA\Items(type: 'string')),
        new OA\Property(property: 'sort_order', type: 'integer', nullable: true),
        new OA\Property(property: 'status', type: 'string', enum: ['active', 'inactive'], default: 'active'),
    ])), responses: [new OA\Response(response: 201, description: 'Team role created successfully'), new OA\Response(response: 401, description: 'Unauthenticated'), new OA\Response(response: 403, description: 'Insufficient team permission'), new OA\Response(response: 422, description: 'Validation error'), new OA\Response(response: 409, description: 'Team role conflict')])]
    public function route39(): void {}

    #[OA\Patch(path: '/api/tenant/v1/team-roles/{team_role_uuid}', summary: 'Update a tenant team role', tags: ['Tenant Access Control'], security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'X-Tenant', in: 'header', required: true, description: 'Tenant UUID or slug.', schema: new OA\Schema(type: 'string')), new OA\Parameter(name: 'team_role_uuid', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))], requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(type: 'object', properties: [
        new OA\Property(property: 'name', type: 'string', maxLength: 150),
        new OA\Property(property: 'code', type: 'string', maxLength: 80),
        new OA\Property(property: 'description', type: 'string', nullable: true),
        new OA\Property(property: 'permissions', type: 'array', nullable: true, items: new OA\Items(type: 'string')),
        new OA\Property(property: 'sort_order', type: 'integer', nullable: true),
        new OA\Property(property: 'status', type: 'string', enum: ['active', 'inactive']),
    ])), responses: [new OA\Response(response: 200, description: 'Team role updated successfully'), new OA\Response(response: 401, description: 'Unauthenticated'), new OA\Response(response: 403, description: 'Insufficient permission or system role restriction'), new OA\Response(response: 404, description: 'Team role not found'), new OA\Response(response: 409, description: 'Team role conflict'), new OA\Response(response: 422, description: 'Validation error')])]
    public function route40(): void {}

    #[OA\Put(path: '/api/tenant/v1/team-roles/{team_role_uuid}', summary: 'Update a tenant team role', tags: ['Tenant Access Control'], security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'X-Tenant', in: 'header', required: true, description: 'Tenant UUID or slug.', schema: new OA\Schema(type: 'string')), new OA\Parameter(name: 'team_role_uuid', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))], requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(type: 'object', properties: [
        new OA\Property(property: 'name', type: 'string', maxLength: 150),
        new OA\Property(property: 'code', type: 'string', maxLength: 80),
        new OA\Property(property: 'description', type: 'string', nullable: true),
        new OA\Property(property: 'permissions', type: 'array', nullable: true, items: new OA\Items(type: 'string')),
        new OA\Property(property: 'sort_order', type: 'integer', nullable: true),
        new OA\Property(property: 'status', type: 'string', enum: ['active', 'inactive']),
    ])), responses: [new OA\Response(response: 200, description: 'Team role updated successfully'), new OA\Response(response: 401, description: 'Unauthenticated'), new OA\Response(response: 403, description: 'Insufficient permission or system role restriction'), new OA\Response(response: 404, description: 'Team role not found'), new OA\Response(response: 409, description: 'Team role conflict'), new OA\Response(response: 422, description: 'Validation error')])]
    public function route41(): void {}

    #[OA\Delete(path: '/api/tenant/v1/team-roles/{team_role_uuid}', summary: 'Delete a tenant team role', tags: ['Tenant Access Control'], security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'X-Tenant', in: 'header', required: true, description: 'Tenant UUID or slug.', schema: new OA\Schema(type: 'string')), new OA\Parameter(name: 'team_role_uuid', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))], responses: [new OA\Response(response: 200, description: 'Team role deleted successfully'), new OA\Response(response: 401, description: 'Unauthenticated'), new OA\Response(response: 403, description: 'Insufficient permission or system role restriction'), new OA\Response(response: 404, description: 'Team role not found'), new OA\Response(response: 409, description: 'Team role is assigned and cannot be deleted')])]
    public function route42(): void {}

    #[OA\Post(path: '/api/tenant/v1/teams/export', summary: 'POST teams/export', tags: ['Access Control'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route43(): void {}

    #[OA\Get(path: '/api/tenant/v1/teams', summary: 'GET teams', tags: ['Access Control'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route44(): void {}

    #[OA\Post(path: '/api/tenant/v1/teams', summary: 'POST teams', tags: ['Access Control'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route45(): void {}

    #[OA\Delete(path: '/api/tenant/v1/teams/bulk', summary: 'DELETE teams/bulk', tags: ['Access Control'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route46(): void {}

    #[OA\Get(path: '/api/tenant/v1/teams/{team_uuid}', summary: 'GET teams/{team_uuid}', tags: ['Access Control'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route47(): void {}

    #[OA\Patch(path: '/api/tenant/v1/teams/{team_uuid}', summary: 'PATCH teams/{team_uuid}', tags: ['Access Control'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route48(): void {}

    #[OA\Put(path: '/api/tenant/v1/teams/{team_uuid}', summary: 'PUT teams/{team_uuid}', tags: ['Access Control'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route49(): void {}

    #[OA\Delete(path: '/api/tenant/v1/teams/{team_uuid}', summary: 'DELETE teams/{team_uuid}', tags: ['Access Control'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route50(): void {}

    #[OA\Get(path: '/api/tenant/v1/teams/{team_uuid}/permissions', summary: 'GET teams/{team_uuid}/permissions', tags: ['Access Control'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route51(): void {}

    #[OA\Put(path: '/api/tenant/v1/teams/{team_uuid}/permissions', summary: 'PUT teams/{team_uuid}/permissions', tags: ['Access Control'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route52(): void {}

    #[OA\Get(path: '/api/tenant/v1/teams/{team_uuid}/members', summary: 'GET teams/{team_uuid}/members', tags: ['Access Control'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route53(): void {}

    #[OA\Post(path: '/api/tenant/v1/teams/{team_uuid}/members', summary: 'POST teams/{team_uuid}/members', tags: ['Access Control'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route54(): void {}

    #[OA\Patch(path: '/api/tenant/v1/teams/{team_uuid}/members/{member_uuid}', summary: 'PATCH teams/{team_uuid}/members/{member_uuid}', tags: ['Access Control'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route55(): void {}

    #[OA\Delete(path: '/api/tenant/v1/teams/{team_uuid}/members/{member_uuid}', summary: 'DELETE teams/{team_uuid}/members/{member_uuid}', tags: ['Access Control'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route56(): void {}

    #[OA\Get(path: '/api/tenant/v1/teams/{team_uuid}/settings', summary: 'GET teams/{team_uuid}/settings', tags: ['Access Control'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route57(): void {}

    #[OA\Put(path: '/api/tenant/v1/teams/{team_uuid}/settings', summary: 'PUT teams/{team_uuid}/settings', tags: ['Access Control'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route58(): void {}

    #[OA\Get(path: '/api/tenant/v1/teams/{team_uuid}/assignments', summary: 'GET teams/{team_uuid}/assignments', tags: ['Access Control'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route59(): void {}

    #[OA\Post(path: '/api/tenant/v1/teams/{team_uuid}/assignments', summary: 'POST teams/{team_uuid}/assignments', tags: ['Access Control'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route60(): void {}

    #[OA\Delete(path: '/api/tenant/v1/teams/{team_uuid}/assignments/{assignment_id}', summary: 'DELETE teams/{team_uuid}/assignments/{assignment_id}', tags: ['Access Control'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route61(): void {}

    #[OA\Get(path: '/api/tenant/v1/teams/{team_uuid}/projects', summary: 'GET teams/{team_uuid}/projects', tags: ['Access Control'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route62(): void {}

    #[OA\Get(path: '/api/tenant/v1/teams/{team_uuid}/tasks', summary: 'GET teams/{team_uuid}/tasks', tags: ['Access Control'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route63(): void {}

    #[OA\Get(path: '/api/tenant/v1/teams/{team_uuid}/activity', summary: 'GET teams/{team_uuid}/activity', tags: ['Access Control'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route64(): void {}

    #[OA\Get(path: '/api/tenant/v1/users', summary: 'GET users', tags: ['Tenant API'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route65(): void {}

    #[OA\Post(path: '/api/tenant/v1/users/invite', summary: 'POST users/invite', tags: ['Tenant API'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route66(): void {}

    #[OA\Get(path: '/api/tenant/v1/users/{user_uuid}', summary: 'GET users/{user_uuid}', tags: ['Tenant API'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route67(): void {}

    #[OA\Patch(path: '/api/tenant/v1/users/{user_uuid}', summary: 'PATCH users/{user_uuid}', tags: ['Tenant API'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route68(): void {}

    #[OA\Put(path: '/api/tenant/v1/users/{user_uuid}', summary: 'PUT users/{user_uuid}', tags: ['Tenant API'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route69(): void {}

    #[OA\Put(path: '/api/tenant/v1/users/{user_uuid}/roles', summary: 'PUT users/{user_uuid}/roles', tags: ['Tenant API'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route70(): void {}

    #[OA\Post(path: '/api/tenant/v1/users/{user_uuid}/suspend', summary: 'POST users/{user_uuid}/suspend', tags: ['Tenant API'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route71(): void {}

    #[OA\Post(path: '/api/tenant/v1/users/{user_uuid}/activate', summary: 'POST users/{user_uuid}/activate', tags: ['Tenant API'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route72(): void {}

    #[OA\Post(path: '/api/tenant/v1/users/{user_uuid}/reset-password', summary: 'POST users/{user_uuid}/reset-password', tags: ['Tenant API'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route73(): void {}

    #[OA\Post(path: '/api/tenant/v1/users/{user_uuid}/force-logout', summary: 'POST users/{user_uuid}/force-logout', tags: ['Tenant API'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route74(): void {}

    #[OA\Post(path: '/api/tenant/v1/users/{user_uuid}/require-2fa', summary: 'POST users/{user_uuid}/require-2fa', tags: ['Tenant API'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route75(): void {}

    #[OA\Get(path: '/api/tenant/v1/staff/dashboard', summary: 'GET staff/dashboard', tags: ['Tenant API'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route76(): void {}

    #[OA\Get(path: '/api/tenant/v1/staff/grid', summary: 'GET staff/grid', tags: ['Tenant API'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route77(): void {}

    #[OA\Post(path: '/api/tenant/v1/staff/import', summary: 'POST staff/import', tags: ['Tenant API'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route78(): void {}

    #[OA\Post(path: '/api/tenant/v1/staff/export', summary: 'POST staff/export', tags: ['Tenant API'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route79(): void {}

    #[OA\Delete(path: '/api/tenant/v1/staff/bulk', summary: 'DELETE staff/bulk', tags: ['Tenant API'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route80(): void {}

    #[OA\Get(path: '/api/tenant/v1/staff', summary: 'GET staff', tags: ['Tenant API'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route81(): void {}

    #[OA\Post(path: '/api/tenant/v1/staff', summary: 'POST staff', tags: ['Tenant API'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route82(): void {}

    #[OA\Get(path: '/api/tenant/v1/staff/{staff_uuid}', summary: 'GET staff/{staff_uuid}', tags: ['Tenant API'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route83(): void {}

    #[OA\Patch(path: '/api/tenant/v1/staff/{staff_uuid}', summary: 'PATCH staff/{staff_uuid}', tags: ['Tenant API'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route84(): void {}

    #[OA\Put(path: '/api/tenant/v1/staff/{staff_uuid}', summary: 'PUT staff/{staff_uuid}', tags: ['Tenant API'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route85(): void {}

    #[OA\Delete(path: '/api/tenant/v1/staff/{staff_uuid}', summary: 'DELETE staff/{staff_uuid}', tags: ['Tenant API'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route86(): void {}

    #[OA\Post(path: '/api/tenant/v1/staff/{staff_uuid}/restore', summary: 'POST staff/{staff_uuid}/restore', tags: ['Tenant API'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route87(): void {}

    #[OA\Get(path: '/api/tenant/v1/staff/{staff_uuid}/activity', summary: 'GET staff/{staff_uuid}/activity', tags: ['Tenant API'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route88(): void {}

    #[OA\Get(path: '/api/tenant/v1/staff/{staff_uuid}/roles', summary: 'GET staff/{staff_uuid}/roles', tags: ['Tenant API'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route89(): void {}

    #[OA\Put(path: '/api/tenant/v1/staff/{staff_uuid}/roles', summary: 'PUT staff/{staff_uuid}/roles', tags: ['Tenant API'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route90(): void {}

    #[OA\Get(path: '/api/tenant/v1/staff/{staff_uuid}/teams', summary: 'GET staff/{staff_uuid}/teams', tags: ['Tenant API'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route91(): void {}

    #[OA\Put(path: '/api/tenant/v1/staff/{staff_uuid}/teams', summary: 'PUT staff/{staff_uuid}/teams', tags: ['Tenant API'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route92(): void {}

    #[OA\Get(path: '/api/tenant/v1/staff/{staff_uuid}/projects', summary: 'GET staff/{staff_uuid}/projects', tags: ['Tenant API'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route93(): void {}

    #[OA\Put(path: '/api/tenant/v1/staff/{staff_uuid}/projects', summary: 'PUT staff/{staff_uuid}/projects', tags: ['Tenant API'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route94(): void {}

    #[OA\Get(path: '/api/tenant/v1/staff/{staff_uuid}/tasks', summary: 'GET staff/{staff_uuid}/tasks', tags: ['Tenant API'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route95(): void {}

    #[OA\Put(path: '/api/tenant/v1/staff/{staff_uuid}/tasks', summary: 'PUT staff/{staff_uuid}/tasks', tags: ['Tenant API'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route96(): void {}

    #[OA\Get(path: '/api/tenant/v1/staff/{staff_uuid}/bank-accounts', summary: 'GET staff/{staff_uuid}/bank-accounts', tags: ['Tenant API'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route97(): void {}

    #[OA\Post(path: '/api/tenant/v1/staff/{staff_uuid}/bank-accounts', summary: 'POST staff/{staff_uuid}/bank-accounts', tags: ['Tenant API'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route98(): void {}

    #[OA\Patch(path: '/api/tenant/v1/staff/{staff_uuid}/bank-accounts/{id}', summary: 'PATCH staff/{staff_uuid}/bank-accounts/{id}', tags: ['Tenant API'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route99(): void {}

    #[OA\Put(path: '/api/tenant/v1/staff/{staff_uuid}/bank-accounts/{id}', summary: 'PUT staff/{staff_uuid}/bank-accounts/{id}', tags: ['Tenant API'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route100(): void {}

    #[OA\Delete(path: '/api/tenant/v1/staff/{staff_uuid}/bank-accounts/{id}', summary: 'DELETE staff/{staff_uuid}/bank-accounts/{id}', tags: ['Tenant API'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route101(): void {}

    #[OA\Get(path: '/api/tenant/v1/staff/{staff_uuid}/salary-structures', summary: 'GET staff/{staff_uuid}/salary-structures', tags: ['Tenant API'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route102(): void {}

    #[OA\Post(path: '/api/tenant/v1/staff/{staff_uuid}/salary-structures', summary: 'POST staff/{staff_uuid}/salary-structures', tags: ['Tenant API'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route103(): void {}

    #[OA\Patch(path: '/api/tenant/v1/staff/{staff_uuid}/salary-structures/{id}', summary: 'PATCH staff/{staff_uuid}/salary-structures/{id}', tags: ['Tenant API'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route104(): void {}

    #[OA\Put(path: '/api/tenant/v1/staff/{staff_uuid}/salary-structures/{id}', summary: 'PUT staff/{staff_uuid}/salary-structures/{id}', tags: ['Tenant API'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route105(): void {}

    #[OA\Get(path: '/api/tenant/v1/staff/{staff_uuid}/tabs/{tab}', summary: 'GET staff/{staff_uuid}/tabs/{tab}', tags: ['Tenant API'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route106(): void {}

    #[OA\Get(path: '/api/tenant/v1/todo-lists/dashboard', summary: 'GET todo-lists/dashboard', tags: ['Tenant API'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route107(): void {}

    #[OA\Get(path: '/api/tenant/v1/todo-lists/kanban', summary: 'GET todo-lists/kanban', tags: ['Tenant API'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route108(): void {}

    #[OA\Get(path: '/api/tenant/v1/todo-lists/calendar', summary: 'GET todo-lists/calendar', tags: ['Tenant API'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route109(): void {}

    #[OA\Post(path: '/api/tenant/v1/todo-lists/export', summary: 'POST todo-lists/export', tags: ['Tenant API'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route110(): void {}

    #[OA\Get(path: '/api/tenant/v1/todo-lists', summary: 'GET todo-lists', tags: ['Tenant API'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route111(): void {}

    #[OA\Post(path: '/api/tenant/v1/todo-lists', summary: 'POST todo-lists', tags: ['Tenant API'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route112(): void {}

    #[OA\Get(path: '/api/tenant/v1/todo-lists/{todo_list_uuid}', summary: 'GET todo-lists/{todo_list_uuid}', tags: ['Tenant API'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route113(): void {}

    #[OA\Patch(path: '/api/tenant/v1/todo-lists/{todo_list_uuid}', summary: 'PATCH todo-lists/{todo_list_uuid}', tags: ['Tenant API'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route114(): void {}

    #[OA\Put(path: '/api/tenant/v1/todo-lists/{todo_list_uuid}', summary: 'PUT todo-lists/{todo_list_uuid}', tags: ['Tenant API'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route115(): void {}

    #[OA\Delete(path: '/api/tenant/v1/todo-lists/{todo_list_uuid}', summary: 'DELETE todo-lists/{todo_list_uuid}', tags: ['Tenant API'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route116(): void {}

    #[OA\Get(path: '/api/tenant/v1/todo-lists/{todo_list_uuid}/tasks', summary: 'GET todo-lists/{todo_list_uuid}/tasks', tags: ['Tenant API'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route117(): void {}

    #[OA\Get(path: '/api/tenant/v1/todo-lists/{todo_list_uuid}/reminders', summary: 'GET todo-lists/{todo_list_uuid}/reminders', tags: ['Tenant API'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route118(): void {}

    #[OA\Post(path: '/api/tenant/v1/todo-lists/{todo_list_uuid}/reminders', summary: 'POST todo-lists/{todo_list_uuid}/reminders', tags: ['Tenant API'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route119(): void {}

    #[OA\Patch(path: '/api/tenant/v1/todo-lists/{todo_list_uuid}/reminders/{reminder_uuid}', summary: 'PATCH todo-lists/{todo_list_uuid}/reminders/{reminder_uuid}', tags: ['Tenant API'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route120(): void {}

    #[OA\Put(path: '/api/tenant/v1/todo-lists/{todo_list_uuid}/reminders/{reminder_uuid}', summary: 'PUT todo-lists/{todo_list_uuid}/reminders/{reminder_uuid}', tags: ['Tenant API'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route121(): void {}

    #[OA\Delete(path: '/api/tenant/v1/todo-lists/{todo_list_uuid}/reminders/{reminder_uuid}', summary: 'DELETE todo-lists/{todo_list_uuid}/reminders/{reminder_uuid}', tags: ['Tenant API'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route122(): void {}

    #[OA\Post(path: '/api/tenant/v1/clients/import', summary: 'POST clients/import', tags: ['CRM'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route123(): void {}

    #[OA\Post(path: '/api/tenant/v1/clients/export', summary: 'POST clients/export', tags: ['CRM'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route124(): void {}

    #[OA\Post(path: '/api/tenant/v1/clients/merge', summary: 'POST clients/merge', tags: ['CRM'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route125(): void {}

    #[OA\Get(path: '/api/tenant/v1/clients', summary: 'GET clients', tags: ['CRM'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route126(): void {}

    #[OA\Post(path: '/api/tenant/v1/clients', summary: 'POST clients', tags: ['CRM'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route127(): void {}

    #[OA\Get(path: '/api/tenant/v1/clients/{client_uuid}', summary: 'GET clients/{client_uuid}', tags: ['CRM'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route128(): void {}

    #[OA\Patch(path: '/api/tenant/v1/clients/{client_uuid}', summary: 'PATCH clients/{client_uuid}', tags: ['CRM'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route129(): void {}

    #[OA\Put(path: '/api/tenant/v1/clients/{client_uuid}', summary: 'PUT clients/{client_uuid}', tags: ['CRM'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route130(): void {}

    #[OA\Delete(path: '/api/tenant/v1/clients/{client_uuid}', summary: 'DELETE clients/{client_uuid}', tags: ['CRM'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route131(): void {}

    #[OA\Post(path: '/api/tenant/v1/clients/{client_uuid}/restore', summary: 'POST clients/{client_uuid}/restore', tags: ['CRM'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route132(): void {}

    #[OA\Get(path: '/api/tenant/v1/clients/{client_uuid}/contacts', summary: 'GET clients/{client_uuid}/contacts', tags: ['CRM'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route133(): void {}

    #[OA\Post(path: '/api/tenant/v1/clients/{client_uuid}/contacts', summary: 'POST clients/{client_uuid}/contacts', tags: ['CRM'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route134(): void {}

    #[OA\Patch(path: '/api/tenant/v1/clients/{client_uuid}/contacts/{contact_uuid}', summary: 'PATCH clients/{client_uuid}/contacts/{contact_uuid}', tags: ['CRM'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route135(): void {}

    #[OA\Put(path: '/api/tenant/v1/clients/{client_uuid}/contacts/{contact_uuid}', summary: 'PUT clients/{client_uuid}/contacts/{contact_uuid}', tags: ['CRM'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route136(): void {}

    #[OA\Delete(path: '/api/tenant/v1/clients/{client_uuid}/contacts/{contact_uuid}', summary: 'DELETE clients/{client_uuid}/contacts/{contact_uuid}', tags: ['CRM'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route137(): void {}

    #[OA\Get(path: '/api/tenant/v1/clients/{client_uuid}/addresses', summary: 'GET clients/{client_uuid}/addresses', tags: ['CRM'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route138(): void {}

    #[OA\Post(path: '/api/tenant/v1/clients/{client_uuid}/addresses', summary: 'POST clients/{client_uuid}/addresses', tags: ['CRM'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route139(): void {}

    #[OA\Patch(path: '/api/tenant/v1/clients/{client_uuid}/addresses/{address_id}', summary: 'PATCH clients/{client_uuid}/addresses/{address_id}', tags: ['CRM'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route140(): void {}

    #[OA\Put(path: '/api/tenant/v1/clients/{client_uuid}/addresses/{address_id}', summary: 'PUT clients/{client_uuid}/addresses/{address_id}', tags: ['CRM'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route141(): void {}

    #[OA\Delete(path: '/api/tenant/v1/clients/{client_uuid}/addresses/{address_id}', summary: 'DELETE clients/{client_uuid}/addresses/{address_id}', tags: ['CRM'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route142(): void {}

    #[OA\Get(path: '/api/tenant/v1/clients/{client_uuid}/activity', summary: 'GET clients/{client_uuid}/activity', tags: ['CRM'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route143(): void {}

    #[OA\Get(path: '/api/tenant/v1/clients/{client_uuid}/{resource}', summary: 'GET clients/{client_uuid}/{resource}', tags: ['CRM'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route144(): void {}

    #[OA\Post(path: '/api/tenant/v1/vendors/import', summary: 'POST vendors/import', tags: ['CRM'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route145(): void {}

    #[OA\Post(path: '/api/tenant/v1/vendors/export', summary: 'POST vendors/export', tags: ['CRM'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route146(): void {}

    #[OA\Get(path: '/api/tenant/v1/vendors', summary: 'GET vendors', tags: ['CRM'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route147(): void {}

    #[OA\Post(path: '/api/tenant/v1/vendors', summary: 'POST vendors', tags: ['CRM'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route148(): void {}

    #[OA\Get(path: '/api/tenant/v1/vendors/{vendor_uuid}', summary: 'GET vendors/{vendor_uuid}', tags: ['CRM'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route149(): void {}

    #[OA\Patch(path: '/api/tenant/v1/vendors/{vendor_uuid}', summary: 'PATCH vendors/{vendor_uuid}', tags: ['CRM'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route150(): void {}

    #[OA\Put(path: '/api/tenant/v1/vendors/{vendor_uuid}', summary: 'PUT vendors/{vendor_uuid}', tags: ['CRM'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route151(): void {}

    #[OA\Delete(path: '/api/tenant/v1/vendors/{vendor_uuid}', summary: 'DELETE vendors/{vendor_uuid}', tags: ['CRM'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route152(): void {}

    #[OA\Get(path: '/api/tenant/v1/vendors/{vendor_uuid}/contacts', summary: 'GET vendors/{vendor_uuid}/contacts', tags: ['CRM'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route153(): void {}

    #[OA\Post(path: '/api/tenant/v1/vendors/{vendor_uuid}/contacts', summary: 'POST vendors/{vendor_uuid}/contacts', tags: ['CRM'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route154(): void {}

    #[OA\Patch(path: '/api/tenant/v1/vendors/{vendor_uuid}/contacts/{contact_uuid}', summary: 'PATCH vendors/{vendor_uuid}/contacts/{contact_uuid}', tags: ['CRM'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route155(): void {}

    #[OA\Put(path: '/api/tenant/v1/vendors/{vendor_uuid}/contacts/{contact_uuid}', summary: 'PUT vendors/{vendor_uuid}/contacts/{contact_uuid}', tags: ['CRM'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route156(): void {}

    #[OA\Delete(path: '/api/tenant/v1/vendors/{vendor_uuid}/contacts/{contact_uuid}', summary: 'DELETE vendors/{vendor_uuid}/contacts/{contact_uuid}', tags: ['CRM'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route157(): void {}

    #[OA\Get(path: '/api/tenant/v1/vendors/{vendor_uuid}/addresses', summary: 'GET vendors/{vendor_uuid}/addresses', tags: ['CRM'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route158(): void {}

    #[OA\Post(path: '/api/tenant/v1/vendors/{vendor_uuid}/addresses', summary: 'POST vendors/{vendor_uuid}/addresses', tags: ['CRM'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route159(): void {}

    #[OA\Patch(path: '/api/tenant/v1/vendors/{vendor_uuid}/addresses/{address_id}', summary: 'PATCH vendors/{vendor_uuid}/addresses/{address_id}', tags: ['CRM'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route160(): void {}

    #[OA\Put(path: '/api/tenant/v1/vendors/{vendor_uuid}/addresses/{address_id}', summary: 'PUT vendors/{vendor_uuid}/addresses/{address_id}', tags: ['CRM'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route161(): void {}

    #[OA\Delete(path: '/api/tenant/v1/vendors/{vendor_uuid}/addresses/{address_id}', summary: 'DELETE vendors/{vendor_uuid}/addresses/{address_id}', tags: ['CRM'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route162(): void {}

    #[OA\Get(path: '/api/tenant/v1/vendors/{vendor_uuid}/bank-accounts', summary: 'GET vendors/{vendor_uuid}/bank-accounts', tags: ['CRM'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route163(): void {}

    #[OA\Post(path: '/api/tenant/v1/vendors/{vendor_uuid}/bank-accounts', summary: 'POST vendors/{vendor_uuid}/bank-accounts', tags: ['CRM'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route164(): void {}

    #[OA\Patch(path: '/api/tenant/v1/vendors/{vendor_uuid}/bank-accounts/{account_id}', summary: 'PATCH vendors/{vendor_uuid}/bank-accounts/{account_id}', tags: ['CRM'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route165(): void {}

    #[OA\Put(path: '/api/tenant/v1/vendors/{vendor_uuid}/bank-accounts/{account_id}', summary: 'PUT vendors/{vendor_uuid}/bank-accounts/{account_id}', tags: ['CRM'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route166(): void {}

    #[OA\Delete(path: '/api/tenant/v1/vendors/{vendor_uuid}/bank-accounts/{account_id}', summary: 'DELETE vendors/{vendor_uuid}/bank-accounts/{account_id}', tags: ['CRM'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route167(): void {}

    #[OA\Get(path: '/api/tenant/v1/vendors/{vendor_uuid}/activity', summary: 'GET vendors/{vendor_uuid}/activity', tags: ['CRM'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route168(): void {}

    #[OA\Get(path: '/api/tenant/v1/vendors/{vendor_uuid}/{resource}', summary: 'GET vendors/{vendor_uuid}/{resource}', tags: ['CRM'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route169(): void {}

    #[OA\Get(path: '/api/tenant/v1/renewals', summary: 'GET renewals', tags: ['Projects & Work'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route170(): void {}

    #[OA\Post(path: '/api/tenant/v1/renewals', summary: 'POST renewals', tags: ['Projects & Work'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route171(): void {}

    #[OA\Get(path: '/api/tenant/v1/renewals/dashboard', summary: 'GET renewals/dashboard', tags: ['Projects & Work'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route172(): void {}

    #[OA\Get(path: '/api/tenant/v1/renewals/calendar', summary: 'GET renewals/calendar', tags: ['Projects & Work'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route173(): void {}

    #[OA\Get(path: '/api/tenant/v1/client-renewals', summary: 'GET client-renewals', tags: ['Tenant API'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route174(): void {}

    #[OA\Get(path: '/api/tenant/v1/vendor-renewals', summary: 'GET vendor-renewals', tags: ['Tenant API'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route175(): void {}

    #[OA\Post(path: '/api/tenant/v1/renewals/export', summary: 'POST renewals/export', tags: ['Projects & Work'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route176(): void {}

    #[OA\Get(path: '/api/tenant/v1/renewals/{renewal_uuid}', summary: 'GET renewals/{renewal_uuid}', tags: ['Projects & Work'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route177(): void {}

    #[OA\Patch(path: '/api/tenant/v1/renewals/{renewal_uuid}', summary: 'PATCH renewals/{renewal_uuid}', tags: ['Projects & Work'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route178(): void {}

    #[OA\Put(path: '/api/tenant/v1/renewals/{renewal_uuid}', summary: 'PUT renewals/{renewal_uuid}', tags: ['Projects & Work'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route179(): void {}

    #[OA\Delete(path: '/api/tenant/v1/renewals/{renewal_uuid}', summary: 'DELETE renewals/{renewal_uuid}', tags: ['Projects & Work'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route180(): void {}

    #[OA\Post(path: '/api/tenant/v1/renewals/{renewal_uuid}/renew', summary: 'POST renewals/{renewal_uuid}/renew', tags: ['Projects & Work'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route181(): void {}

    #[OA\Post(path: '/api/tenant/v1/renewals/{renewal_uuid}/cancel', summary: 'POST renewals/{renewal_uuid}/cancel', tags: ['Projects & Work'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route182(): void {}

    #[OA\Post(path: '/api/tenant/v1/renewals/{renewal_uuid}/send-reminder', summary: 'POST renewals/{renewal_uuid}/send-reminder', tags: ['Projects & Work'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route183(): void {}

    #[OA\Get(path: '/api/tenant/v1/renewals/{renewal_uuid}/history', summary: 'GET renewals/{renewal_uuid}/history', tags: ['Projects & Work'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route184(): void {}

    #[OA\Get(path: '/api/tenant/v1/renewals/{renewal_uuid}/{kind}', summary: 'GET renewals/{renewal_uuid}/{kind}', tags: ['Projects & Work'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route185(): void {}

    #[OA\Post(path: '/api/tenant/v1/renewals/{renewal_uuid}/{kind}', summary: 'POST renewals/{renewal_uuid}/{kind}', tags: ['Projects & Work'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route186(): void {}

    #[OA\Patch(path: '/api/tenant/v1/renewals/{renewal_uuid}/{kind}/{id}', summary: 'PATCH renewals/{renewal_uuid}/{kind}/{id}', tags: ['Projects & Work'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route187(): void {}

    #[OA\Put(path: '/api/tenant/v1/renewals/{renewal_uuid}/{kind}/{id}', summary: 'PUT renewals/{renewal_uuid}/{kind}/{id}', tags: ['Projects & Work'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route188(): void {}

    #[OA\Delete(path: '/api/tenant/v1/renewals/{renewal_uuid}/{kind}/{id}', summary: 'DELETE renewals/{renewal_uuid}/{kind}/{id}', tags: ['Projects & Work'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route189(): void {}

    #[OA\Put(path: '/api/tenant/v1/renewals/{renewal_uuid}/amc', summary: 'PUT renewals/{renewal_uuid}/amc', tags: ['Projects & Work'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route190(): void {}

    #[OA\Patch(path: '/api/tenant/v1/renewals/{renewal_uuid}/amc', summary: 'PATCH renewals/{renewal_uuid}/amc', tags: ['Projects & Work'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route191(): void {}

    #[OA\Get(path: '/api/tenant/v1/renewals/{renewal_uuid}/amc', summary: 'GET renewals/{renewal_uuid}/amc', tags: ['Projects & Work'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route192(): void {}

    #[OA\Post(path: '/api/tenant/v1/renewals/{renewal_uuid}/amc', summary: 'POST renewals/{renewal_uuid}/amc', tags: ['Projects & Work'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route193(): void {}

    #[OA\Get(path: '/api/tenant/v1/renewals/{renewal_uuid}/amc/visits', summary: 'GET renewals/{renewal_uuid}/amc/visits', tags: ['Projects & Work'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route194(): void {}

    #[OA\Patch(path: '/api/tenant/v1/renewals/{renewal_uuid}/amc/visits/{id}', summary: 'PATCH renewals/{renewal_uuid}/amc/visits/{id}', tags: ['Projects & Work'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route195(): void {}

    #[OA\Put(path: '/api/tenant/v1/renewals/{renewal_uuid}/amc/visits/{id}', summary: 'PUT renewals/{renewal_uuid}/amc/visits/{id}', tags: ['Projects & Work'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route196(): void {}

    #[OA\Get(path: '/api/tenant/v1/projects/dashboard', summary: 'GET projects/dashboard', tags: ['Projects & Work'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route197(): void {}

    #[OA\Get(path: '/api/tenant/v1/projects/kanban', summary: 'GET projects/kanban', tags: ['Projects & Work'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route198(): void {}

    #[OA\Get(path: '/api/tenant/v1/projects/gantt', summary: 'GET projects/gantt', tags: ['Projects & Work'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route199(): void {}

    #[OA\Get(path: '/api/tenant/v1/projects/calendar', summary: 'GET projects/calendar', tags: ['Projects & Work'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route200(): void {}

    #[OA\Post(path: '/api/tenant/v1/projects/export', summary: 'POST projects/export', tags: ['Projects & Work'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route201(): void {}

    #[OA\Get(path: '/api/tenant/v1/projects', summary: 'GET projects', tags: ['Projects & Work'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route202(): void {}

    #[OA\Post(path: '/api/tenant/v1/projects', summary: 'POST projects', tags: ['Projects & Work'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route203(): void {}

    #[OA\Get(path: '/api/tenant/v1/projects/{project_uuid}', summary: 'GET projects/{project_uuid}', tags: ['Projects & Work'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route204(): void {}

    #[OA\Patch(path: '/api/tenant/v1/projects/{project_uuid}', summary: 'PATCH projects/{project_uuid}', tags: ['Projects & Work'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route205(): void {}

    #[OA\Put(path: '/api/tenant/v1/projects/{project_uuid}', summary: 'PUT projects/{project_uuid}', tags: ['Projects & Work'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route206(): void {}

    #[OA\Delete(path: '/api/tenant/v1/projects/{project_uuid}', summary: 'DELETE projects/{project_uuid}', tags: ['Projects & Work'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route207(): void {}

    #[OA\Post(path: '/api/tenant/v1/projects/{project_uuid}/archive', summary: 'POST projects/{project_uuid}/archive', tags: ['Projects & Work'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route208(): void {}

    #[OA\Get(path: '/api/tenant/v1/projects/{project_uuid}/tasks', summary: 'GET projects/{project_uuid}/tasks', tags: ['Projects & Work'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route209(): void {}

    #[OA\Post(path: '/api/tenant/v1/projects/{project_uuid}/tasks', summary: 'POST projects/{project_uuid}/tasks', tags: ['Projects & Work'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route210(): void {}

    #[OA\Get(path: '/api/tenant/v1/projects/{project_uuid}/{resource}', summary: 'GET projects/{project_uuid}/{resource}', tags: ['Projects & Work'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route211(): void {}

    #[OA\Post(path: '/api/tenant/v1/projects/{project_uuid}/{resource}', summary: 'POST projects/{project_uuid}/{resource}', tags: ['Projects & Work'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route212(): void {}

    #[OA\Get(path: '/api/tenant/v1/tasks/dashboard', summary: 'GET tasks/dashboard', tags: ['Projects & Work'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route213(): void {}

    #[OA\Get(path: '/api/tenant/v1/tasks/kanban', summary: 'GET tasks/kanban', tags: ['Projects & Work'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route214(): void {}

    #[OA\Get(path: '/api/tenant/v1/tasks/calendar', summary: 'GET tasks/calendar', tags: ['Projects & Work'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route215(): void {}

    #[OA\Get(path: '/api/tenant/v1/tasks/my', summary: 'GET tasks/my', tags: ['Projects & Work'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route216(): void {}

    #[OA\Get(path: '/api/tenant/v1/tasks/team', summary: 'GET tasks/team', tags: ['Projects & Work'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route217(): void {}

    #[OA\Post(path: '/api/tenant/v1/tasks/export', summary: 'POST tasks/export', tags: ['Projects & Work'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route218(): void {}

    #[OA\Get(path: '/api/tenant/v1/tasks', summary: 'GET tasks', tags: ['Projects & Work'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route219(): void {}

    #[OA\Post(path: '/api/tenant/v1/tasks', summary: 'POST tasks', tags: ['Projects & Work'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route220(): void {}

    #[OA\Get(path: '/api/tenant/v1/tasks/{task_uuid}', summary: 'GET tasks/{task_uuid}', tags: ['Projects & Work'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route221(): void {}

    #[OA\Patch(path: '/api/tenant/v1/tasks/{task_uuid}', summary: 'PATCH tasks/{task_uuid}', tags: ['Projects & Work'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route222(): void {}

    #[OA\Put(path: '/api/tenant/v1/tasks/{task_uuid}', summary: 'PUT tasks/{task_uuid}', tags: ['Projects & Work'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route223(): void {}

    #[OA\Delete(path: '/api/tenant/v1/tasks/{task_uuid}', summary: 'DELETE tasks/{task_uuid}', tags: ['Projects & Work'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route224(): void {}

    #[OA\Post(path: '/api/tenant/v1/tasks/{task_uuid}/assign', summary: 'POST tasks/{task_uuid}/assign', tags: ['Projects & Work'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route225(): void {}

    #[OA\Post(path: '/api/tenant/v1/tasks/{task_uuid}/status', summary: 'POST tasks/{task_uuid}/status', tags: ['Projects & Work'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route226(): void {}

    #[OA\Post(path: '/api/tenant/v1/tasks/{task_uuid}/complete', summary: 'POST tasks/{task_uuid}/complete', tags: ['Projects & Work'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route227(): void {}

    #[OA\Post(path: '/api/tenant/v1/tasks/{task_uuid}/clone', summary: 'POST tasks/{task_uuid}/clone', tags: ['Projects & Work'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route228(): void {}

    #[OA\Get(path: '/api/tenant/v1/tasks/{task_uuid}/{resource}', summary: 'GET tasks/{task_uuid}/{resource}', tags: ['Projects & Work'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route229(): void {}

    #[OA\Post(path: '/api/tenant/v1/tasks/{task_uuid}/{resource}', summary: 'POST tasks/{task_uuid}/{resource}', tags: ['Projects & Work'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route230(): void {}

    #[OA\Get(path: '/api/tenant/v1/issues/dashboard', summary: 'GET issues/dashboard', tags: ['Projects & Work'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route231(): void {}

    #[OA\Get(path: '/api/tenant/v1/issues/kanban', summary: 'GET issues/kanban', tags: ['Projects & Work'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route232(): void {}

    #[OA\Post(path: '/api/tenant/v1/issues/export', summary: 'POST issues/export', tags: ['Projects & Work'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route233(): void {}

    #[OA\Get(path: '/api/tenant/v1/issues', summary: 'GET issues', tags: ['Projects & Work'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route234(): void {}

    #[OA\Post(path: '/api/tenant/v1/issues', summary: 'POST issues', tags: ['Projects & Work'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route235(): void {}

    #[OA\Get(path: '/api/tenant/v1/issues/{issue_uuid}', summary: 'GET issues/{issue_uuid}', tags: ['Projects & Work'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route236(): void {}

    #[OA\Patch(path: '/api/tenant/v1/issues/{issue_uuid}', summary: 'PATCH issues/{issue_uuid}', tags: ['Projects & Work'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route237(): void {}

    #[OA\Put(path: '/api/tenant/v1/issues/{issue_uuid}', summary: 'PUT issues/{issue_uuid}', tags: ['Projects & Work'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route238(): void {}

    #[OA\Delete(path: '/api/tenant/v1/issues/{issue_uuid}', summary: 'DELETE issues/{issue_uuid}', tags: ['Projects & Work'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route239(): void {}

    #[OA\Post(path: '/api/tenant/v1/issues/{issue_uuid}/assign', summary: 'POST issues/{issue_uuid}/assign', tags: ['Projects & Work'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route240(): void {}

    #[OA\Post(path: '/api/tenant/v1/issues/{issue_uuid}/status', summary: 'POST issues/{issue_uuid}/status', tags: ['Projects & Work'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route241(): void {}

    #[OA\Post(path: '/api/tenant/v1/issues/{issue_uuid}/{state}', summary: 'POST issues/{issue_uuid}/{state}', tags: ['Projects & Work'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route242(): void {}

    #[OA\Get(path: '/api/tenant/v1/leads/dashboard', summary: 'GET leads/dashboard', tags: ['CRM'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route243(): void {}

    #[OA\Get(path: '/api/tenant/v1/leads/kanban', summary: 'GET leads/kanban', tags: ['CRM'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route244(): void {}

    #[OA\Get(path: '/api/tenant/v1/leads/form-options', summary: 'GET leads/form-options', tags: ['CRM'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route245(): void {}

    #[OA\Post(path: '/api/tenant/v1/leads/import', summary: 'POST leads/import', tags: ['CRM'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route246(): void {}

    #[OA\Post(path: '/api/tenant/v1/leads/export', summary: 'POST leads/export', tags: ['CRM'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route247(): void {}

    #[OA\Post(path: '/api/tenant/v1/leads/merge', summary: 'POST leads/merge', tags: ['CRM'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route248(): void {}

    #[OA\Get(path: '/api/tenant/v1/leads', summary: 'GET leads', tags: ['CRM'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route249(): void {}

    #[OA\Post(path: '/api/tenant/v1/leads', summary: 'POST leads', tags: ['CRM'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route250(): void {}

    #[OA\Get(path: '/api/tenant/v1/leads/{lead_uuid}', summary: 'GET leads/{lead_uuid}', tags: ['CRM'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route251(): void {}

    #[OA\Patch(path: '/api/tenant/v1/leads/{lead_uuid}', summary: 'PATCH leads/{lead_uuid}', tags: ['CRM'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route252(): void {}

    #[OA\Put(path: '/api/tenant/v1/leads/{lead_uuid}', summary: 'PUT leads/{lead_uuid}', tags: ['CRM'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route253(): void {}

    #[OA\Delete(path: '/api/tenant/v1/leads/{lead_uuid}', summary: 'DELETE leads/{lead_uuid}', tags: ['CRM'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route254(): void {}

    #[OA\Post(path: '/api/tenant/v1/leads/{lead_uuid}/duplicate', summary: 'POST leads/{lead_uuid}/duplicate', tags: ['CRM'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route255(): void {}

    #[OA\Post(path: '/api/tenant/v1/leads/{lead_uuid}/convert', summary: 'POST leads/{lead_uuid}/convert', tags: ['CRM'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route256(): void {}

    #[OA\Post(path: '/api/tenant/v1/leads/{lead_uuid}/mark-lost', summary: 'POST leads/{lead_uuid}/mark-lost', tags: ['CRM'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route257(): void {}

    #[OA\Get(path: '/api/tenant/v1/leads/{lead_uuid}/contacts', summary: 'GET leads/{lead_uuid}/contacts', tags: ['CRM'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route258(): void {}

    #[OA\Post(path: '/api/tenant/v1/leads/{lead_uuid}/contacts', summary: 'POST leads/{lead_uuid}/contacts', tags: ['CRM'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route259(): void {}

    #[OA\Patch(path: '/api/tenant/v1/leads/{lead_uuid}/contacts/{contact_uuid}', summary: 'PATCH leads/{lead_uuid}/contacts/{contact_uuid}', tags: ['CRM'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route260(): void {}

    #[OA\Put(path: '/api/tenant/v1/leads/{lead_uuid}/contacts/{contact_uuid}', summary: 'PUT leads/{lead_uuid}/contacts/{contact_uuid}', tags: ['CRM'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route261(): void {}

    #[OA\Delete(path: '/api/tenant/v1/leads/{lead_uuid}/contacts/{contact_uuid}', summary: 'DELETE leads/{lead_uuid}/contacts/{contact_uuid}', tags: ['CRM'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route262(): void {}

    #[OA\Get(path: '/api/tenant/v1/leads/{lead_uuid}/addresses', summary: 'GET leads/{lead_uuid}/addresses', tags: ['CRM'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route263(): void {}

    #[OA\Post(path: '/api/tenant/v1/leads/{lead_uuid}/addresses', summary: 'POST leads/{lead_uuid}/addresses', tags: ['CRM'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route264(): void {}

    #[OA\Patch(path: '/api/tenant/v1/leads/{lead_uuid}/addresses/{address_id}', summary: 'PATCH leads/{lead_uuid}/addresses/{address_id}', tags: ['CRM'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route265(): void {}

    #[OA\Put(path: '/api/tenant/v1/leads/{lead_uuid}/addresses/{address_id}', summary: 'PUT leads/{lead_uuid}/addresses/{address_id}', tags: ['CRM'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route266(): void {}

    #[OA\Delete(path: '/api/tenant/v1/leads/{lead_uuid}/addresses/{address_id}', summary: 'DELETE leads/{lead_uuid}/addresses/{address_id}', tags: ['CRM'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route267(): void {}

    #[OA\Get(path: '/api/tenant/v1/leads/{lead_uuid}/activities', summary: 'GET leads/{lead_uuid}/activities', tags: ['CRM'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route268(): void {}

    #[OA\Post(path: '/api/tenant/v1/leads/{lead_uuid}/activities', summary: 'POST leads/{lead_uuid}/activities', tags: ['CRM'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route269(): void {}

    #[OA\Patch(path: '/api/tenant/v1/leads/{lead_uuid}/activities/{activity_uuid}', summary: 'PATCH leads/{lead_uuid}/activities/{activity_uuid}', tags: ['CRM'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route270(): void {}

    #[OA\Put(path: '/api/tenant/v1/leads/{lead_uuid}/activities/{activity_uuid}', summary: 'PUT leads/{lead_uuid}/activities/{activity_uuid}', tags: ['CRM'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route271(): void {}

    #[OA\Get(path: '/api/tenant/v1/leads/{lead_uuid}/activity', summary: 'GET leads/{lead_uuid}/activity', tags: ['CRM'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route272(): void {}

    #[OA\Get(path: '/api/tenant/v1/finance/dashboard', summary: 'GET finance/dashboard', tags: ['Tenant API'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route273(): void {}

    #[OA\Get(path: '/api/tenant/v1/invoices', summary: 'GET invoices', tags: ['Finance'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route274(): void {}

    #[OA\Post(path: '/api/tenant/v1/invoices', summary: 'POST invoices', tags: ['Finance'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route275(): void {}

    #[OA\Get(path: '/api/tenant/v1/invoices/{invoice_uuid}', summary: 'GET invoices/{invoice_uuid}', tags: ['Finance'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route276(): void {}

    #[OA\Patch(path: '/api/tenant/v1/invoices/{invoice_uuid}', summary: 'PATCH invoices/{invoice_uuid}', tags: ['Finance'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route277(): void {}

    #[OA\Put(path: '/api/tenant/v1/invoices/{invoice_uuid}', summary: 'PUT invoices/{invoice_uuid}', tags: ['Finance'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route278(): void {}

    #[OA\Post(path: '/api/tenant/v1/invoices/{invoice_uuid}/items', summary: 'POST invoices/{invoice_uuid}/items', tags: ['Finance'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route279(): void {}

    #[OA\Patch(path: '/api/tenant/v1/invoices/{invoice_uuid}/items/{item_id}', summary: 'PATCH invoices/{invoice_uuid}/items/{item_id}', tags: ['Finance'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route280(): void {}

    #[OA\Put(path: '/api/tenant/v1/invoices/{invoice_uuid}/items/{item_id}', summary: 'PUT invoices/{invoice_uuid}/items/{item_id}', tags: ['Finance'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route281(): void {}

    #[OA\Delete(path: '/api/tenant/v1/invoices/{invoice_uuid}/items/{item_id}', summary: 'DELETE invoices/{invoice_uuid}/items/{item_id}', tags: ['Finance'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route282(): void {}

    #[OA\Post(path: '/api/tenant/v1/invoices/{invoice_uuid}/send', summary: 'POST invoices/{invoice_uuid}/send', tags: ['Finance'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route283(): void {}

    #[OA\Post(path: '/api/tenant/v1/invoices/{invoice_uuid}/cancel', summary: 'POST invoices/{invoice_uuid}/cancel', tags: ['Finance'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route284(): void {}

    #[OA\Get(path: '/api/tenant/v1/invoices/{invoice_uuid}/pdf', summary: 'GET invoices/{invoice_uuid}/pdf', tags: ['Finance'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route285(): void {}

    #[OA\Get(path: '/api/tenant/v1/payments', summary: 'GET payments', tags: ['Finance'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route286(): void {}

    #[OA\Post(path: '/api/tenant/v1/payments', summary: 'POST payments', tags: ['Finance'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route287(): void {}

    #[OA\Get(path: '/api/tenant/v1/payments/{payment_uuid}', summary: 'GET payments/{payment_uuid}', tags: ['Finance'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route288(): void {}

    #[OA\Post(path: '/api/tenant/v1/payments/{payment_uuid}/void', summary: 'POST payments/{payment_uuid}/void', tags: ['Finance'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route289(): void {}

    #[OA\Get(path: '/api/tenant/v1/payments/{payment_uuid}/receipt', summary: 'GET payments/{payment_uuid}/receipt', tags: ['Finance'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route290(): void {}

    #[OA\Get(path: '/api/tenant/v1/expenses', summary: 'GET expenses', tags: ['Finance'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route291(): void {}

    #[OA\Post(path: '/api/tenant/v1/expenses', summary: 'POST expenses', tags: ['Finance'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route292(): void {}

    #[OA\Get(path: '/api/tenant/v1/expenses/{expense_uuid}', summary: 'GET expenses/{expense_uuid}', tags: ['Finance'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route293(): void {}

    #[OA\Patch(path: '/api/tenant/v1/expenses/{expense_uuid}', summary: 'PATCH expenses/{expense_uuid}', tags: ['Finance'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route294(): void {}

    #[OA\Put(path: '/api/tenant/v1/expenses/{expense_uuid}', summary: 'PUT expenses/{expense_uuid}', tags: ['Finance'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route295(): void {}

    #[OA\Post(path: '/api/tenant/v1/expenses/{expense_uuid}/approve', summary: 'POST expenses/{expense_uuid}/approve', tags: ['Finance'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route296(): void {}

    #[OA\Post(path: '/api/tenant/v1/expenses/{expense_uuid}/reject', summary: 'POST expenses/{expense_uuid}/reject', tags: ['Finance'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route297(): void {}

    #[OA\Get(path: '/api/tenant/v1/settings/', summary: 'GET settings/', tags: ['Settings'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route298(): void {}

    #[OA\Patch(path: '/api/tenant/v1/settings/', summary: 'PATCH settings/', tags: ['Settings'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route299(): void {}

    #[OA\Put(path: '/api/tenant/v1/settings/', summary: 'PUT settings/', tags: ['Settings'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route300(): void {}

    #[OA\Get(path: '/api/tenant/v1/settings/lookups', summary: 'GET settings/lookups', tags: ['Settings'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route301(): void {}

    #[OA\Put(path: '/api/tenant/v1/settings/lookups/reorder', summary: 'PUT settings/lookups/reorder', tags: ['Settings'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route302(): void {}

    #[OA\Delete(path: '/api/tenant/v1/settings/lookups/{lookup_uuid}', summary: 'DELETE settings/lookups/{lookup_uuid}', tags: ['Settings'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route303(): void {}

    #[OA\Get(path: '/api/tenant/v1/settings/notification-templates', summary: 'GET settings/notification-templates', tags: ['Settings'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route304(): void {}

    #[OA\Post(path: '/api/tenant/v1/settings/notification-templates', summary: 'POST settings/notification-templates', tags: ['Settings'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route305(): void {}

    #[OA\Patch(path: '/api/tenant/v1/settings/notification-templates/{template_uuid}', summary: 'PATCH settings/notification-templates/{template_uuid}', tags: ['Settings'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route306(): void {}

    #[OA\Put(path: '/api/tenant/v1/settings/notification-templates/{template_uuid}', summary: 'PUT settings/notification-templates/{template_uuid}', tags: ['Settings'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route307(): void {}

    #[OA\Get(path: '/api/tenant/v1/settings/backups/runs', summary: 'GET settings/backups/runs', tags: ['Settings'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route308(): void {}

    #[OA\Post(path: '/api/tenant/v1/settings/backups/run', summary: 'POST settings/backups/run', tags: ['Settings'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route309(): void {}

    #[OA\Post(path: '/api/tenant/v1/settings/backups/restore', summary: 'POST settings/backups/restore', tags: ['Settings'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route310(): void {}

    #[OA\Get(path: '/api/tenant/v1/reminders', summary: 'GET reminders', tags: ['Tenant API'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route311(): void {}

    #[OA\Post(path: '/api/tenant/v1/reminders', summary: 'POST reminders', tags: ['Tenant API'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route312(): void {}

    #[OA\Patch(path: '/api/tenant/v1/reminders/{reminder_uuid}', summary: 'PATCH reminders/{reminder_uuid}', tags: ['Tenant API'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route313(): void {}

    #[OA\Put(path: '/api/tenant/v1/reminders/{reminder_uuid}', summary: 'PUT reminders/{reminder_uuid}', tags: ['Tenant API'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route314(): void {}

    #[OA\Delete(path: '/api/tenant/v1/reminders/{reminder_uuid}', summary: 'DELETE reminders/{reminder_uuid}', tags: ['Tenant API'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route315(): void {}

    #[OA\Get(path: '/api/tenant/v1/communication/logs', summary: 'GET communication/logs', tags: ['Tenant API'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route316(): void {}

    #[OA\Post(path: '/api/tenant/v1/communication/email', summary: 'POST communication/email', tags: ['Tenant API'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route317(): void {}

    #[OA\Post(path: '/api/tenant/v1/communication/sms', summary: 'POST communication/sms', tags: ['Tenant API'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route318(): void {}

    #[OA\Post(path: '/api/tenant/v1/communication/whatsapp', summary: 'POST communication/whatsapp', tags: ['Tenant API'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route319(): void {}

    #[OA\Post(path: '/api/tenant/v1/communication/push', summary: 'POST communication/push', tags: ['Tenant API'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route320(): void {}

    #[OA\Post(path: '/api/tenant/v1/communication/logs/{log_uuid}/retry', summary: 'POST communication/logs/{log_uuid}/retry', tags: ['Tenant API'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route321(): void {}

    #[OA\Get(path: '/api/tenant/v1/help/articles', summary: 'GET help/articles', tags: ['Tenant API'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route322(): void {}

    #[OA\Get(path: '/api/tenant/v1/help/articles/{slug}', summary: 'GET help/articles/{slug}', tags: ['Tenant API'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route323(): void {}

    #[OA\Get(path: '/api/tenant/v1/help/faqs', summary: 'GET help/faqs', tags: ['Tenant API'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route324(): void {}

    #[OA\Get(path: '/api/tenant/v1/help/release-notes', summary: 'GET help/release-notes', tags: ['Tenant API'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route325(): void {}

    #[OA\Get(path: '/api/tenant/v1/help/system-status', summary: 'GET help/system-status', tags: ['Tenant API'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route326(): void {}

    #[OA\Post(path: '/api/tenant/v1/help/contact-support', summary: 'POST help/contact-support', tags: ['Tenant API'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route327(): void {}

    #[OA\Get(path: '/api/tenant/v1/help/tickets', summary: 'GET help/tickets', tags: ['Tenant API'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route328(): void {}

    #[OA\Get(path: '/api/tenant/v1/help/tickets/{ticket_uuid}', summary: 'GET help/tickets/{ticket_uuid}', tags: ['Tenant API'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route329(): void {}

    #[OA\Post(path: '/api/tenant/v1/help/tickets/{ticket_uuid}/comments', summary: 'POST help/tickets/{ticket_uuid}/comments', tags: ['Tenant API'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route330(): void {}

    #[OA\Get(path: '/api/tenant/v1/profile/api-tokens', summary: 'GET profile/api-tokens', tags: ['Tenant Auth & Profile'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route331(): void {}

    #[OA\Post(path: '/api/tenant/v1/profile/api-tokens', summary: 'POST profile/api-tokens', tags: ['Tenant Auth & Profile'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route332(): void {}

    #[OA\Post(path: '/api/tenant/v1/profile/api-tokens/{token_uuid}/rotate', summary: 'POST profile/api-tokens/{token_uuid}/rotate', tags: ['Tenant Auth & Profile'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route333(): void {}

    #[OA\Post(path: '/api/tenant/v1/profile/api-tokens/{token_uuid}/revoke', summary: 'POST profile/api-tokens/{token_uuid}/revoke', tags: ['Tenant Auth & Profile'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route334(): void {}

    #[OA\Get(path: '/api/tenant/v1/notifications', summary: 'GET notifications', tags: ['Notifications'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route335(): void {}

    #[OA\Get(path: '/api/tenant/v1/notifications/unread-count', summary: 'GET notifications/unread-count', tags: ['Notifications'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route336(): void {}

    #[OA\Post(path: '/api/tenant/v1/notifications/bulk/read', summary: 'POST notifications/bulk/read', tags: ['Notifications'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route337(): void {}

    #[OA\Post(path: '/api/tenant/v1/notifications/{notification_id}/read', summary: 'POST notifications/{notification_id}/read', tags: ['Notifications'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route338(): void {}

    #[OA\Post(path: '/api/tenant/v1/notifications/{notification_id}/unread', summary: 'POST notifications/{notification_id}/unread', tags: ['Notifications'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route339(): void {}

    #[OA\Get(path: '/api/tenant/v1/notifications/{notification_id}', summary: 'GET notifications/{notification_id}', tags: ['Notifications'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route340(): void {}

    #[OA\Delete(path: '/api/tenant/v1/notifications/{notification_id}', summary: 'DELETE notifications/{notification_id}', tags: ['Notifications'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route341(): void {}

    #[OA\Get(path: '/api/tenant/v1/calendars', summary: 'GET calendars', tags: ['Calendar'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route342(): void {}

    #[OA\Post(path: '/api/tenant/v1/calendars', summary: 'POST calendars', tags: ['Calendar'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route343(): void {}

    #[OA\Get(path: '/api/tenant/v1/calendars/{calendar_uuid}', summary: 'GET calendars/{calendar_uuid}', tags: ['Calendar'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route344(): void {}

    #[OA\Patch(path: '/api/tenant/v1/calendars/{calendar_uuid}', summary: 'PATCH calendars/{calendar_uuid}', tags: ['Calendar'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route345(): void {}

    #[OA\Put(path: '/api/tenant/v1/calendars/{calendar_uuid}', summary: 'PUT calendars/{calendar_uuid}', tags: ['Calendar'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route346(): void {}

    #[OA\Delete(path: '/api/tenant/v1/calendars/{calendar_uuid}', summary: 'DELETE calendars/{calendar_uuid}', tags: ['Calendar'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route347(): void {}

    #[OA\Get(path: '/api/tenant/v1/calendar-events', summary: 'GET calendar-events', tags: ['Calendar'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route348(): void {}

    #[OA\Post(path: '/api/tenant/v1/calendar-events', summary: 'POST calendar-events', tags: ['Calendar'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route349(): void {}

    #[OA\Get(path: '/api/tenant/v1/calendar-events/{event_uuid}', summary: 'GET calendar-events/{event_uuid}', tags: ['Calendar'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route350(): void {}

    #[OA\Patch(path: '/api/tenant/v1/calendar-events/{event_uuid}', summary: 'PATCH calendar-events/{event_uuid}', tags: ['Calendar'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route351(): void {}

    #[OA\Put(path: '/api/tenant/v1/calendar-events/{event_uuid}', summary: 'PUT calendar-events/{event_uuid}', tags: ['Calendar'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route352(): void {}

    #[OA\Delete(path: '/api/tenant/v1/calendar-events/{event_uuid}', summary: 'DELETE calendar-events/{event_uuid}', tags: ['Calendar'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route353(): void {}

    #[OA\Post(path: '/api/tenant/v1/calendar-events/{event_uuid}/reschedule', summary: 'POST calendar-events/{event_uuid}/reschedule', tags: ['Calendar'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route354(): void {}

    #[OA\Post(path: '/api/tenant/v1/calendar-events/{event_uuid}/video-meeting', summary: 'POST calendar-events/{event_uuid}/video-meeting', tags: ['Calendar'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route355(): void {}

    #[OA\Post(path: '/api/tenant/v1/calendar-events/{event_uuid}/room-booking', summary: 'POST calendar-events/{event_uuid}/room-booking', tags: ['Calendar'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route356(): void {}

    #[OA\Get(path: '/api/tenant/v1/calendar-events/{event_uuid}/{kind}', summary: 'GET calendar-events/{event_uuid}/{kind}', tags: ['Calendar'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route357(): void {}

    #[OA\Post(path: '/api/tenant/v1/calendar-events/{event_uuid}/{kind}', summary: 'POST calendar-events/{event_uuid}/{kind}', tags: ['Calendar'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route358(): void {}

    #[OA\Get(path: '/api/tenant/v1/meeting-rooms', summary: 'GET meeting-rooms', tags: ['Tenant API'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route359(): void {}

    #[OA\Post(path: '/api/tenant/v1/meeting-rooms', summary: 'POST meeting-rooms', tags: ['Tenant API'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route360(): void {}

    #[OA\Patch(path: '/api/tenant/v1/meeting-rooms/{room_id}', summary: 'PATCH meeting-rooms/{room_id}', tags: ['Tenant API'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route361(): void {}

    #[OA\Put(path: '/api/tenant/v1/meeting-rooms/{room_id}', summary: 'PUT meeting-rooms/{room_id}', tags: ['Tenant API'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route362(): void {}

    #[OA\Get(path: '/api/tenant/v1/integrations/providers', summary: 'GET integrations/providers', tags: ['Integrations'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route363(): void {}

    #[OA\Get(path: '/api/tenant/v1/integrations', summary: 'GET integrations', tags: ['Integrations'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route364(): void {}

    #[OA\Post(path: '/api/tenant/v1/integrations', summary: 'POST integrations', tags: ['Integrations'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route365(): void {}

    #[OA\Get(path: '/api/tenant/v1/integrations/webhooks', summary: 'GET integrations/webhooks', tags: ['Integrations'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route366(): void {}

    #[OA\Get(path: '/api/tenant/v1/integrations/sync-jobs', summary: 'GET integrations/sync-jobs', tags: ['Integrations'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route367(): void {}

    #[OA\Post(path: '/api/tenant/v1/integrations/sync-jobs/{job_id}/retry', summary: 'POST integrations/sync-jobs/{job_id}/retry', tags: ['Integrations'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route368(): void {}

    #[OA\Get(path: '/api/tenant/v1/integrations/{integration_uuid}', summary: 'GET integrations/{integration_uuid}', tags: ['Integrations'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route369(): void {}

    #[OA\Patch(path: '/api/tenant/v1/integrations/{integration_uuid}', summary: 'PATCH integrations/{integration_uuid}', tags: ['Integrations'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route370(): void {}

    #[OA\Put(path: '/api/tenant/v1/integrations/{integration_uuid}', summary: 'PUT integrations/{integration_uuid}', tags: ['Integrations'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route371(): void {}

    #[OA\Post(path: '/api/tenant/v1/integrations/{integration_uuid}/credentials/rotate', summary: 'POST integrations/{integration_uuid}/credentials/rotate', tags: ['Integrations'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route372(): void {}

    #[OA\Post(path: '/api/tenant/v1/integrations/{integration_uuid}/disconnect', summary: 'POST integrations/{integration_uuid}/disconnect', tags: ['Integrations'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route373(): void {}

    #[OA\Get(path: '/api/tenant/v1/integrations/{integration_uuid}/field-mappings', summary: 'GET integrations/{integration_uuid}/field-mappings', tags: ['Integrations'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route374(): void {}

    #[OA\Put(path: '/api/tenant/v1/integrations/{integration_uuid}/field-mappings', summary: 'PUT integrations/{integration_uuid}/field-mappings', tags: ['Integrations'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route375(): void {}

    #[OA\Get(path: '/api/tenant/v1/integrations/{integration_uuid}/rate-limits', summary: 'GET integrations/{integration_uuid}/rate-limits', tags: ['Integrations'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route376(): void {}

    #[OA\Get(path: '/api/tenant/v1/audit/{type}', summary: 'GET audit/{type}', tags: ['Tenant API'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route377(): void {}

    #[OA\Get(path: '/api/tenant/v1/audit/activity-logs/{activity_id}/compare', summary: 'GET audit/activity-logs/{activity_id}/compare', tags: ['Tenant API'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route378(): void {}

    #[OA\Post(path: '/api/tenant/v1/audit/export', summary: 'POST audit/export', tags: ['Tenant API'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route379(): void {}

    #[OA\Get(path: '/api/tenant/v1/business/selectors', summary: 'GET business/selectors', tags: ['Tenant API'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route380(): void {}

    #[OA\Get(path: '/api/tenant/v1/lookups', summary: 'GET lookups', tags: ['Tenant API'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route381(): void {}

    #[OA\Get(path: '/api/tenant/v1/documents/dashboard', summary: 'GET documents/dashboard', tags: ['Files & Documents'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route382(): void {}

    #[OA\Get(path: '/api/tenant/v1/document-folders', summary: 'GET document-folders', tags: ['Tenant API'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route383(): void {}

    #[OA\Post(path: '/api/tenant/v1/document-folders', summary: 'POST document-folders', tags: ['Tenant API'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route384(): void {}

    #[OA\Post(path: '/api/tenant/v1/document-folders/{folder_uuid}/files', summary: 'POST document-folders/{folder_uuid}/files', tags: ['Tenant API'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route385(): void {}

    #[OA\Get(path: '/api/tenant/v1/files', summary: 'GET files', tags: ['Files & Documents'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route386(): void {}

    #[OA\Post(path: '/api/tenant/v1/files', summary: 'POST files', tags: ['Files & Documents'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route387(): void {}

    #[OA\Get(path: '/api/tenant/v1/files/{file_uuid}/download', summary: 'GET files/{file_uuid}/download', tags: ['Files & Documents'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route388(): void {}

    #[OA\Delete(path: '/api/tenant/v1/files/{file_uuid}', summary: 'DELETE files/{file_uuid}', tags: ['Files & Documents'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route389(): void {}

    #[OA\Get(path: '/api/tenant/v1/attachments', summary: 'GET attachments', tags: ['Files & Documents'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route390(): void {}

    #[OA\Post(path: '/api/tenant/v1/attachments', summary: 'POST attachments', tags: ['Files & Documents'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route391(): void {}

    #[OA\Delete(path: '/api/tenant/v1/attachments/{attachment_id}', summary: 'DELETE attachments/{attachment_id}', tags: ['Files & Documents'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route392(): void {}

    #[OA\Get(path: '/api/tenant/v1/notes', summary: 'GET notes', tags: ['Tenant API'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route393(): void {}

    #[OA\Post(path: '/api/tenant/v1/notes', summary: 'POST notes', tags: ['Tenant API'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route394(): void {}

    #[OA\Patch(path: '/api/tenant/v1/notes/{note_uuid}', summary: 'PATCH notes/{note_uuid}', tags: ['Tenant API'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route395(): void {}

    #[OA\Put(path: '/api/tenant/v1/notes/{note_uuid}', summary: 'PUT notes/{note_uuid}', tags: ['Tenant API'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route396(): void {}

    #[OA\Delete(path: '/api/tenant/v1/notes/{note_uuid}', summary: 'DELETE notes/{note_uuid}', tags: ['Tenant API'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route397(): void {}

    #[OA\Get(path: '/api/tenant/v1/tags', summary: 'GET tags', tags: ['Tenant API'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route398(): void {}

    #[OA\Post(path: '/api/tenant/v1/tags', summary: 'POST tags', tags: ['Tenant API'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route399(): void {}

    #[OA\Patch(path: '/api/tenant/v1/tags/{tag_uuid}', summary: 'PATCH tags/{tag_uuid}', tags: ['Tenant API'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route400(): void {}

    #[OA\Put(path: '/api/tenant/v1/tags/{tag_uuid}', summary: 'PUT tags/{tag_uuid}', tags: ['Tenant API'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route401(): void {}

    #[OA\Delete(path: '/api/tenant/v1/tags/{tag_uuid}', summary: 'DELETE tags/{tag_uuid}', tags: ['Tenant API'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route402(): void {}

    #[OA\Get(path: '/api/tenant/v1/custom-fields', summary: 'GET custom-fields', tags: ['Tenant API'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route403(): void {}

    #[OA\Post(path: '/api/tenant/v1/custom-fields', summary: 'POST custom-fields', tags: ['Tenant API'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route404(): void {}

    #[OA\Get(path: '/api/tenant/v1/payroll/dashboard', summary: 'GET payroll/dashboard', tags: ['HRMS'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route405(): void {}

    #[OA\Get(path: '/api/tenant/v1/payroll/cycles', summary: 'GET payroll/cycles', tags: ['HRMS'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route406(): void {}

    #[OA\Post(path: '/api/tenant/v1/payroll/cycles', summary: 'POST payroll/cycles', tags: ['HRMS'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route407(): void {}

    #[OA\Get(path: '/api/tenant/v1/payroll/cycles/{cycle_uuid}', summary: 'GET payroll/cycles/{cycle_uuid}', tags: ['HRMS'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route408(): void {}

    #[OA\Patch(path: '/api/tenant/v1/payroll/cycles/{cycle_uuid}', summary: 'PATCH payroll/cycles/{cycle_uuid}', tags: ['HRMS'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route409(): void {}

    #[OA\Put(path: '/api/tenant/v1/payroll/cycles/{cycle_uuid}', summary: 'PUT payroll/cycles/{cycle_uuid}', tags: ['HRMS'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route410(): void {}

    #[OA\Post(path: '/api/tenant/v1/payroll/cycles/{cycle_uuid}/generate-preview', summary: 'POST payroll/cycles/{cycle_uuid}/generate-preview', tags: ['HRMS'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route411(): void {}

    #[OA\Post(path: '/api/tenant/v1/payroll/cycles/{cycle_uuid}/generate', summary: 'POST payroll/cycles/{cycle_uuid}/generate', tags: ['HRMS'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route412(): void {}

    #[OA\Post(path: '/api/tenant/v1/payroll/cycles/{cycle_uuid}/{action}', summary: 'POST payroll/cycles/{cycle_uuid}/{action}', tags: ['HRMS'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route413(): void {}

    #[OA\Get(path: '/api/tenant/v1/payroll/payrolls', summary: 'GET payroll/payrolls', tags: ['HRMS'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route414(): void {}

    #[OA\Get(path: '/api/tenant/v1/payroll/payrolls/{payroll_uuid}', summary: 'GET payroll/payrolls/{payroll_uuid}', tags: ['HRMS'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route415(): void {}

    #[OA\Patch(path: '/api/tenant/v1/payroll/payrolls/{payroll_uuid}', summary: 'PATCH payroll/payrolls/{payroll_uuid}', tags: ['HRMS'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route416(): void {}

    #[OA\Put(path: '/api/tenant/v1/payroll/payrolls/{payroll_uuid}', summary: 'PUT payroll/payrolls/{payroll_uuid}', tags: ['HRMS'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route417(): void {}

    #[OA\Get(path: '/api/tenant/v1/payroll/payrolls/{payroll_uuid}/items', summary: 'GET payroll/payrolls/{payroll_uuid}/items', tags: ['HRMS'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route418(): void {}

    #[OA\Get(path: '/api/tenant/v1/payroll/payslips', summary: 'GET payroll/payslips', tags: ['HRMS'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route419(): void {}

    #[OA\Post(path: '/api/tenant/v1/payroll/payslips/generate', summary: 'POST payroll/payslips/generate', tags: ['HRMS'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route420(): void {}

    #[OA\Post(path: '/api/tenant/v1/payroll/payslips/email', summary: 'POST payroll/payslips/email', tags: ['HRMS'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route421(): void {}

    #[OA\Get(path: '/api/tenant/v1/payroll/payslips/{payslip_id}/download', summary: 'GET payroll/payslips/{payslip_id}/download', tags: ['HRMS'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route422(): void {}

    #[OA\Get(path: '/api/tenant/v1/payroll/component-types', summary: 'GET payroll/component-types', tags: ['HRMS'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route423(): void {}

    #[OA\Post(path: '/api/tenant/v1/payroll/component-types', summary: 'POST payroll/component-types', tags: ['HRMS'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route424(): void {}

    #[OA\Get(path: '/api/tenant/v1/payroll/components', summary: 'GET payroll/components', tags: ['HRMS'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route425(): void {}

    #[OA\Post(path: '/api/tenant/v1/payroll/components', summary: 'POST payroll/components', tags: ['HRMS'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route426(): void {}

    #[OA\Patch(path: '/api/tenant/v1/payroll/components/{component_id}', summary: 'PATCH payroll/components/{component_id}', tags: ['HRMS'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route427(): void {}

    #[OA\Put(path: '/api/tenant/v1/payroll/components/{component_id}', summary: 'PUT payroll/components/{component_id}', tags: ['HRMS'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route428(): void {}

    #[OA\Get(path: '/api/tenant/v1/payroll/component-assignments', summary: 'GET payroll/component-assignments', tags: ['HRMS'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route429(): void {}

    #[OA\Post(path: '/api/tenant/v1/payroll/component-assignments', summary: 'POST payroll/component-assignments', tags: ['HRMS'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route430(): void {}

    #[OA\Get(path: '/api/tenant/v1/payroll/loans', summary: 'GET payroll/loans', tags: ['HRMS'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route431(): void {}

    #[OA\Post(path: '/api/tenant/v1/payroll/loans', summary: 'POST payroll/loans', tags: ['HRMS'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route432(): void {}

    #[OA\Patch(path: '/api/tenant/v1/payroll/loans/{loan_id}', summary: 'PATCH payroll/loans/{loan_id}', tags: ['HRMS'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route433(): void {}

    #[OA\Put(path: '/api/tenant/v1/payroll/loans/{loan_id}', summary: 'PUT payroll/loans/{loan_id}', tags: ['HRMS'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route434(): void {}

    #[OA\Get(path: '/api/tenant/v1/payroll/reimbursements', summary: 'GET payroll/reimbursements', tags: ['HRMS'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route435(): void {}

    #[OA\Post(path: '/api/tenant/v1/payroll/reimbursements', summary: 'POST payroll/reimbursements', tags: ['HRMS'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route436(): void {}

    #[OA\Post(path: '/api/tenant/v1/payroll/reimbursements/{reimbursement_id}/approve', summary: 'POST payroll/reimbursements/{reimbursement_id}/approve', tags: ['HRMS'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route437(): void {}

    #[OA\Get(path: '/api/tenant/v1/payroll/bank-transfers', summary: 'GET payroll/bank-transfers', tags: ['HRMS'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route438(): void {}

    #[OA\Post(path: '/api/tenant/v1/payroll/bank-transfers', summary: 'POST payroll/bank-transfers', tags: ['HRMS'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route439(): void {}

    #[OA\Post(path: '/api/tenant/v1/payroll/bank-transfers/{transfer_id}/mark-paid', summary: 'POST payroll/bank-transfers/{transfer_id}/mark-paid', tags: ['HRMS'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route440(): void {}

    #[OA\Get(path: '/api/tenant/v1/payroll/tax-slabs', summary: 'GET payroll/tax-slabs', tags: ['HRMS'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route441(): void {}

    #[OA\Post(path: '/api/tenant/v1/payroll/tax-slabs', summary: 'POST payroll/tax-slabs', tags: ['HRMS'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route442(): void {}

    #[OA\Get(path: '/api/tenant/v1/payroll/pf-settings', summary: 'GET payroll/pf-settings', tags: ['HRMS'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route443(): void {}

    #[OA\Put(path: '/api/tenant/v1/payroll/pf-settings', summary: 'PUT payroll/pf-settings', tags: ['HRMS'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route444(): void {}

    #[OA\Get(path: '/api/tenant/v1/payroll/esi-settings', summary: 'GET payroll/esi-settings', tags: ['HRMS'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route445(): void {}

    #[OA\Put(path: '/api/tenant/v1/payroll/esi-settings', summary: 'PUT payroll/esi-settings', tags: ['HRMS'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route446(): void {}

    #[OA\Post(path: '/api/tenant/v1/payroll/export', summary: 'POST payroll/export', tags: ['HRMS'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route447(): void {}

    #[OA\Get(path: '/api/tenant/v1/holidays', summary: 'GET holidays', tags: ['HRMS'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route448(): void {}

    #[OA\Post(path: '/api/tenant/v1/holidays', summary: 'POST holidays', tags: ['HRMS'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route449(): void {}

    #[OA\Get(path: '/api/tenant/v1/holidays/{holiday_uuid}', summary: 'GET holidays/{holiday_uuid}', tags: ['HRMS'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route450(): void {}

    #[OA\Patch(path: '/api/tenant/v1/holidays/{holiday_uuid}', summary: 'PATCH holidays/{holiday_uuid}', tags: ['HRMS'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route451(): void {}

    #[OA\Put(path: '/api/tenant/v1/holidays/{holiday_uuid}', summary: 'PUT holidays/{holiday_uuid}', tags: ['HRMS'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route452(): void {}

    #[OA\Delete(path: '/api/tenant/v1/holidays/{holiday_uuid}', summary: 'DELETE holidays/{holiday_uuid}', tags: ['HRMS'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route453(): void {}

    #[OA\Post(path: '/api/tenant/v1/holidays/{holiday_uuid}/duplicate-next-year', summary: 'POST holidays/{holiday_uuid}/duplicate-next-year', tags: ['HRMS'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route454(): void {}

    #[OA\Post(path: '/api/tenant/v1/holidays/import', summary: 'POST holidays/import', tags: ['HRMS'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route455(): void {}

    #[OA\Post(path: '/api/tenant/v1/holidays/export', summary: 'POST holidays/export', tags: ['HRMS'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route456(): void {}

    #[OA\Get(path: '/api/tenant/v1/holiday-calendars', summary: 'GET holiday-calendars', tags: ['Tenant API'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route457(): void {}

    #[OA\Post(path: '/api/tenant/v1/holiday-calendars', summary: 'POST holiday-calendars', tags: ['Tenant API'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route458(): void {}

    #[OA\Get(path: '/api/tenant/v1/holiday-calendars/{calendar_uuid}', summary: 'GET holiday-calendars/{calendar_uuid}', tags: ['Tenant API'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route459(): void {}

    #[OA\Patch(path: '/api/tenant/v1/holiday-calendars/{calendar_uuid}', summary: 'PATCH holiday-calendars/{calendar_uuid}', tags: ['Tenant API'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route460(): void {}

    #[OA\Put(path: '/api/tenant/v1/holiday-calendars/{calendar_uuid}', summary: 'PUT holiday-calendars/{calendar_uuid}', tags: ['Tenant API'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route461(): void {}

    #[OA\Delete(path: '/api/tenant/v1/holiday-calendars/{calendar_uuid}', summary: 'DELETE holiday-calendars/{calendar_uuid}', tags: ['Tenant API'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route462(): void {}

    #[OA\Get(path: '/api/tenant/v1/holiday-groups', summary: 'GET holiday-groups', tags: ['Tenant API'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route463(): void {}

    #[OA\Post(path: '/api/tenant/v1/holiday-groups', summary: 'POST holiday-groups', tags: ['Tenant API'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route464(): void {}

    #[OA\Patch(path: '/api/tenant/v1/holiday-groups/{group_uuid}', summary: 'PATCH holiday-groups/{group_uuid}', tags: ['Tenant API'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route465(): void {}

    #[OA\Put(path: '/api/tenant/v1/holiday-groups/{group_uuid}', summary: 'PUT holiday-groups/{group_uuid}', tags: ['Tenant API'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route466(): void {}

    #[OA\Get(path: '/api/tenant/v1/holiday-groups/{group_uuid}/members', summary: 'GET holiday-groups/{group_uuid}/members', tags: ['Tenant API'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route467(): void {}

    #[OA\Post(path: '/api/tenant/v1/holiday-groups/{group_uuid}/members', summary: 'POST holiday-groups/{group_uuid}/members', tags: ['Tenant API'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route468(): void {}

    #[OA\Delete(path: '/api/tenant/v1/holiday-groups/{group_uuid}/members/{staff_uuid}', summary: 'DELETE holiday-groups/{group_uuid}/members/{staff_uuid}', tags: ['Tenant API'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route469(): void {}

    #[OA\Get(path: '/api/tenant/v1/navigation/sidebar', summary: 'GET navigation/sidebar', tags: ['Tenant API'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route470(): void {}

    #[OA\Get(path: '/api/tenant/v1/dashboard/summary', summary: 'GET dashboard/summary', tags: ['Tenant API'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route471(): void {}

    #[OA\Get(path: '/api/tenant/v1/dashboard/charts/{chart}', summary: 'GET dashboard/charts/{chart}', tags: ['Tenant API'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route472(): void {}

    #[OA\Get(path: '/api/tenant/v1/dashboard/recent-activities', summary: 'GET dashboard/recent-activities', tags: ['Tenant API'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route473(): void {}

    #[OA\Get(path: '/api/tenant/v1/dashboard/widgets', summary: 'GET dashboard/widgets', tags: ['Tenant API'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route474(): void {}

    #[OA\Put(path: '/api/tenant/v1/dashboard/widgets', summary: 'PUT dashboard/widgets', tags: ['Tenant API'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route475(): void {}

    #[OA\Post(path: '/api/tenant/v1/dashboard/export', summary: 'POST dashboard/export', tags: ['Tenant API'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route476(): void {}

    #[OA\Get(path: '/api/tenant/v1/dashboard/{widget}', summary: 'GET dashboard/{widget}', tags: ['Tenant API'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route477(): void {}

    #[OA\Get(path: '/api/tenant/v1/reports/dashboard', summary: 'GET reports/dashboard', tags: ['Reports'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route478(): void {}

    #[OA\Get(path: '/api/tenant/v1/reports/custom', summary: 'GET reports/custom', tags: ['Reports'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route479(): void {}

    #[OA\Post(path: '/api/tenant/v1/reports/custom', summary: 'POST reports/custom', tags: ['Reports'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route480(): void {}

    #[OA\Post(path: '/api/tenant/v1/reports/custom/{report_uuid}/run', summary: 'POST reports/custom/{report_uuid}/run', tags: ['Reports'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route481(): void {}

    #[OA\Get(path: '/api/tenant/v1/reports/{report_code}', summary: 'GET reports/{report_code}', tags: ['Reports'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route482(): void {}

    #[OA\Post(path: '/api/tenant/v1/reports/{report_code}/export', summary: 'POST reports/{report_code}/export', tags: ['Reports'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route483(): void {}

    #[OA\Get(path: '/api/tenant/v1/leave/dashboard', summary: 'GET leave/dashboard', tags: ['HRMS'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route484(): void {}

    #[OA\Get(path: '/api/tenant/v1/leave/requests', summary: 'GET leave/requests', tags: ['HRMS'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route485(): void {}

    #[OA\Post(path: '/api/tenant/v1/leave/requests', summary: 'POST leave/requests', tags: ['HRMS'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route486(): void {}

    #[OA\Get(path: '/api/tenant/v1/leave/requests/{request_id}', summary: 'GET leave/requests/{request_id}', tags: ['HRMS'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route487(): void {}

    #[OA\Post(path: '/api/tenant/v1/leave/requests/{request_id}/approve', summary: 'POST leave/requests/{request_id}/approve', tags: ['HRMS'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route488(): void {}

    #[OA\Post(path: '/api/tenant/v1/leave/requests/{request_id}/reject', summary: 'POST leave/requests/{request_id}/reject', tags: ['HRMS'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route489(): void {}

    #[OA\Post(path: '/api/tenant/v1/leave/requests/{request_id}/cancel', summary: 'POST leave/requests/{request_id}/cancel', tags: ['HRMS'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route490(): void {}

    #[OA\Get(path: '/api/tenant/v1/leave/balances', summary: 'GET leave/balances', tags: ['HRMS'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route491(): void {}

    #[OA\Post(path: '/api/tenant/v1/leave/balances/adjust', summary: 'POST leave/balances/adjust', tags: ['HRMS'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route492(): void {}

    #[OA\Get(path: '/api/tenant/v1/leave/calendar', summary: 'GET leave/calendar', tags: ['HRMS'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route493(): void {}

    #[OA\Get(path: '/api/tenant/v1/leave/types', summary: 'GET leave/types', tags: ['HRMS'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route494(): void {}

    #[OA\Post(path: '/api/tenant/v1/leave/types', summary: 'POST leave/types', tags: ['HRMS'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route495(): void {}

    #[OA\Get(path: '/api/tenant/v1/attendance/dashboard', summary: 'GET attendance/dashboard', tags: ['HRMS'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route496(): void {}

    #[OA\Get(path: '/api/tenant/v1/attendance/daily', summary: 'GET attendance/daily', tags: ['HRMS'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route497(): void {}

    #[OA\Get(path: '/api/tenant/v1/attendance/monthly', summary: 'GET attendance/monthly', tags: ['HRMS'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route498(): void {}

    #[OA\Post(path: '/api/tenant/v1/attendance/check-in', summary: 'POST attendance/check-in', tags: ['HRMS'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route499(): void {}

    #[OA\Post(path: '/api/tenant/v1/attendance/check-out', summary: 'POST attendance/check-out', tags: ['HRMS'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route500(): void {}

    #[OA\Post(path: '/api/tenant/v1/attendance/records', summary: 'POST attendance/records', tags: ['HRMS'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route501(): void {}

    #[OA\Get(path: '/api/tenant/v1/attendance/records/{record_id}', summary: 'GET attendance/records/{record_id}', tags: ['HRMS'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route502(): void {}

    #[OA\Patch(path: '/api/tenant/v1/attendance/records/{record_id}', summary: 'PATCH attendance/records/{record_id}', tags: ['HRMS'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route503(): void {}

    #[OA\Put(path: '/api/tenant/v1/attendance/records/{record_id}', summary: 'PUT attendance/records/{record_id}', tags: ['HRMS'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route504(): void {}

    #[OA\Post(path: '/api/tenant/v1/attendance/import', summary: 'POST attendance/import', tags: ['HRMS'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route505(): void {}

    #[OA\Post(path: '/api/tenant/v1/attendance/export', summary: 'POST attendance/export', tags: ['HRMS'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route506(): void {}

    #[OA\Get(path: '/api/tenant/v1/attendance/requests', summary: 'GET attendance/requests', tags: ['HRMS'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route507(): void {}

    #[OA\Post(path: '/api/tenant/v1/attendance/requests', summary: 'POST attendance/requests', tags: ['HRMS'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route508(): void {}

    #[OA\Get(path: '/api/tenant/v1/attendance/requests/{request_uuid}', summary: 'GET attendance/requests/{request_uuid}', tags: ['HRMS'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route509(): void {}

    #[OA\Post(path: '/api/tenant/v1/attendance/requests/{request_uuid}/approve', summary: 'POST attendance/requests/{request_uuid}/approve', tags: ['HRMS'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route510(): void {}

    #[OA\Post(path: '/api/tenant/v1/attendance/requests/{request_uuid}/reject', summary: 'POST attendance/requests/{request_uuid}/reject', tags: ['HRMS'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route511(): void {}
}
