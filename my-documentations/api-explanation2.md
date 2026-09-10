# Tenant Bank Accounts API Explanation

## Scope and implementation status

This document describes the selected tenant bank-account API contract and the frontend integration expectations.

All intended endpoints are tenant-scoped under:

`/api/tenant/v1`

They should use:

- `tenant.context`
- `auth:sanctum`
- `tenant.token`
- JSON request and response headers

Important workspace finding: the documented global tenant bank-account routes are not currently registered in `backend2/routes/api-tenant.php`. The workspace currently contains:

- staff-scoped accounts under `/staff/{staff_uuid}/bank-accounts`;
- vendor-scoped accounts under `/vendors/{vendor_uuid}/bank-accounts`;
- the frontend finance page calling the intended global `/bank-accounts` endpoints.

Therefore, the global endpoints documented below are the required contract, but they need backend route/controller implementation before the finance page can work against a live server.

## Bank-account data model

A tenant bank account belongs to an owner:

- `owner_type`: `tenant`, `client`, `vendor`, or `staff`;
- `owner_id`: internal database ID of that owner;
- `bank_name`;
- encrypted account number;
- encrypted routing number;
- `ifsc_code`;
- `is_primary`;
- tenant and timestamp columns.

Public requests should use `owner_uuid` for client, vendor, or staff owners. The backend must resolve that UUID to a tenant-owned internal ID. A missing owner defaults to the current tenant only when `owner_type=tenant`.

The frontend must never display, store, log, or send back encrypted database columns.

## Authentication and permissions

Every request must include:

```http
Authorization: Bearer <tenant-token>
Accept: application/json
Content-Type: application/json
```

| Operation | Permission |
|---|---|
| List accounts | `finance.bank_account.view` |
| Create account | currently documented as `finance.bank_account.edit` |
| Update account | `finance.bank_account.edit` |
| Delete account | `finance.bank_account.edit` |
| Set primary account | `finance.bank_account.edit` |

Recommended permission change: create should use a dedicated `finance.bank_account.create` permission. The current vendor create route already uses `finance.bank_account.create`, so the global route should use the same permission.

## Standard responses

Successful responses use the project envelope:

```json
{
  "success": true,
  "message": "OK",
  "data": {}
}
```

Paginated responses include:

```json
{
  "current_page": 1,
  "per_page": 25,
  "total": 1,
  "last_page": 1
}
```

Authentication and authorization failures should remain standard API errors:

- `401`: missing or invalid tenant token;
- `403`: authenticated user lacks the required permission;
- `404`: account or owner is not in the current tenant;
- `422`: invalid request fields;
- `409`: invalid state, such as deleting a protected primary account.

## Safe account response

The account serializer should return only safe fields:

```json
{
  "id": 15,
  "owner_type": "tenant",
  "owner_id": 7,
  "owner_uuid": null,
  "owner_name": "Example Company",
  "bank_name": "Example Bank",
  "ifsc_code": "EXAM0001234",
  "is_primary": true,
  "account_number_masked": "********1234",
  "created_at": "2026-09-08T10:00:00.000000Z",
  "updated_at": "2026-09-08T10:00:00.000000Z"
}
```

The response must not contain:

- `account_number`;
- `routing_number`;
- `account_number_encrypted`;
- `routing_number_encrypted`.

Owner relationship fields should be resolved server-side so the UI does not need a separate request for every row.

# 1. List bank accounts

## Endpoint

```http
GET /api/tenant/v1/bank-accounts
```

Permission: `finance.bank_account.view`.

## Use

Use this endpoint for the tenant finance bank-account page. It returns only accounts belonging to the current tenant and orders them newest first.

## Query parameters

| Parameter | Type | Required | Description |
|---|---|---:|---|
| `page` | integer | No | Page number. |
| `per_page` | integer | No | Page size. Default: 25. |
| `owner_type` | string | No | Filter by tenant, client, vendor, or staff. |
| `owner_uuid` | string | No | Filter by public owner UUID. |
| `search` | string | No | Search bank name, IFSC, or owner label. |

## Example request

```http
GET /api/tenant/v1/bank-accounts?page=1&per_page=25&owner_type=staff
```

## Example response

```json
{
  "success": true,
  "message": "OK",
  "data": [
    {
      "id": 15,
      "owner_type": "tenant",
      "owner_id": 7,
      "owner_uuid": null,
      "owner_name": "Example Company",
      "bank_name": "Example Bank",
      "ifsc_code": "EXAM0001234",
      "is_primary": true,
      "account_number_masked": "********1234"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 25,
    "total": 1,
    "last_page": 1
  }
}
```

## Frontend behavior

- Show only `account_number_masked`.
- Show owner name and owner type.
- Use numeric `id` only with this current contract.
- Refresh the list after every mutation.
- Do not assume a list row includes plaintext account or routing numbers.

# 2. Create a bank account

## Endpoint

```http
POST /api/tenant/v1/bank-accounts
```

Permission: currently `finance.bank_account.edit`; recommended: `finance.bank_account.create`.

## Tenant-owned request

```json
{
  "owner_type": "tenant",
  "bank_name": "Example Bank",
  "account_number": "12345678901234",
  "routing_number": "110000000",
  "ifsc_code": "EXAM0001234",
  "is_primary": true
}
```

## Staff, client, or vendor request

```json
{
  "owner_type": "staff",
  "owner_uuid": "staff-uuid",
  "bank_name": "Example Bank",
  "account_number": "12345678901234",
  "routing_number": "110000000",
  "ifsc_code": "EXAM0001234",
  "is_primary": false
}
```

## Validation

The backend should:

- allow only `tenant`, `client`, `vendor`, and `staff`;
- require `owner_uuid` for every non-tenant owner;
- verify the owner belongs to the current tenant;
- validate bank name and IFSC format;
- validate account and routing number length/content;
- validate `is_primary` as boolean;
- encrypt account and routing numbers before storage;
- clear an existing primary account for the same tenant and owner inside a transaction.

## Response

HTTP status: `201 Created`.

```json
{
  "success": true,
  "message": "Bank account saved.",
  "data": {
    "bank_account": {
      "id": 15,
      "owner_type": "staff",
      "owner_uuid": "staff-uuid",
      "owner_name": "Asha Shah",
      "bank_name": "Example Bank",
      "ifsc_code": "EXAM0001234",
      "account_number_masked": "********1234",
      "is_primary": true
    }
  }
}
```

The plaintext values must not appear in this response.

# 3. Update a bank account

## Endpoint

```http
PUT /api/tenant/v1/bank-accounts/{account_id}
PATCH /api/tenant/v1/bank-accounts/{account_id}
```

Permission: `finance.bank_account.edit`.

`account_id` is currently numeric and must belong to the current tenant.

## Request body

The update is partial:

```json
{
  "owner_type": "staff",
  "owner_uuid": "staff-uuid",
  "bank_name": "Updated Bank",
  "account_number": "98765432109876",
  "routing_number": "120000000",
  "ifsc_code": "UPDT0005678",
  "is_primary": true
}
```

Only supplied encrypted fields should be replaced. If account or routing numbers are omitted, the existing encrypted values remain unchanged.

## Response

```json
{
  "success": true,
  "message": "Bank account updated.",
  "data": {
    "bank_account": {
      "id": 15,
      "owner_type": "staff",
      "owner_name": "Asha Shah",
      "bank_name": "Updated Bank",
      "ifsc_code": "UPDT0005678",
      "account_number_masked": "********9876",
      "is_primary": true
    }
  }
}
```

The backend must not allow a client to change `tenant_id`, `owner_id`, or encrypted columns directly.

# 4. Delete a bank account

## Endpoint

```http
DELETE /api/tenant/v1/bank-accounts/{account_id}
```

Permission: `finance.bank_account.edit` under the current contract.

## Response

```json
{
  "success": true,
  "message": "Bank account deleted.",
  "data": null
}
```

## Safety rules

The current documented behavior permanently deletes the account. The backend should first prevent deletion when:

- the account is primary;
- the account is referenced by a payroll bank transfer;
- the account is the only active account for a required owner.

Prefer soft deletion or retention for financial records. The UI should require confirmation and explain why deletion may be blocked.

# 5. Set a primary bank account

## Endpoint

```http
POST /api/tenant/v1/bank-accounts/{account_id}/set-primary
```

Permission: `finance.bank_account.edit`.

## Request body

```json
{
  "reason": "Payroll settlement account changed"
}
```

`reason` is required and must be a string with a maximum length of 1000 characters.

## Behavior

The backend must:

1. resolve the account within the current tenant;
2. resolve its owner;
3. clear the previous primary account for that same owner;
4. set the selected account as primary;
5. write an audit event containing the reason and actor;
6. return the masked selected account.

The clear-and-set operation must be transactional.

## Response

```json
{
  "success": true,
  "message": "Primary bank account updated.",
  "data": {
    "bank_account": {
      "id": 15,
      "owner_type": "tenant",
      "bank_name": "Example Bank",
      "is_primary": true,
      "account_number_masked": "********1234"
    }
  }
}
```

# Existing related endpoints

These routes exist separately from the intended global tenant account API.

## Staff accounts

```http
GET /api/tenant/v1/staff/{staff_uuid}/bank-accounts
POST /api/tenant/v1/staff/{staff_uuid}/bank-accounts
PUT|PATCH /api/tenant/v1/staff/{staff_uuid}/bank-accounts/{id}
DELETE /api/tenant/v1/staff/{staff_uuid}/bank-accounts/{id}
```

Permission: `staff.manage_bank`.

Current implementation uses the `staff_bank_accounts` table. Its serializer should also exclude encrypted account fields and return a consistent masked field such as `account_number_masked`.

## Vendor accounts

```http
GET /api/tenant/v1/vendors/{vendor_uuid}/bank-accounts
POST /api/tenant/v1/vendors/{vendor_uuid}/bank-accounts
PUT|PATCH /api/tenant/v1/vendors/{vendor_uuid}/bank-accounts/{account_id}
DELETE /api/tenant/v1/vendors/{vendor_uuid}/bank-accounts/{account_id}
```

Permissions:

- list: `finance.bank_account.view`;
- create: `finance.bank_account.create`;
- update: `finance.bank_account.edit`;
- delete: `finance.bank_account.delete`.

These routes use `bank_accounts` with the vendor owner relationship. The current vendor create response should be fixed to use the same safe masked serializer because it currently returns the raw inserted row.

# Frontend integration flow

1. Load owner selectors for staff, clients, and vendors.
2. Load `GET /bank-accounts` with pagination.
3. Display owner label, bank name, IFSC, masked account number, and primary status.
4. For create and edit, require account-number re-entry because GET responses never include plaintext values.
5. Show a confirmation dialog before deleting or changing the primary account.
6. Require a reason for set-primary.
7. Hide create, edit, delete, and primary actions unless the user has the corresponding permission.
8. Refresh the list after create, update, delete, and set-primary.
9. Display API validation, permission, conflict, and ownership errors near the relevant form/action.
10. Never log request bodies containing account or routing numbers.

The existing frontend finance API client calls:

- `GET /bank-accounts`;
- `POST /bank-accounts`;
- `PATCH /bank-accounts/{id}`;
- `DELETE /bank-accounts/{id}`;
- `POST /bank-accounts/{id}/set-primary`.

Until the missing global routes are implemented, these calls will return route-not-found errors from the current backend.

# Recommended improvements

1. Add the missing global bank-account routes and controller methods.
2. Add a dedicated `finance.bank_account.create` permission.
3. Use a single safe serializer for tenant, staff, client, and vendor accounts.
4. Remove both encrypted account and routing columns from every response.
5. Return relationship labels such as `owner_uuid` and `owner_name`.
6. Validate and tenant-scope every owner lookup.
7. Use transactions for primary-account changes.
8. Add a database strategy enforcing at most one primary account per tenant/owner.
9. Persist set-primary reason, actor, previous account, and new account in an audit table.
10. Block deletion of primary or payroll-linked accounts, or require a replacement.
11. Prefer public UUIDs for account URLs instead of numeric IDs.
12. Prefer soft deletion or a financial-record retention policy.
13. Add pagination and owner filters to the global list.
14. Add tests for tenant isolation, encryption, response masking, owner conversion, duplicate primaries, deletion rules, and payroll references.
15. Standardize field names across account endpoints: use `account_number_masked` instead of returning `account_number: "****"`.

## Files checked

- `api-endpoints.md`
- `backend2/routes/api-tenant.php`
- `backend2/app/Http/Controllers/TenantCrmController.php`
- `backend2/app/Http/Controllers/TenantStaffController.php`
- `backend2/app/Services/Tenant/TenantWorkspaceService.php` (if present)
- `backend2/database/migrations/*bank*`
- `frontend/src/features/tenant/api/tenantBusinessApi.ts`
- `frontend/src/features/tenant/pages/TenantBusinessPages.tsx`

