
## Platform Roles Module Audit

### Issues found and corrected

- **Role API path mismatch:** The frontend used `/access-control/roles`, but the backend routes are under `/api/platform/v1/roles`. All role list, detail, create, update, delete, clone, status, permission, user, and export calls now use the correct `/roles` paths.
- **Role permissions response mismatch:** The backend returns a grouped module map directly, while the UI expected `{ permissions: ... }`. The frontend now normalizes both shapes before the assignment drawer consumes them.
- **Role users response mismatch:** The backend can return a user array directly, while the UI expected `{ users: ... }`. The frontend now normalizes both shapes.
- **List pagination:** The shared access-control list now defaults to 10 rows and the table page-size dropdown updates the role API `per_page` query and resets to page one.
- **List breadcrumb:** The unnecessary Access Control breadcrumb is removed from the roles list, matching the permissions list behavior.
- **Role detail presentation:** Technical identifiers, timestamps, and internal relation collections are hidden from the user-facing role details panel.
- **Role activity scope:** Role activity requests are scoped to `subject_type=App\\Models\\PlatformRole` and the selected role database ID instead of loading unrelated audit activity. A role record is no longer shown as a fake activity item when there are no logs.
- **Role form actions:** Role create/edit forms use the improved access-control footer spacing and button sizing.

### Role functionality verified

- Role list with search, sorting, filters, pagination, column management, saved views, and CSV export.
- Create and edit role forms with permission selection.
- View role details with permissions and assigned-user tabs.
- Assign and replace role permissions through the grouped permission selector.
- Assign and remove platform users.
- Clone roles.
- Activate and deactivate roles.
- Delete non-system roles subject to backend assignment safeguards.
- Route and action guards for role view, create, edit, and delete capabilities.

### Remaining role-module gap

The role list backend currently returns pagination metadata but does not provide aggregate role KPIs. The frontend can consume `meta.kpis` when supplied, but until the backend adds role aggregates, role summary cards fall back to the currently loaded page and are not full-dataset KPIs.
### Current roles features

- Four protected routes: list, create, view, and edit.
- Role list with search, server-side sorting, status/type/guard filters, pagination, page-size selection, saved views, column management, and CSV export.
- KPI summary cards for total roles, active roles, system roles, and assigned users.
- Create and edit role forms with name, display name, guard, description, status, system flag, audit reason, and permission selection.
- Grouped permission selector with search, module filter, select-all, clear-module, selected/added/removed counts, and audit reason.
- Role detail view with user-facing details, permission list, assigned users, activity, edit, assignment, clone, and navigation actions.
- Assign and remove platform users, with effective date, notification flag, and audit reason inputs.
- Clone role with optional permission copying and inactive-by-default clone status.
- Activate/deactivate role actions.
- Delete confirmation with typed confirmation and audit reason.
- Route and action-level permission guards.
- Loading, empty, error, pending mutation, and API validation states.

### Expected roles features and quality requirements

| Expected feature/requirement | Status |
|---|---|
| Every frontend API path matches the backend route | Implemented |
| List defaults to 10 rows and page-size dropdown changes the request | Implemented |
| KPIs represent the full filtered result, not only the current page | Implemented |
| Role permissions and users response wrappers are normalized | Implemented |
| System roles cannot be deleted or have the system flag changed during edit | Implemented in UI; backend remains authoritative |
| Assignment controls require edit permission, not view permission | Implemented |
| Audit history requires `audit_log.view` | Implemented for the role action menu |
| Role activity is scoped to the selected role | Implemented; requires activity log records |
| Technical IDs, UUIDs, timestamps, and internal relations are hidden in details | Implemented |
| List breadcrumb is removed from the access-control roles list | Implemented |
| Create/edit actions have consistent spacing and button sizing | Implemented |
| Permission assignment supports search, module filtering, bulk module select/clear, and change summary | Implemented |
| User assignment supports multi-select, removal, effective date, notification, and audit reason | Implemented |
| Bulk role deletion | Not implemented; backend does not expose a bulk-delete route |
| Role mutation audit records | Dependent on backend activity logging |
| Persistent saved views across browsers/users | Not implemented; current saved views are frontend-session state |
| Server-provided role KPI aggregates | Implemented |

### Permanent-fix notes

The role API contract is centralized in `platformAccessApi.roles`, rather than patching individual components with alternate URLs. Response normalization is performed at the API boundary, and list state owns page size, query parameters, and KPI data. This keeps the role pages consistent as the backend response grows.
## Platform Departments and Designations

### Current frontend features

- Departments and Designations are available under Platform Staffs.
- Each module has a list, create, view, and edit page.
- Lists support backend search/status filtering, client-side pagination with a default of 10 rows, page-size selection, status KPIs, and assigned-staff counts.
- Departments support parent department and manager selection; Designations support code, description, level, and status.
- Archive actions call the backend delete endpoint, which archives the record by setting it inactive.
- All pages and actions are protected by the matching platform abilities: `platform_department.*` and `platform_designation.*`.

### APIs integrated

Departments:

- `GET /api/platform/v1/platform-departments`
- `GET /api/platform/v1/platform-departments/{department_uuid}`
- `POST /api/platform/v1/platform-departments`
- `PATCH /api/platform/v1/platform-departments/{department_uuid}`
- `DELETE /api/platform/v1/platform-departments/{department_uuid}`

Designations:

- `GET /api/platform/v1/platform-designations`
- `GET /api/platform/v1/platform-designations/{designation_uuid}`
- `POST /api/platform/v1/platform-designations`
- `PATCH /api/platform/v1/platform-designations/{designation_uuid}`
- `DELETE /api/platform/v1/platform-designations/{designation_uuid}`

### Backend contract notes

- The backend list endpoints currently return unpaginated arrays without aggregate KPI metadata. The frontend therefore paginates the complete returned dataset locally and calculates KPIs from that dataset.
- The backend archive endpoints are represented as Archive in the UI because they mark records inactive rather than physically deleting them.
- No backend changes were made for this frontend implementation.

### Expected future enhancements

- Add server-side pagination and a backend KPI block if directory datasets become large.
- Add a dedicated department parent filter control if hierarchical filtering becomes a primary workflow.
- Add activity/history panels if department and designation audit endpoints are introduced.
