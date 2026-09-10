<?php

namespace App\Swagger;

use OpenApi\Attributes as OA;

final class CommonApi
{
    #[OA\Get(path: '/api/common/v1/locations/countries', summary: 'List countries', tags: ['Common'], parameters: [
        new OA\Parameter(name: 'search', in: 'query', schema: new OA\Schema(type: 'string', maxLength: 100)),
        new OA\Parameter(name: 'status', in: 'query', schema: new OA\Schema(type: 'string', enum: ['active', 'inactive'], default: 'active')),
    ], responses: [new OA\Response(response: 200, description: 'Countries fetched successfully'), new OA\Response(response: 422, description: 'Validation error')])]
    public function countries(): void {}

    #[OA\Get(path: '/api/common/v1/locations/states', summary: 'List states by country', tags: ['Common'], parameters: [
        new OA\Parameter(name: 'country_id', in: 'query', required: true, schema: new OA\Schema(type: 'integer')),
        new OA\Parameter(name: 'search', in: 'query', schema: new OA\Schema(type: 'string', maxLength: 100)),
    ], responses: [new OA\Response(response: 200, description: 'States fetched successfully'), new OA\Response(response: 422, description: 'Validation error')])]
    public function states(): void {}

    #[OA\Get(path: '/api/common/v1/locations/cities', summary: 'List cities by state', tags: ['Common'], parameters: [
        new OA\Parameter(name: 'state_id', in: 'query', required: true, schema: new OA\Schema(type: 'integer')),
        new OA\Parameter(name: 'search', in: 'query', schema: new OA\Schema(type: 'string', maxLength: 100)),
    ], responses: [new OA\Response(response: 200, description: 'Cities fetched successfully'), new OA\Response(response: 422, description: 'Validation error')])]
    public function cities(): void {}

    #[OA\Get(path: '/api/common/v1/business-types', summary: 'List business types', tags: ['Common'], parameters: [
        new OA\Parameter(name: 'search', in: 'query', schema: new OA\Schema(type: 'string', maxLength: 100)),
        new OA\Parameter(name: 'code', in: 'query', schema: new OA\Schema(type: 'string', maxLength: 80)),
        new OA\Parameter(name: 'status', in: 'query', schema: new OA\Schema(type: 'string', enum: ['active', 'inactive'], default: 'active')),
    ], responses: [new OA\Response(response: 200, description: 'Business types fetched successfully'), new OA\Response(response: 422, description: 'Validation error')])]
    public function businessTypes(): void {}

    #[OA\Get(path: '/api/common/v1/industries', summary: 'List industries', tags: ['Common'], parameters: [
        new OA\Parameter(name: 'search', in: 'query', schema: new OA\Schema(type: 'string', maxLength: 100)),
        new OA\Parameter(name: 'code', in: 'query', schema: new OA\Schema(type: 'string', maxLength: 80)),
        new OA\Parameter(name: 'status', in: 'query', schema: new OA\Schema(type: 'string', enum: ['active', 'inactive'], default: 'active')),
    ], responses: [new OA\Response(response: 200, description: 'Industries fetched successfully'), new OA\Response(response: 422, description: 'Validation error')])]
    public function industries(): void {}

    #[OA\Get(path: '/api/common/v1/currencies', summary: 'List currencies', tags: ['Common'], parameters: [
        new OA\Parameter(name: 'search', in: 'query', schema: new OA\Schema(type: 'string', maxLength: 100)),
        new OA\Parameter(name: 'code', in: 'query', schema: new OA\Schema(type: 'string', minLength: 3, maxLength: 3)),
        new OA\Parameter(name: 'symbol', in: 'query', schema: new OA\Schema(type: 'string', maxLength: 10)),
        new OA\Parameter(name: 'decimal_places', in: 'query', schema: new OA\Schema(type: 'integer', minimum: 0, maximum: 10)),
        new OA\Parameter(name: 'status', in: 'query', schema: new OA\Schema(type: 'string', enum: ['active', 'inactive'], default: 'active')),
    ], responses: [new OA\Response(response: 200, description: 'Currencies fetched successfully'), new OA\Response(response: 422, description: 'Validation error')])]
    public function currencies(): void {}

    #[OA\Get(path: '/api/common/v1/languages', summary: 'List languages', tags: ['Common'], parameters: [
        new OA\Parameter(name: 'search', in: 'query', schema: new OA\Schema(type: 'string', maxLength: 100)),
        new OA\Parameter(name: 'code', in: 'query', schema: new OA\Schema(type: 'string', minLength: 2, maxLength: 2)),
        new OA\Parameter(name: 'iso3', in: 'query', schema: new OA\Schema(type: 'string', minLength: 3, maxLength: 3)),
        new OA\Parameter(name: 'status', in: 'query', schema: new OA\Schema(type: 'string', enum: ['active', 'inactive'], default: 'active')),
    ], responses: [new OA\Response(response: 200, description: 'Languages fetched successfully'), new OA\Response(response: 422, description: 'Validation error')])]
    public function languages(): void {}

    #[OA\Get(path: '/api/common/v1/timezones', summary: 'List timezones', tags: ['Common'], parameters: [
        new OA\Parameter(name: 'search', in: 'query', schema: new OA\Schema(type: 'string', maxLength: 100)),
        new OA\Parameter(name: 'identifier', in: 'query', schema: new OA\Schema(type: 'string', maxLength: 100)),
        new OA\Parameter(name: 'utc_offset', in: 'query', schema: new OA\Schema(type: 'string', maxLength: 10)),
        new OA\Parameter(name: 'status', in: 'query', schema: new OA\Schema(type: 'string', enum: ['active', 'inactive'], default: 'active')),
    ], responses: [new OA\Response(response: 200, description: 'Timezones fetched successfully'), new OA\Response(response: 422, description: 'Validation error')])]
    public function timezones(): void {}

    #[OA\Get(path: '/api/common/v1/dateformats', summary: 'List date formats', tags: ['Common'], parameters: [
        new OA\Parameter(name: 'search', in: 'query', schema: new OA\Schema(type: 'string', maxLength: 100)),
        new OA\Parameter(name: 'code', in: 'query', schema: new OA\Schema(type: 'string', maxLength: 50)),
        new OA\Parameter(name: 'format', in: 'query', schema: new OA\Schema(type: 'string', maxLength: 50)),
        new OA\Parameter(name: 'status', in: 'query', schema: new OA\Schema(type: 'string', enum: ['active', 'inactive'], default: 'active')),
    ], responses: [new OA\Response(response: 200, description: 'Date formats fetched successfully'), new OA\Response(response: 422, description: 'Validation error')])]
    public function dateFormats(): void {}

    #[OA\Get(path: '/api/common/v1/timeformats', summary: 'List time formats', tags: ['Common'], parameters: [
        new OA\Parameter(name: 'search', in: 'query', schema: new OA\Schema(type: 'string', maxLength: 100)),
        new OA\Parameter(name: 'code', in: 'query', schema: new OA\Schema(type: 'string', maxLength: 50)),
        new OA\Parameter(name: 'format', in: 'query', schema: new OA\Schema(type: 'string', maxLength: 50)),
        new OA\Parameter(name: 'status', in: 'query', schema: new OA\Schema(type: 'string', enum: ['active', 'inactive'], default: 'active')),
    ], responses: [new OA\Response(response: 200, description: 'Time formats fetched successfully'), new OA\Response(response: 422, description: 'Validation error')])]
    public function timeFormats(): void {}
}
