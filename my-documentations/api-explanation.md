Implement following apis
check frontend connection with these apis
correct frontend UI properly for these modules and apis
use relationships for relational data for sending apis response. 
display proper apis data and relational data in UI


# Selected API block: Platform Dashboard APIs

This section documents the selected Platform Dashboard APIs from `api-endpoints.md`, cross-checked against `backend/routes/api-platform.php`, `backend/app/Http/Controllers/Platform/PlatformDashboardController.php`, and `backend/app/Http/Controllers/Platform/PlatformReportsController.php`.

All endpoints are platform-scoped and require the platform authentication/session middleware. Every selected route currently uses `platform.permission:dashboard.view`.

## Important route correction

The selected endpoint list contains:

```text
POST /api/platform/v1/export
```

However, the route is declared inside `Route::prefix('dashboard')`, so the implemented backend path is:

```text
POST /api/platform/v1/dashboard/export
```

The frontend and API documentation should use the implemented `/dashboard/export` path, or the route should be moved outside the dashboard prefix if `/export` is intended.

## Common query filters

Dashboard chart endpoints accept optional query parameters:

```text
date_from=2026-01-01&date_to=2026-09-30
```

The controller applies these filters with `whereDate`. The current implementation does not validate date format or reject an inverted range.

Common response wrapper:

```json
{
  "success": true,
  "message": "OK",
  "data": {}
}
```

# 1. Platform dashboard summary

`GET /api/platform/v1/summary`

Permission: `dashboard.view`.

Purpose: provide platform-wide tenant, revenue, billing, and operations KPIs for the admin dashboard.

Response:

```json
{
  "success": true,
  "message": "OK",
  "data": {
    "tenants": {
      "total": 120,
      "active": 90,
      "trial": 18,
      "suspended": 7,
      "expired": 5,
      "new_today": 2,
      "new_this_week": 8,
      "new_this_month": 24
    },
    "revenue": {
      "mrr": "450000.00",
      "arr": "5400000.00",
      "collected_today": "12500.00",
      "collected_this_month": "220000.00",
      "currency": "INR"
    },
    "billing": {
      "overdue_invoice_count": 9,
      "overdue_balance": "87500.00",
      "failed_payment_count": 4
    },
    "operations": {
      "open_incidents": 1,
      "critical_security_events": 2,
      "failed_queue_jobs": 3,
      "failed_scheduler_runs": 1
    }
  }
}
```

The summary uses date filters for several totals, but `new_today`, `new_this_week`, `new_this_month`, and collected revenue windows are based on the current time windows and ignore supplied date filters. MRR is calculated from active/trial monthly subscriptions and ARR is MRR multiplied by 12.

# 2. All dashboard charts

`GET /api/platform/v1/charts`

Permission: `dashboard.view`.

Returns all chart datasets in one request:

```json
{
  "success": true,
  "message": "OK",
  "data": {
    "tenant_growth": [],
    "revenue": [],
    "plan_distribution": [],
    "subscription_status": [],
    "tenant_status": [],
    "usage": []
  }
}
```

Current implementation returns both `subscription_status` and `tenant_status` using the subscription-status query. `tenant_status` is therefore not actually grouped by tenant status.

# 3. Individual dashboard chart

`GET /api/platform/v1/charts/{chart}`

Permission: `dashboard.view`.

Supported values:

- `tenant-growth`: daily tenant registrations, limited to 30 rows;
- `revenue`: daily successful/failed payment totals, limited to 30 rows;
- `api-usage-trend`, `storage-usage-trend`, `usage`: usage snapshots grouped by period;
- `payment-success-failure-trend`: currently returns the same revenue dataset;
- `plan-distribution`: subscription count and payable revenue by plan;
- `subscription-status`: subscription counts by status.

Examples:

```text
GET /api/platform/v1/charts/tenant-growth?date_from=2026-09-01&date_to=2026-09-30
GET /api/platform/v1/charts/revenue?date_from=2026-09-01&date_to=2026-09-30
```

Example response for tenant growth:

```json
{
  "success": true,
  "message": "OK",
  "data": [
    { "date": "2026-09-01", "count": 4 },
    { "date": "2026-09-02", "count": 7 }
  ]
}
```

Unknown chart identifiers return a structured 404 error with code `DASHBOARD_CHART_NOT_FOUND`.

# 4. Combined recent dashboard data

`GET /api/platform/v1/recent`

Permission: `dashboard.view`.

Returns the latest five rows for recent tenants, recent payments, and overdue invoices:

```json
{
  "success": true,
  "message": "OK",
  "data": {
    "recent_tenants": [],
    "recent_payments": [],
    "overdue_invoices": []
  }
}
```

This endpoint is intended for a dashboard overview and is not paginated.

# 5. Recent tenants

`GET /api/platform/v1/recent-tenants`

Permission: `dashboard.view`.

Returns up to five newest non-deleted tenants with owner, latest plan, subscription, and tenant status fields:

```json
{
  "success": true,
  "message": "OK",
  "data": [
    {
      "uuid": "tenant-uuid",
      "organization_name": "Acme Ltd",
      "slug": "acme-ltd",
      "owner_name": "Asha Shah",
      "owner_email": "owner@example.com",
      "plan_name": "Growth",
      "subscription_status": "active",
      "status": "active",
      "created_at": "2026-09-08T09:00:00Z"
    }
  ]
}
```

# 6. Recent payments

`GET /api/platform/v1/recent-payments`

Permission: `dashboard.view`.

Returns up to five latest platform payments joined to tenant names:

```json
{
  "success": true,
  "message": "OK",
  "data": [
    {
      "uuid": "payment-uuid",
      "payment_number": "PAY-1001",
      "organization_name": "Acme Ltd",
      "tenant_name": "Acme Ltd",
      "amount": "25000.00",
      "currency": "INR",
      "gateway": "razorpay",
      "payment_status": "success",
      "paid_at": "2026-09-08T11:30:00Z"
    }
  ]
}
```

The response repeats the organization name as both `organization_name` and `tenant_name`; the API can standardize this field.

# 7. Overdue invoices

`GET /api/platform/v1/overdue-invoices`

Permission: `dashboard.view`.

Returns up to five non-deleted invoices with a positive balance and due date before today:

```json
{
  "success": true,
  "message": "OK",
  "data": [
    {
      "uuid": "invoice-uuid",
      "invoice_number": "INV-1001",
      "organization_name": "Acme Ltd",
      "tenant_name": "Acme Ltd",
      "balance_amount": "18000.00",
      "currency": "INR",
      "due_date": "2026-08-31",
      "status": "overdue"
    }
  ]
}
```

The query uses balance and due date rather than requiring the stored status to equal `overdue`.

# 8. Alerts

`GET /api/platform/v1/alerts`

Permission: `dashboard.view`.

Returns active monitoring alerts and security events together:

```json
{
  "success": true,
  "message": "OK",
  "data": {
    "alerts": [],
    "active_alerts": [],
    "security_events": []
  }
}
```

`alerts` and `active_alerts` contain the same open-alert dataset. Security events are limited to five latest records and include event, severity, IP address, tenant name, and actor name.

# 9. Active alerts

`GET /api/platform/v1/active-alerts`

Permission: `dashboard.view`.

Returns up to five monitoring alerts whose status is `open`, ordered newest first:

```json
{
  "success": true,
  "message": "OK",
  "data": []
}
```

# 10. Security events

`GET /api/platform/v1/security-events`

Permission: `dashboard.view`.

Returns up to five latest security events joined to tenant and user labels:

```json
{
  "success": true,
  "message": "OK",
  "data": [
    {
      "id": 44,
      "event": "login_failed",
      "severity": "high",
      "ip_address": "203.0.113.10",
      "created_at": "2026-09-08T12:00:00Z",
      "tenant_name": "Acme Ltd",
      "actor": "Asha Shah"
    }
  ]
}
```

The route is not paginated and has no date, severity, tenant, or event filters. Because IP addresses and security metadata are sensitive, the frontend should restrict display according to platform role.

# 11. Export platform dashboard data

Selected list path: `POST /api/platform/v1/export`.

Implemented backend path: `POST /api/platform/v1/dashboard/export`.

Permission: `dashboard.view`.

The current implementation ignores request filters and immediately creates a CSV containing all non-deleted tenants with `uuid`, organization name, slug, status, and creation date. A request body is not required.

Recommended request body for a future filtered implementation:

```json
{
  "format": "csv",
  "sections": ["tenants"],
  "date_from": "2026-01-01",
  "date_to": "2026-09-30"
}
```

Current response status is 201:

```json
{
  "success": true,
  "message": "Dashboard export created.",
  "data": {
    "file": {
      "uuid": "file-uuid",
      "original_name": "dashboard-20260908123000.csv",
      "mime_type": "text/csv",
      "size_bytes": 12345
    }
  }
}
```

The file is stored privately and an audit event is written. A separate authenticated file-download endpoint is needed for the frontend to retrieve it.

## Frontend integration flow

1. Load the summary and combined recent data in parallel after platform login.
2. Load all charts once, or request individual charts when the dashboard uses lazy loading.
3. Use the five-row recent endpoints for compact dashboard cards and link to full management screens.
4. Display active alerts and security events with role-appropriate masking.
5. Use date filters consistently across summary and chart requests.
6. Call the implemented `/dashboard/export` path and use the returned file UUID with the secure file-download flow.

## Recommended backend improvements

1. Correct `api-endpoints.md` or route registration so the export path is unambiguous.
2. Validate `date_from`, `date_to`, timezone, and range limits on all date-filtered endpoints.
3. Make summary date filtering consistent; current rolling today/week/month metrics ignore requested date filters.
4. Fix `tenant_status` to use tenant statuses rather than duplicating subscription status.
5. Return a distinct dataset for `payment-success-failure-trend` instead of reusing revenue output.
6. Add pagination and filtering to recent tenants, payments, invoices, alerts, and security events.
7. Add severity, tenant, event, and date filters to security events and restrict sensitive fields by permission.
8. Replace duplicate response fields such as `organization_name` and `tenant_name` with a documented canonical field.
9. Validate and honor export format, section, date, and filter inputs; do not always export every tenant.
10. Return a secure, short-lived download URL or provide a documented file-download endpoint for export files.
11. Add export size limits, background jobs for large datasets, and export audit metadata.
12. Add database indexes for status/date columns used by dashboard aggregations.
13. Add caching or pre-aggregated metrics for high-volume platform dashboards.
14. Add tests for platform authorization, export path, date boundaries, tenant visibility, sensitive security-event fields, empty datasets, and unknown chart codes.

## Files checked

- `api-endpoints.md`
- `backend/routes/api-platform.php`
- `backend/app/Http/Controllers/Platform/PlatformDashboardController.php`
- `backend/app/Http/Controllers/Platform/PlatformReportsController.php`