<?php

namespace App\Swagger;

use OpenApi\Attributes as OA;

final class ZPlatformApi
{
    #[OA\Get(path: '/api/platform/v1/summary', summary: 'GET summary', tags: ['Platform Dashboard'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route1(): void {}

    #[OA\Get(path: '/api/platform/v1/charts', summary: 'GET charts', tags: ['Platform Dashboard'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route2(): void {}

    #[OA\Get(path: '/api/platform/v1/charts/{chart}', summary: 'GET charts/{chart}', tags: ['Platform Dashboard'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route3(): void {}

    #[OA\Get(path: '/api/platform/v1/recent', summary: 'GET recent', tags: ['Platform Dashboard'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route4(): void {}

    #[OA\Get(path: '/api/platform/v1/recent-tenants', summary: 'GET recent-tenants', tags: ['Platform Dashboard'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route5(): void {}

    #[OA\Get(path: '/api/platform/v1/recent-payments', summary: 'GET recent-payments', tags: ['Platform Dashboard'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route6(): void {}

    #[OA\Get(path: '/api/platform/v1/overdue-invoices', summary: 'GET overdue-invoices', tags: ['Platform Dashboard'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route7(): void {}

    #[OA\Get(path: '/api/platform/v1/alerts', summary: 'GET alerts', tags: ['Platform Dashboard'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route8(): void {}

    #[OA\Get(path: '/api/platform/v1/active-alerts', summary: 'GET active-alerts', tags: ['Platform Dashboard'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route9(): void {}

    #[OA\Get(path: '/api/platform/v1/security-events', summary: 'GET security-events', tags: ['Platform Dashboard'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route10(): void {}

    #[OA\Post(path: '/api/platform/v1/dashboard/export', summary: 'POST dashboard/export', tags: ['Platform Dashboard'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route11(): void {}

    #[OA\Get(path: '/api/platform/v1/profile', summary: 'GET profile', tags: ['Platform Settings'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route12(): void {}

    #[OA\Patch(path: '/api/platform/v1/profile', summary: 'PATCH profile', tags: ['Platform Settings'], security: [['bearerAuth' => []]], requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(type: 'object', properties: [
        new OA\Property(property: 'first_name', type: 'string', maxLength: 100),
        new OA\Property(property: 'last_name', type: 'string', nullable: true, maxLength: 100),
        new OA\Property(property: 'display_name', type: 'string', maxLength: 200),
        new OA\Property(property: 'mobile', type: 'string', nullable: true, maxLength: 20),
        new OA\Property(property: 'timezone', type: 'string', maxLength: 100),
        new OA\Property(property: 'locale', type: 'string', maxLength: 20),
    ])), responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route13(): void {}

    #[OA\Put(path: '/api/platform/v1/profile', summary: 'PUT profile', tags: ['Platform Settings'], security: [['bearerAuth' => []]], requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(type: 'object', properties: [
        new OA\Property(property: 'first_name', type: 'string', maxLength: 100),
        new OA\Property(property: 'last_name', type: 'string', nullable: true, maxLength: 100),
        new OA\Property(property: 'display_name', type: 'string', maxLength: 200),
        new OA\Property(property: 'mobile', type: 'string', nullable: true, maxLength: 20),
        new OA\Property(property: 'timezone', type: 'string', maxLength: 100),
        new OA\Property(property: 'locale', type: 'string', maxLength: 20),
    ])), responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route14(): void {}

    #[OA\Put(path: '/api/platform/v1/profile/password', summary: 'PUT profile/password', tags: ['Platform Settings'], security: [['bearerAuth' => []]], requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(type: 'object', required: ['current_password', 'password', 'password_confirmation'], properties: [
        new OA\Property(property: 'current_password', type: 'string', format: 'password'),
        new OA\Property(property: 'password', type: 'string', format: 'password', minLength: 8),
        new OA\Property(property: 'password_confirmation', type: 'string', format: 'password'),
    ])), responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route15(): void {}

    #[OA\Get(path: '/api/platform/v1/profile/sessions', summary: 'GET profile/sessions', tags: ['Platform Settings'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route16(): void {}

    #[OA\Delete(path: '/api/platform/v1/profile/sessions/{sessionId}', summary: 'DELETE profile/sessions/{sessionId}', tags: ['Platform Settings'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route17(): void {}

    #[OA\Get(path: '/api/platform/v1/permissions/grouped', summary: 'GET permissions/grouped', tags: ['Access Control'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route18(): void {}

    #[OA\Get(path: '/api/platform/v1/permissions', summary: 'GET permissions', tags: ['Access Control'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route19(): void {}

    #[OA\Get(path: '/api/platform/v1/permissions/{permission_uuid}', summary: 'GET permissions/{permission_uuid}', tags: ['Access Control'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route20(): void {}

    #[OA\Post(path: '/api/platform/v1/permissions/export', summary: 'POST permissions/export', tags: ['Access Control'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route21(): void {}

    #[OA\Post(path: '/api/platform/v1/permissions', summary: 'POST permissions', tags: ['Access Control'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route22(): void {}

    #[OA\Patch(path: '/api/platform/v1/permissions/{permission_uuid}', summary: 'PATCH permissions/{permission_uuid}', tags: ['Access Control'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route23(): void {}

    #[OA\Put(path: '/api/platform/v1/permissions/{permission_uuid}', summary: 'PUT permissions/{permission_uuid}', tags: ['Access Control'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route24(): void {}

    #[OA\Delete(path: '/api/platform/v1/permissions/{permission_uuid}', summary: 'DELETE permissions/{permission_uuid}', tags: ['Access Control'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route25(): void {}

    #[OA\Get(path: '/api/platform/v1/roles', summary: 'GET roles', tags: ['Access Control'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route26(): void {}

    #[OA\Get(path: '/api/platform/v1/roles/{role_uuid}', summary: 'GET roles/{role_uuid}', tags: ['Access Control'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route27(): void {}

    #[OA\Get(path: '/api/platform/v1/roles/{role_uuid}/permissions', summary: 'GET roles/{role_uuid}/permissions', tags: ['Access Control'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route28(): void {}

    #[OA\Get(path: '/api/platform/v1/roles/{role_uuid}/users', summary: 'GET roles/{role_uuid}/users', tags: ['Access Control'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route29(): void {}

    #[OA\Post(path: '/api/platform/v1/roles/export', summary: 'POST roles/export', tags: ['Access Control'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route30(): void {}

    #[OA\Post(path: '/api/platform/v1/roles', summary: 'POST roles', tags: ['Access Control'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route31(): void {}

    #[OA\Put(path: '/api/platform/v1/roles/{role_uuid}', summary: 'PUT roles/{role_uuid}', tags: ['Access Control'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route32(): void {}

    #[OA\Patch(path: '/api/platform/v1/roles/{role_uuid}', summary: 'PATCH roles/{role_uuid}', tags: ['Access Control'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route33(): void {}

    #[OA\Delete(path: '/api/platform/v1/roles/{role_uuid}', summary: 'DELETE roles/{role_uuid}', tags: ['Access Control'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route34(): void {}

    #[OA\Post(path: '/api/platform/v1/roles/{role_uuid}/clone', summary: 'POST roles/{role_uuid}/clone', tags: ['Access Control'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route35(): void {}

    #[OA\Post(path: '/api/platform/v1/roles/{role_uuid}/activate', summary: 'POST roles/{role_uuid}/activate', tags: ['Access Control'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route36(): void {}

    #[OA\Post(path: '/api/platform/v1/roles/{role_uuid}/deactivate', summary: 'POST roles/{role_uuid}/deactivate', tags: ['Access Control'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route37(): void {}

    #[OA\Put(path: '/api/platform/v1/roles/{role_uuid}/permissions', summary: 'PUT roles/{role_uuid}/permissions', tags: ['Access Control'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route38(): void {}

    #[OA\Post(path: '/api/platform/v1/roles/{role_uuid}/users', summary: 'POST roles/{role_uuid}/users', tags: ['Access Control'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route39(): void {}

    #[OA\Delete(path: '/api/platform/v1/roles/{role_uuid}/users/{platform_user_uuid}', summary: 'DELETE roles/{role_uuid}/users/{platform_user_uuid}', tags: ['Access Control'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route40(): void {}

    #[OA\Get(path: '/api/platform/v1/platform-departments', summary: 'GET platform-departments', tags: ['Organization'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route41(): void {}

    #[OA\Get(path: '/api/platform/v1/platform-departments/{department_uuid}', summary: 'GET platform-departments/{department_uuid}', tags: ['Organization'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route42(): void {}

    #[OA\Post(path: '/api/platform/v1/platform-departments', summary: 'POST platform-departments', tags: ['Organization'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route43(): void {}

    #[OA\Put(path: '/api/platform/v1/platform-departments/{department_uuid}', summary: 'PUT platform-departments/{department_uuid}', tags: ['Organization'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route44(): void {}

    #[OA\Patch(path: '/api/platform/v1/platform-departments/{department_uuid}', summary: 'PATCH platform-departments/{department_uuid}', tags: ['Organization'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route45(): void {}

    #[OA\Delete(path: '/api/platform/v1/platform-departments/{department_uuid}', summary: 'DELETE platform-departments/{department_uuid}', tags: ['Organization'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route46(): void {}

    #[OA\Get(path: '/api/platform/v1/platform-designations', summary: 'GET platform-designations', tags: ['Organization'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route47(): void {}

    #[OA\Get(path: '/api/platform/v1/platform-designations/{designation_uuid}', summary: 'GET platform-designations/{designation_uuid}', tags: ['Organization'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route48(): void {}

    #[OA\Post(path: '/api/platform/v1/platform-designations', summary: 'POST platform-designations', tags: ['Organization'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route49(): void {}

    #[OA\Put(path: '/api/platform/v1/platform-designations/{designation_uuid}', summary: 'PUT platform-designations/{designation_uuid}', tags: ['Organization'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route50(): void {}

    #[OA\Patch(path: '/api/platform/v1/platform-designations/{designation_uuid}', summary: 'PATCH platform-designations/{designation_uuid}', tags: ['Organization'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route51(): void {}

    #[OA\Delete(path: '/api/platform/v1/platform-designations/{designation_uuid}', summary: 'DELETE platform-designations/{designation_uuid}', tags: ['Organization'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route52(): void {}

    #[OA\Get(path: '/api/platform/v1/platform-users', summary: 'GET platform-users', tags: ['Platform Users'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route53(): void {}

    #[OA\Post(path: '/api/platform/v1/platform-users/export', summary: 'POST platform-users/export', tags: ['Platform Users'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route54(): void {}

    #[OA\Get(path: '/api/platform/v1/platform-users/{platform_user_uuid}', summary: 'GET platform-users/{platform_user_uuid}', tags: ['Platform Users'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route55(): void {}

    #[OA\Get(path: '/api/platform/v1/platform-users/{platform_user_uuid}/roles', summary: 'GET platform-users/{platform_user_uuid}/roles', tags: ['Platform Users'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route56(): void {}

    #[OA\Get(path: '/api/platform/v1/platform-users/{platform_user_uuid}/teams', summary: 'GET platform-users/{platform_user_uuid}/teams', tags: ['Platform Users'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route57(): void {}

    #[OA\Get(path: '/api/platform/v1/platform-users/{platform_user_uuid}/permissions', summary: 'GET platform-users/{platform_user_uuid}/permissions', tags: ['Platform Users'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route58(): void {}

    #[OA\Get(path: '/api/platform/v1/platform-users/{platform_user_uuid}/activity', summary: 'GET platform-users/{platform_user_uuid}/activity', tags: ['Platform Users'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route59(): void {}

    #[OA\Post(path: '/api/platform/v1/platform-users', summary: 'POST platform-users', tags: ['Platform Users'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route60(): void {}

    #[OA\Post(path: '/api/platform/v1/platform-users/invite', summary: 'POST platform-users/invite', tags: ['Platform Users'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route61(): void {}

    #[OA\Patch(path: '/api/platform/v1/platform-users/{platform_user_uuid}', summary: 'PATCH platform-users/{platform_user_uuid}', tags: ['Platform Users'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route62(): void {}

    #[OA\Put(path: '/api/platform/v1/platform-users/{platform_user_uuid}', summary: 'PUT platform-users/{platform_user_uuid}', tags: ['Platform Users'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route63(): void {}

    #[OA\Delete(path: '/api/platform/v1/platform-users/{platform_user_uuid}', summary: 'DELETE platform-users/{platform_user_uuid}', tags: ['Platform Users'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route64(): void {}

    #[OA\Post(path: '/api/platform/v1/platform-users/{platform_user_uuid}/restore', summary: 'POST platform-users/{platform_user_uuid}/restore', tags: ['Platform Users'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route65(): void {}

    #[OA\Post(path: '/api/platform/v1/platform-users/{platform_user_uuid}/suspend', summary: 'POST platform-users/{platform_user_uuid}/suspend', tags: ['Platform Users'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route66(): void {}

    #[OA\Post(path: '/api/platform/v1/platform-users/{platform_user_uuid}/activate', summary: 'POST platform-users/{platform_user_uuid}/activate', tags: ['Platform Users'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route67(): void {}

    #[OA\Post(path: '/api/platform/v1/platform-users/{platform_user_uuid}/reset-password', summary: 'POST platform-users/{platform_user_uuid}/reset-password', tags: ['Platform Users'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route68(): void {}

    #[OA\Post(path: '/api/platform/v1/platform-users/{platform_user_uuid}/force-logout', summary: 'POST platform-users/{platform_user_uuid}/force-logout', tags: ['Platform Users'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route69(): void {}

    #[OA\Post(path: '/api/platform/v1/platform-users/{platform_user_uuid}/require-2fa', summary: 'POST platform-users/{platform_user_uuid}/require-2fa', tags: ['Platform Users'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route70(): void {}

    #[OA\Put(path: '/api/platform/v1/platform-users/{platform_user_uuid}/roles', summary: 'PUT platform-users/{platform_user_uuid}/roles', tags: ['Platform Users'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route71(): void {}

    #[OA\Put(path: '/api/platform/v1/platform-users/{platform_user_uuid}/teams', summary: 'PUT platform-users/{platform_user_uuid}/teams', tags: ['Platform Users'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route72(): void {}

    #[OA\Put(path: '/api/platform/v1/platform-users/{platform_user_uuid}/permissions', summary: 'PUT platform-users/{platform_user_uuid}/permissions', tags: ['Platform Users'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route73(): void {}

    #[OA\Get(path: '/api/platform/v1/platform-teams', summary: 'GET platform-teams', tags: ['Teams'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route74(): void {}

    #[OA\Get(path: '/api/platform/v1/platform-teams/{team_uuid}', summary: 'GET platform-teams/{team_uuid}', tags: ['Teams'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route75(): void {}

    #[OA\Get(path: '/api/platform/v1/platform-teams/{team_uuid}/members', summary: 'GET platform-teams/{team_uuid}/members', tags: ['Teams'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route76(): void {}

    #[OA\Get(path: '/api/platform/v1/platform-teams/{team_uuid}/assignments', summary: 'GET platform-teams/{team_uuid}/assignments', tags: ['Teams'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route77(): void {}

    #[OA\Get(path: '/api/platform/v1/platform-team-roles', summary: 'GET platform-team-roles', tags: ['Teams'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route78(): void {}

    #[OA\Get(path: '/api/platform/v1/platform-team-roles/{role_uuid}', summary: 'GET platform-team-roles/{role_uuid}', tags: ['Teams'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route79(): void {}

    #[OA\Post(path: '/api/platform/v1/platform-teams', summary: 'POST platform-teams', tags: ['Teams'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route80(): void {}

    #[OA\Put(path: '/api/platform/v1/platform-teams/{team_uuid}', summary: 'PUT platform-teams/{team_uuid}', tags: ['Teams'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route81(): void {}

    #[OA\Patch(path: '/api/platform/v1/platform-teams/{team_uuid}', summary: 'PATCH platform-teams/{team_uuid}', tags: ['Teams'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route82(): void {}

    #[OA\Delete(path: '/api/platform/v1/platform-teams/{team_uuid}', summary: 'DELETE platform-teams/{team_uuid}', tags: ['Teams'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route83(): void {}

    #[OA\Post(path: '/api/platform/v1/platform-teams/{team_uuid}/members', summary: 'POST platform-teams/{team_uuid}/members', tags: ['Teams'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route84(): void {}

    #[OA\Put(path: '/api/platform/v1/platform-teams/{team_uuid}/members/{member_id}', summary: 'PUT platform-teams/{team_uuid}/members/{member_id}', tags: ['Teams'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route85(): void {}

    #[OA\Patch(path: '/api/platform/v1/platform-teams/{team_uuid}/members/{member_id}', summary: 'PATCH platform-teams/{team_uuid}/members/{member_id}', tags: ['Teams'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route86(): void {}

    #[OA\Delete(path: '/api/platform/v1/platform-teams/{team_uuid}/members/{member_id}', summary: 'DELETE platform-teams/{team_uuid}/members/{member_id}', tags: ['Teams'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route87(): void {}

    #[OA\Post(path: '/api/platform/v1/platform-teams/{team_uuid}/assignments', summary: 'POST platform-teams/{team_uuid}/assignments', tags: ['Teams'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route88(): void {}

    #[OA\Delete(path: '/api/platform/v1/platform-teams/{team_uuid}/assignments/{assignment_id}', summary: 'DELETE platform-teams/{team_uuid}/assignments/{assignment_id}', tags: ['Teams'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route89(): void {}

    #[OA\Post(path: '/api/platform/v1/platform-team-roles', summary: 'POST platform-team-roles', tags: ['Teams'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route90(): void {}

    #[OA\Put(path: '/api/platform/v1/platform-team-roles/{role_uuid}', summary: 'PUT platform-team-roles/{role_uuid}', tags: ['Teams'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route91(): void {}

    #[OA\Patch(path: '/api/platform/v1/platform-team-roles/{role_uuid}', summary: 'PATCH platform-team-roles/{role_uuid}', tags: ['Teams'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route92(): void {}

    #[OA\Delete(path: '/api/platform/v1/platform-team-roles/{role_uuid}', summary: 'DELETE platform-team-roles/{role_uuid}', tags: ['Teams'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route93(): void {}

    #[OA\Get(path: '/api/platform/v1/features', summary: 'GET features', tags: ['Features'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route94(): void {}

    #[OA\Get(path: '/api/platform/v1/features/options/modules', summary: 'GET features/options/modules', tags: ['Features'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route95(): void {}

    #[OA\Get(path: '/api/platform/v1/features/{feature_uuid}', summary: 'GET features/{feature_uuid}', tags: ['Features'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route96(): void {}

    #[OA\Post(path: '/api/platform/v1/features/export', summary: 'POST features/export', tags: ['Features'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route97(): void {}

    #[OA\Post(path: '/api/platform/v1/features', summary: 'POST features', tags: ['Features'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route98(): void {}

    #[OA\Post(path: '/api/platform/v1/features/import', summary: 'POST features/import', tags: ['Features'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route99(): void {}

    #[OA\Patch(path: '/api/platform/v1/features/{feature_uuid}', summary: 'PATCH features/{feature_uuid}', tags: ['Features'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route100(): void {}

    #[OA\Put(path: '/api/platform/v1/features/{feature_uuid}', summary: 'PUT features/{feature_uuid}', tags: ['Features'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route101(): void {}

    #[OA\Delete(path: '/api/platform/v1/features/bulk', summary: 'DELETE features/bulk', tags: ['Features'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route102(): void {}

    #[OA\Delete(path: '/api/platform/v1/features/{feature_uuid}', summary: 'DELETE features/{feature_uuid}', tags: ['Features'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route103(): void {}

    #[OA\Get(path: '/api/platform/v1/modules', summary: 'GET modules', tags: ['Modules'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route104(): void {}

    #[OA\Get(path: '/api/platform/v1/modules/{module_uuid}', summary: 'GET modules/{module_uuid}', tags: ['Modules'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route105(): void {}

    #[OA\Get(path: '/api/platform/v1/modules/{module_uuid}/features', summary: 'GET modules/{module_uuid}/features', tags: ['Modules'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route106(): void {}

    #[OA\Get(path: '/api/platform/v1/modules/{module_uuid}/tenants', summary: 'GET modules/{module_uuid}/tenants', tags: ['Modules'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route107(): void {}

    #[OA\Post(path: '/api/platform/v1/modules/export', summary: 'POST modules/export', tags: ['Modules'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route108(): void {}

    #[OA\Post(path: '/api/platform/v1/modules', summary: 'POST modules', tags: ['Modules'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route109(): void {}

    #[OA\Post(path: '/api/platform/v1/modules/import', summary: 'POST modules/import', tags: ['Modules'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route110(): void {}

    #[OA\Patch(path: '/api/platform/v1/modules/{module_uuid}', summary: 'PATCH modules/{module_uuid}', tags: ['Modules'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route111(): void {}

    #[OA\Put(path: '/api/platform/v1/modules/{module_uuid}', summary: 'PUT modules/{module_uuid}', tags: ['Modules'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route112(): void {}

    #[OA\Delete(path: '/api/platform/v1/modules/bulk', summary: 'DELETE modules/bulk', tags: ['Modules'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route113(): void {}

    #[OA\Delete(path: '/api/platform/v1/modules/{module_uuid}', summary: 'DELETE modules/{module_uuid}', tags: ['Modules'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route114(): void {}

    #[OA\Post(path: '/api/platform/v1/modules/{module_uuid}/enable', summary: 'POST modules/{module_uuid}/enable', tags: ['Modules'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route115(): void {}

    #[OA\Post(path: '/api/platform/v1/modules/{module_uuid}/disable', summary: 'POST modules/{module_uuid}/disable', tags: ['Modules'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route116(): void {}

    #[OA\Put(path: '/api/platform/v1/modules/{module_uuid}/features', summary: 'PUT modules/{module_uuid}/features', tags: ['Modules'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route117(): void {}

    #[OA\Get(path: '/api/platform/v1/coupons', summary: 'GET coupons', tags: ['Coupons'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route118(): void {}

    #[OA\Get(path: '/api/platform/v1/coupons/{coupon_uuid}', summary: 'GET coupons/{coupon_uuid}', tags: ['Coupons'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route119(): void {}

    #[OA\Get(path: '/api/platform/v1/coupons/{coupon_uuid}/redemptions', summary: 'GET coupons/{coupon_uuid}/redemptions', tags: ['Coupons'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route120(): void {}

    #[OA\Post(path: '/api/platform/v1/coupons/export', summary: 'POST coupons/export', tags: ['Coupons'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route121(): void {}

    #[OA\Post(path: '/api/platform/v1/coupons', summary: 'POST coupons', tags: ['Coupons'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route122(): void {}

    #[OA\Post(path: '/api/platform/v1/coupons/import', summary: 'POST coupons/import', tags: ['Coupons'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route123(): void {}

    #[OA\Patch(path: '/api/platform/v1/coupons/{coupon_uuid}', summary: 'PATCH coupons/{coupon_uuid}', tags: ['Coupons'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route124(): void {}

    #[OA\Put(path: '/api/platform/v1/coupons/{coupon_uuid}', summary: 'PUT coupons/{coupon_uuid}', tags: ['Coupons'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route125(): void {}

    #[OA\Delete(path: '/api/platform/v1/coupons/bulk', summary: 'DELETE coupons/bulk', tags: ['Coupons'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route126(): void {}

    #[OA\Delete(path: '/api/platform/v1/coupons/{coupon_uuid}', summary: 'DELETE coupons/{coupon_uuid}', tags: ['Coupons'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route127(): void {}

    #[OA\Post(path: '/api/platform/v1/coupons/{coupon_uuid}/activate', summary: 'POST coupons/{coupon_uuid}/activate', tags: ['Coupons'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route128(): void {}

    #[OA\Post(path: '/api/platform/v1/coupons/{coupon_uuid}/deactivate', summary: 'POST coupons/{coupon_uuid}/deactivate', tags: ['Coupons'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route129(): void {}

    #[OA\Get(path: '/api/platform/v1/addons', summary: 'GET addons', tags: ['Plans'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route130(): void {}

    #[OA\Get(path: '/api/platform/v1/addons/{addon_uuid}', summary: 'GET addons/{addon_uuid}', tags: ['Plans'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route131(): void {}

    #[OA\Post(path: '/api/platform/v1/addons/export', summary: 'POST addons/export', tags: ['Plans'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route132(): void {}

    #[OA\Post(path: '/api/platform/v1/addons', summary: 'POST addons', tags: ['Plans'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route133(): void {}

    #[OA\Post(path: '/api/platform/v1/addons/import', summary: 'POST addons/import', tags: ['Plans'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route134(): void {}

    #[OA\Patch(path: '/api/platform/v1/addons/{addon_uuid}', summary: 'PATCH addons/{addon_uuid}', tags: ['Plans'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route135(): void {}

    #[OA\Put(path: '/api/platform/v1/addons/{addon_uuid}', summary: 'PUT addons/{addon_uuid}', tags: ['Plans'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route136(): void {}

    #[OA\Delete(path: '/api/platform/v1/addons/bulk', summary: 'DELETE addons/bulk', tags: ['Plans'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route137(): void {}

    #[OA\Delete(path: '/api/platform/v1/addons/{addon_uuid}', summary: 'DELETE addons/{addon_uuid}', tags: ['Plans'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route138(): void {}

    #[OA\Post(path: '/api/platform/v1/addons/{addon_uuid}/activate', summary: 'POST addons/{addon_uuid}/activate', tags: ['Plans'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route139(): void {}

    #[OA\Post(path: '/api/platform/v1/addons/{addon_uuid}/deactivate', summary: 'POST addons/{addon_uuid}/deactivate', tags: ['Plans'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route140(): void {}

    #[OA\Get(path: '/api/platform/v1/plans', summary: 'GET plans', tags: ['Plans'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route141(): void {}

    #[OA\Get(path: '/api/platform/v1/plans/{plan_uuid}', summary: 'GET plans/{plan_uuid}', tags: ['Plans'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route142(): void {}

    #[OA\Get(path: '/api/platform/v1/plans/{plan_uuid}/features', summary: 'GET plans/{plan_uuid}/features', tags: ['Plans'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route143(): void {}

    #[OA\Get(path: '/api/platform/v1/plans/{plan_uuid}/addons', summary: 'GET plans/{plan_uuid}/addons', tags: ['Plans'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route144(): void {}

    #[OA\Get(path: '/api/platform/v1/plans/{plan_uuid}/subscriptions', summary: 'GET plans/{plan_uuid}/subscriptions', tags: ['Plans'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route145(): void {}

    #[OA\Post(path: '/api/platform/v1/plans/export', summary: 'POST plans/export', tags: ['Plans'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route146(): void {}

    #[OA\Post(path: '/api/platform/v1/plans', summary: 'POST plans', tags: ['Plans'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route147(): void {}

    #[OA\Post(path: '/api/platform/v1/plans/import', summary: 'POST plans/import', tags: ['Plans'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route148(): void {}

    #[OA\Patch(path: '/api/platform/v1/plans/{plan_uuid}', summary: 'PATCH plans/{plan_uuid}', tags: ['Plans'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route149(): void {}

    #[OA\Put(path: '/api/platform/v1/plans/{plan_uuid}', summary: 'PUT plans/{plan_uuid}', tags: ['Plans'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route150(): void {}

    #[OA\Delete(path: '/api/platform/v1/plans/bulk', summary: 'DELETE plans/bulk', tags: ['Plans'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route151(): void {}

    #[OA\Delete(path: '/api/platform/v1/plans/{plan_uuid}', summary: 'DELETE plans/{plan_uuid}', tags: ['Plans'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route152(): void {}

    #[OA\Post(path: '/api/platform/v1/plans/{plan_uuid}/clone', summary: 'POST plans/{plan_uuid}/clone', tags: ['Plans'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route153(): void {}

    #[OA\Post(path: '/api/platform/v1/plans/{plan_uuid}/activate', summary: 'POST plans/{plan_uuid}/activate', tags: ['Plans'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route154(): void {}

    #[OA\Post(path: '/api/platform/v1/plans/{plan_uuid}/deactivate', summary: 'POST plans/{plan_uuid}/deactivate', tags: ['Plans'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route155(): void {}

    #[OA\Put(path: '/api/platform/v1/plans/{plan_uuid}/features', summary: 'PUT plans/{plan_uuid}/features', tags: ['Plans'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route156(): void {}

    #[OA\Put(path: '/api/platform/v1/plans/{plan_uuid}/addons', summary: 'PUT plans/{plan_uuid}/addons', tags: ['Plans'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route157(): void {}

    #[OA\Put(path: '/api/platform/v1/coupons/{coupon_uuid}/plans', summary: 'PUT coupons/{coupon_uuid}/plans', tags: ['Coupons'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route158(): void {}

    #[OA\Put(path: '/api/platform/v1/coupons/{coupon_uuid}/tenants', summary: 'PUT coupons/{coupon_uuid}/tenants', tags: ['Coupons'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route159(): void {}

    #[OA\Get(path: '/api/platform/v1/support/tickets', summary: 'GET support/tickets', tags: ['Support'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route160(): void {}

    #[OA\Post(path: '/api/platform/v1/support/tickets', summary: 'POST support/tickets', tags: ['Support'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route161(): void {}

    #[OA\Post(path: '/api/platform/v1/support/tickets/export', summary: 'POST support/tickets/export', tags: ['Support'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route162(): void {}

    #[OA\Get(path: '/api/platform/v1/support/tickets/{ticket_uuid}', summary: 'GET support/tickets/{ticket_uuid}', tags: ['Support'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route163(): void {}

    #[OA\Patch(path: '/api/platform/v1/support/tickets/{ticket_uuid}', summary: 'PATCH support/tickets/{ticket_uuid}', tags: ['Support'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route164(): void {}

    #[OA\Put(path: '/api/platform/v1/support/tickets/{ticket_uuid}', summary: 'PUT support/tickets/{ticket_uuid}', tags: ['Support'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route165(): void {}

    #[OA\Post(path: '/api/platform/v1/support/tickets/{ticket_uuid}/assign', summary: 'POST support/tickets/{ticket_uuid}/assign', tags: ['Support'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route166(): void {}

    #[OA\Post(path: '/api/platform/v1/support/tickets/{ticket_uuid}/comments', summary: 'POST support/tickets/{ticket_uuid}/comments', tags: ['Support'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route167(): void {}

    #[OA\Post(path: '/api/platform/v1/support/tickets/{ticket_uuid}/attachments', summary: 'POST support/tickets/{ticket_uuid}/attachments', tags: ['Support'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route168(): void {}

    #[OA\Post(path: '/api/platform/v1/support/tickets/{ticket_uuid}/close', summary: 'POST support/tickets/{ticket_uuid}/close', tags: ['Support'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route169(): void {}

    #[OA\Post(path: '/api/platform/v1/support/tickets/{ticket_uuid}/reopen', summary: 'POST support/tickets/{ticket_uuid}/reopen', tags: ['Support'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route170(): void {}

    #[OA\Get(path: '/api/platform/v1/support/knowledge-base/categories', summary: 'GET support/knowledge-base/categories', tags: ['Support'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route171(): void {}

    #[OA\Get(path: '/api/platform/v1/support/knowledge-base/articles', summary: 'GET support/knowledge-base/articles', tags: ['Support'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route172(): void {}

    #[OA\Get(path: '/api/platform/v1/support/knowledge-base/articles/{article_uuid}', summary: 'GET support/knowledge-base/articles/{article_uuid}', tags: ['Support'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route173(): void {}

    #[OA\Post(path: '/api/platform/v1/support/knowledge-base/categories', summary: 'POST support/knowledge-base/categories', tags: ['Support'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route174(): void {}

    #[OA\Patch(path: '/api/platform/v1/support/knowledge-base/categories/{category_uuid}', summary: 'PATCH support/knowledge-base/categories/{category_uuid}', tags: ['Support'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route175(): void {}

    #[OA\Put(path: '/api/platform/v1/support/knowledge-base/categories/{category_uuid}', summary: 'PUT support/knowledge-base/categories/{category_uuid}', tags: ['Support'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route176(): void {}

    #[OA\Post(path: '/api/platform/v1/support/knowledge-base/articles', summary: 'POST support/knowledge-base/articles', tags: ['Support'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route177(): void {}

    #[OA\Patch(path: '/api/platform/v1/support/knowledge-base/articles/{article_uuid}', summary: 'PATCH support/knowledge-base/articles/{article_uuid}', tags: ['Support'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route178(): void {}

    #[OA\Put(path: '/api/platform/v1/support/knowledge-base/articles/{article_uuid}', summary: 'PUT support/knowledge-base/articles/{article_uuid}', tags: ['Support'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route179(): void {}

    #[OA\Post(path: '/api/platform/v1/support/knowledge-base/articles/{article_uuid}/publish', summary: 'POST support/knowledge-base/articles/{article_uuid}/publish', tags: ['Support'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route180(): void {}

    #[OA\Post(path: '/api/platform/v1/support/knowledge-base/articles/{article_uuid}/unpublish', summary: 'POST support/knowledge-base/articles/{article_uuid}/unpublish', tags: ['Support'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route181(): void {}

    #[OA\Post(path: '/api/platform/v1/support/knowledge-base/articles/{article_uuid}/archive', summary: 'POST support/knowledge-base/articles/{article_uuid}/archive', tags: ['Support'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route182(): void {}

    #[OA\Get(path: '/api/platform/v1/monitoring/services', summary: 'GET monitoring/services', tags: ['Monitoring'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route183(): void {}

    #[OA\Get(path: '/api/platform/v1/monitoring/services/{service_code}/logs', summary: 'GET monitoring/services/{service_code}/logs', tags: ['Monitoring'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route184(): void {}

    #[OA\Get(path: '/api/platform/v1/monitoring/queue-jobs', summary: 'GET monitoring/queue-jobs', tags: ['Monitoring'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route185(): void {}

    #[OA\Get(path: '/api/platform/v1/monitoring/queue-jobs/{job_id}', summary: 'GET monitoring/queue-jobs/{job_id}', tags: ['Monitoring'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route186(): void {}

    #[OA\Get(path: '/api/platform/v1/monitoring/scheduler-logs', summary: 'GET monitoring/scheduler-logs', tags: ['Monitoring'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route187(): void {}

    #[OA\Get(path: '/api/platform/v1/monitoring/api-request-logs', summary: 'GET monitoring/api-request-logs', tags: ['Monitoring'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route188(): void {}

    #[OA\Get(path: '/api/platform/v1/monitoring/alerts', summary: 'GET monitoring/alerts', tags: ['Monitoring'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route189(): void {}

    #[OA\Get(path: '/api/platform/v1/monitoring/incidents', summary: 'GET monitoring/incidents', tags: ['Monitoring'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route190(): void {}

    #[OA\Get(path: '/api/platform/v1/monitoring/incidents/{incident_id}', summary: 'GET monitoring/incidents/{incident_id}', tags: ['Monitoring'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route191(): void {}

    #[OA\Get(path: '/api/platform/v1/monitoring/tenant-usage-snapshots', summary: 'GET monitoring/tenant-usage-snapshots', tags: ['Monitoring'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route192(): void {}

    #[OA\Post(path: '/api/platform/v1/monitoring/queue-jobs/{job_id}/retry', summary: 'POST monitoring/queue-jobs/{job_id}/retry', tags: ['Monitoring'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route193(): void {}

    #[OA\Delete(path: '/api/platform/v1/monitoring/queue-jobs/{job_id}', summary: 'DELETE monitoring/queue-jobs/{job_id}', tags: ['Monitoring'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route194(): void {}

    #[OA\Post(path: '/api/platform/v1/monitoring/alerts/{alert_id}/resolve', summary: 'POST monitoring/alerts/{alert_id}/resolve', tags: ['Monitoring'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route195(): void {}

    #[OA\Post(path: '/api/platform/v1/monitoring/incidents', summary: 'POST monitoring/incidents', tags: ['Monitoring'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route196(): void {}

    #[OA\Patch(path: '/api/platform/v1/monitoring/incidents/{incident_id}', summary: 'PATCH monitoring/incidents/{incident_id}', tags: ['Monitoring'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route197(): void {}

    #[OA\Put(path: '/api/platform/v1/monitoring/incidents/{incident_id}', summary: 'PUT monitoring/incidents/{incident_id}', tags: ['Monitoring'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route198(): void {}

    #[OA\Post(path: '/api/platform/v1/monitoring/incidents/{incident_id}/resolve', summary: 'POST monitoring/incidents/{incident_id}/resolve', tags: ['Monitoring'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route199(): void {}

    #[OA\Get(path: '/api/platform/v1/providers', summary: 'GET providers', tags: ['Integrations'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route200(): void {}

    #[OA\Post(path: '/api/platform/v1/providers', summary: 'POST providers', tags: ['Integrations'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route201(): void {}

    #[OA\Patch(path: '/api/platform/v1/providers/{provider_code}', summary: 'PATCH providers/{provider_code}', tags: ['Integrations'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route202(): void {}

    #[OA\Put(path: '/api/platform/v1/providers/{provider_code}', summary: 'PUT providers/{provider_code}', tags: ['Integrations'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route203(): void {}

    #[OA\Get(path: '/api/platform/v1/platform', summary: 'GET platform', tags: ['Platform Settings'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route204(): void {}

    #[OA\Get(path: '/api/platform/v1/notification-templates', summary: 'GET notification-templates', tags: ['Operations'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route205(): void {}

    #[OA\Get(path: '/api/platform/v1/backups', summary: 'GET backups', tags: ['Operations'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route206(): void {}

    #[OA\Get(path: '/api/platform/v1/backups/runs', summary: 'GET backups/runs', tags: ['Operations'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route207(): void {}

    #[OA\Get(path: '/api/platform/v1/backups/runs/{run_uuid}/download', summary: 'GET backups/runs/{run_uuid}/download', tags: ['Operations'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route208(): void {}

    #[OA\Get(path: '/api/platform/v1/backups/runs/{run_uuid}', summary: 'GET backups/runs/{run_uuid}', tags: ['Operations'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route209(): void {}

    #[OA\Put(path: '/api/platform/v1/platform', summary: 'PUT platform', tags: ['Platform Settings'], security: [['bearerAuth' => []]], requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(type: 'object', required: ['settings'], properties: [
        new OA\Property(property: 'settings', type: 'array', minItems: 1, items: new OA\Items(type: 'object', required: ['group', 'key'], properties: [
            new OA\Property(property: 'group', type: 'string', enum: ['general', 'security', 'billing', 'email', 'storage', 'queue', 'integration'], maxLength: 100),
            new OA\Property(property: 'key', type: 'string', pattern: '^[a-z][a-z0-9_.-]*$', maxLength: 150),
            new OA\Property(property: 'value', nullable: true),
            new OA\Property(property: 'value_type', type: 'string', enum: ['string', 'integer', 'boolean', 'number', 'json']),
            new OA\Property(property: 'is_encrypted', type: 'boolean'),
        ])),
    ])), responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route210(): void {}

    #[OA\Post(path: '/api/platform/v1/notification-templates', summary: 'POST notification-templates', tags: ['Operations'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route211(): void {}

    #[OA\Patch(path: '/api/platform/v1/notification-templates/{template_uuid}', summary: 'PATCH notification-templates/{template_uuid}', tags: ['Operations'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route212(): void {}

    #[OA\Put(path: '/api/platform/v1/notification-templates/{template_uuid}', summary: 'PUT notification-templates/{template_uuid}', tags: ['Operations'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route213(): void {}

    #[OA\Put(path: '/api/platform/v1/backups', summary: 'PUT backups', tags: ['Operations'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route214(): void {}

    #[OA\Post(path: '/api/platform/v1/backups/run', summary: 'POST backups/run', tags: ['Operations'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route215(): void {}

    #[OA\Get(path: '/api/platform/v1/audit/activity-logs', summary: 'GET audit/activity-logs', tags: ['Audit'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route216(): void {}

    #[OA\Get(path: '/api/platform/v1/audit/security-events', summary: 'GET audit/security-events', tags: ['Audit'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route217(): void {}

    #[OA\Post(path: '/api/platform/v1/audit/security-events/{event_id}/review', summary: 'POST audit/security-events/{event_id}/review', tags: ['Audit'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route218(): void {}

    #[OA\Post(path: '/api/platform/v1/audit/export', summary: 'POST audit/export', tags: ['Audit'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route219(): void {}

    #[OA\Get(path: '/api/platform/v1/legal/documents', summary: 'GET legal/documents', tags: ['Content'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route220(): void {}

    #[OA\Post(path: '/api/platform/v1/legal/documents', summary: 'POST legal/documents', tags: ['Content'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route221(): void {}

    #[OA\Get(path: '/api/platform/v1/legal/documents/{document_uuid}/acceptances', summary: 'GET legal/documents/{document_uuid}/acceptances', tags: ['Content'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route222(): void {}

    #[OA\Get(path: '/api/platform/v1/legal/documents/{document_uuid}', summary: 'GET legal/documents/{document_uuid}', tags: ['Content'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route223(): void {}

    #[OA\Patch(path: '/api/platform/v1/legal/documents/{document_uuid}', summary: 'PATCH legal/documents/{document_uuid}', tags: ['Content'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route224(): void {}

    #[OA\Put(path: '/api/platform/v1/legal/documents/{document_uuid}', summary: 'PUT legal/documents/{document_uuid}', tags: ['Content'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route225(): void {}

    #[OA\Post(path: '/api/platform/v1/legal/documents/{document_uuid}/publish', summary: 'POST legal/documents/{document_uuid}/publish', tags: ['Content'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route226(): void {}

    #[OA\Get(path: '/api/platform/v1/announcements', summary: 'GET announcements', tags: ['Content'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route227(): void {}

    #[OA\Post(path: '/api/platform/v1/announcements', summary: 'POST announcements', tags: ['Content'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route228(): void {}

    #[OA\Get(path: '/api/platform/v1/announcements/{announcement_uuid}', summary: 'GET announcements/{announcement_uuid}', tags: ['Content'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route229(): void {}

    #[OA\Patch(path: '/api/platform/v1/announcements/{announcement_uuid}', summary: 'PATCH announcements/{announcement_uuid}', tags: ['Content'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route230(): void {}

    #[OA\Put(path: '/api/platform/v1/announcements/{announcement_uuid}', summary: 'PUT announcements/{announcement_uuid}', tags: ['Content'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route231(): void {}

    #[OA\Post(path: '/api/platform/v1/announcements/{announcement_uuid}/publish', summary: 'POST announcements/{announcement_uuid}/publish', tags: ['Content'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route232(): void {}

    #[OA\Post(path: '/api/platform/v1/announcements/{announcement_uuid}/archive', summary: 'POST announcements/{announcement_uuid}/archive', tags: ['Content'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route233(): void {}

    #[OA\Delete(path: '/api/platform/v1/announcements/{announcement_uuid}', summary: 'DELETE announcements/{announcement_uuid}', tags: ['Content'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route234(): void {}

    #[OA\Get(path: '/api/platform/v1/webhook-endpoints', summary: 'GET webhook-endpoints', tags: ['Integrations'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route235(): void {}

    #[OA\Post(path: '/api/platform/v1/webhook-endpoints', summary: 'POST webhook-endpoints', tags: ['Integrations'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route236(): void {}

    #[OA\Get(path: '/api/platform/v1/webhook-endpoints/{endpoint_uuid}/deliveries', summary: 'GET webhook-endpoints/{endpoint_uuid}/deliveries', tags: ['Integrations'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route237(): void {}

    #[OA\Get(path: '/api/platform/v1/webhook-endpoints/{endpoint_uuid}', summary: 'GET webhook-endpoints/{endpoint_uuid}', tags: ['Integrations'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route238(): void {}

    #[OA\Patch(path: '/api/platform/v1/webhook-endpoints/{endpoint_uuid}', summary: 'PATCH webhook-endpoints/{endpoint_uuid}', tags: ['Integrations'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route239(): void {}

    #[OA\Put(path: '/api/platform/v1/webhook-endpoints/{endpoint_uuid}', summary: 'PUT webhook-endpoints/{endpoint_uuid}', tags: ['Integrations'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route240(): void {}

    #[OA\Delete(path: '/api/platform/v1/webhook-endpoints/{endpoint_uuid}', summary: 'DELETE webhook-endpoints/{endpoint_uuid}', tags: ['Integrations'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route241(): void {}

    #[OA\Get(path: '/api/platform/v1/webhook-deliveries/{delivery_uuid}', summary: 'GET webhook-deliveries/{delivery_uuid}', tags: ['Integrations'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route242(): void {}

    #[OA\Post(path: '/api/platform/v1/webhook-deliveries/{delivery_uuid}/retry', summary: 'POST webhook-deliveries/{delivery_uuid}/retry', tags: ['Integrations'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route243(): void {}

    #[OA\Get(path: '/api/platform/v1/api-tokens', summary: 'GET api-tokens', tags: ['API Tokens'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route244(): void {}

    #[OA\Post(path: '/api/platform/v1/api-tokens', summary: 'POST api-tokens', tags: ['API Tokens'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route245(): void {}

    #[OA\Get(path: '/api/platform/v1/api-tokens/{token_uuid}', summary: 'GET api-tokens/{token_uuid}', tags: ['API Tokens'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route246(): void {}

    #[OA\Post(path: '/api/platform/v1/api-tokens/{token_uuid}/rotate', summary: 'POST api-tokens/{token_uuid}/rotate', tags: ['API Tokens'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route247(): void {}

    #[OA\Post(path: '/api/platform/v1/api-tokens/{token_uuid}/revoke', summary: 'POST api-tokens/{token_uuid}/revoke', tags: ['API Tokens'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route248(): void {}

    #[OA\Get(path: '/api/platform/v1/files', summary: 'GET files', tags: ['Operations'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route249(): void {}

    #[OA\Get(path: '/api/platform/v1/files/{file_uuid}', summary: 'GET files/{file_uuid}', tags: ['Operations'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route250(): void {}

    #[OA\Get(path: '/api/platform/v1/files/{file_uuid}/download', summary: 'GET files/{file_uuid}/download', tags: ['Operations'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route251(): void {}

    #[OA\Get(path: '/api/platform/v1/attachments', summary: 'GET attachments', tags: ['Integrations'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route252(): void {}

    #[OA\Get(path: '/api/platform/v1/notes', summary: 'GET notes', tags: ['Operations'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route253(): void {}

    #[OA\Post(path: '/api/platform/v1/files', summary: 'POST files', tags: ['Operations'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route254(): void {}

    #[OA\Delete(path: '/api/platform/v1/files/{file_uuid}', summary: 'DELETE files/{file_uuid}', tags: ['Operations'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route255(): void {}

    #[OA\Post(path: '/api/platform/v1/attachments', summary: 'POST attachments', tags: ['Integrations'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route256(): void {}

    #[OA\Delete(path: '/api/platform/v1/attachments/{attachment_id}', summary: 'DELETE attachments/{attachment_id}', tags: ['Integrations'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route257(): void {}

    #[OA\Post(path: '/api/platform/v1/notes', summary: 'POST notes', tags: ['Operations'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route258(): void {}

    #[OA\Patch(path: '/api/platform/v1/notes/{note_uuid}', summary: 'PATCH notes/{note_uuid}', tags: ['Operations'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route259(): void {}

    #[OA\Put(path: '/api/platform/v1/notes/{note_uuid}', summary: 'PUT notes/{note_uuid}', tags: ['Operations'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route260(): void {}

    #[OA\Delete(path: '/api/platform/v1/notes/{note_uuid}', summary: 'DELETE notes/{note_uuid}', tags: ['Operations'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route261(): void {}

    #[OA\Get(path: '/api/platform/v1/activity-logs', summary: 'GET activity-logs', tags: ['Audit'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route262(): void {}

    #[OA\Get(path: '/api/platform/v1/activity-logs/{activity_id}/compare', summary: 'GET activity-logs/{activity_id}/compare', tags: ['Audit'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route263(): void {}

    #[OA\Get(path: '/api/platform/v1/tenant-integrations', summary: 'GET tenant-integrations', tags: ['Integrations'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route264(): void {}

    #[OA\Get(path: '/api/platform/v1/tenant-integrations/{integration_uuid}', summary: 'GET tenant-integrations/{integration_uuid}', tags: ['Integrations'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route265(): void {}

    #[OA\Get(path: '/api/platform/v1/tenant-integrations/{integration_uuid}/mappings', summary: 'GET tenant-integrations/{integration_uuid}/mappings', tags: ['Integrations'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route266(): void {}

    #[OA\Get(path: '/api/platform/v1/tenant-integrations/{integration_uuid}/rate-limits', summary: 'GET tenant-integrations/{integration_uuid}/rate-limits', tags: ['Integrations'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route267(): void {}

    #[OA\Get(path: '/api/platform/v1/webhooks', summary: 'GET webhooks', tags: ['Integrations'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route268(): void {}

    #[OA\Get(path: '/api/platform/v1/webhooks/{webhook_id}', summary: 'GET webhooks/{webhook_id}', tags: ['Integrations'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route269(): void {}

    #[OA\Get(path: '/api/platform/v1/webhooks/{webhook_id}/logs', summary: 'GET webhooks/{webhook_id}/logs', tags: ['Integrations'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route270(): void {}

    #[OA\Get(path: '/api/platform/v1/sync-jobs', summary: 'GET sync-jobs', tags: ['Operations'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route271(): void {}

    #[OA\Post(path: '/api/platform/v1/tenant-integrations', summary: 'POST tenant-integrations', tags: ['Integrations'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route272(): void {}

    #[OA\Patch(path: '/api/platform/v1/tenant-integrations/{integration_uuid}', summary: 'PATCH tenant-integrations/{integration_uuid}', tags: ['Integrations'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route273(): void {}

    #[OA\Put(path: '/api/platform/v1/tenant-integrations/{integration_uuid}', summary: 'PUT tenant-integrations/{integration_uuid}', tags: ['Integrations'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route274(): void {}

    #[OA\Post(path: '/api/platform/v1/tenant-integrations/{integration_uuid}/credentials', summary: 'POST tenant-integrations/{integration_uuid}/credentials', tags: ['Integrations'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route275(): void {}

    #[OA\Post(path: '/api/platform/v1/tenant-integrations/{integration_uuid}/test', summary: 'POST tenant-integrations/{integration_uuid}/test', tags: ['Integrations'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route276(): void {}

    #[OA\Post(path: '/api/platform/v1/tenant-integrations/{integration_uuid}/disconnect', summary: 'POST tenant-integrations/{integration_uuid}/disconnect', tags: ['Integrations'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route277(): void {}

    #[OA\Put(path: '/api/platform/v1/tenant-integrations/{integration_uuid}/mappings', summary: 'PUT tenant-integrations/{integration_uuid}/mappings', tags: ['Integrations'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route278(): void {}

    #[OA\Post(path: '/api/platform/v1/webhooks', summary: 'POST webhooks', tags: ['Integrations'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route279(): void {}

    #[OA\Patch(path: '/api/platform/v1/webhooks/{webhook_id}', summary: 'PATCH webhooks/{webhook_id}', tags: ['Integrations'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route280(): void {}

    #[OA\Put(path: '/api/platform/v1/webhooks/{webhook_id}', summary: 'PUT webhooks/{webhook_id}', tags: ['Integrations'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route281(): void {}

    #[OA\Delete(path: '/api/platform/v1/webhooks/{webhook_id}', summary: 'DELETE webhooks/{webhook_id}', tags: ['Integrations'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route282(): void {}

    #[OA\Post(path: '/api/platform/v1/webhook-logs/{log_id}/retry', summary: 'POST webhook-logs/{log_id}/retry', tags: ['Integrations'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route283(): void {}

    #[OA\Post(path: '/api/platform/v1/sync-jobs/{job_id}/retry', summary: 'POST sync-jobs/{job_id}/retry', tags: ['Operations'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route284(): void {}

    #[OA\Get(path: '/api/platform/v1/remote-login-sessions', summary: 'GET remote-login-sessions', tags: ['Security'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route285(): void {}

    #[OA\Get(path: '/api/platform/v1/remote-login-sessions/{session_uuid}', summary: 'GET remote-login-sessions/{session_uuid}', tags: ['Security'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route286(): void {}

    #[OA\Post(path: '/api/platform/v1/remote-login-sessions/{session_uuid}/end', summary: 'POST remote-login-sessions/{session_uuid}/end', tags: ['Security'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route287(): void {}

    #[OA\Get(path: '/api/platform/v1/reports/export-jobs', summary: 'GET reports/export-jobs', tags: ['Reports'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route288(): void {}

    #[OA\Get(path: '/api/platform/v1/reports/export-jobs/{job_uuid}', summary: 'GET reports/export-jobs/{job_uuid}', tags: ['Reports'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route289(): void {}

    #[OA\Get(path: '/api/platform/v1/reports/{report_code}', summary: 'GET reports/{report_code}', tags: ['Reports'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route290(): void {}

    #[OA\Post(path: '/api/platform/v1/reports/{report_code}/export', summary: 'POST reports/{report_code}/export', tags: ['Reports'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route291(): void {}

    #[OA\Get(path: '/api/platform/v1/tenants', summary: 'GET tenants', tags: ['Tenants'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route292(): void {}

    #[OA\Post(path: '/api/platform/v1/tenants', summary: 'POST tenants', tags: ['Tenants'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route293(): void {}

    #[OA\Get(path: '/api/platform/v1/tenants/{tenant_uuid}', summary: 'GET tenants/{tenant_uuid}', tags: ['Tenants'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route294(): void {}

    #[OA\Patch(path: '/api/platform/v1/tenants/{tenant_uuid}', summary: 'PATCH tenants/{tenant_uuid}', tags: ['Tenants'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route295(): void {}

    #[OA\Put(path: '/api/platform/v1/tenants/{tenant_uuid}', summary: 'PUT tenants/{tenant_uuid}', tags: ['Tenants'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route296(): void {}

    #[OA\Delete(path: '/api/platform/v1/tenants/bulk', summary: 'DELETE tenants/bulk', tags: ['Tenants'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route297(): void {}

    #[OA\Delete(path: '/api/platform/v1/tenants/{tenant_uuid}', summary: 'DELETE tenants/{tenant_uuid}', tags: ['Tenants'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route298(): void {}

    #[OA\Post(path: '/api/platform/v1/tenants/{tenant_uuid}/restore', summary: 'POST tenants/{tenant_uuid}/restore', tags: ['Tenants'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route299(): void {}

    #[OA\Post(path: '/api/platform/v1/tenants/{tenant_uuid}/activate', summary: 'POST tenants/{tenant_uuid}/activate', tags: ['Tenants'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route300(): void {}

    #[OA\Post(path: '/api/platform/v1/tenants/{tenant_uuid}/suspend', summary: 'POST tenants/{tenant_uuid}/suspend', tags: ['Tenants'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route301(): void {}

    #[OA\Post(path: '/api/platform/v1/tenants/{tenant_uuid}/reactivate', summary: 'POST tenants/{tenant_uuid}/reactivate', tags: ['Tenants'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route302(): void {}

    #[OA\Post(path: '/api/platform/v1/tenants/{tenant_uuid}/archive', summary: 'POST tenants/{tenant_uuid}/archive', tags: ['Tenants'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route303(): void {}

    #[OA\Post(path: '/api/platform/v1/tenants/{tenant_uuid}/extend-trial', summary: 'POST tenants/{tenant_uuid}/extend-trial', tags: ['Tenants'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route304(): void {}

    #[OA\Get(path: '/api/platform/v1/onboarding/tenants', summary: 'GET onboarding/tenants', tags: ['Tenants'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route305(): void {}

    #[OA\Get(path: '/api/platform/v1/onboarding/tenants/{tenant_uuid}', summary: 'GET onboarding/tenants/{tenant_uuid}', tags: ['Tenants'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route306(): void {}

    #[OA\Put(path: '/api/platform/v1/onboarding/tenants/{tenant_uuid}/steps/{step_code}', summary: 'PUT onboarding/tenants/{tenant_uuid}/steps/{step_code}', tags: ['Tenants'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route307(): void {}

    #[OA\Get(path: '/api/platform/v1/trials', summary: 'GET trials', tags: ['Tenants'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route308(): void {}

    #[OA\Post(path: '/api/platform/v1/trials/{tenant_uuid}/extend', summary: 'POST trials/{tenant_uuid}/extend', tags: ['Tenants'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route309(): void {}

    #[OA\Post(path: '/api/platform/v1/trials/{tenant_uuid}/convert', summary: 'POST trials/{tenant_uuid}/convert', tags: ['Tenants'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route310(): void {}

    #[OA\Post(path: '/api/platform/v1/tenants/{tenant_uuid}/change-plan', summary: 'POST tenants/{tenant_uuid}/change-plan', tags: ['Tenants'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route311(): void {}

    #[OA\Post(path: '/api/platform/v1/tenants/{tenant_uuid}/reset-owner-password', summary: 'POST tenants/{tenant_uuid}/reset-owner-password', tags: ['Tenants'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route312(): void {}

    #[OA\Post(path: '/api/platform/v1/tenants/{tenant_uuid}/payment-order', summary: 'POST tenants/{tenant_uuid}/payment-order', tags: ['Tenants'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route313(): void {}

    #[OA\Post(path: '/api/platform/v1/tenants/{tenant_uuid}/impersonate', summary: 'POST tenants/{tenant_uuid}/impersonate', tags: ['Tenants'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route314(): void {}

    #[OA\Delete(path: '/api/platform/v1/tenants/{tenant_uuid}/impersonate/{session_uuid}', summary: 'DELETE tenants/{tenant_uuid}/impersonate/{session_uuid}', tags: ['Tenants'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route315(): void {}

    #[OA\Put(path: '/api/platform/v1/tenants/{tenant_uuid}/modules', summary: 'PUT tenants/{tenant_uuid}/modules', tags: ['Tenants'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route316(): void {}

    #[OA\Get(path: '/api/platform/v1/tenants/{tenant_uuid}/module-entitlements', summary: 'GET tenants/{tenant_uuid}/module-entitlements', tags: ['Tenants'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route317(): void {}

    #[OA\Put(path: '/api/platform/v1/tenants/{tenant_uuid}/modules/{module_code}', summary: 'PUT tenants/{tenant_uuid}/modules/{module_code}', tags: ['Tenants'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route318(): void {}

    #[OA\Get(path: '/api/platform/v1/tenants/{tenant_uuid}/{tab}', summary: 'GET tenants/{tenant_uuid}/{tab}', tags: ['Tenants'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route319(): void {}

    #[OA\Get(path: '/api/platform/v1/subscriptions', summary: 'GET subscriptions', tags: ['Subscriptions'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route320(): void {}

    #[OA\Get(path: '/api/platform/v1/subscriptions/{subscription_uuid}/usage', summary: 'GET subscriptions/{subscription_uuid}/usage', tags: ['Subscriptions'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route321(): void {}

    #[OA\Get(path: '/api/platform/v1/subscriptions/{subscription_uuid}/history', summary: 'GET subscriptions/{subscription_uuid}/history', tags: ['Subscriptions'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route322(): void {}

    #[OA\Post(path: '/api/platform/v1/subscriptions', summary: 'POST subscriptions', tags: ['Subscriptions'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route323(): void {}

    #[OA\Post(path: '/api/platform/v1/subscriptions/export', summary: 'POST subscriptions/export', tags: ['Subscriptions'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route324(): void {}

    #[OA\Patch(path: '/api/platform/v1/subscriptions/{subscription_uuid}', summary: 'PATCH subscriptions/{subscription_uuid}', tags: ['Subscriptions'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route325(): void {}

    #[OA\Put(path: '/api/platform/v1/subscriptions/{subscription_uuid}', summary: 'PUT subscriptions/{subscription_uuid}', tags: ['Subscriptions'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route326(): void {}

    #[OA\Post(path: '/api/platform/v1/subscriptions/{subscription_uuid}/upgrade', summary: 'POST subscriptions/{subscription_uuid}/upgrade', tags: ['Subscriptions'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route327(): void {}

    #[OA\Post(path: '/api/platform/v1/subscriptions/{subscription_uuid}/downgrade', summary: 'POST subscriptions/{subscription_uuid}/downgrade', tags: ['Subscriptions'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route328(): void {}

    #[OA\Post(path: '/api/platform/v1/subscriptions/{subscription_uuid}/renew', summary: 'POST subscriptions/{subscription_uuid}/renew', tags: ['Subscriptions'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route329(): void {}

    #[OA\Post(path: '/api/platform/v1/subscriptions/{subscription_uuid}/pause', summary: 'POST subscriptions/{subscription_uuid}/pause', tags: ['Subscriptions'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route330(): void {}

    #[OA\Post(path: '/api/platform/v1/subscriptions/{subscription_uuid}/resume', summary: 'POST subscriptions/{subscription_uuid}/resume', tags: ['Subscriptions'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route331(): void {}

    #[OA\Post(path: '/api/platform/v1/subscriptions/{subscription_uuid}/cancel', summary: 'POST subscriptions/{subscription_uuid}/cancel', tags: ['Subscriptions'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route332(): void {}

    #[OA\Post(path: '/api/platform/v1/subscriptions/{subscription_uuid}/addons', summary: 'POST subscriptions/{subscription_uuid}/addons', tags: ['Subscriptions'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route333(): void {}

    #[OA\Patch(path: '/api/platform/v1/subscriptions/{subscription_uuid}/addons/{addon_id}', summary: 'PATCH subscriptions/{subscription_uuid}/addons/{addon_id}', tags: ['Subscriptions'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route334(): void {}

    #[OA\Put(path: '/api/platform/v1/subscriptions/{subscription_uuid}/addons/{addon_id}', summary: 'PUT subscriptions/{subscription_uuid}/addons/{addon_id}', tags: ['Subscriptions'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route335(): void {}

    #[OA\Delete(path: '/api/platform/v1/subscriptions/{subscription_uuid}/addons/{addon_id}', summary: 'DELETE subscriptions/{subscription_uuid}/addons/{addon_id}', tags: ['Subscriptions'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route336(): void {}

    #[OA\Post(path: '/api/platform/v1/subscriptions/{subscription_uuid}/apply-coupon', summary: 'POST subscriptions/{subscription_uuid}/apply-coupon', tags: ['Subscriptions'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route337(): void {}

    #[OA\Delete(path: '/api/platform/v1/subscriptions/{subscription_uuid}/coupons/{coupon_uuid}', summary: 'DELETE subscriptions/{subscription_uuid}/coupons/{coupon_uuid}', tags: ['Subscriptions'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route338(): void {}

    #[OA\Post(path: '/api/platform/v1/subscriptions/{subscription_uuid}/invoice', summary: 'POST subscriptions/{subscription_uuid}/invoice', tags: ['Subscriptions'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route339(): void {}

    #[OA\Get(path: '/api/platform/v1/subscriptions/{subscription_uuid}', summary: 'GET subscriptions/{subscription_uuid}', tags: ['Subscriptions'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route340(): void {}

    #[OA\Get(path: '/api/platform/v1/billing/invoices', summary: 'GET billing/invoices', tags: ['Billing'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route341(): void {}

    #[OA\Get(path: '/api/platform/v1/billing/invoices/{invoice_uuid}', summary: 'GET billing/invoices/{invoice_uuid}', tags: ['Billing'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route342(): void {}

    #[OA\Get(path: '/api/platform/v1/billing/invoices/{invoice_uuid}/pdf', summary: 'GET billing/invoices/{invoice_uuid}/pdf', tags: ['Billing'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route343(): void {}

    #[OA\Post(path: '/api/platform/v1/billing/invoices', summary: 'POST billing/invoices', tags: ['Billing'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route344(): void {}

    #[OA\Post(path: '/api/platform/v1/billing/invoices/export', summary: 'POST billing/invoices/export', tags: ['Billing'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route345(): void {}

    #[OA\Patch(path: '/api/platform/v1/billing/invoices/{invoice_uuid}', summary: 'PATCH billing/invoices/{invoice_uuid}', tags: ['Billing'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route346(): void {}

    #[OA\Put(path: '/api/platform/v1/billing/invoices/{invoice_uuid}', summary: 'PUT billing/invoices/{invoice_uuid}', tags: ['Billing'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route347(): void {}

    #[OA\Delete(path: '/api/platform/v1/billing/invoices/{invoice_uuid}', summary: 'DELETE billing/invoices/{invoice_uuid}', tags: ['Billing'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route348(): void {}

    #[OA\Post(path: '/api/platform/v1/billing/invoices/{invoice_uuid}/send', summary: 'POST billing/invoices/{invoice_uuid}/send', tags: ['Billing'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route349(): void {}

    #[OA\Post(path: '/api/platform/v1/billing/invoices/{invoice_uuid}/payments', summary: 'POST billing/invoices/{invoice_uuid}/payments', tags: ['Billing'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route350(): void {}

    #[OA\Get(path: '/api/platform/v1/billing/payments', summary: 'GET billing/payments', tags: ['Billing'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route351(): void {}

    #[OA\Get(path: '/api/platform/v1/billing/payments/{payment_uuid}', summary: 'GET billing/payments/{payment_uuid}', tags: ['Billing'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route352(): void {}

    #[OA\Get(path: '/api/platform/v1/billing/refunds', summary: 'GET billing/refunds', tags: ['Billing'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route353(): void {}

    #[OA\Get(path: '/api/platform/v1/billing/refunds/{refund_uuid}', summary: 'GET billing/refunds/{refund_uuid}', tags: ['Billing'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route354(): void {}

    #[OA\Post(path: '/api/platform/v1/billing/payments', summary: 'POST billing/payments', tags: ['Billing'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route355(): void {}

    #[OA\Post(path: '/api/platform/v1/billing/payments/export', summary: 'POST billing/payments/export', tags: ['Billing'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route356(): void {}

    #[OA\Post(path: '/api/platform/v1/billing/payments/{payment_uuid}/retry', summary: 'POST billing/payments/{payment_uuid}/retry', tags: ['Billing'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route357(): void {}

    #[OA\Post(path: '/api/platform/v1/billing/payments/{payment_uuid}/reconcile', summary: 'POST billing/payments/{payment_uuid}/reconcile', tags: ['Billing'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route358(): void {}

    #[OA\Post(path: '/api/platform/v1/billing/payments/{payment_uuid}/refund', summary: 'POST billing/payments/{payment_uuid}/refund', tags: ['Billing'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route359(): void {}

    #[OA\Post(path: '/api/platform/v1/billing/refunds', summary: 'POST billing/refunds', tags: ['Billing'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route360(): void {}

    #[OA\Post(path: '/api/platform/v1/billing/refunds/export', summary: 'POST billing/refunds/export', tags: ['Billing'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route361(): void {}

    #[OA\Post(path: '/api/platform/v1/billing/refunds/{refund_uuid}/retry', summary: 'POST billing/refunds/{refund_uuid}/retry', tags: ['Billing'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route362(): void {}

}
