<?php

namespace App\Swagger;

use OpenApi\Attributes as OA;

final class ZZTenantApi
{
    #[OA\Post(path: '/api/tenant/v1/expenses/export', summary: 'POST api/tenant/v1/expenses/export', tags: ['Finance'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route1(): void {}

    #[OA\Post(path: '/api/tenant/v1/invoices/export', summary: 'POST api/tenant/v1/invoices/export', tags: ['Finance'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route2(): void {}

    #[OA\Post(path: '/api/tenant/v1/payments/export', summary: 'POST api/tenant/v1/payments/export', tags: ['Finance'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route3(): void {}

    #[OA\Get(path: '/api/tenant/v1/settings/branding', summary: 'GET api/tenant/v1/settings/branding', tags: ['Settings'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route4(): void {}

    #[OA\Head(path: '/api/tenant/v1/settings/branding', summary: 'HEAD api/tenant/v1/settings/branding', tags: ['Settings'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route5(): void {}

    #[OA\Put(path: '/api/tenant/v1/settings/branding', summary: 'PUT api/tenant/v1/settings/branding', tags: ['Settings'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route6(): void {}

    #[OA\Patch(path: '/api/tenant/v1/settings/branding', summary: 'PATCH api/tenant/v1/settings/branding', tags: ['Settings'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route7(): void {}

    #[OA\Get(path: '/api/tenant/v1/settings/communication', summary: 'GET api/tenant/v1/settings/communication', tags: ['Settings'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route8(): void {}

    #[OA\Head(path: '/api/tenant/v1/settings/communication', summary: 'HEAD api/tenant/v1/settings/communication', tags: ['Settings'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route9(): void {}

    #[OA\Put(path: '/api/tenant/v1/settings/communication', summary: 'PUT api/tenant/v1/settings/communication', tags: ['Settings'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route10(): void {}

    #[OA\Patch(path: '/api/tenant/v1/settings/communication', summary: 'PATCH api/tenant/v1/settings/communication', tags: ['Settings'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route11(): void {}

    #[OA\Get(path: '/api/tenant/v1/settings/company', summary: 'GET api/tenant/v1/settings/company', tags: ['Settings'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route12(): void {}

    #[OA\Head(path: '/api/tenant/v1/settings/company', summary: 'HEAD api/tenant/v1/settings/company', tags: ['Settings'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route13(): void {}

    #[OA\Put(path: '/api/tenant/v1/settings/company', summary: 'PUT api/tenant/v1/settings/company', tags: ['Settings'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route14(): void {}

    #[OA\Patch(path: '/api/tenant/v1/settings/company', summary: 'PATCH api/tenant/v1/settings/company', tags: ['Settings'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route15(): void {}

    #[OA\Get(path: '/api/tenant/v1/settings/crm', summary: 'GET api/tenant/v1/settings/crm', tags: ['Settings'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route16(): void {}

    #[OA\Head(path: '/api/tenant/v1/settings/crm', summary: 'HEAD api/tenant/v1/settings/crm', tags: ['Settings'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route17(): void {}

    #[OA\Put(path: '/api/tenant/v1/settings/crm', summary: 'PUT api/tenant/v1/settings/crm', tags: ['Settings'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route18(): void {}

    #[OA\Patch(path: '/api/tenant/v1/settings/crm', summary: 'PATCH api/tenant/v1/settings/crm', tags: ['Settings'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route19(): void {}

    #[OA\Get(path: '/api/tenant/v1/settings/general', summary: 'GET api/tenant/v1/settings/general', tags: ['Settings'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route20(): void {}

    #[OA\Head(path: '/api/tenant/v1/settings/general', summary: 'HEAD api/tenant/v1/settings/general', tags: ['Settings'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route21(): void {}

    #[OA\Put(path: '/api/tenant/v1/settings/general', summary: 'PUT api/tenant/v1/settings/general', tags: ['Settings'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route22(): void {}

    #[OA\Patch(path: '/api/tenant/v1/settings/general', summary: 'PATCH api/tenant/v1/settings/general', tags: ['Settings'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route23(): void {}

    #[OA\Get(path: '/api/tenant/v1/settings/hr', summary: 'GET api/tenant/v1/settings/hr', tags: ['Settings'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route24(): void {}

    #[OA\Head(path: '/api/tenant/v1/settings/hr', summary: 'HEAD api/tenant/v1/settings/hr', tags: ['Settings'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route25(): void {}

    #[OA\Put(path: '/api/tenant/v1/settings/hr', summary: 'PUT api/tenant/v1/settings/hr', tags: ['Settings'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route26(): void {}

    #[OA\Patch(path: '/api/tenant/v1/settings/hr', summary: 'PATCH api/tenant/v1/settings/hr', tags: ['Settings'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route27(): void {}

    #[OA\Get(path: '/api/tenant/v1/settings/integrations', summary: 'GET api/tenant/v1/settings/integrations', tags: ['Settings'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route28(): void {}

    #[OA\Head(path: '/api/tenant/v1/settings/integrations', summary: 'HEAD api/tenant/v1/settings/integrations', tags: ['Settings'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route29(): void {}

    #[OA\Put(path: '/api/tenant/v1/settings/integrations', summary: 'PUT api/tenant/v1/settings/integrations', tags: ['Settings'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route30(): void {}

    #[OA\Patch(path: '/api/tenant/v1/settings/integrations', summary: 'PATCH api/tenant/v1/settings/integrations', tags: ['Settings'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route31(): void {}

    #[OA\Get(path: '/api/tenant/v1/settings/localization', summary: 'GET api/tenant/v1/settings/localization', tags: ['Settings'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route32(): void {}

    #[OA\Head(path: '/api/tenant/v1/settings/localization', summary: 'HEAD api/tenant/v1/settings/localization', tags: ['Settings'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route33(): void {}

    #[OA\Put(path: '/api/tenant/v1/settings/localization', summary: 'PUT api/tenant/v1/settings/localization', tags: ['Settings'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route34(): void {}

    #[OA\Patch(path: '/api/tenant/v1/settings/localization', summary: 'PATCH api/tenant/v1/settings/localization', tags: ['Settings'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route35(): void {}

    #[OA\Get(path: '/api/tenant/v1/settings/security', summary: 'GET api/tenant/v1/settings/security', tags: ['Settings'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route36(): void {}

    #[OA\Head(path: '/api/tenant/v1/settings/security', summary: 'HEAD api/tenant/v1/settings/security', tags: ['Settings'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route37(): void {}

    #[OA\Put(path: '/api/tenant/v1/settings/security', summary: 'PUT api/tenant/v1/settings/security', tags: ['Settings'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route38(): void {}

    #[OA\Patch(path: '/api/tenant/v1/settings/security', summary: 'PATCH api/tenant/v1/settings/security', tags: ['Settings'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route39(): void {}

    #[OA\Get(path: '/api/tenant/v1/settings/storage', summary: 'GET api/tenant/v1/settings/storage', tags: ['Settings'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route40(): void {}

    #[OA\Head(path: '/api/tenant/v1/settings/storage', summary: 'HEAD api/tenant/v1/settings/storage', tags: ['Settings'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route41(): void {}

    #[OA\Put(path: '/api/tenant/v1/settings/storage', summary: 'PUT api/tenant/v1/settings/storage', tags: ['Settings'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route42(): void {}

    #[OA\Patch(path: '/api/tenant/v1/settings/storage', summary: 'PATCH api/tenant/v1/settings/storage', tags: ['Settings'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Successful response')])]
    public function route43(): void {}

}
