# Common API Requests

Base URL: http://127.0.0.1:8000/api/common/v1

All APIs below are public. No login, bearer token, or request body is required. Every request uses GET and sends inputs through URL query parameters.

Successful responses contain:
- success: true
- message: a description of the result
- data: an array of records

Validation errors return HTTP 422 with success, message, and errors. Unexpected errors return HTTP 500 with success, message, and data set to null.

## 1. List countries

Endpoint: GET /locations/countries

Purpose: Returns active countries ordered by sort order and name.

Query parameters:
- search - optional string, partial match against name, ISO2, ISO3, or phone code; maximum 100 characters.
- status - optional value active or inactive; defaults to active.

Request body: None.

Example:
    curl --request GET "http://127.0.0.1:8000/api/common/v1/locations/countries?search=united&status=active" --header "Accept: application/json"

## 2. List states by country

Endpoint: GET /locations/states

Purpose: Returns active states belonging to the supplied country.

Query parameters:
- country_id - required integer and must exist in countries.id.
- search - optional string, partial match against state name; maximum 100 characters.

Request body: None.

Example:
    curl --request GET "http://127.0.0.1:8000/api/common/v1/locations/states?country_id=1&search=cal" --header "Accept: application/json"

## 3. List cities by state

Endpoint: GET /locations/cities

Purpose: Returns active cities belonging to the supplied state.

Query parameters:
- state_id - required integer and must exist in states.id.
- search - optional string, partial match against city name; maximum 100 characters.

Request body: None.

Example:
    curl --request GET "http://127.0.0.1:8000/api/common/v1/locations/cities?state_id=1&search=burg" --header "Accept: application/json"

## 4. List business types

Endpoint: GET /business-types

Purpose: Returns active business types.

Query parameters:
- search - optional string, partial match against name; maximum 100 characters.
- code - optional exact business type code; maximum 80 characters.
- status - optional value active or inactive; defaults to active.

Request body: None.

Example:
    curl --request GET "http://127.0.0.1:8000/api/common/v1/business-types?search=limited&status=active" --header "Accept: application/json"

## 5. List industries

Endpoint: GET /industries

Purpose: Returns active industries.

Query parameters:
- search - optional string, partial match against name; maximum 100 characters.
- code - optional exact industry code; maximum 80 characters.
- status - optional value active or inactive; defaults to active.

Request body: None.

Example:
    curl --request GET "http://127.0.0.1:8000/api/common/v1/industries?search=technology" --header "Accept: application/json"

## 6. List currencies

Endpoint: GET /currencies

Purpose: Returns active currencies.

Query parameters:
- search - optional string, partial match against name, code, or symbol; maximum 100 characters.
- code - optional exact three-character currency code.
- symbol - optional exact symbol; maximum 10 characters.
- decimal_places - optional integer from 0 to 10.
- status - optional value active or inactive; defaults to active.

Request body: None.

Example:
    curl --request GET "http://127.0.0.1:8000/api/common/v1/currencies?code=USD&decimal_places=2" --header "Accept: application/json"

## 7. List languages

Endpoint: GET /languages

Purpose: Returns active languages.

Query parameters:
- search - optional string, partial match against name, code, or native name; maximum 100 characters.
- code - optional exact two-character ISO 639-1 code.
- iso3 - optional exact three-character ISO 639-3 code.
- status - optional value active or inactive; defaults to active.

Request body: None.

Example:
    curl --request GET "http://127.0.0.1:8000/api/common/v1/languages?search=english&code=en" --header "Accept: application/json"

## 8. List timezones

Endpoint: GET /timezones

Purpose: Returns active IANA timezones.

Query parameters:
- search - optional string, partial match against name, identifier, or UTC offset; maximum 100 characters.
- identifier - optional exact timezone identifier, such as Asia/Kolkata; maximum 100 characters.
- utc_offset - optional exact offset, such as +05:30; maximum 10 characters.
- status - optional value active or inactive; defaults to active.

Request body: None.

Example:
    curl --request GET "http://127.0.0.1:8000/api/common/v1/timezones?identifier=Asia%2FKolkata" --header "Accept: application/json"

## 9. List date formats

Endpoint: GET /dateformats

Purpose: Returns active date display formats.

Query parameters:
- search - optional string, partial match against name, code, or format; maximum 100 characters.
- code - optional exact format code; maximum 50 characters.
- format - optional exact PHP date format, such as Y-m-d; maximum 50 characters.
- status - optional value active or inactive; defaults to active.

Request body: None.

Example:
    curl --request GET "http://127.0.0.1:8000/api/common/v1/dateformats?format=Y-m-d" --header "Accept: application/json"

## 10. List time formats

Endpoint: GET /timeformats

Purpose: Returns active time display formats.

Query parameters:
- search - optional string, partial match against name, code, or format; maximum 100 characters.
- code - optional exact format code; maximum 50 characters.
- format - optional exact PHP time format, such as H:i; maximum 50 characters.
- status - optional value active or inactive; defaults to active.

Request body: None.

Example:
    curl --request GET "http://127.0.0.1:8000/api/common/v1/timeformats?format=H%3Ai" --header "Accept: application/json"


    
# Common Login APIs

Base URL: `{{BASE_URL}}/api/auth/v1`

These endpoints are public unless stated otherwise. Send `Accept: application/json`; JSON requests also send `Content-Type: application/json`.

Successful responses use the shared envelope:

```json
{
    "success": true,
    "message": "A description of the result.",
    "data": {}
}
```

Validation failures return HTTP 422 with `success`, `message`, and `errors`. Business failures return `success`, `message`, `data: null`, and an application error `code`.

## 1. Discover accounts

Endpoint: POST `/accounts/discover`

Purpose: Finds active platform and tenant accounts associated with an email address before password authentication.

Authentication: Public. This endpoint does not issue an access token.

Request body:

```json
{
    "email": "user@example.com",
    "device_name": "Chrome on Windows"
}
```

The `device_name` field is optional. The returned `discovery_token` expires after five minutes.

Response data contains `email`, `accounts`, `discovery_token`, and `expires_in_seconds`. Each account contains an opaque `account_ref`, account type, authentication surface, display label, tenant context, status, and last login time.

Example:

```bash
curl --request POST "{{BASE_URL}}/api/auth/v1/accounts/discover" \
  --header "Accept: application/json" \
  --header "Content-Type: application/json" \
  --data '{"email":"user@example.com","device_name":"Chrome on Windows"}'
```

## 2. Login

Endpoint: POST `/accounts/login`

Purpose: Authenticates the account selected during discovery and creates a Sanctum session.

Authentication: Public. The request must contain the valid discovery token and selected account reference.

Request body:

```json
{
    "email": "user@example.com",
    "discovery_token": "discovery-token",
    "account_ref": "account-reference",
    "password": "secret-password",
    "remember": false,
    "device_name": "Chrome on Windows"
}
```

When 2FA is disabled, the response contains an access token, token type, user, account type, and tenant context. Token abilities are populated from the account's active permissions and the token expires after twelve hours. When 2FA is enabled, the response contains `requires_2fa: true` and a five-minute challenge token instead of an access token.

Business errors: `INVALID_CREDENTIALS`, `DISCOVERY_TOKEN_EXPIRED`, and `INVALID_ACCOUNT_REF`.

Example:

```bash
curl --request POST "{{BASE_URL}}/api/auth/v1/accounts/login" \
  --header "Accept: application/json" \
  --header "Content-Type: application/json" \
  --data '{"email":"user@example.com","discovery_token":"discovery-token","account_ref":"account-reference","password":"secret-password","device_name":"Chrome on Windows"}'
```

## 3. Verify login 2FA

Endpoint: POST `/accounts/login/2fa`

Purpose: Completes a login that paused for TOTP verification.

Authentication: Public, but possession of the short-lived challenge token and a valid TOTP code is required.

Request body:

```json
{
    "challenge_token": "challenge-token",
    "code": "123456",
    "remember_device": false,
    "device_name": "Chrome on Windows"
}
```

A valid code consumes the challenge and returns the normal login session payload. Business errors are `TWO_FACTOR_CHALLENGE_EXPIRED` and `INVALID_2FA_CODE`.

Request body: Required JSON body shown above.


## 4. Get current session

Endpoint: GET `/me`

Purpose: Returns the authenticated platform or tenant account represented by the bearer token.

Authentication: `auth:sanctum` Bearer token.

Request body: None.

Example:

```bash
curl --request GET "{{BASE_URL}}/api/auth/v1/me" \
  --header "Accept: application/json" \
  --header "Authorization: Bearer {access_token}"
```

## 5. Logout

Endpoint: POST `/logout`

Purpose: Revokes the current Sanctum session. Set `all_devices` to true to revoke every session for the authenticated account.

Authentication: `auth:sanctum` Bearer token.

Request body: `{ "all_devices": true }` is optional.

Response data: `{ "logged_out": true }`.


## 6. Refresh token

Endpoint: POST `/refresh`

Purpose: Rotates the current Sanctum token while preserving its explicit permission abilities. The replacement expires after twelve hours.

Authentication: `auth:sanctum` Bearer token.

Request body: None.

# Platform Logged-in User APIs

Base URL: `{{BASE_URL}}/api/platform/v1`

All platform user endpoints except health require both `auth:sanctum` and `platform.token`. Only an authenticated `PlatformUser` may access them. Tenant bearer tokens are rejected.


Request body: None.

Example:

```bash
curl --request POST "{{BASE_URL}}/api/auth/v1/refresh" --header "Accept: application/json" --header "Authorization: Bearer {access_token}"
```
## 1. Platform health

Endpoint: GET `/health`

Purpose: Checks platform API readiness.

Authentication: Public.

Response data: `{ "scope": "platform", "version": "v1" }` with the message `Platform API is ready.`

Example:

```bash
curl --request GET "{{BASE_URL}}/api/platform/v1/health" --header "Accept: application/json"
```

## 2. Get platform profile

Endpoint: GET `/profile`

Purpose: Returns the authenticated platform user's identity, contact, locale, timezone, verification, 2FA, and status fields.

Authentication: `auth:sanctum` + `platform.token`.

Request body: None.


## 3. Update platform profile

Endpoint: PUT,PATCH `/profile`

Purpose: Updates the authenticated platform user's profile.

Authentication: `auth:sanctum` + `platform.token`.

Request body:

```json
{
    "first_name": "Alex",
    "last_name": "Morgan",
    "display_name": "Alex Morgan",
    "mobile": "+15551234567",
    "timezone": "Asia/Kolkata",
    "locale": "en"
}
```

All fields are optional for partial updates. The response data contains the refreshed `user` object. The update is recorded in the activity log.


## 4. Change platform password

Endpoint: PUT `/profile/password`

Purpose: Changes the authenticated platform user's password after verifying the current password.

Authentication: `auth:sanctum` + `platform.token`.

Request body:

```json
{
    "current_password": "old-password",
    "password": "new-password",
    "password_confirmation": "new-password"
}
```

The new password must be at least eight characters and confirmed. A wrong current password returns `INVALID_PASSWORD`. Successful changes are recorded in the activity log.


## 5. List platform sessions

Endpoint: GET `/profile/sessions`

Purpose: Lists the authenticated platform user's Sanctum sessions, including token ID, device name, abilities, last-used time, expiry, and creation time.

Authentication: `auth:sanctum` + `platform.token`.

Request body: None.


Request body: None.

Example:

```bash
curl --request GET "{{BASE_URL}}/api/platform/v1/profile/sessions" --header "Authorization: Bearer {access_token}"
```
## 6. Revoke platform session

Endpoint: DELETE `/profile/sessions/{sessionId}`

Purpose: Revokes one Sanctum session belonging to the authenticated platform user.

Authentication: `auth:sanctum` + `platform.token`.

Request body: None.

A session belonging to another user cannot be revoked. A missing session returns HTTP 404.

# Public Authentication and Platform APIs

Password recovery is public. The platform verification, 2FA, and preference endpoints are currently reachable without a bearer token and identify the platform user with `email` and `password`. Responses follow the standard `success`, `message`, `data` envelope. These endpoints do not use pagination; preferences are returned as a user-owned collection.


Request body: None.

Example:

```bash
curl --request DELETE "{{BASE_URL}}/api/platform/v1/profile/sessions/{sessionId}" --header "Authorization: Bearer {access_token}"
```
## 1. Forgot password

Endpoint: POST `/api/auth/v1/password/forgot`

Authentication: Public.

Request body:
```json
{"email":"user@example.com","account_ref":"optional","discovery_token":"optional"}
```

The response is generic to avoid account enumeration. In local environments, `data.reset_token` is returned for development.

```bash
curl --request POST "{{BASE_URL}}/api/auth/v1/password/forgot" --header "Accept: application/json" --header "Content-Type: application/json" --data '{"email":"user@example.com"}'
```

## 2. Reset password

Endpoint: POST `/api/auth/v1/password/reset`

Authentication: Public.

```bash
curl --request POST "{{BASE_URL}}/api/auth/v1/password/reset" --header "Accept: application/json" --header "Content-Type: application/json" --data '{"email":"user@example.com","token":"reset-token","password":"new-password","password_confirmation":"new-password"}'
```

## 3. Resend platform verification email

Endpoint: POST `/api/platform/v1/verify-email/resend`

Request body: `{"email":"admin@example.com","password":"current-password"}`.

```bash
curl --request POST "{{BASE_URL}}/api/platform/v1/verify-email/resend" --header "Accept: application/json" --header "Content-Type: application/json" --data '{"email":"admin@example.com","password":"current-password"}'
```

## 4. Enable platform 2FA

Endpoint: POST `/api/platform/v1/2fa/enable`

Request body: `{"email":"admin@example.com","password":"current-password"}`. Returns a `setup_token`, secret, and provisioning URI.

```bash
curl --request POST "{{BASE_URL}}/api/platform/v1/2fa/enable" --header "Accept: application/json" --header "Content-Type: application/json" --data '{"email":"admin@example.com","password":"current-password"}'
```

## 5. Confirm platform 2FA

Endpoint: POST `/api/platform/v1/2fa/confirm`

Request body: `{"setup_token":"setup-token","code":"123456"}`.

```bash
curl --request POST "{{BASE_URL}}/api/platform/v1/2fa/confirm" --header "Accept: application/json" --header "Content-Type: application/json" --data '{"setup_token":"setup-token","code":"123456"}'
```

## 6. Disable platform 2FA

Endpoint: POST `/api/platform/v1/2fa/disable`

Request body: `{"email":"admin@example.com","password":"current-password"}`.

```bash
curl --request POST "{{BASE_URL}}/api/platform/v1/2fa/disable" --header "Accept: application/json" --header "Content-Type: application/json" --data '{"email":"admin@example.com","password":"current-password"}'
```

## 7. Get platform preferences

Endpoint: GET `/api/platform/v1/settings/preferences`

Query parameters: `email` and `password` are required; `group` and `key` are optional filters. No pagination is used.

```bash
curl --get "{{BASE_URL}}/api/platform/v1/settings/preferences" --header "Accept: application/json" --data-urlencode "email=admin@example.com" --data-urlencode "password=current-password" --data-urlencode "group=ui" --data-urlencode "key=theme"
```

## 8. Update platform preferences

Endpoint: PUT `/api/platform/v1/settings/preferences`

Request body:
```json
{"email":"admin@example.com","password":"current-password","preferences":{"ui":{"theme":"dark","density":"compact"}}}
```

```bash
curl --request PUT "{{BASE_URL}}/api/platform/v1/settings/preferences" --header "Accept: application/json" --header "Content-Type: application/json" --data '{"email":"admin@example.com","password":"current-password","preferences":{"ui":{"theme":"dark","density":"compact"}}}'
```


# API Rate Limits

All API routes are rate limited. HTTP 429 is returned when a limit is exceeded.

| Route group | Limit | Key |
|---|---:|---|
| Common lookup APIs | 120/minute | Client IP |
| Login, discovery, and login 2FA | 10/minute | Client IP + normalized email |
| Password forgot | 5/minute | Client IP + normalized email |
| Password reset | 10/minute | Client IP + normalized email |
| Public platform security APIs | 10/minute | Client IP + normalized email |
| Read preferences | 60/minute | Client IP + normalized email |
| Write preferences | 30/minute | Client IP + normalized email |
| Authenticated account/platform APIs | 60/minute | Authenticated user ID |
| Platform health | 60/minute | Client IP |







# Platform Permission APIs

Base URL: `{{BASE_URL}}/api/platform/v1`

All permission endpoints require:

- `Authorization: Bearer {access_token}`
- an authenticated `PlatformUser`
- the ability listed for that endpoint

Successful responses use the standard `success`, `message`, and `data` envelope. The grouped endpoint is not paginated. The list endpoint is paginated and includes `meta`.

## 1. Get grouped permissions

Endpoint: GET `/api/platform/v1/permissions/grouped`

Purpose: Returns active platform permissions grouped by `module`, ordered by module and permission name.

Required ability: `platform_permission.view`

Request body: None.

Response data: An object whose keys are module names and whose values are permission arrays.

Example:

```bash
curl --request GET "{{BASE_URL}}/api/platform/v1/permissions/grouped" \
  --header "Accept: application/json" \
  --header "Authorization: Bearer {access_token}"
```

## 2. List permissions

Endpoint: GET `/api/platform/v1/permissions`

Purpose: Returns a paginated platform permission list. Each record includes `roles_count`.

Required ability: `platform_permission.view`

Query parameters:

- `search`: searches `name`, `display_name`, and `module`
- `filter[module]`
- `filter[guard_name]`
- `filter[status]`: `active` or `inactive`
- `sort`: `module`, `name`, `display_name`, `status`, `created_at`, or `updated_at`
- `direction`: `asc` or `desc`
- `per_page`: 1-100; defaults to 50
- `scope=selected`: limits results to `selected_ids[]`
- `selected_ids[]`: permission UUIDs when `scope=selected`

Example with filters:

```bash
curl --get "{{BASE_URL}}/api/platform/v1/permissions" \
  --header "Accept: application/json" \
  --header "Authorization: Bearer {access_token}" \
  --data-urlencode "search=invoice" \
  --data-urlencode "filter[module]=billing" \
  --data-urlencode "filter[status]=active" \
  --data-urlencode "sort=name" \
  --data-urlencode "direction=asc" \
  --data-urlencode "per_page=50"
```

Example selecting specific permissions:

```bash
curl --get "{{BASE_URL}}/api/platform/v1/permissions" \
  --header "Accept: application/json" \
  --header "Authorization: Bearer {access_token}" \
  --data-urlencode "scope=selected" \
  --data-urlencode "selected_ids[]=UUID-1" \
  --data-urlencode "selected_ids[]=UUID-2"
```

## 3. Create a permission

Endpoint: POST `/api/platform/v1/permissions`

Purpose: Creates a platform permission.

Required ability: `platform_permission.create`

Request body:

```json
{
    "module": "billing",
    "name": "billing.invoice.view",
    "display_name": "View invoices",
    "guard_name": "platform",
    "description": "Allows viewing invoices.",
    "is_system": false,
    "status": "active"
}
```

Required fields: `module` and `name`.

Optional fields: `display_name`, `guard_name`, `description`, `is_system`, and `status`. The default guard is `platform`; status defaults to `active`.

Success response: HTTP `201 Created`, with the record under `data.permission`.

Example:

```bash
curl --request POST "{{BASE_URL}}/api/platform/v1/permissions" \
  --header "Accept: application/json" \
  --header "Content-Type: application/json" \
  --header "Authorization: Bearer {access_token}" \
  --data '{
    "module": "billing",
    "name": "billing.invoice.view",
    "display_name": "View invoices",
    "guard_name": "platform",
    "description": "Allows viewing invoices.",
    "is_system": false,
    "status": "active"
  }'
```

## 4. Get a permission

Endpoint: GET `/api/platform/v1/permissions/{permission_uuid}`

Purpose: Returns one platform permission, including `roles_count`.

Required ability: `platform_permission.view`

Path parameter: `permission_uuid` - the permission UUID.

Request body: None.

Unknown UUID: HTTP `404` with code `PERMISSION_NOT_FOUND`.

Example:

```bash
curl --request GET "{{BASE_URL}}/api/platform/v1/permissions/{permission_uuid}" \
  --header "Accept: application/json" \
  --header "Authorization: Bearer {access_token}"
```

## 5. Update a permission

Endpoint: PUT|PATCH `/api/platform/v1/permissions/{permission_uuid}`

Purpose: Updates a platform permission and returns the refreshed record under `data.permission`.

Required ability: `platform_permission.edit`

Path parameter: `permission_uuid` - the permission UUID.

PATCH supports partial updates. PUT accepts the same fields:

```json
{
    "module": "billing",
    "name": "billing.invoice.view",
    "display_name": "View invoices",
    "guard_name": "platform",
    "description": "Updated description.",
    "status": "active"
}
```

System permissions cannot be renamed. This returns HTTP `403` with code `SYSTEM_PERMISSION_RENAME_FORBIDDEN`.

Example:

```bash
curl --request PATCH "{{BASE_URL}}/api/platform/v1/permissions/{permission_uuid}" \
  --header "Accept: application/json" \
  --header "Content-Type: application/json" \
  --header "Authorization: Bearer {access_token}" \
  --data '{
    "display_name": "View customer invoices",
    "description": "Updated description."
  }'
```

## 6. Delete a permission

Endpoint: DELETE `/api/platform/v1/permissions/{permission_uuid}`

Purpose: Deletes a platform permission.

Required ability: `platform_permission.delete`

Path parameter: `permission_uuid` - the permission UUID.

System permissions cannot be deleted. This returns HTTP `403` with code `SYSTEM_PERMISSION_DELETE_FORBIDDEN`.

Permissions assigned to one or more roles cannot be deleted. This returns HTTP `409` with code `PERMISSION_IN_USE`.

Example:

```bash
curl --request DELETE "{{BASE_URL}}/api/platform/v1/permissions/{permission_uuid}" \
  --header "Accept: application/json" \
  --header "Authorization: Bearer {access_token}"
```

## 7. Export permissions

Endpoint: POST `/api/platform/v1/permissions/export`

Purpose: Exports platform permissions as CSV.

Required ability: `platform_permission.view`

Request body:

```json
{
    "format": "csv",
    "delivery": "download",
    "scope": "filtered",
    "filters": {
        "module": "billing",
        "guard_name": "platform",
        "status": "active"
    },
    "sort": "name",
    "direction": "asc",
    "columns": ["uuid", "module", "name", "roles_count"],
    "selected_ids": [],
    "timezone": "Asia/Kolkata",
    "email_when_ready": false
}
```

- `format`: currently only `csv`
- `delivery`: `job` (default) or `download`
- `scope`: `filtered` (default) or `selected`
- `filters`: supports `module`, `guard_name`, and `status`
- `columns[]`: allowed values are `uuid`, `module`, `name`, `display_name`, `guard_name`, `description`, `is_system`, `status`, `roles_count`, `created_at`, and `updated_at`
- Immediate downloads are limited to 5,000 records.

When `delivery=job`, the response is HTTP `202 Accepted` with the queued `job_id`. When `delivery=download`, the response is a CSV file.

Example: queue an export:

```bash
curl --request POST "{{BASE_URL}}/api/platform/v1/permissions/export" \
  --header "Accept: application/json" \
  --header "Content-Type: application/json" \
  --header "Authorization: Bearer {access_token}" \
  --data '{
    "format": "csv",
    "delivery": "job",
    "scope": "filtered",
    "filters": {"module": "billing", "status": "active"},
    "sort": "name",
    "direction": "asc",
    "columns": ["uuid", "module", "name", "roles_count"],
    "email_when_ready": true
  }'
```

Example: download a CSV immediately:

```bash
curl --request POST "{{BASE_URL}}/api/platform/v1/permissions/export" \
  --header "Accept: text/csv" \
  --header "Content-Type: application/json" \
  --header "Authorization: Bearer {access_token}" \
  --output platform-permissions.csv \
  --data '{
    "format": "csv",
    "delivery": "download",
    "scope": "filtered",
    "filters": {"status": "active"},
    "columns": ["uuid", "module", "name", "display_name", "roles_count"]
  }'
```

## Common errors

- HTTP `401`: unauthenticated request.
- HTTP `403`: missing ability or forbidden system-permission operation.
- HTTP `404`: UUID not found.
- HTTP `409`: permission is assigned to one or more roles.
- HTTP `422`: validation failure or immediate export exceeds 5,000 records.


# Platform Role APIs

Base URL: `{{BASE_URL}}/api/platform/v1`

All role APIs require an authenticated platform Sanctum bearer token. Each endpoint also requires the ability shown below. Use `{role_uuid}` and `{platform_user_uuid}` as UUID values.

## 1. List roles

Endpoint: GET `/api/platform/v1/roles`

Ability: `platform_role.view`.

Returns a paginated list with `permissions_count` and `users_count`. Filters include `search`, `filter[status]`, `filter[guard_name]`, `filter[type]` (`system` or `custom`), `sort`, `direction`, `per_page` (default 25), and `scope=selected` with `selected_ids[]`.

```bash
curl --get "{{BASE_URL}}/api/platform/v1/roles" \\
  --header "Accept: application/json" \\
  --header "Authorization: Bearer {access_token}" \\
  --data-urlencode "search=manager" \\
  --data-urlencode "filter[status]=active" \\
  --data-urlencode "filter[type]=custom" \\
  --data-urlencode "sort=name" \\
  --data-urlencode "direction=asc" \\
  --data-urlencode "per_page=25"
```

## 2. Create a role

Endpoint: POST `/api/platform/v1/roles`

Ability: `platform_role.create`.

Required fields: `name`, `display_name`. Optional fields: `guard_name`, `description`, `is_system`, `status`, `permission_ids[]`, and `audit_reason`. Returns HTTP `201 Created` with the role under `data.role`.

```bash
curl --request POST "{{BASE_URL}}/api/platform/v1/roles" \\
  --header "Accept: application/json" \\
  --header "Content-Type: application/json" \\
  --header "Authorization: Bearer {access_token}" \\
  --data '{
    "name": "billing_manager",
    "display_name": "Billing Manager",
    "guard_name": "platform",
    "description": "Manages platform billing.",
    "status": "active",
    "permission_ids": ["{permission_uuid}"],
    "audit_reason": "Initial role setup"
  }'
```

## 3. Get a role

Endpoint: GET `/api/platform/v1/roles/{role_uuid}`

Ability: `platform_role.view`.

Returns the role with permissions, assigned platform users, `permissions_count`, and `users_count`. Unknown UUIDs return `404 ROLE_NOT_FOUND`.

```bash
curl --request GET "{{BASE_URL}}/api/platform/v1/roles/{role_uuid}" \\
  --header "Accept: application/json" \\
  --header "Authorization: Bearer {access_token}"
```

## 4. Update a role

Endpoint: PUT or PATCH `/api/platform/v1/roles/{role_uuid}`

Ability: `platform_role.edit`.

PATCH supports partial updates. Supplying `permission_ids[]` replaces the role's permission assignments. System roles cannot be renamed and return `403 SYSTEM_ROLE_RENAME_FORBIDDEN`.

```bash
curl --request PATCH "{{BASE_URL}}/api/platform/v1/roles/{role_uuid}" \\
  --header "Accept: application/json" \\
  --header "Content-Type: application/json" \\
  --header "Authorization: Bearer {access_token}" \\
  --data '{
    "display_name": "Senior Billing Manager",
    "description": "Updated role description.",
    "audit_reason": "Updated responsibilities"
  }'
```

## 5. Delete a role

Endpoint: DELETE `/api/platform/v1/roles/{role_uuid}`

Ability: `platform_role.delete`.

System roles return `403 SYSTEM_ROLE_DELETE_FORBIDDEN`. Roles assigned to users return `409 ROLE_IN_USE`. Successful deletion returns `data: null`.

```bash
curl --request DELETE "{{BASE_URL}}/api/platform/v1/roles/{role_uuid}" \\
  --header "Accept: application/json" \\
  --header "Authorization: Bearer {access_token}"
```

## 6. Clone a role

Endpoint: POST `/api/platform/v1/roles/{role_uuid}/clone`

Ability: `platform_role.create`.

Required fields: `name`, `display_name`. By default, permissions and description are copied. The clone is custom and defaults to `inactive`. Use `copy_permissions`, `copy_description`, `status`, and `audit_reason` to customize it. Returns HTTP `201 Created`.

```bash
curl --request POST "{{BASE_URL}}/api/platform/v1/roles/{role_uuid}/clone" \\
  --header "Accept: application/json" \\
  --header "Content-Type: application/json" \\
  --header "Authorization: Bearer {access_token}" \\
  --data '{
    "name": "billing_manager_copy",
    "display_name": "Billing Manager Copy",
    "copy_permissions": true,
    "copy_description": true,
    "status": "inactive"
  }'
```

## 7. Activate a role

Endpoint: POST `/api/platform/v1/roles/{role_uuid}/activate`

Ability: `platform_role.edit`.

```bash
curl --request POST "{{BASE_URL}}/api/platform/v1/roles/{role_uuid}/activate" \\
  --header "Accept: application/json" \\
  --header "Authorization: Bearer {access_token}"
```

## 8. Deactivate a role

Endpoint: POST `/api/platform/v1/roles/{role_uuid}/deactivate`

Ability: `platform_role.edit`.

```bash
curl --request POST "{{BASE_URL}}/api/platform/v1/roles/{role_uuid}/deactivate" \\
  --header "Accept: application/json" \\
  --header "Authorization: Bearer {access_token}"
```

## 9. List role permissions

Endpoint: GET `/api/platform/v1/roles/{role_uuid}/permissions`

Ability: `platform_role.view`.

Returns assigned permissions grouped by module and ordered by module and permission name.

```bash
curl --request GET "{{BASE_URL}}/api/platform/v1/roles/{role_uuid}/permissions" \\
  --header "Accept: application/json" \\
  --header "Authorization: Bearer {access_token}"
```

## 10. Replace role permissions

Endpoint: PUT `/api/platform/v1/roles/{role_uuid}/permissions`

Ability: `platform_role.edit`.

Request body requires `permission_ids[]` with at least one permission UUID. Existing assignments are synchronized to the supplied list. `audit_reason` is optional and limited to 500 characters.

```bash
curl --request PUT "{{BASE_URL}}/api/platform/v1/roles/{role_uuid}/permissions" \\
  --header "Accept: application/json" \\
  --header "Content-Type: application/json" \\
  --header "Authorization: Bearer {access_token}" \\
  --data '{
    "permission_ids": ["{permission_uuid_1}", "{permission_uuid_2}"],
    "audit_reason": "Align permissions with the new role scope"
  }'
```

## 11. Export roles

Endpoint: POST `/api/platform/v1/roles/export`

Ability: `platform_role.view`.

The default `delivery=job` queues an export and returns HTTP `202 Accepted`. Use `delivery=download` for an immediate CSV, limited to 5,000 records. Supported filters are `status`, `guard_name`, and `type`. Allowed columns are `uuid`, `name`, `display_name`, `guard_name`, `is_system`, `status`, `permissions_count`, `users_count`, `created_at`, and `updated_at`.

Queue export:

```bash
curl --request POST "{{BASE_URL}}/api/platform/v1/roles/export" \\
  --header "Accept: application/json" \\
  --header "Content-Type: application/json" \\
  --header "Authorization: Bearer {access_token}" \\
  --data '{
    "format": "csv",
    "delivery": "job",
    "scope": "filtered",
    "filters": {"status": "active", "type": "custom"},
    "sort": "name",
    "direction": "asc",
    "columns": ["uuid", "name", "display_name", "permissions_count", "users_count"],
    "email_when_ready": true
  }'
```

Download CSV:

```bash
curl --request POST "{{BASE_URL}}/api/platform/v1/roles/export" \\
  --header "Accept: text/csv" \\
  --header "Content-Type: application/json" \\
  --header "Authorization: Bearer {access_token}" \\
  --output platform-roles.csv \\
  --data '{
    "format": "csv",
    "delivery": "download",
    "scope": "filtered",
    "filters": {"status": "active"},
    "columns": ["uuid", "name", "display_name", "permissions_count", "users_count"]
  }'
```

## 12. List users assigned to a role

Endpoint: GET `/api/platform/v1/roles/{role_uuid}/users`

Ability: `platform_role.view`.

Returns assigned platform users ordered by display name. Each user includes `uuid`, `display_name`, `email`, `department`, and `status`.

```bash
curl --request GET "{{BASE_URL}}/api/platform/v1/roles/{role_uuid}/users" \\
  --header "Accept: application/json" \\
  --header "Authorization: Bearer {access_token}"
```

## 13. Assign users to a role

Endpoint: POST `/api/platform/v1/roles/{role_uuid}/users`

Ability: `platform_role.edit`.

Request body requires `platform_user_ids[]` containing platform-user UUIDs. Existing assignments are preserved. Returns the current assignment count under `data.users`.

```bash
curl --request POST "{{BASE_URL}}/api/platform/v1/roles/{role_uuid}/users" \\
  --header "Accept: application/json" \\
  --header "Content-Type: application/json" \\
  --header "Authorization: Bearer {access_token}" \\
  --data '{
    "platform_user_ids": ["{platform_user_uuid_1}", "{platform_user_uuid_2}"],
    "audit_reason": "Assign staff to billing role"
  }'
```

## 14. Remove a user from a role

Endpoint: DELETE `/api/platform/v1/roles/{role_uuid}/users/{platform_user_uuid}`

Ability: `platform_role.edit`.

Removes the assignment only; it does not delete the user or role.

```bash
curl --request DELETE "{{BASE_URL}}/api/platform/v1/roles/{role_uuid}/users/{platform_user_uuid}" \\
  --header "Accept: application/json" \\
  --header "Authorization: Bearer {access_token}"
```

## Common errors

- HTTP `401`: unauthenticated request.
- HTTP `403`: missing ability or forbidden system-role operation.
- HTTP `404`: role or platform-user UUID not found.
- HTTP `409`: role is assigned to users and cannot be deleted.
- HTTP `422`: request validation failed or immediate export exceeds 5,000 records.


# Platform Department APIs

Base URL: `{{BASE_URL}}/api/platform/v1`. These endpoints require an authenticated platform bearer token and the ability listed for each endpoint. Department lists are returned as non-paginated catalogue collections.

## 1. List departments

Endpoint: GET `/api/platform/v1/platform-departments`

Required ability: `platform_department.view`.

Supports `search` (name and code), `filter[status]`, `filter[parent_uuid]`, `sort` (`name`, `code`, `status`, `created_at`, `updated_at`), and `direction` (`asc` or `desc`). Each item includes `users_count`, `children_count`, and `teams_count`, plus parent and manager details.

```bash
curl --get "{{BASE_URL}}/api/platform/v1/platform-departments" --header "Accept: application/json" --header "Authorization: Bearer {access_token}" --data-urlencode "search=finance" --data-urlencode "filter[status]=active" --data-urlencode "sort=name" --data-urlencode "direction=asc"
```

## 2. Create a department

Endpoint: POST `/api/platform/v1/platform-departments`

Required ability: `platform_department.create`.

Required fields are `name` and `code`. Optional fields are `parent_id`/`parent_uuid`, `platform_manager_user_id`/`manager_platform_user_uuid`, and `status` (`active` or `inactive`). The parent department and manager must be active. Returns HTTP `201` with `data.department`.

```bash
curl --request POST "{{BASE_URL}}/api/platform/v1/platform-departments" --header "Accept: application/json" --header "Content-Type: application/json" --header "Authorization: Bearer {access_token}" --data '{"name":"Finance","code":"finance","parent_uuid":"{parent_uuid}","manager_platform_user_uuid":"{platform_user_uuid}","status":"active"}'
```

## 3. Get a department

Endpoint: GET `/api/platform/v1/platform-departments/{department_uuid}`

Required ability: `platform_department.view`.

Returns the department with parent, manager, and user/child/team counts.

```bash
curl --request GET "{{BASE_URL}}/api/platform/v1/platform-departments/{department_uuid}" --header "Accept: application/json" --header "Authorization: Bearer {access_token}"
```

## 4. Update a department

Endpoint: PUT or PATCH `/api/platform/v1/platform-departments/{department_uuid}`

Required ability: `platform_department.edit`.

`PATCH` supports partial updates. Supported fields are `name`, `code`, `parent_uuid`, `manager_platform_user_uuid`, and `status`. A department cannot be its own parent.

```bash
curl --request PATCH "{{BASE_URL}}/api/platform/v1/platform-departments/{department_uuid}" --header "Accept: application/json" --header "Content-Type: application/json" --header "Authorization: Bearer {access_token}" --data '{"name":"Finance Operations","status":"active"}'
```

## 5. Archive a department

Endpoint: DELETE `/api/platform/v1/platform-departments/{department_uuid}`

Required ability: `platform_department.delete`.

Archives the department by setting `status=inactive`. The row is retained and the response follows the standard delete envelope with `data: null`.

```bash
curl --request DELETE "{{BASE_URL}}/api/platform/v1/platform-departments/{department_uuid}" --header "Accept: application/json" --header "Authorization: Bearer {access_token}"
```

# Platform Designation APIs

Base URL: `{{BASE_URL}}/api/platform/v1`. These endpoints require an authenticated platform bearer token and the ability listed for each endpoint. Designation lists are returned as non-paginated catalogue collections.

## 1. List designations

Endpoint: GET `/api/platform/v1/platform-designations`

Required ability: `platform_designation.view`.

Supports `search` (name and code), `filter[status]`, `filter[level]`, `sort` (`name`, `code`, `level`, `status`, `created_at`, `updated_at`), and `direction` (`asc` or `desc`). Each item includes `users_count`.

```bash
curl --get "{{BASE_URL}}/api/platform/v1/platform-designations" --header "Accept: application/json" --header "Authorization: Bearer {access_token}" --data-urlencode "search=manager" --data-urlencode "filter[status]=active" --data-urlencode "filter[level]=3"
```

## 2. Create a designation

Endpoint: POST `/api/platform/v1/platform-designations`

Required ability: `platform_designation.create`.

Required field is `name`. Optional fields are `code`, `description`, `level`, and `status` (`active` or `inactive`). If `code` is omitted, the API generates a unique code from the name. Returns HTTP `201` with `data.designation`.

```bash
curl --request POST "{{BASE_URL}}/api/platform/v1/platform-designations" --header "Accept: application/json" --header "Content-Type: application/json" --header "Authorization: Bearer {access_token}" --data '{"name":"Finance Manager","code":"finance_manager","description":"Manages finance operations","level":3,"status":"active"}'
```

## 3. Get a designation

Endpoint: GET `/api/platform/v1/platform-designations/{designation_uuid}`

Required ability: `platform_designation.view`.

Returns the designation with `users_count`.

```bash
curl --request GET "{{BASE_URL}}/api/platform/v1/platform-designations/{designation_uuid}" --header "Accept: application/json" --header "Authorization: Bearer {access_token}"
```

## 4. Update a designation

Endpoint: PUT or PATCH `/api/platform/v1/platform-designations/{designation_uuid}`

Required ability: `platform_designation.edit`.

`PATCH` supports partial updates. Supported fields are `name`, `code`, `description`, `level`, and `status`. If the name changes without a supplied code, a new unique code is generated.

```bash
curl --request PATCH "{{BASE_URL}}/api/platform/v1/platform-designations/{designation_uuid}" --header "Accept: application/json" --header "Content-Type: application/json" --header "Authorization: Bearer {access_token}" --data '{"name":"Senior Finance Manager","level":4}'
```

## 5. Archive a designation

Endpoint: DELETE `/api/platform/v1/platform-designations/{designation_uuid}`

Required ability: `platform_designation.delete`.

Archives the designation by setting `status=inactive`. Existing staff records retain their reference; inactive designations should not be assigned to new staff.

```bash
curl --request DELETE "{{BASE_URL}}/api/platform/v1/platform-designations/{designation_uuid}" --header "Accept: application/json" --header "Authorization: Bearer {access_token}"
```

## Common errors

- HTTP `401`: unauthenticated request.
- HTTP `403`: the user lacks the required platform ability.
- HTTP `404`: the supplied department or designation UUID does not exist.
- HTTP `422`: request validation failed.


# Platform User APIs

Base URL: `{{BASE_URL}}/api/platform/v1`

All endpoints require an authenticated platform Sanctum bearer token, the `platform.token` middleware, and the ability listed for the endpoint. Replace `{platform_user_uuid}` with a platform-user UUID.

## 1. Export platform users

Endpoint: POST `/api/platform/v1/platform-users/export`

Ability: `platform_user.view`.

Exports `uuid`, `employee_code`, `display_name`, `email`, `status`, and `created_at` to the configured filesystem under `platform-exports/`. No filters are accepted. The response includes `data.export.filename`, `path`, `size`, and `disk`.

```bash
curl --request POST "{{BASE_URL}}/api/platform/v1/platform-users/export" \\
  --header "Accept: application/json" \\
  --header "Authorization: Bearer {access_token}"
```

## 2. Invite a platform user

Endpoint: POST `/api/platform/v1/platform-users/invite`

Ability: `platform_user.create`.

Required fields: `first_name`, `email`. Supports `last_name`, `display_name`, `employee_code`, `mobile`, `password`, `designation`/`designation_uuid`, `department`/`department_uuid`, `manager_id`/`manager_uuid`, `timezone`, `locale`, `two_factor_enabled`, `status`, `profile_photo_file_id`, role IDs/UUIDs, and team IDs/UUIDs. If no password is supplied, a temporary password is generated. A reset token is logged/queued for the invitation; it is exposed only in local environments. Returns HTTP `201 Created`.

```bash
curl --request POST "{{BASE_URL}}/api/platform/v1/platform-users/invite" \\
  --header "Accept: application/json" \\
  --header "Content-Type: application/json" \\
  --header "Authorization: Bearer {access_token}" \\
  --data '{
    "first_name": "Alex",
    "last_name": "Morgan",
    "email": "alex@example.com",
    "display_name": "Alex Morgan",
    "department": 1,
    "designation": 1,
    "role_uuids": ["{role_uuid}"],
    "team_uuids": ["{team_uuid}"],
    "status": "active"
  }'
```

## 3. List platform users

Endpoint: GET `/api/platform/v1/platform-users`

Ability: `platform_user.view`.

Returns a paginated list ordered by newest user first. Each item includes roles, teams, department, designation, profile-photo data, and `direct_permissions_count`. Query parameters: `search`, `filter[status]`, `filter[department]`, and `per_page` (default 25).

```bash
curl --get "{{BASE_URL}}/api/platform/v1/platform-users" \\
  --header "Accept: application/json" \\
  --header "Authorization: Bearer {access_token}" \\
  --data-urlencode "search=alex" \\
  --data-urlencode "filter[status]=active" \\
  --data-urlencode "filter[department]=1" \\
  --data-urlencode "per_page=25"
```

## 4. Create a platform user

Endpoint: POST `/api/platform/v1/platform-users`

Ability: `platform_user.create`.

Required fields are `first_name` and `email`. Password is optional; a temporary password is generated when omitted and returned only in local environments. Email and employee code must be unique. Returns HTTP `201 Created` with `data.user`.

```bash
curl --request POST "{{BASE_URL}}/api/platform/v1/platform-users" \\
  --header "Accept: application/json" \\
  --header "Content-Type: application/json" \\
  --header "Authorization: Bearer {access_token}" \\
  --data '{
    "first_name": "Jamie",
    "last_name": "Lee",
    "email": "jamie@example.com",
    "employee_code": "EMP-1001",
    "password": "temporary-password",
    "status": "active",
    "role_ids": [1],
    "team_ids": [1]
  }'
```

## 5. Get a platform user

Endpoint: GET `/api/platform/v1/platform-users/{platform_user_uuid}`

Ability: `platform_user.view`.

Returns the user with roles, teams, direct permissions, department, designation, manager, and profile-photo fields. Unknown UUIDs return `404 PLATFORM_USER_NOT_FOUND`.

```bash
curl --request GET "{{BASE_URL}}/api/platform/v1/platform-users/{platform_user_uuid}" \\
  --header "Accept: application/json" \\
  --header "Authorization: Bearer {access_token}"
```

## 6. Update a platform user

Endpoint: PUT or PATCH `/api/platform/v1/platform-users/{platform_user_uuid}`

Ability: `platform_user.edit`.

All fields are optional for PATCH. Roles or teams are synchronized only when their arrays are supplied. Password input is not accepted for updates; use reset-password instead.

```bash
curl --request PATCH "{{BASE_URL}}/api/platform/v1/platform-users/{platform_user_uuid}" \\
  --header "Accept: application/json" \\
  --header "Content-Type: application/json" \\
  --header "Authorization: Bearer {access_token}" \\
  --data '{
    "display_name": "Jamie Lee",
    "department": 2,
    "status": "active",
    "role_uuids": ["{role_uuid}"]
  }'
```

## 7. Delete a platform user

Endpoint: DELETE `/api/platform/v1/platform-users/{platform_user_uuid}`

Ability: `platform_user.delete`.

Soft-deletes the platform user. Successful response returns `data: null`.

```bash
curl --request DELETE "{{BASE_URL}}/api/platform/v1/platform-users/{platform_user_uuid}" \\
  --header "Accept: application/json" \\
  --header "Authorization: Bearer {access_token}"
```

## 8. Restore a platform user

Endpoint: POST `/api/platform/v1/platform-users/{platform_user_uuid}/restore`

Ability: `platform_user.edit`.

Searches deleted users and restores the matching UUID. Returns the restored record under `data.user`.

```bash
curl --request POST "{{BASE_URL}}/api/platform/v1/platform-users/{platform_user_uuid}/restore" \\
  --header "Accept: application/json" \\
  --header "Authorization: Bearer {access_token}"
```

## 9. Suspend a platform user

Endpoint: POST `/api/platform/v1/platform-users/{platform_user_uuid}/suspend`

Ability: `platform_user.suspend`.

Sets status to `suspended`, revokes all tokens, and records an audit event.

```bash
curl --request POST "{{BASE_URL}}/api/platform/v1/platform-users/{platform_user_uuid}/suspend" \\
  --header "Accept: application/json" \\
  --header "Authorization: Bearer {access_token}"
```

## 10. Activate a platform user

Endpoint: POST `/api/platform/v1/platform-users/{platform_user_uuid}/activate`

Ability: `platform_user.edit`.

Sets status to `active`; previously revoked tokens are not restored.

```bash
curl --request POST "{{BASE_URL}}/api/platform/v1/platform-users/{platform_user_uuid}/activate" \\
  --header "Accept: application/json" \\
  --header "Authorization: Bearer {access_token}"
```

## 11. Reset a user password

Endpoint: POST `/api/platform/v1/platform-users/{platform_user_uuid}/reset-password`

Ability: `platform_user.edit`.

Generates and queues reset instructions. Returns `data.sent: true`; the token is returned only in local environments.

```bash
curl --request POST "{{BASE_URL}}/api/platform/v1/platform-users/{platform_user_uuid}/reset-password" \\
  --header "Accept: application/json" \\
  --header "Authorization: Bearer {access_token}"
```

## 12. Force logout

Endpoint: POST `/api/platform/v1/platform-users/{platform_user_uuid}/force-logout`

Ability: `platform_user.edit`.

Revokes every authentication token for the selected user.

```bash
curl --request POST "{{BASE_URL}}/api/platform/v1/platform-users/{platform_user_uuid}/force-logout" \\
  --header "Accept: application/json" \\
  --header "Authorization: Bearer {access_token}"
```

## 13. Require two-factor authentication

Endpoint: POST `/api/platform/v1/platform-users/{platform_user_uuid}/require-2fa`

Ability: `platform_user.edit`.

Sets `two_factor_required=true` and revokes the user's existing tokens. This does not generate or store a secret or recovery codes. If the user has not enrolled yet, their next password login returns `requires_2fa_setup: true`; the login UI then opens setup, confirms the authenticator code, and continues through the normal 2FA challenge.

```bash
curl --request POST "{{BASE_URL}}/api/platform/v1/platform-users/{platform_user_uuid}/require-2fa" \\
  --header "Accept: application/json" \\
  --header "Authorization: Bearer {access_token}"
```

## 14. List assigned roles

Endpoint: GET `/api/platform/v1/platform-users/{platform_user_uuid}/roles`

Ability: `platform_user.view`.

Returns assigned roles under `data.roles`.

```bash
curl --request GET "{{BASE_URL}}/api/platform/v1/platform-users/{platform_user_uuid}/roles" \\
  --header "Accept: application/json" \\
  --header "Authorization: Bearer {access_token}"
```

## 15. Synchronize assigned roles

Endpoint: PUT `/api/platform/v1/platform-users/{platform_user_uuid}/roles`

Ability: `platform_user.edit`.

Use `role_uuids[]` or `role_ids[]`. An empty array removes all assignments.

```bash
curl --request PUT "{{BASE_URL}}/api/platform/v1/platform-users/{platform_user_uuid}/roles" \\
  --header "Accept: application/json" \\
  --header "Content-Type: application/json" \\
  --header "Authorization: Bearer {access_token}" \\
  --data '{"role_uuids":["{role_uuid}"]}'
```

## 16. List assigned teams

Endpoint: GET `/api/platform/v1/platform-users/{platform_user_uuid}/teams`

Ability: `platform_user.view`.

Returns active team memberships under `data.teams`, including team UUID, name, code, status, joined date, and membership status.

```bash
curl --request GET "{{BASE_URL}}/api/platform/v1/platform-users/{platform_user_uuid}/teams" \\
  --header "Accept: application/json" \\
  --header "Authorization: Bearer {access_token}"
```

## 17. Synchronize assigned teams

Endpoint: PUT `/api/platform/v1/platform-users/{platform_user_uuid}/teams`

Ability: `platform_user.edit`.

Use `team_uuids[]` or `team_ids[]`. An empty array removes all memberships.

```bash
curl --request PUT "{{BASE_URL}}/api/platform/v1/platform-users/{platform_user_uuid}/teams" \\
  --header "Accept: application/json" \\
  --header "Content-Type: application/json" \\
  --header "Authorization: Bearer {access_token}" \\
  --data '{"team_uuids":["{team_uuid}"]}'
```

## 18. List direct permissions

Endpoint: GET `/api/platform/v1/platform-users/{platform_user_uuid}/permissions`

Ability: `platform_user.view`.

Returns direct permissions under `data.permissions`. These are separate from role-inherited permissions.

```bash
curl --request GET "{{BASE_URL}}/api/platform/v1/platform-users/{platform_user_uuid}/permissions" \\
  --header "Accept: application/json" \\
  --header "Authorization: Bearer {access_token}"
```

## 19. Synchronize direct permissions

Endpoint: PUT `/api/platform/v1/platform-users/{platform_user_uuid}/permissions`

Ability: `platform_user.edit`.

Use `permission_uuids[]` or `permission_ids[]`. An empty array removes all direct permissions. `audit_reason` is optional and limited to 500 characters.

```bash
curl --request PUT "{{BASE_URL}}/api/platform/v1/platform-users/{platform_user_uuid}/permissions" \\
  --header "Accept: application/json" \\
  --header "Content-Type: application/json" \\
  --header "Authorization: Bearer {access_token}" \\
  --data '{"permission_uuids":["{permission_uuid}"],"audit_reason":"Update access"}'
```

## 20. Get user activity

Endpoint: GET `/api/platform/v1/platform-users/{platform_user_uuid}/activity`

Ability: `audit_log.view`.

Returns the latest 100 activity entries where the selected platform user is the actor, ordered newest first. This endpoint is intentionally not paginated.

```bash
curl --request GET "{{BASE_URL}}/api/platform/v1/platform-users/{platform_user_uuid}/activity" \\
  --header "Accept: application/json" \\
  --header "Authorization: Bearer {access_token}"
```

## Common errors

- HTTP `401`: unauthenticated request.
- HTTP `403`: missing required platform ability.
- HTTP `404`: platform-user UUID not found; restore also searches deleted users.
- HTTP `422`: validation failed.


# Platform Team APIs

Base URL: `{{BASE_URL}}/api/platform/v1`. All endpoints require an authenticated platform bearer token and the ability listed for the endpoint. Team and team-role lists are paginated; member, assignment, and grouped collections are not paginated unless stated.

## 1. List teams

Endpoint: GET `/api/platform/v1/platform-teams`

Required ability: `platform_team.view`.

Supports `search`, `filter[status]`/ `status`, `filter[visibility]`/ `visibility`, `sort`, `direction`, and `per_page` (default 25). Returns lead/assistant details, `members_count`, and `assignments_count`.

```bash
curl --get "{{BASE_URL}}/api/platform/v1/platform-teams" --header "Accept: application/json" --header "Authorization: Bearer {access_token}" --data-urlencode "search=finance" --data-urlencode "filter[status]=active" --data-urlencode "per_page=25"
```

## 2. Create a team

Endpoint: POST `/api/platform/v1/platform-teams`

Required ability: `platform_team.create`.

Required: `name`, `code`. Optional: `platform_department_id`/`department_uuid`, description, lead/assistant user IDs or UUIDs, email, phone, color, icon, visibility, and status. Defaults are `visibility=internal` and `status=active`. Returns HTTP `201` with `data.team`.

```bash
curl --request POST "{{BASE_URL}}/api/platform/v1/platform-teams" --header "Accept: application/json" --header "Content-Type: application/json" --header "Authorization: Bearer {access_token}" --data '{"name":"Finance Team","code":"finance","description":"Finance operations","lead_platform_user_id":1,"assistant_lead_platform_user_id":2,"visibility":"internal","status":"active"}'
```

## 3. Get a team

Endpoint: GET `/api/platform/v1/platform-teams/{team_uuid}`

Required ability: `platform_team.view`.

Returns lead and assistant-lead details plus member and assignment counts.

```bash
curl --request GET "{{BASE_URL}}/api/platform/v1/platform-teams/{team_uuid}" --header "Accept: application/json" --header "Authorization: Bearer {access_token}"
```

## 4. Update a team

Endpoint: PUT|PATCH `/api/platform/v1/platform-teams/{team_uuid}`

Required ability: `platform_team.edit`.

PATCH supports partial updates and applies the same field validation as create.

```bash
curl --request PATCH "{{BASE_URL}}/api/platform/v1/platform-teams/{team_uuid}" --header "Accept: application/json" --header "Content-Type: application/json" --header "Authorization: Bearer {access_token}" --data '{"name":"Finance Operations","status":"active"}'
```

## 5. Delete/archive a team

Endpoint: DELETE `/api/platform/v1/platform-teams/{team_uuid}`

Required ability: `platform_team.delete`.

Sets status to `inactive` and soft-deletes the team. Successful response has `data: null`.

```bash
curl --request DELETE "{{BASE_URL}}/api/platform/v1/platform-teams/{team_uuid}" --header "Accept: application/json" --header "Authorization: Bearer {access_token}"
```

## 6. List team members

Endpoint: GET `/api/platform/v1/platform-teams/{team_uuid}/members`

Required ability: `platform_team.view`.

Returns membership data, platform-user details, and team-role details ordered by display name.

```bash
curl --request GET "{{BASE_URL}}/api/platform/v1/platform-teams/{team_uuid}/members" --header "Accept: application/json" --header "Authorization: Bearer {access_token}"
```

## 7. Add or save team members

Endpoint: POST `/api/platform/v1/platform-teams/{team_uuid}/members`

Required ability: `platform_team.assign`.

Send one top-level member or a `members[]` array. Use UUID or numeric ID aliases for users/team roles. Existing membership is updated.

```bash
curl --request POST "{{BASE_URL}}/api/platform/v1/platform-teams/{team_uuid}/members" --header "Accept: application/json" --header "Content-Type: application/json" --header "Authorization: Bearer {access_token}" --data '{"platform_user_uuid":"{platform_user_uuid}","team_role_uuid":"{role_uuid}","effective_from":"2026-08-31","status":"active"}'
```

## 8. Update a team member

Endpoint: PUT|PATCH `/api/platform/v1/platform-teams/{team_uuid}/members/{member_id}`

Required ability: `platform_team.assign`.

Supports team role, joined/effective-from date, left/effective-to date, and status.

```bash
curl --request PATCH "{{BASE_URL}}/api/platform/v1/platform-teams/{team_uuid}/members/{member_id}" --header "Accept: application/json" --header "Content-Type: application/json" --header "Authorization: Bearer {access_token}" --data '{"team_role_uuid":"{role_uuid}","status":"inactive","effective_to":"2026-12-31"}'
```

## 9. Remove a team member

Endpoint: DELETE `/api/platform/v1/platform-teams/{team_uuid}/members/{member_id}`

Required ability: `platform_team.assign`.

Permanently removes only the membership record.

```bash
curl --request DELETE "{{BASE_URL}}/api/platform/v1/platform-teams/{team_uuid}/members/{member_id}" --header "Accept: application/json" --header "Authorization: Bearer {access_token}"
```

## 10. List team assignments

Endpoint: GET `/api/platform/v1/platform-teams/{team_uuid}/assignments`

Required ability: `platform_team.view`.

Returns assignments newest first.

```bash
curl --request GET "{{BASE_URL}}/api/platform/v1/platform-teams/{team_uuid}/assignments" --header "Accept: application/json" --header "Authorization: Bearer {access_token}"
```

## 11. Create a team assignment

Endpoint: POST `/api/platform/v1/platform-teams/{team_uuid}/assignments`

Required ability: `platform_team.assign`.

Required: `assignable_type`, `assignable_id`. Optional: `assignment_role`. The current user is recorded as `assigned_by`; status starts as `active`. Returns HTTP `201` with `data.assignment`.

```bash
curl --request POST "{{BASE_URL}}/api/platform/v1/platform-teams/{team_uuid}/assignments" --header "Accept: application/json" --header "Content-Type: application/json" --header "Authorization: Bearer {access_token}" --data '{"assignable_type":"App\\Models\\Project","assignable_id":42,"assignment_role":"Reviewer"}'
```

## 12. Release a team assignment

Endpoint: DELETE `/api/platform/v1/platform-teams/{team_uuid}/assignments/{assignment_id}`

Required ability: `platform_team.assign`.

Marks the assignment `released` and records `released_at`; the row is retained.

```bash
curl --request DELETE "{{BASE_URL}}/api/platform/v1/platform-teams/{team_uuid}/assignments/{assignment_id}" --header "Accept: application/json" --header "Authorization: Bearer {access_token}"
```

## 13. List team roles

Endpoint: GET `/api/platform/v1/platform-team-roles`

Required ability: `platform_team.view`.

Paginated list with decoded `permissions` array and `permissions_count`. Supports `search`, `filter[status]`, `sort`, `direction`, and `per_page` (default 25).

```bash
curl --get "{{BASE_URL}}/api/platform/v1/platform-team-roles" --header "Accept: application/json" --header "Authorization: Bearer {access_token}" --data-urlencode "search=review" --data-urlencode "filter[status]=active" --data-urlencode "per_page=25"
```

## 14. Create a team role

Endpoint: POST `/api/platform/v1/platform-team-roles`

Required ability: `platform_team.create`.

Required: `name`. Optional: `description`, `permissions` array, `is_system`, and `status`. Code and sort order are generated automatically. Returns HTTP `201` with `data.team_role`.

```bash
curl --request POST "{{BASE_URL}}/api/platform/v1/platform-team-roles" --header "Accept: application/json" --header "Content-Type: application/json" --header "Authorization: Bearer {access_token}" --data '{"name":"Reviewer","description":"Reviews team work","permissions":["view","comment"],"status":"active"}'
```

## 15. Get a team role

Endpoint: GET `/api/platform/v1/platform-team-roles/{role_uuid}`

Required ability: `platform_team.view`.

Returns decoded permissions and `permissions_count`.

```bash
curl --request GET "{{BASE_URL}}/api/platform/v1/platform-team-roles/{role_uuid}" --header "Accept: application/json" --header "Authorization: Bearer {access_token}"
```

## 16. Update a team role

Endpoint: PUT|PATCH `/api/platform/v1/platform-team-roles/{role_uuid}`

Required ability: `platform_team.edit`.

PATCH supports partial updates. Changing `name` regenerates a unique code.

```bash
curl --request PATCH "{{BASE_URL}}/api/platform/v1/platform-team-roles/{role_uuid}" --header "Accept: application/json" --header "Content-Type: application/json" --header "Authorization: Bearer {access_token}" --data '{"name":"Senior Reviewer","permissions":["view","comment","approve"]}'
```

## 17. Delete a team role

Endpoint: DELETE `/api/platform/v1/platform-team-roles/{role_uuid}`

Required ability: `platform_team.delete`.

Archives the role. System roles return `403 SYSTEM_TEAM_ROLE_DELETE_FORBIDDEN`; roles assigned to members return `409 TEAM_ROLE_IN_USE`.

```bash
curl --request DELETE "{{BASE_URL}}/api/platform/v1/platform-team-roles/{role_uuid}" --header "Accept: application/json" --header "Authorization: Bearer {access_token}"
```

## Common errors

- HTTP `401`: unauthenticated request.
- HTTP `403`: missing ability or forbidden operation.
- HTTP `404`: team, role, user, member, or assignment not found.
- HTTP `409`: team role is assigned to a member.
- HTTP `422`: validation failed.


# Platform Feature APIs

Base path: `/api/platform/v1`. All endpoints use the standard response envelope from `docs/api-response-standards.md`. Replace `{access_token}` and `{feature_uuid}` with real values.

## List features

GET `/features` requires `feature.view`. The response is paginated and includes `plans`, `plan_features`, and `plans_count`. Filters are optional; pagination is used because this endpoint returns a collection.

```bash
curl --get "{{BASE_URL}}/api/platform/v1/features" \
  --header "Accept: application/json" \
  --header "Authorization: Bearer {access_token}" \
  --data-urlencode "search=storage" \
  --data-urlencode "filter[module]=billing" \
  --data-urlencode "filter[status]=active" \
  --data-urlencode "sort=module" \
  --data-urlencode "direction=asc" \
  --data-urlencode "per_page=25"
```

## Create a feature

POST `/features` requires `feature.create`. Required fields are `module`, `name`, `code`, and `data_type`. Optional `plan_uuids[]`/ `plan_ids[]` assign plans, or `plan_features[]` assigns plans with pivot `value` and `metadata`. Returns HTTP `201`.

```bash
curl --request POST "{{BASE_URL}}/api/platform/v1/features" \
  --header "Accept: application/json" \
  --header "Content-Type: application/json" \
  --header "Authorization: Bearer {access_token}" \
  --data '{"module":"billing","name":"Storage limit","code":"storage_limit","data_type":"integer","unit":"GB","description":"Maximum storage","status":"active","plan_features":[{"plan_uuid":"{plan_uuid}","value":"100","metadata":{"source":"catalogue"}}]}'
```

## Get a feature

GET `/features/{feature_uuid}` requires `feature.view`. Returns the feature and its plan relationships.

```bash
curl --request GET "{{BASE_URL}}/api/platform/v1/features/{feature_uuid}" \
  --header "Accept: application/json" \
  --header "Authorization: Bearer {access_token}"
```

## Update a feature

PUT or PATCH `/features/{feature_uuid}` requires `feature.edit`. PATCH accepts only the fields being changed. Sending a plan relationship field synchronizes the complete plan assignment list.

```bash
curl --request PATCH "{{BASE_URL}}/api/platform/v1/features/{feature_uuid}" \
  --header "Accept: application/json" \
  --header "Content-Type: application/json" \
  --header "Authorization: Bearer {access_token}" \
  --data '{"name":"Storage capacity","status":"inactive","plan_uuids":["{plan_uuid}"]}'
```

## Delete a feature

DELETE `/features/{feature_uuid}` requires `feature.delete`. A feature assigned to any plan is protected and returns HTTP `409` with code `FEATURE_IN_USE`.

```bash
curl --request DELETE "{{BASE_URL}}/api/platform/v1/features/{feature_uuid}" \
  --header "Accept: application/json" \
  --header "Authorization: Bearer {access_token}"
```

## Export features

POST `/features/export` requires `feature.view`. The current API queues a CSV export and returns HTTP `202` with `data.export.job_id`, `status=queued`, and `format=csv`.

```bash
curl --request POST "{{BASE_URL}}/api/platform/v1/features/export" \
  --header "Accept: application/json" \
  --header "Content-Type: application/json" \
  --header "Authorization: Bearer {access_token}" \
  --data '{"format":"csv","filters":{"module":"billing","status":"active"},"sort":"module","direction":"asc","columns":["uuid","module","name","code","data_type","plans_count"]}'
```

## Import features

POST `/features/import` requires `feature.create`. Submit an existing `file_id` or a CSV upload. The API returns HTTP `202` with queued import metadata.

```bash
curl --request POST "{{BASE_URL}}/api/platform/v1/features/import" \
  --header "Accept: application/json" \
  --header "Authorization: Bearer {access_token}" \
  --form "file_id={file_id}"
```

For a direct CSV upload:

```bash
curl --request POST "{{BASE_URL}}/api/platform/v1/features/import" \
  --header "Accept: application/json" \
  --header "Authorization: Bearer {access_token}" \
  --form "file=@features.csv"
```

## Bulk delete features

DELETE `/features/bulk` requires `feature.delete`. The body must contain a non-empty `feature_uuids` array. The response reports deleted and skipped counts; in-use features are skipped.

```bash
curl --request DELETE "{{BASE_URL}}/api/platform/v1/features/bulk" \
  --header "Accept: application/json" \
  --header "Content-Type: application/json" \
  --header "Authorization: Bearer {access_token}" \
  --data '{"feature_uuids":["{feature_uuid_1}","{feature_uuid_2}"]}'
```

## Common errors

- HTTP `401`: unauthenticated request.
- HTTP `403`: missing feature ability.
- HTTP `404`: feature UUID not found.
- HTTP `409`: feature is protected or assigned to catalogue data.
- HTTP `422`: request validation failed.

# Platform Module APIs

Base path: `/api/platform/v1`. Responses use the standard success, validation, pagination, and error envelopes. Replace placeholders with real values.

## List modules

GET `/modules` requires `module.view`. Returns a paginated list ordered by `sort_order` and name. Metadata includes distinct feature-module values and module statistics.

```bash
curl --get "{{BASE_URL}}/api/platform/v1/modules" --header "Accept: application/json" --header "Authorization: Bearer {access_token}" --data-urlencode "search=billing" --data-urlencode "status=active" --data-urlencode "category=billing" --data-urlencode "per_page=10"
```

## Create a module

POST `/modules` requires `module.edit`. Required fields are `name` and `code`; returns HTTP `201` with `data.module`.

```bash
curl --request POST "{{BASE_URL}}/api/platform/v1/modules" --header "Accept: application/json" --header "Content-Type: application/json" --header "Authorization: Bearer {access_token}" --data '{"name":"Billing","code":"billing","description":"Billing catalogue","category":"billing","is_core":false,"status":"active","sort_order":10}'
```

## Export modules

POST `/modules/export` requires `module.view`. Queues a CSV export and returns HTTP `202` with `data.export.job_id`, `status=queued`, and `format=csv`.

```bash
curl --request POST "{{BASE_URL}}/api/platform/v1/modules/export" --header "Accept: application/json" --header "Content-Type: application/json" --header "Authorization: Bearer {access_token}" --data '{"format":"csv","filters":{"status":"active","category":"billing"}}'
```

## Import modules

POST `/modules/import` requires `module.edit`. Submit an existing `file_id` or a CSV upload; the response is HTTP `202` with queued import metadata.

```bash
curl --request POST "{{BASE_URL}}/api/platform/v1/modules/import" --header "Accept: application/json" --header "Authorization: Bearer {access_token}" --form "file=@modules.csv"
```

## Bulk delete modules

DELETE `/modules/bulk` requires `module.edit`. Core modules and modules with features or tenant overrides are skipped.

```bash
curl --request DELETE "{{BASE_URL}}/api/platform/v1/modules/bulk" --header "Accept: application/json" --header "Content-Type: application/json" --header "Authorization: Bearer {access_token}" --data '{"module_uuids":["{module_uuid_1}","{module_uuid_2}"]}'
```

## Get a module

GET `/modules/{module_uuid}` requires `module.view`. Returns the module, assigned features, tenant overrides, enabled tenant count, relationship counts, and latest 25 activity records.

```bash
curl --request GET "{{BASE_URL}}/api/platform/v1/modules/{module_uuid}" --header "Accept: application/json" --header "Authorization: Bearer {access_token}"
```

## Update a module

PUT or PATCH `/modules/{module_uuid}` requires `module.edit`. PATCH accepts partial fields.

```bash
curl --request PATCH "{{BASE_URL}}/api/platform/v1/modules/{module_uuid}" --header "Accept: application/json" --header "Content-Type: application/json" --header "Authorization: Bearer {access_token}" --data '{"description":"Updated billing catalogue","status":"inactive"}'
```

## Delete a module

DELETE `/modules/{module_uuid}` requires `module.edit`. Deletion is blocked with HTTP `409` and code `MODULE_IN_USE` when the module is core or has related features or tenant overrides.

```bash
curl --request DELETE "{{BASE_URL}}/api/platform/v1/modules/{module_uuid}" --header "Accept: application/json" --header "Authorization: Bearer {access_token}"
```

## Enable a module

POST `/modules/{module_uuid}/enable` requires `module.edit`.

```bash
curl --request POST "{{BASE_URL}}/api/platform/v1/modules/{module_uuid}/enable" --header "Accept: application/json" --header "Authorization: Bearer {access_token}"
```

## Disable a module

POST `/modules/{module_uuid}/disable` requires `module.edit`.

```bash
curl --request POST "{{BASE_URL}}/api/platform/v1/modules/{module_uuid}/disable" --header "Accept: application/json" --header "Authorization: Bearer {access_token}"
```

## List module features

GET `/modules/{module_uuid}/features` requires `module.view`. Returns assigned features ordered by name, including their plan relationships.

```bash
curl --request GET "{{BASE_URL}}/api/platform/v1/modules/{module_uuid}/features" --header "Accept: application/json" --header "Authorization: Bearer {access_token}"
```

## Replace module features

PUT `/modules/{module_uuid}/features` requires `module.edit`. Send `feature_uuids` as an array; an empty array removes all assignments by moving them to `unassigned`.

```bash
curl --request PUT "{{BASE_URL}}/api/platform/v1/modules/{module_uuid}/features" --header "Accept: application/json" --header "Content-Type: application/json" --header "Authorization: Bearer {access_token}" --data '{"feature_uuids":["{feature_uuid_1}","{feature_uuid_2}"]}'
```

## Common errors

- HTTP `401`: unauthenticated request.
- HTTP `403`: missing module ability.
- HTTP `404`: module or feature UUID not found.
- HTTP `409`: module is core or has related features/tenant overrides.
- HTTP `422`: validation failed.

# Platform Coupon APIs

Base path: `/api/platform/v1`. All responses use the standard response envelope. Replace placeholders with real values.

## List coupons

GET `/coupons` requires `coupon.view`. Returns a paginated list of non-deleted coupons with plan/tenant relationships and coupon statistics.

```bash
curl --get "{{BASE_URL}}/api/platform/v1/coupons" --header "Accept: application/json" --header "Authorization: Bearer {access_token}" --data-urlencode "search=SUMMER" --data-urlencode "status=active" --data-urlencode "discount_type=percent" --data-urlencode "per_page=10"
```

## Create a coupon

POST `/coupons` requires `coupon.create`. Required fields are `code`, `name`, `discount_type`, and `discount_value`. Codes are normalized to uppercase. Optional plan and tenant UUID arrays synchronize applicability. Returns HTTP `201`.

```bash
curl --request POST "{{BASE_URL}}/api/platform/v1/coupons" --header "Accept: application/json" --header "Content-Type: application/json" --header "Authorization: Bearer {access_token}" --data '{"code":"summer25","name":"Summer discount","discount_type":"percent","discount_value":25,"starts_at":"2026-09-01T00:00:00Z","expires_at":"2026-09-30T23:59:59Z","max_redemptions":100,"plan_uuids":["{plan_uuid}"],"tenant_uuids":["{tenant_uuid}"]}'
```

## Get a coupon

GET `/coupons/{coupon_uuid}` requires `coupon.view`. Returns applicable plans, tenants, recent redemptions with invoice/payment relationships, and the latest 25 activity records.

```bash
curl --request GET "{{BASE_URL}}/api/platform/v1/coupons/{coupon_uuid}" --header "Accept: application/json" --header "Authorization: Bearer {access_token}"
```

## Update a coupon

PUT or PATCH `/coupons/{coupon_uuid}` requires `coupon.edit`. PATCH accepts partial fields. Sending `plan_uuids` or `tenant_uuids` replaces that assignment list.

```bash
curl --request PATCH "{{BASE_URL}}/api/platform/v1/coupons/{coupon_uuid}" --header "Accept: application/json" --header "Content-Type: application/json" --header "Authorization: Bearer {access_token}" --data '{"code":"winter30","discount_value":30,"plan_uuids":["{plan_uuid}"]}'
```

## Delete a coupon

DELETE `/coupons/{coupon_uuid}` requires `coupon.delete`. The coupon is archived first. Redeemed coupons remain without `deleted_at`; unused coupons are soft-deleted.

```bash
curl --request DELETE "{{BASE_URL}}/api/platform/v1/coupons/{coupon_uuid}" --header "Accept: application/json" --header "Authorization: Bearer {access_token}"
```

## Activate a coupon

POST `/coupons/{coupon_uuid}/activate` requires `coupon.edit`.

```bash
curl --request POST "{{BASE_URL}}/api/platform/v1/coupons/{coupon_uuid}/activate" --header "Accept: application/json" --header "Authorization: Bearer {access_token}"
```

## Deactivate a coupon

POST `/coupons/{coupon_uuid}/deactivate` requires `coupon.edit`.

```bash
curl --request POST "{{BASE_URL}}/api/platform/v1/coupons/{coupon_uuid}/deactivate" --header "Accept: application/json" --header "Authorization: Bearer {access_token}"
```

## Bulk delete coupons

DELETE `/coupons/bulk` requires `coupon.delete`. Send a non-empty `coupon_uuids` array. The response returns `deleted` and `archived` counts.

```bash
curl --request DELETE "{{BASE_URL}}/api/platform/v1/coupons/bulk" --header "Accept: application/json" --header "Content-Type: application/json" --header "Authorization: Bearer {access_token}" --data '{"coupon_uuids":["{coupon_uuid_1}","{coupon_uuid_2}"]}'
```

## Export coupons

POST `/coupons/export` requires `coupon.view`. Queues a CSV export and returns HTTP `202` with `data.export.job_id`, `status=queued`, and `format=csv`.

```bash
curl --request POST "{{BASE_URL}}/api/platform/v1/coupons/export" --header "Accept: application/json" --header "Content-Type: application/json" --header "Authorization: Bearer {access_token}" --data '{"format":"csv","filters":{"status":"active","discount_type":"percent"}}'
```

## Import coupons

POST `/coupons/import` requires `coupon.create`. Submit a CSV upload or existing `file_id`; returns HTTP `202` with queued import metadata.

```bash
curl --request POST "{{BASE_URL}}/api/platform/v1/coupons/import" --header "Accept: application/json" --header "Authorization: Bearer {access_token}" --form "file=@coupons.csv"
```

## Common errors

- HTTP `401`: unauthenticated request.
- HTTP `403`: missing coupon ability.
- HTTP `404`: coupon UUID not found.
- HTTP `409`: coupon business rule prevents the operation.
- HTTP `422`: validation failed.

# Platform Add-on APIs

Base path: `/api/platform/v1`. Responses follow `docs/api-response-standards.md`. Replace placeholders with real values.

## List add-ons

GET `/addons` requires `plan.view`. Returns a paginated list with plan and subscription-assignment counts and statistics.

```bash
curl --get "{{BASE_URL}}/api/platform/v1/addons" --header "Accept: application/json" --header "Authorization: Bearer {access_token}" --data-urlencode "search=storage" --data-urlencode "status=active" --data-urlencode "per_page=10"
```

## Create an add-on

POST `/addons` requires `plan.create`. Required fields are `name`, `pricing_type`, and `price`. Pricing types are `recurring`, `one time`, `usage based`, and `tiered`. A code is generated when omitted. Returns HTTP `201`.

```bash
curl --request POST "{{BASE_URL}}/api/platform/v1/addons" --header "Accept: application/json" --header "Content-Type: application/json" --header "Authorization: Bearer {access_token}" --data '{"name":"Extra storage","pricing_type":"recurring","price":9.99,"currency":"USD","is_public":true,"status":"active"}'
```

## Get an add-on

GET `/addons/{addon_uuid}` requires `plan.view`. Returns plans, current subscription assignments with tenant details, `features_limits`, and recent activity.

```bash
curl --request GET "{{BASE_URL}}/api/platform/v1/addons/{addon_uuid}" --header "Accept: application/json" --header "Authorization: Bearer {access_token}"
```

## Update an add-on

PUT or PATCH `/addons/{addon_uuid}` requires `plan.edit`. PATCH accepts partial fields and supplied codes remain unique.

```bash
curl --request PATCH "{{BASE_URL}}/api/platform/v1/addons/{addon_uuid}" --header "Accept: application/json" --header "Content-Type: application/json" --header "Authorization: Bearer {access_token}" --data '{"price":12.50,"status":"inactive"}'
```

## Delete an add-on

DELETE `/addons/{addon_uuid}` requires `plan.delete`. Deletion returns HTTP `409` with code `ADDON_PLAN_IN_USE` when assigned to a subscription.

```bash
curl --request DELETE "{{BASE_URL}}/api/platform/v1/addons/{addon_uuid}" --header "Accept: application/json" --header "Authorization: Bearer {access_token}"
```

## Bulk delete add-ons

DELETE `/addons/bulk` requires `plan.delete`. Send a non-empty `addon_uuids` array. Assigned add-ons are skipped.

```bash
curl --request DELETE "{{BASE_URL}}/api/platform/v1/addons/bulk" --header "Accept: application/json" --header "Content-Type: application/json" --header "Authorization: Bearer {access_token}" --data '{"addon_uuids":["{addon_uuid_1}","{addon_uuid_2}"]}'
```

## Activate an add-on

POST `/addons/{addon_uuid}/activate` requires `plan.edit`.

```bash
curl --request POST "{{BASE_URL}}/api/platform/v1/addons/{addon_uuid}/activate" --header "Accept: application/json" --header "Authorization: Bearer {access_token}"
```

## Deactivate an add-on

POST `/addons/{addon_uuid}/deactivate` requires `plan.edit`.

```bash
curl --request POST "{{BASE_URL}}/api/platform/v1/addons/{addon_uuid}/deactivate" --header "Accept: application/json" --header "Authorization: Bearer {access_token}"
```

## Export add-ons

POST `/addons/export` requires `plan.view`. Queues a CSV export and returns HTTP `202` with export metadata.

```bash
curl --request POST "{{BASE_URL}}/api/platform/v1/addons/export" --header "Accept: application/json" --header "Content-Type: application/json" --header "Authorization: Bearer {access_token}" --data '{"format":"csv","filters":{"status":"active"}}'
```

## Import add-ons

POST `/addons/import` requires `plan.create`. Submit a CSV upload or existing `file_id`; returns HTTP `202` with queued import metadata.

```bash
curl --request POST "{{BASE_URL}}/api/platform/v1/addons/import" --header "Accept: application/json" --header "Authorization: Bearer {access_token}" --form "file=@addons.csv"
```

## Common errors

- HTTP `401`: unauthenticated request.
- HTTP `403`: missing plan ability.
- HTTP `404`: add-on UUID not found.
- HTTP `409`: add-on is assigned to a subscription.
- HTTP `422`: validation failed.

# Platform Plan APIs

Base path: `/api/platform/v1`. Responses follow `docs/api-response-standards.md`. Replace placeholders with real values.

## List plans

GET `/plans` requires `plan.view`. Returns a paginated list of non-deleted plans with feature/add-on counts and statistics.

```bash
curl --get "{{BASE_URL}}/api/platform/v1/plans" --header "Accept: application/json" --header "Authorization: Bearer {access_token}" --data-urlencode "search=professional" --data-urlencode "status=active" --data-urlencode "billing_cycle=monthly" --data-urlencode "currency=USD" --data-urlencode "per_page=10"
```

## Create a plan

POST `/plans` requires `plan.create`. Required fields are `name`, `code`, `billing_cycle`, and `base_price`. Returns HTTP `201`.

```bash
curl --request POST "{{BASE_URL}}/api/platform/v1/plans" --header "Accept: application/json" --header "Content-Type: application/json" --header "Authorization: Bearer {access_token}" --data '{"name":"Professional","code":"professional","billing_cycle":"monthly","base_price":49.99,"description":"Professional plan","currency":"USD","trial_days":14,"is_custom":false,"is_public":true,"status":"active"}'
```

## Get a plan

GET `/plans/{plan_uuid}` requires `plan.view`. Returns features, add-ons, coupon history, subscriptions with tenant data, subscription history, counts, and recent activity.

```bash
curl --request GET "{{BASE_URL}}/api/platform/v1/plans/{plan_uuid}" --header "Accept: application/json" --header "Authorization: Bearer {access_token}"
```

## Update a plan

PUT or PATCH `/plans/{plan_uuid}` requires `plan.edit`. PATCH accepts partial fields.

```bash
curl --request PATCH "{{BASE_URL}}/api/platform/v1/plans/{plan_uuid}" --header "Accept: application/json" --header "Content-Type: application/json" --header "Authorization: Bearer {access_token}" --data '{"base_price":59.99,"status":"inactive"}'
```

## Delete a plan

DELETE `/plans/{plan_uuid}` requires `plan.delete`. Plans referenced by subscriptions or subscription history return HTTP `409` with code `PLAN_IN_USE`.

```bash
curl --request DELETE "{{BASE_URL}}/api/platform/v1/plans/{plan_uuid}" --header "Accept: application/json" --header "Authorization: Bearer {access_token}"
```

## Bulk delete plans

DELETE `/plans/bulk` requires `plan.delete`. Send a non-empty `plan_uuids` array. In-use plans are archived; unused plans are deleted.

```bash
curl --request DELETE "{{BASE_URL}}/api/platform/v1/plans/bulk" --header "Accept: application/json" --header "Content-Type: application/json" --header "Authorization: Bearer {access_token}" --data '{"plan_uuids":["{plan_uuid_1}","{plan_uuid_2}"]}'
```

## Clone a plan

POST `/plans/{plan_uuid}/clone` requires `plan.create`. Required fields are `name`, `code`, `billing_cycle`, and `base_price`. Features are copied by default and the clone defaults to inactive.

```bash
curl --request POST "{{BASE_URL}}/api/platform/v1/plans/{plan_uuid}/clone" --header "Accept: application/json" --header "Content-Type: application/json" --header "Authorization: Bearer {access_token}" --data '{"name":"Professional Copy","code":"professional_copy","billing_cycle":"monthly","base_price":49.99,"copy_features":true}'
```

## Activate a plan

POST `/plans/{plan_uuid}/activate` requires `plan.edit`.

```bash
curl --request POST "{{BASE_URL}}/api/platform/v1/plans/{plan_uuid}/activate" --header "Accept: application/json" --header "Authorization: Bearer {access_token}"
```

## Deactivate a plan

POST `/plans/{plan_uuid}/deactivate` requires `plan.edit`.

```bash
curl --request POST "{{BASE_URL}}/api/platform/v1/plans/{plan_uuid}/deactivate" --header "Accept: application/json" --header "Authorization: Bearer {access_token}"
```

## List plan features

GET `/plans/{plan_uuid}/features` requires `plan.view`. Each feature includes pivot `value` and `metadata`.

```bash
curl --request GET "{{BASE_URL}}/api/platform/v1/plans/{plan_uuid}/features" --header "Accept: application/json" --header "Authorization: Bearer {access_token}"
```

## Replace plan features

PUT `/plans/{plan_uuid}/features` requires `plan.edit`. Send an array of feature objects; an empty array removes all assignments.

```bash
curl --request PUT "{{BASE_URL}}/api/platform/v1/plans/{plan_uuid}/features" --header "Accept: application/json" --header "Content-Type: application/json" --header "Authorization: Bearer {access_token}" --data '{"features":[{"feature_uuid":"{feature_uuid}","value":"100","metadata":{"unit":"GB"}}]}'
```

## List plan add-ons

GET `/plans/{plan_uuid}/addons` requires `plan.view`.

```bash
curl --request GET "{{BASE_URL}}/api/platform/v1/plans/{plan_uuid}/addons" --header "Accept: application/json" --header "Authorization: Bearer {access_token}"
```

## Replace plan add-ons

PUT `/plans/{plan_uuid}/addons` requires `plan.edit`. Send an `addon_uuids` array; an empty array removes all assignments. If the pivot table is unavailable, the API returns HTTP `503` with code `PLAN_ADDONS_TABLE_MISSING`.

```bash
curl --request PUT "{{BASE_URL}}/api/platform/v1/plans/{plan_uuid}/addons" --header "Accept: application/json" --header "Content-Type: application/json" --header "Authorization: Bearer {access_token}" --data '{"addon_uuids":["{addon_uuid}"]}'
```

## List active plan subscriptions

GET `/plans/{plan_uuid}/subscriptions` requires `plan.view`. Returns paginated active subscriptions with tenant organization data.

```bash
curl --get "{{BASE_URL}}/api/platform/v1/plans/{plan_uuid}/subscriptions" --header "Accept: application/json" --header "Authorization: Bearer {access_token}" --data-urlencode "per_page=10"
```

## Export plans

POST `/plans/export` requires `plan.view`. Queues a CSV export and returns HTTP `202` with export metadata.

```bash
curl --request POST "{{BASE_URL}}/api/platform/v1/plans/export" --header "Accept: application/json" --header "Content-Type: application/json" --header "Authorization: Bearer {access_token}" --data '{"format":"csv","filters":{"status":"active","currency":"USD"}}'
```

## Import plans

POST `/plans/import` requires `plan.create`. Submit a CSV upload or existing `file_id`; returns HTTP `202` with queued import metadata.

```bash
curl --request POST "{{BASE_URL}}/api/platform/v1/plans/import" --header "Accept: application/json" --header "Authorization: Bearer {access_token}" --form "file=@plans.csv"
```

## Common errors

- HTTP `401`: unauthenticated request.
- HTTP `403`: missing plan ability.
- HTTP `404`: plan, feature, or add-on UUID not found.
- HTTP `409`: plan is in use.
- HTTP `422`: validation failed.
- HTTP `503`: plan add-on assignment infrastructure is unavailable.

## Replace coupon plan assignments

PUT `/coupons/{coupon_uuid}/plans` requires `coupon.edit`. The required `plan_uuids` array replaces all existing coupon-plan assignments. An empty array removes every assignment. The response returns the updated plans under `data.plans`.

```bash
curl --request PUT "{{BASE_URL}}/api/platform/v1/coupons/{coupon_uuid}/plans"   --header "Accept: application/json"   --header "Content-Type: application/json"   --header "Authorization: Bearer {access_token}"   --data '{"plan_uuids":["{plan_uuid_1}","{plan_uuid_2}"]}'
```

Unknown coupon or plan UUIDs return HTTP `404`. Missing or invalid input returns HTTP `422`.

# Platform Knowledge-Base APIs

Base path: `/api/platform/v1`. Responses follow `docs/api-response-standards.md`. Replace placeholders with real values.

## List categories

GET `/knowledge-base/categories` requires `support.knowledge_base.view`. Returns a paginated list with parent, children, and article-count relationships. Pagination defaults to 25.

```bash
curl --get "{{BASE_URL}}/api/platform/v1/knowledge-base/categories" --header "Accept: application/json" --header "Authorization: Bearer {access_token}" --data-urlencode "per_page=25"
```

## Create a category

POST `/knowledge-base/categories` requires `support.knowledge_base.create`. Required field: `name`. Optional `parent_uuid`, `slug`, `audience`, and `status`. Slug and audience default automatically. Returns HTTP `201`.

```bash
curl --request POST "{{BASE_URL}}/api/platform/v1/knowledge-base/categories" --header "Accept: application/json" --header "Content-Type: application/json" --header "Authorization: Bearer {access_token}" --data '{"name":"Account Security","slug":"account-security","audience":"all","status":"active","parent_uuid":"{parent_category_uuid}"}'
```

## Update a category

PUT or PATCH `/knowledge-base/categories/{category_uuid}` requires `support.knowledge_base.edit`. PATCH accepts partial name, slug, audience, and status fields.

```bash
curl --request PATCH "{{BASE_URL}}/api/platform/v1/knowledge-base/categories/{category_uuid}" --header "Accept: application/json" --header "Content-Type: application/json" --header "Authorization: Bearer {access_token}" --data '{"name":"Account and Security"}'
```

## List articles

GET `/knowledge-base/articles` requires `support.knowledge_base.view`. Returns paginated non-deleted articles with category and creator relationships. Pagination defaults to 25.

```bash
curl --get "{{BASE_URL}}/api/platform/v1/knowledge-base/articles" --header "Accept: application/json" --header "Authorization: Bearer {access_token}" --data-urlencode "status=published" --data-urlencode "per_page=25"
```

## Create an article

POST `/knowledge-base/articles` requires `support.knowledge_base.create`. Required fields are `title` and `body`. Optional category, slug, audience, and status fields are supported. The authenticated platform user is stored as creator. Returns HTTP `201`.

```bash
curl --request POST "{{BASE_URL}}/api/platform/v1/knowledge-base/articles" --header "Accept: application/json" --header "Content-Type: application/json" --header "Authorization: Bearer {access_token}" --data '{"title":"How to configure two-factor authentication","body":"Open security settings, start setup, verify the authenticator code, and save the recovery codes.","category_uuid":"{category_uuid}","audience":"all","status":"draft"}'
```

## Get an article

GET `/knowledge-base/articles/{article_uuid}` requires `support.knowledge_base.view`. Returns the article with category, parent category, creator, and recent activity relationships.

```bash
curl --request GET "{{BASE_URL}}/api/platform/v1/knowledge-base/articles/{article_uuid}" --header "Accept: application/json" --header "Authorization: Bearer {access_token}"
```

## Update an article

PUT or PATCH `/knowledge-base/articles/{article_uuid}` requires `support.knowledge_base.edit`. PATCH accepts partial fields; category UUIDs are resolved and validated as resources.

```bash
curl --request PATCH "{{BASE_URL}}/api/platform/v1/knowledge-base/articles/{article_uuid}" --header "Accept: application/json" --header "Content-Type: application/json" --header "Authorization: Bearer {access_token}" --data '{"category_uuid":"{category_uuid}","body":"Updated setup instructions.","status":"published"}'
```

## Publish an article

POST `/knowledge-base/articles/{article_uuid}/publish` requires `support.knowledge_base.publish`. Sets status to published and records the publication time.

```bash
curl --request POST "{{BASE_URL}}/api/platform/v1/knowledge-base/articles/{article_uuid}/publish" --header "Accept: application/json" --header "Authorization: Bearer {access_token}"
```

## Unpublish an article

POST `/knowledge-base/articles/{article_uuid}/unpublish` requires `support.knowledge_base.publish`. Returns the article to draft and clears publication time.

```bash
curl --request POST "{{BASE_URL}}/api/platform/v1/knowledge-base/articles/{article_uuid}/unpublish" --header "Accept: application/json" --header "Authorization: Bearer {access_token}"
```

## Archive an article

POST `/knowledge-base/articles/{article_uuid}/archive` requires `support.knowledge_base.edit`. Sets status to archived and soft-deletes the article so it no longer appears in the normal list.

```bash
curl --request POST "{{BASE_URL}}/api/platform/v1/knowledge-base/articles/{article_uuid}/archive" --header "Accept: application/json" --header "Authorization: Bearer {access_token}"
```

## Common errors

- HTTP `401`: unauthenticated request.
- HTTP `403`: missing knowledge-base ability.
- HTTP `404`: category or article UUID not found.
- HTTP `422`: validation failed.

# Platform Legal Document APIs

Base path: `/api/platform/v1`. Endpoints require an authenticated platform user, `auth:sanctum`, `platform.token`, and the legal-document ability listed below. Responses follow `docs/api-response-standards.md`.

## List legal documents

GET `/legal/documents` requires `legal_document.view`. Returns paginated legal documents with creator relationships. Optional query parameters are `document_type`, `status`, `search`, and `per_page`.


Request body: None.

Example:

```bash
curl --request GET "{{BASE_URL}}/api/platform/v1/legal/documents" --header "Accept: application/json" --header "Authorization: Bearer {access_token}"
```
## Create a legal document

POST `/legal/documents` requires `legal_document.create`. Required fields are `document_type`, `title`, `version`, and `content`. Optional `status` accepts `draft` or `review` and defaults to `draft`. Returns HTTP `201` with creator and acceptance relationships.

```bash
curl --request POST "{{BASE_URL}}/api/platform/v1/legal/documents" --header "Accept: application/json" --header "Content-Type: application/json" --header "Authorization: Bearer {access_token}" --data '{"document_type":"terms_of_service","title":"Terms of Service","version":"2026.1","content":"These are the terms...","status":"draft"}'
```

## Get a legal document

GET `/legal/documents/{document_uuid}` requires `legal_document.view`. Returns the document, creator, and recent acceptance records with tenant and user relationships.


Request body: None.

Example:

```bash
curl --request GET "{{BASE_URL}}/api/platform/v1/legal/documents/{document_uuid}" --header "Accept: application/json" --header "Authorization: Bearer {access_token}"
```
## Update a legal document

PUT or PATCH `/legal/documents/{document_uuid}` requires `legal_document.edit`. PATCH accepts `document_type`, `title`, `version`, `content`, and `status`. Published documents cannot have their legal content, title, type, or version changed; create a new version instead.


## Publish a legal document

POST `/legal/documents/{document_uuid}/publish` requires `legal_document.publish`. Draft or review documents become `published` and receive `published_at`. Repeating the request for an already published document is idempotent. Superseded or archived documents cannot be published.


## List document acceptances

GET `/legal/documents/{document_uuid}/acceptances` requires `legal_document.view`. Returns paginated acceptance records with related document, tenant, and user data. Optional filters are `tenant_id`, `user_id`, and `per_page`. Acceptance records are read-only through these platform APIs.


Request body: None.

Example:

```bash
curl --request GET "{{BASE_URL}}/api/platform/v1/legal/documents/{document_uuid}/acceptances" --header "Accept: application/json" --header "Authorization: Bearer {access_token}"
```
## Common errors

- HTTP `401`: unauthenticated request.
- HTTP `403`: missing legal-document ability.
- HTTP `404`: legal document or UUID not found.
- HTTP `409`: published legal document is immutable.
- HTTP `422`: validation failed or lifecycle transition is invalid.

# Platform Announcement APIs

Base path: `/api/platform/v1`. Endpoints require an authenticated platform user, `auth:sanctum`, `platform.token`, and the announcement ability listed below. Responses follow `docs/api-response-standards.md`.

## List announcements

GET `/announcements` requires `announcement.view`. Returns paginated, non-deleted announcements with creator relationships. Optional query parameters are `audience`, `status`, `search`, and `per_page`.


Request body: None.

Example:

```bash
curl --request DELETE "{{BASE_URL}}/api/platform/v1/announcements" --header "Accept: application/json" --header "Authorization: Bearer {access_token}"
```
## Create an announcement

POST `/announcements` requires `announcement.create`. Required fields are `title` and `body`. Optional fields are `audience` (`all`, `platform_users`, or `tenants`) and `status` (`draft`, `review`, or `scheduled`). New announcements default to audience `all` and status `draft`. Returns HTTP `201` with the creator relationship.

```bash
curl --request POST "{{BASE_URL}}/api/platform/v1/announcements" --header "Accept: application/json" --header "Content-Type: application/json" --header "Authorization: Bearer {access_token}" --data '{"title":"Scheduled maintenance","body":"The platform will be unavailable during the maintenance window.","audience":"all","status":"draft"}'
```

## Get an announcement

GET `/announcements/{announcement_uuid}` requires `announcement.view`. Returns the complete announcement with its creator relationship.


Request body: None.

Example:

```bash
curl --request GET "{{BASE_URL}}/api/platform/v1/announcements/{announcement_uuid}" --header "Accept: application/json" --header "Authorization: Bearer {access_token}"
```
## Update an announcement

PUT or PATCH `/announcements/{announcement_uuid}` requires `announcement.edit`. PATCH accepts `title`, `body`, `audience`, and `status`. Published announcements cannot have their title, body, or audience changed and return `409 ANNOUNCEMENT_IMMUTABLE`.


## Publish an announcement

POST `/announcements/{announcement_uuid}/publish` requires `announcement.publish`. Draft, review, and scheduled announcements become `published` and receive `published_at`. Repeating the request for an already published announcement is idempotent.


## Archive an announcement

POST `/announcements/{announcement_uuid}/archive` requires `announcement.delete`. Sets status to `archived` and soft-deletes the record. Repeating the request is safe.


## Delete an announcement

DELETE `/announcements/{announcement_uuid}` requires `announcement.delete`. Uses the same soft-archive behavior as the archive endpoint and preserves the historical record.


Request body: None.

Example:

```bash
curl --request DELETE "{{BASE_URL}}/api/platform/v1/announcements/{announcement_uuid}" --header "Accept: application/json" --header "Authorization: Bearer {access_token}"
```
## Common errors

- HTTP `401`: unauthenticated request.
- HTTP `403`: missing announcement ability.
- HTTP `404`: announcement UUID not found.
- HTTP `409`: published announcement is immutable.
- HTTP `422`: validation failed or lifecycle transition is invalid.


# Platform Monitoring APIs

Base path: `/api/platform/v1`. All endpoints require `auth:sanctum`, `platform.token`, and an authenticated platform user. Responses follow `docs/api-response-standards.md`.

## List monitoring services

GET `/services` requires `monitoring.view`. Returns paginated services with health-check log relationships. Optional query parameters are `status`, `service_type`, and `per_page`.


## List service logs

GET `/services/{service_code}/logs` requires `monitoring.view`. Returns paginated health-check history with the related service. Optional query parameters are `status` and `per_page`.


## List API request logs

GET `/api-request-logs` requires `monitoring.view`. Optional filters are `tenant_id`, `status_code`, `method`, and `per_page`. Each record includes related tenant and user data.


## List queue jobs

GET `/queue-jobs` requires `monitoring.view`. Optional filters are `status`, `queue`, and `per_page`.


## Retry or delete a queue job

POST `/queue-jobs/{job_id}/retry` requires `monitoring.manage`. Marks an eligible job as `retry_queued` and increments its attempt count. The operation is idempotent for already running, successful, or retry-queued jobs.

DELETE `/queue-jobs/{job_id}` requires `monitoring.manage`. Marks the history record as `deleted` without physically removing it.


Request body: None.

Example:

```bash
curl --request POST "{{BASE_URL}}/api/platform/v1/queue-jobs/{job_id}/retry" --header "Authorization: Bearer {access_token}"
curl --request DELETE "{{BASE_URL}}/api/platform/v1/queue-jobs/{job_id}" --header "Authorization: Bearer {access_token}"
```
## Scheduler logs

GET `/scheduler-logs` requires `monitoring.view`. Optional filters are `status`, `command`, and `per_page`.


Request body: None.

Example:

```bash
curl --get "{{BASE_URL}}/api/platform/v1/scheduler-logs" --header "Authorization: Bearer {access_token}"
```
## Alerts

GET `/alerts` requires `monitoring.view`; optional filters are `status`, `severity`, and `per_page`. Alertable and resolver relationships are included.

POST `/alerts/{alert_id}/resolve` requires `monitoring.manage`. Request body: `{"resolution_notes":"Investigated and recovered the service."}`. Returns the resolved alert with relationships.


## Incidents

GET `/incidents` requires `monitoring.view`; optional filters are `status`, `severity`, and `per_page`.

POST `/incidents` requires `monitoring.manage`. Required fields are `title` and `severity`; optional fields are `status`, `started_at`, and `summary`. Returns HTTP `201` with the incident and relationships.

GET `/incidents/{incident_id}` requires `monitoring.view`. Returns the incident with activity history and actor relationships.

PUT or PATCH `/incidents/{incident_id}` requires `monitoring.manage`. PATCH accepts partial incident fields.

POST `/incidents/{incident_id}/resolve` requires `monitoring.manage`. Accepts optional `resolution_notes`, records the resolver and timestamp, and creates an audit event.


## Tenant usage snapshots

GET `/tenant-usage-snapshots` requires `monitoring.view`. Optional filters are `tenant_id` and `per_page`. Each record includes its related tenant.

# Platform Integration Provider APIs

Base path: `/api/platform/v1`. Provider catalog endpoints require an authenticated platform user and follow the standard response envelope.


## List providers

GET `/providers` requires `integration.view`. Optional filters are `search`, `category`, `auth_type`, `status`, and `per_page`. Records include tenant-integration and tenant relationships; credentials are not returned.


## Create a provider

POST `/providers` requires `integration.create`. Required fields are `name`, `code`, `category`, and `auth_type`. Optional fields are `status` (`active` or `inactive`) and non-secret `metadata`. Returns HTTP `201` with relationships.

Example:

```bash
curl --request POST "{{BASE_URL}}/api/platform/v1/providers" --header "Accept: application/json" --header "Content-Type: application/json" --header "Authorization: Bearer {access_token}" --data '{"name":"Razorpay","code":"razorpay","category":"payment","auth_type":"api_key","status":"active","metadata":{"api_version":"v1","supports_webhooks":true}}'
```

Secret, password, token, credential, private-key, and API-key metadata fields are rejected.

## Update a provider

PUT or PATCH `/providers/{provider_code}` requires `integration.edit`. PATCH accepts partial `name`, `category`, `auth_type`, `status`, and `metadata` fields. The provider code is stable and cannot be changed. Updates are audited with old and new values.

# Platform Settings APIs

Base path: `/api/platform/v1`. Settings endpoints require an authenticated platform user and `setting.view` or `setting.edit` as noted.


## Get platform settings

GET `/platform` requires `setting.view`. Optional query parameter: `group`. Results include the related `updated_by` platform user. Sensitive or encrypted values are returned as `[REDACTED]`.


## Update platform settings

PUT `/platform` requires `setting.edit`. Send a non-empty `settings` array. Each item requires `group`, `key`, and `value`; `value_type` and `is_encrypted` are optional. Supported groups are `general`, `security`, `billing`, `email`, `storage`, `queue`, and `integration`.

Example body:

```json
{"settings":[{"group":"email","key":"driver","value":"smtp","value_type":"string"},{"group":"security","key":"session_timeout","value":60,"value_type":"integer"}]}
```

# Notification Template APIs

Global notification-template endpoints require `setting.view` for reads and `setting.edit` for writes. Tenant-specific templates are excluded.


## List templates

GET `/notification-templates` supports `channel`, `status`, and `per_page`. Returns paginated global templates.


## Create a template

POST `/notification-templates` requires `setting.edit`. Required fields are `code`, `channel`, and `body`. Supported channels are `email`, `sms`, `in_app`, `push`, and `webhook`. Email templates require `subject`; `variables` and `status` are optional. Returns HTTP `201`.


## Update a template

PUT or PATCH `/notification-templates/{template_uuid}` requires `setting.edit`. PATCH accepts `channel`, `subject`, `body`, `variables`, and `status`.

# Backup APIs

Backup endpoints require an authenticated platform user and `setting.view` for reads or `setting.edit` for writes. Backup requests are queued; a worker must process queued runs and update their status.


## Get backup settings and latest run

GET `/backups` requires `setting.view`. Returns backup settings with updater relationships and the latest backup run with its associated file reference. Protected paths are never returned.


## Update backup settings

PUT `/backups` requires `setting.edit`. Send a non-empty `settings` array containing `key` and `value`. Changes are audited with masked old and new values.


## Queue a backup

POST `/backups/run` requires `setting.edit`. Optional body: `{"backup_type":"full"}`. Creates a `queued` backup run and returns HTTP `202`.


## List or get backup runs

GET `/backups/runs` requires `setting.view`; optional filters are `status` and `per_page`. GET `/backups/runs/{run_uuid}` returns one run with safe file metadata.


## Get a backup download reference

GET `/backups/runs/{run_uuid}/download` requires `setting.view`. Only completed runs with an associated file can return a download reference. Raw filesystem paths are not exposed.

# Platform Audit and Security APIs

Base path: `/api/platform/v1`. All audit endpoints require an authenticated platform user.


Request body: None.

Example:

```bash
curl --request GET "{{BASE_URL}}/api/platform/v1/backups/runs/{run_uuid}/download" --header "Authorization: Bearer {access_token}"
```
## List activity logs

GET `/audit/activity-logs` requires `audit_log.view`. Optional filters are `tenant_id`, `event`, `subject_type`, `request_id`, `actor_platform_user_id`, `from`, `to`, `sort`, `direction`, and `per_page`. Results include actor and subject relationships.


## List security events

GET `/audit/security-events` requires `audit_log.view`. Optional filters are `tenant_id`, `user_id`, `event`, `severity`, `review_status`, `from`, `to`, `sort`, `direction`, and `per_page`. Metadata is redacted before returning it.


## Review a security event

POST `/audit/security-events/{event_id}/review` requires `audit_log.view`. Required body fields are `review_status` (`reviewed`, `dismissed`, or `escalated`) and `review_notes` with at least 10 characters.

```bash
curl --request POST "{{BASE_URL}}/api/platform/v1/audit/security-events/{event_id}/review" --header "Accept: application/json" --header "Content-Type: application/json" --header "Authorization: Bearer {access_token}" --data '{"review_status":"reviewed","review_notes":"Confirmed expected administrator activity."}'
```

The original event is preserved, and repeated identical reviews are idempotent.

## Export audit records

POST `/audit/export` requires `audit_log.export`. Required field: `source` (`activity_logs` or `security_events`). Optional fields are `format` (`csv`), `delivery` (`download` or `job`), `tenant_id`, `event`, `severity`, `status`, `from`, and `to`.

Small `download` exports return masked CSV and are limited to 5,000 records. `job` delivery creates a tracked `report_export_jobs` record and returns HTTP `202`. CSV cells are escaped to prevent spreadsheet formula injection.

# Common errors for these platform APIs

- HTTP `401`: unauthenticated request.
- HTTP `403`: missing platform permission.
- HTTP `404`: requested resource not found.
- HTTP `409`: requested backup is not available for download.
- HTTP `422`: validation failed or an export exceeds the immediate-download limit.

# Platform Announcement API Endpoint Index

Base path: `/api/platform/v1`. These endpoints require an authenticated platform user and follow `docs/api-response-standards.md`.

| Method | Endpoint | Ability |
| --- | --- | --- |
| GET | `/announcements` | `announcement.view` |
| POST | `/announcements` | `announcement.create` |
| GET | `/announcements/{announcement_uuid}` | `announcement.view` |
| PUT, PATCH | `/announcements/{announcement_uuid}` | `announcement.edit` |
| POST | `/announcements/{announcement_uuid}/publish` | `announcement.publish` |
| POST | `/announcements/{announcement_uuid}/archive` | `announcement.delete` |
| DELETE | `/announcements/{announcement_uuid}` | `announcement.delete` |

Announcement details and behavior are documented in the `Platform Announcement APIs` section above. Lists are paginated and include creator relationships. Published announcements are immutable, while archive and delete are idempotent soft-archive operations.

# Platform Webhook Endpoint and Delivery APIs

Base path: `/api/platform/v1`. Webhook APIs require an authenticated platform user and follow `docs/api-response-standards.md`.


## List webhook endpoints

GET `/webhook-endpoints` requires `integration.view`. Returns paginated endpoints with optional tenant relationships. Optional query parameters are `tenant_id`, `status`, `search`, and `per_page`. Secret hashes are never returned.


Request body: None.

Example:

```bash
curl --request GET "{{BASE_URL}}/api/platform/v1/webhook-endpoints" --header "Accept: application/json" --header "Authorization: Bearer {access_token}"
```
## Create a webhook endpoint

POST `/webhook-endpoints` requires `integration.create`. Required fields are `name`, `url`, and a non-empty `events` array. Optional fields are `secret`, `status`, and `tenant_uuid`. Only HTTPS or HTTP URLs that do not target localhost, local domains, or private/reserved IP addresses are accepted. Secrets must be at least 16 characters and are stored as hashes.

```bash
curl --request POST "{{BASE_URL}}/api/platform/v1/webhook-endpoints" --header "Accept: application/json" --header "Content-Type: application/json" --header "Authorization: Bearer {access_token}" --data '{"name":"Automation receiver","url":"https://example.com/hooks/platform","events":["invoice.paid","subscription.renewed"],"secret":"replace-with-a-strong-secret","status":"active"}'
```

## Get a webhook endpoint

GET `/webhook-endpoints/{endpoint_uuid}` requires `integration.view`. Returns the endpoint, tenant relationship, and recent delivery records. The secret and secret hash are excluded.


Request body: None.

Example:

```bash
curl --request GET "{{BASE_URL}}/api/platform/v1/webhook-endpoints/{endpoint_uuid}" --header "Accept: application/json" --header "Authorization: Bearer {access_token}"
```
## Update a webhook endpoint

PUT or PATCH `/webhook-endpoints/{endpoint_uuid}` requires `integration.edit`. PATCH accepts `name`, `url`, `events`, `secret`, `status`, and `tenant_uuid`. Secret or URL changes are audited and do not rewrite historical deliveries.


## Delete a webhook endpoint

DELETE `/webhook-endpoints/{endpoint_uuid}` requires `integration.delete`. Sets status to `inactive` and soft-deletes the endpoint. Repeating the operation is safe and preserves delivery history.


Request body: None.

Example:

```bash
curl --request DELETE "{{BASE_URL}}/api/platform/v1/webhook-endpoints/{endpoint_uuid}" --header "Accept: application/json" --header "Authorization: Bearer {access_token}"
```
## List endpoint deliveries

GET `/webhook-endpoints/{endpoint_uuid}/deliveries` requires `integration.view`. Returns paginated delivery history with endpoint relationships. Optional filters are `status`, `event`, and `per_page`. Payloads are redacted before response.


Request body: None.

Example:

```bash
curl --request GET "{{BASE_URL}}/api/platform/v1/webhook-endpoints/{endpoint_uuid}/deliveries" --header "Accept: application/json" --header "Authorization: Bearer {access_token}"
```
## Get a webhook delivery

GET `/webhook-deliveries/{delivery_uuid}` requires `integration.view`. Returns one delivery with endpoint and tenant relationships. Sensitive payload fields are returned as `[REDACTED]`.


Request body: None.

Example:

```bash
curl --request GET "{{BASE_URL}}/api/platform/v1/webhook-deliveries/{delivery_uuid}" --header "Accept: application/json" --header "Authorization: Bearer {access_token}"
```
## Retry a webhook delivery

POST `/webhook-deliveries/{delivery_uuid}/retry` requires `integration.edit`. Failed or retryable deliveries are marked `retry_queued`, their retry count is incremented, and `queued_at` is recorded. Delivered, already queued, or inactive-endpoint deliveries are handled safely.


## Common errors

- HTTP `401`: unauthenticated request.
- HTTP `403`: missing integration permission.
- HTTP `404`: webhook endpoint or delivery UUID not found.
- HTTP `409`: endpoint is inactive or the delivery cannot be retried.
- HTTP `422`: validation failed, including an unsafe URL or invalid event list.

# Platform API Token APIs

Base path: `/api/platform/v1`. Token-management endpoints require an authenticated platform user and follow `docs/api-response-standards.md`. The raw token is returned only by create and rotate responses.

## List API tokens

GET `/api-tokens` requires `api_token.view`. Returns paginated safe metadata only: UUID, name, abilities, creator relationship, usage time, expiry, creation time, and revocation time. Hashes, encrypted previews, and raw secrets are never returned. Optional query parameters are `status` (`active` or `revoked`), `search`, and `per_page`.


Request body: None.

Example:

```bash
curl --request GET "{{BASE_URL}}/api/platform/v1/api-tokens" --header "Accept: application/json" --header "Authorization: Bearer {access_token}"
```
## Create an API token

POST `/api-tokens` requires `api_token.create`. Required fields are `name` and `abilities`; each ability must be an active platform permission. `expires_at` is optional but, when supplied, must be in the future. Returns HTTP `201` with safe token metadata and `raw_token` exactly once.

```bash
curl --request POST "{{BASE_URL}}/api/platform/v1/api-tokens" --header "Accept: application/json" --header "Content-Type: application/json" --header "Authorization: Bearer {access_token}" --data '{"name":"Deployment automation","abilities":["monitoring.view","audit_log.view"],"expires_at":"2030-01-01T00:00:00Z"}'
```

The raw value begins with `plat_` and cannot be recovered after the response.

## Get an API token

GET `/api-tokens/{token_uuid}` requires `api_token.view`. Returns safe metadata and the creator relationship only. Token hashes, encrypted previews, and raw values are excluded.


Request body: None.

Example:

```bash
curl --request GET "{{BASE_URL}}/api/platform/v1/api-tokens/{token_uuid}" --header "Accept: application/json" --header "Authorization: Bearer {access_token}"
```
## Rotate an API token

POST `/api-tokens/{token_uuid}/rotate` requires `api_token.rotate`. Optionally accepts `expires_at`. Rotation is atomic, immediately invalidates the previous token, preserves the same token UUID, and returns the replacement `raw_token` once. Replacing the token does not change its abilities.


## Revoke an API token

POST `/api-tokens/{token_uuid}/revoke` requires `api_token.revoke`. Soft-revokes the token immediately. Repeating the request returns an already-revoked success response; an unknown UUID returns `404 PLATFORM_API_TOKEN_NOT_FOUND`.


## Common errors

- HTTP `401`: unauthenticated request.
- HTTP `403`: missing API-token permission.
- HTTP `404`: API token UUID not found.
- HTTP `422`: validation failed, including an unknown platform ability or expired timestamp.

# Platform Shared Document and Audit APIs

Base path: `/api/platform/v1`. These routes require platform authentication and the permissions shown below. Responses include related entities and file metadata; storage paths are never exposed.

| Method | Endpoint | Permission |
| --- | --- | --- |
| GET | `/files` | `document.view` |
| POST | `/files` | `document.upload` |
| GET | `/files/{file_uuid}` | `document.view` |
| GET | `/files/{file_uuid}/download` | `document.view` |
| DELETE | `/files/{file_uuid}` | `document.delete` |
| GET | `/attachments` | `document.view` |
| POST | `/attachments` | `document.upload` |
| DELETE | `/attachments/{attachment_id}` | `document.delete` |
| GET | `/notes` | `document.view` |
| POST | `/notes` | `document.upload` |
| PUT,PATCH | `/notes/{note_uuid}` | `document.upload` |
| DELETE | `/notes/{note_uuid}` | `document.delete` |
| GET | `/activity-logs` | `audit_log.view` |
| GET | `/activity-logs/{activity_id}/compare` | `audit_log.view` |

`POST /files` accepts multipart field `file` (maximum 51200 KB), optional `disk`, `visibility` (`private`, `public`, `tenant`), and `purpose`. Platform files have `tenant_id = NULL`; private and tenant downloads return URLs valid for 10 minutes, while public downloads return the configured disk URL.

`GET /attachments` requires `attachable_type` and `attachable_uuid`. `POST /attachments` requires `file_uuid`, `attachable_type`, and `attachable_uuid`; `label` is optional. `GET /notes` requires `notable_type` and `notable_uuid`. Notes accept `note` or `body`, plus optional visibility (`private`, `team`, `tenant`, `client`). Entity UUIDs are resolved and scope-checked before use. Activity-log listing supports `subject_type`, `subject_id`, `event`, and `per_page`; compare returns decoded old/new values and changed fields.

Validation failures return `422`, missing resources return `404`, and attached files cannot be deleted (`409`). Mutations create activity-log records.




Request body: None.

Example:

```bash
curl --request GET "{{BASE_URL}}/api/platform/v1/files" --header "Accept: application/json" --header "Authorization: Bearer {access_token}"
```

# Tenant registration and platform tenant APIs

Base URL: `/api`. Send `Accept: application/json` and, for JSON bodies, `Content-Type: application/json`. Platform routes require a platform bearer token and the permission below. Route definitions are in `routes/api.php` and `routes/api-platform.php`.

All responses follow `docs/api-response-standards.md`: success responses contain `success`, `message`, and `data`; validation failures return HTTP 422 with `success: false`, `message: "Validation failed."`, and field-keyed `errors`. Missing resources return 404 with `data: null`. Authentication/permission failures return 401/403. Unexpected failures return 500 without exception details.

## API list

| Method | Endpoint | Permission |
| --- | --- | --- |
| GET | `/api/auth/v1/tenants/plans` | Public |
| POST | `/api/auth/v1/tenants/register` | Public; rate limited |
| POST | `/api/auth/v1/tenants/register/payment/confirm` | Public; rate limited |
| GET | `/api/platform/v1/tenants` | `tenant.view` |
| POST | `/api/platform/v1/tenants` | `tenant.create` |
| GET | `/api/platform/v1/tenants/{tenant_uuid}` | `tenant.view` |
| PUT,PATCH | `/api/platform/v1/tenants/{tenant_uuid}` | `tenant.edit` |
| DELETE | `/api/platform/v1/tenants/bulk` | `tenant.delete` |
| DELETE | `/api/platform/v1/tenants/{tenant_uuid}` | `tenant.delete` |
| POST | `/api/platform/v1/tenants/{tenant_uuid}/restore` | `tenant.edit` |
| POST | `/api/platform/v1/tenants/{tenant_uuid}/activate` | `tenant.activate` |
| POST | `/api/platform/v1/tenants/{tenant_uuid}/suspend` | `tenant.suspend` |
| POST | `/api/platform/v1/tenants/{tenant_uuid}/reactivate` | `tenant.activate` |
| POST | `/api/platform/v1/tenants/{tenant_uuid}/archive` | `tenant.delete` |
| POST | `/api/platform/v1/tenants/{tenant_uuid}/extend-trial` | `subscription.edit` |
| POST | `/api/platform/v1/tenants/{tenant_uuid}/change-plan` | `subscription.edit` |
| POST | `/api/platform/v1/tenants/{tenant_uuid}/reset-owner-password` | `tenant.edit` |
| POST | `/api/platform/v1/tenants/{tenant_uuid}/payment-order` | `billing.payment.create` |
| POST | `/api/platform/v1/tenants/{tenant_uuid}/impersonate` | `tenant.impersonate` |
| DELETE | `/api/platform/v1/tenants/{tenant_uuid}/impersonate/{session_uuid}` | `tenant.impersonate` |
| GET | `/api/platform/v1/tenants/{tenant_uuid}/{tab}` | `tenant.view` |
| PUT | `/api/platform/v1/tenants/{tenant_uuid}/modules` | `module.edit` |

## Tenant API cURL examples

Set these variables first:

```bash
BASE_URL="http://127.0.0.1:8000"
TOKEN="your-platform-access-token"
TENANT_UUID="tenant-uuid"
```

Public registration APIs:

```bash
curl --request GET "$BASE_URL/api/auth/v1/tenants/plans" --header "Accept: application/json"
curl --request POST "$BASE_URL/api/auth/v1/tenants/register" --header "Accept: application/json" --header "Content-Type: application/json" --data '{"organization_name":"Example Company","default_currency":"INR","default_timezone":"Asia/Kolkata","owner":{"first_name":"Jane","email":"jane@example.com","password":"secret123","password_confirmation":"secret123"},"office":{"office_name":"Head Office"},"subscription":{"type":"trial","billing_cycle":"monthly"},"payment":{"method":"free"}}'
curl --request POST "$BASE_URL/api/auth/v1/tenants/register/payment/confirm" --header "Accept: application/json" --header "Content-Type: application/json" --data '{"tenant_uuid":"tenant-uuid","razorpay_order_id":"order_ABC123","razorpay_payment_id":"pay_XYZ789","razorpay_signature":"razorpay-signature"}'
```

Platform tenant APIs:

```bash
curl --request GET "$BASE_URL/api/platform/v1/tenants" --header "Accept: application/json" --header "Authorization: Bearer $TOKEN"
curl --request POST "$BASE_URL/api/platform/v1/tenants" --header "Accept: application/json" --header "Content-Type: application/json" --header "Authorization: Bearer $TOKEN" --data '{"organization_name":"Example Company","owner":{"first_name":"Jane","email":"jane@example.com"},"office":{}}'
curl --request GET "$BASE_URL/api/platform/v1/tenants/$TENANT_UUID" --header "Accept: application/json" --header "Authorization: Bearer $TOKEN"
curl --request PATCH "$BASE_URL/api/platform/v1/tenants/$TENANT_UUID" --header "Content-Type: application/json" --header "Authorization: Bearer $TOKEN" --data '{"display_name":"Example Ltd"}'
curl --request DELETE "$BASE_URL/api/platform/v1/tenants/bulk" --header "Content-Type: application/json" --header "Authorization: Bearer $TOKEN" --data '{"tenant_uuids":["tenant-uuid"],"reason":"Customer requested closure"}'
curl --request DELETE "$BASE_URL/api/platform/v1/tenants/$TENANT_UUID" --header "Authorization: Bearer $TOKEN"
curl --request POST "$BASE_URL/api/platform/v1/tenants/$TENANT_UUID/restore" --header "Authorization: Bearer $TOKEN"
curl --request POST "$BASE_URL/api/platform/v1/tenants/$TENANT_UUID/activate" --header "Authorization: Bearer $TOKEN"
curl --request POST "$BASE_URL/api/platform/v1/tenants/$TENANT_UUID/suspend" --header "Content-Type: application/json" --header "Authorization: Bearer $TOKEN" --data '{"reason":"Payment overdue"}'
curl --request POST "$BASE_URL/api/platform/v1/tenants/$TENANT_UUID/reactivate" --header "Authorization: Bearer $TOKEN"
curl --request POST "$BASE_URL/api/platform/v1/tenants/$TENANT_UUID/archive" --header "Authorization: Bearer $TOKEN"
curl --request POST "$BASE_URL/api/platform/v1/tenants/$TENANT_UUID/extend-trial" --header "Content-Type: application/json" --header "Authorization: Bearer $TOKEN" --data '{"trial_ends_at":"2026-10-15 23:59:59","reason":"Approved extension"}'
curl --request POST "$BASE_URL/api/platform/v1/tenants/$TENANT_UUID/change-plan" --header "Content-Type: application/json" --header "Authorization: Bearer $TOKEN" --data '{"plan_uuid":"plan-uuid","reason":"Upgrade requested"}'
curl --request POST "$BASE_URL/api/platform/v1/tenants/$TENANT_UUID/reset-owner-password" --header "Content-Type: application/json" --header "Authorization: Bearer $TOKEN" --data '{"reason":"Support request"}'
curl --request POST "$BASE_URL/api/platform/v1/tenants/$TENANT_UUID/payment-order" --header "Content-Type: application/json" --header "Authorization: Bearer $TOKEN" --data '{"amount":499,"currency":"INR","method":"online"}'
curl --request POST "$BASE_URL/api/platform/v1/tenants/$TENANT_UUID/impersonate" --header "Content-Type: application/json" --header "Authorization: Bearer $TOKEN" --data '{"reason":"Investigate reported issue","duration_minutes":30}'
curl --request DELETE "$BASE_URL/api/platform/v1/tenants/$TENANT_UUID/impersonate/session-uuid" --header "Authorization: Bearer $TOKEN"
curl --request GET "$BASE_URL/api/platform/v1/tenants/$TENANT_UUID/users" --header "Accept: application/json" --header "Authorization: Bearer $TOKEN"
curl --request PUT "$BASE_URL/api/platform/v1/tenants/$TENANT_UUID/modules" --header "Content-Type: application/json" --header "Authorization: Bearer $TOKEN" --data '{"modules":[{"module_code":"crm","enabled":true}]}'
```

Replace placeholder UUIDs and request values with real records from your environment. The `{tab}` example above uses `users`; valid tabs are documented in the Tenant tabs section.

## Public plans and registration

`GET /api/auth/v1/tenants/plans` needs no body. Returns `data.plans`, ordered by `base_price`, with `uuid`, `name`, `code`, `billing_cycle`, `base_price`, `currency`, `trial_days`, and `description`. Only active, public, non-deleted plans are eligible.

`POST /api/auth/v1/tenants/register` example (replace the plan UUID with an eligible UUID):

```json
{
  "organization_name": "Example Company",
  "owner": {
    "first_name": "Jane",
    "last_name": "Doe",
    "email": "jane@example.com",
    "password": "secret123",
    "password_confirmation": "secret123"
  },
  "office": {
    "office_name": "Head Office",
    "address_line_1": "1 Main Street",
    "postal_code": "400001"
  },
  "plan_uuid": "11111111-1111-4111-8111-111111111111",
  "trial_days": 15,
  "subscription": {"type": "trial", "billing_cycle": "monthly"},
  "payment": {"method": "free"}
}
```

Required: `organization_name`, `owner.first_name`, `owner.email`, and a confirmed string `owner.password` of 8–255 characters. Owner email is trimmed and lowercased. Office is optional for public registration; a default head office is always created.

Optional organization fields: `legal_name`, `display_name`, unique `organization_code` and `slug` (letters, numbers, dashes, underscores), `business_type_id`, `industry_id`, `company_size` (`self`, `small`, `medium`, `large`, `enterprise`), `gst_number`, `pan_number`, `registration_number`, `website`, `description`, `default_currency` (3 characters), and `default_timezone` (valid timezone). Related business type and industry IDs must exist.

Optional owner fields: `last_name`, `display_name`, and `mobile`. Office fields: `office_name`, `office_code`, `office_type`, `address_line_1`, `address_line_2`, `landmark`, `country_id`, `state_id`, `city_id`, `postal_code`, `contact_person`, `contact_email`, `contact_phone`, `gst_number`, `status`, and `working_hours` (object/array). Location IDs must exist. Office types: `head_office`, `branch`, `regional`, `warehouse`, `factory`, `store`, `remote`, `franchise`; office status: `active`, `inactive`.

`plan_uuid` must identify an eligible public plan. If omitted, the cheapest eligible plan is selected. No eligible plan returns 422 and creates nothing. `trial_days` accepts 0–365 and otherwise uses the plan default. Subscription types: `trial`, `paid`, `free`, `standard`; billing cycles: `monthly`, `quarterly`, `half-yearly`, `yearly`, `lifetime`, `one_time`. The selected plan determines the price regardless of subscription type.

Payment methods: `online`, `cash`, `free`. Omitted method defaults to `free` for a zero-price plan and `online` otherwise. `free` is rejected for a non-zero plan. Zero-value registration creates a settled invoice/payment; a non-zero cash registration creates a pending payment and unpaid invoice. Online registration creates an order and pending payment. After Razorpay checkout, the client must call `POST /api/auth/v1/tenants/register/payment/confirm` with `tenant_uuid`, `razorpay_order_id`, `razorpay_payment_id`, and `razorpay_signature`; the server verifies the signature and marks the payment and invoice as paid.

HTTP 201 returns `data.access_token`, `token_type`, `expires_at`, `tenant`, `owner`, `office`, `subscription` (including `plan`), `invoice` (including `items` and subscription), `invoice_items`, `payment_order`, `payment` (including tenant/invoice/subscription), `razorpay_key`, `roles: ["owner"]`, and active tenant `permissions`. The bearer token expires in 12 hours.

Tenant, head office, owner, owner role/permission assignments, subscription/version, default settings, invoice/items, payment, audit/security events, and token are created in one database transaction. Registration email is queued after commit; run the configured queue worker for delivery. Gateway failure rolls back local registration records.

## Confirm registration payment

`POST /api/auth/v1/tenants/register/payment/confirm` is public and rate limited. It confirms a successful Razorpay checkout for a tenant registration.

Request body:

```json
{
  "tenant_uuid": "11111111-1111-4111-8111-111111111111",
  "razorpay_order_id": "order_ABC123",
  "razorpay_payment_id": "pay_XYZ789",
  "razorpay_signature": "generated-by-razorpay"
}
```

All fields are required. The server validates the Razorpay HMAC signature, verifies that the order belongs to the tenant registration, then updates the matching payment to `paid`, records `paid_at`, marks the related invoice as `paid` with zero balance, and activates a subscription whose status is `pending_payment`. Invalid signatures or unknown orders return HTTP 422. Repeating a valid confirmation is idempotent.

## Platform create, list, detail, and update

`POST /api/platform/v1/tenants` accepts the organization/owner/office structure above, requires `organization_name`, `owner.first_name`, `owner.email`, and an `office` object (an empty object is accepted). Owner password is optional; a 16-character password is generated when absent. Platform creation does not accept a `payment` object or create registration billing records.

Platform-only fields include `logo_file_id`, `favicon_file_id` (existing, non-deleted platform files or files belonging to the updated tenant), tenant `status`, `owner.status` (`active`, `inactive`, `invited`, `suspended`), and `owner.send_invite` (boolean). Tenant statuses: `pending`, `trial`, `active`, `suspended`, `expired`, `cancelled`, `archived`, `inactive`; `inactive` is stored as `pending`. Public registration cannot choose tenant/owner status or branding file IDs.

Platform subscription fields additionally include `starts_at`, `expires_at`, `trial_starts_at`, `trial_ends_at`, `renewal_type` (`manual`, `auto`), and `auto_renew` (boolean). Expiry cannot precede the start. HTTP 201 returns `data.tenant`, `owner`, `office`, and `subscription`. `temporary_password` is included only when `APP_ENV=local`; passwords/hashes and two-factor secrets are otherwise omitted.

`GET /api/platform/v1/tenants` lists non-deleted tenants newest first. Query: `search` (organization name, slug, organization code), `filter[status]`, `page`, and `per_page` (1–100, default 10). Returns an array in `data`, pagination in `meta.current_page`, `per_page`, `total`, `last_page`, and global non-deleted tenant counts in `meta.stats.total`, `active`, `trial`, `suspended`.

Tenant list records intentionally contain only `id`, `uuid`, `organization_name`, `legal_name`, `display_name`, `organization_code`, `owner_name`, `owner_email`, `users_count`, `slug`, `trial_ends_at`, `status`, `plan_name`, `subscription_status`, and `created_at`. Related owner, office, subscription, billing, and other records are not included in list responses; use the tenant tab endpoints when those details are needed.

`GET /api/platform/v1/tenants/{tenant_uuid}` returns the same minimal flat tenant summary fields as the list endpoint. Numeric IDs cannot replace the UUID. Use the tenant tab endpoints for expanded related data.

`PUT|PATCH /api/platform/v1/tenants/{tenant_uuid}` accepts partial fields and returns the same refreshed detail structure. Omitted fields are preserved. Supplied owner/head-office objects are upserted; creating a missing owner requires first name and email. Subscription changes preserve the existing plan when no `plan_uuid` is supplied. Prefer the dedicated change-plan endpoint; plan changes submitted through update are also audited.

Example partial update:

```json
{"display_name": "Example Ltd", "owner": {"mobile": "+919876543210"}, "office": {"address_line_1": "2 Main Street"}}
```

## Archive, restore, activate, suspend, and reactivate

`DELETE /api/platform/v1/tenants/bulk` body:

```json
{"tenant_uuids": ["11111111-1111-4111-8111-111111111111"], "reason": "Customer requested closure"}
```

Accepts 1–100 distinct UUIDs. Archives matching non-deleted tenants atomically, sets `status=archived` and `deleted_at`, and returns `data.archived` (count). Unknown/already-deleted UUIDs are not counted.

`DELETE /api/platform/v1/tenants/{tenant_uuid}` archives rather than hard-deletes and returns HTTP 200 with `data: null`. `POST .../{tenant_uuid}/archive` performs the same archive and returns tenant detail.

`POST .../{tenant_uuid}/restore` removes `deleted_at` and returns tenant detail without changing status. `POST .../{tenant_uuid}/activate` and `POST .../{tenant_uuid}/reactivate` set status to `active` and return detail. Restore a soft-deleted tenant before activating. These actions accept optional string `reason` (maximum 1000 characters); no body is otherwise required.

`POST .../{tenant_uuid}/suspend` body:

```json
{"reason": "Payment overdue", "notify_owner": true, "suspended_until": "2026-10-01 00:00:00"}
```

Reason is required. Optional `notify_owner` queues an owner notification; `suspended_until` is a valid date recorded in audit metadata. This endpoint sets tenant status to `suspended`; it does not schedule automatic reactivation or change individual user statuses. Lifecycle changes are audited and create security records.

## Extend trial and change plan

`POST .../{tenant_uuid}/extend-trial` body:

```json
{"trial_ends_at": "2026-10-15 23:59:59", "reason": "Approved extension"}
```

`trial_ends_at` is required and must be a valid date. Updates the tenant and latest non-deleted subscription together; returns refreshed tenant detail.

`POST .../{tenant_uuid}/change-plan` body:

```json
{"plan_uuid": "11111111-1111-4111-8111-111111111111", "billing_cycle": "monthly", "starts_at": "2026-09-06", "expires_at": "2026-10-06", "renewal_type": "manual", "auto_renew": false, "reason": "Upgrade requested"}
```

Only `plan_uuid` is required; it must reference a non-deleted plan. Upserts the latest subscription under a tenant lock, creates a numbered subscription version, records an audit event, and returns `data.subscription` with plan and tenant objects.

## Reset owner password

`POST .../{tenant_uuid}/reset-owner-password` body:

```json
{"password": "new-secret123", "notify_owner": false, "reason": "Support request"}
```

All fields are optional. Supplied password must contain 8–255 characters; otherwise a random 16-character password is generated. The hash is stored, owner bearer tokens are revoked, and security/audit records are created. Returns safe `data.owner`; `data.temporary_password` exists only in local mode. Notification email contains no password and directs the owner to password recovery.

## Payment order

`POST .../{tenant_uuid}/payment-order` body:

```json
{"amount": 499.00, "currency": "INR", "method": "online", "subscription_uuid": "11111111-1111-4111-8111-111111111111", "notes": {"reference": "INV-1001"}}
```

Required: `amount` (1–9999999999.99, up to two decimal places) and `method` (`online`, `cash`). Optional `currency` has three characters; default is subscription/tenant currency. Optional `subscription_uuid` must identify a non-deleted subscription belonging to this tenant; otherwise the latest subscription is used. `notes` is an object/array.

Cash returns HTTP 201 with a paid `data.payment`. Online returns 201 with `data.order`, pending `data.payment`, and `data.razorpay_key`. Payment includes tenant, invoice, and subscription/plan relationships. Set `RAZORPAY_KEY_ID` and `RAZORPAY_KEY_SECRET`; missing credentials return 503, gateway HTTP/connection failure returns 502. Gateway responses never confirm settlement merely because an order exists.

## Impersonation sessions

`POST .../{tenant_uuid}/impersonate` body:

```json
{"reason": "Investigate reported issue", "duration_minutes": 30, "target_user_uuid": "11111111-1111-4111-8111-111111111111"}
```

Reason is required (5–1000 characters); duration is required (5–240 minutes). Optional target UUID must belong to the tenant and not be soft-deleted. Defaults to the owner. Returns HTTP 201 with `data.session`, including tenant, platform user, target user, status, and expiry. Creates security and audit events. This endpoint records the support session; it does not issue a tenant bearer token.

`DELETE .../{tenant_uuid}/impersonate/{session_uuid}` marks the matching session ended and records `ended_at`; returns 200 with `data: null`. Unknown sessions and sessions belonging to another tenant return 404. Repeated ending succeeds without changing the original end timestamp.

## Tenant tabs

`GET .../{tenant_uuid}/{tab}` requires no body and returns the result in `data[tab]`.

| Tab | Data |
| --- | --- |
| `users` | Non-deleted users with roles/default office; secrets excluded |
| `offices` | Non-deleted offices with country/state/city |
| `subscription` | Latest non-deleted subscription with plan and plan aliases |
| `billing` | Invoices/items/subscriptions/plans and payments/invoices/subscriptions/plans |
| `usage` | Latest 12 usage snapshots |
| `modules` | Overrides with module and updating platform user |
| `settings` | Settings with decoded JSON values; encrypted values are redacted |
| `integrations` | Integrations with provider; credentials excluded |
| `security` | Newest security events with user/tenant |
| `support` | Tickets with assignee and remote-login sessions with platform/target users |
| `files` | Active file metadata; storage paths excluded |
| `activity` | Newest tenant activity logs with actor relationships |

Unsupported tabs return 404 through the route constraint.

## Module overrides

`PUT .../{tenant_uuid}/modules` body:

```json
{"modules": [{"module_code": "crm", "enabled": true, "limits": {"users": 25}, "metadata": {"source": "platform"}}]}
```

`modules` must be present and an array (maximum 100 entries). Each item requires a distinct existing `module_code` and boolean `enabled`; `limits` and `metadata` are optional objects/arrays. Upserts by tenant/module code, preserves existing UUIDs/creation times and omitted optional values, and returns all overrides in `data.modules` with module/updater objects. An empty array leaves existing overrides intact. Updates are audited.
