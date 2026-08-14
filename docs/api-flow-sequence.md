# API Flow Sequence

Source files: `openapi-completed.yaml`, `openapi-rbac.yaml`.

This list merges duplicate method/path pairs from both OpenAPI files and orders APIs by product flow: public/common entry points, platform-user APIs after platform login, then tenant-user APIs.

Flow order:
1. Public/common APIs needed before login.
2. Platform APIs used after platform-user login.
3. Tenant APIs used after tenant-user login.

Total unique operations: 472.

## Public/Common - dropdown and master data before login

1. `GET /api/common/v1/locations/cities` - List active cities  
   Source: openapi-completed.yaml; Tag: 0. Common Master Data
2. `GET /api/common/v1/locations/countries` - List active countries  
   Source: openapi-completed.yaml; Tag: 0. Common Master Data
3. `GET /api/common/v1/locations/states` - List active states  
   Source: openapi-completed.yaml; Tag: 0. Common Master Data

## Public/Auth - account discovery, login, registration, password reset

1. `POST /api/auth/v1/accounts/discover` - Discover accounts by email  
   Source: openapi-completed.yaml; Tag: 1. Unified Auth
2. `POST /api/auth/v1/accounts/login` - Login selected account  
   Source: openapi-completed.yaml; Tag: 1. Unified Auth
3. `POST /api/auth/v1/accounts/login/2fa` - Verify login 2FA challenge  
   Source: openapi-completed.yaml; Tag: 1. Unified Auth
4. `POST /api/auth/v1/logout` - Logout current unified session  
   Source: openapi-completed.yaml; Tag: 1. Unified Auth
5. `GET /api/auth/v1/me` - Current unified session  
   Source: openapi-completed.yaml; Tag: 1. Unified Auth
6. `POST /api/auth/v1/password/forgot` - Request password reset  
   Source: openapi-completed.yaml; Tag: 1. Unified Auth
7. `POST /api/auth/v1/password/reset` - Reset password  
   Source: openapi-completed.yaml; Tag: 1. Unified Auth
8. `POST /api/auth/v1/tenants/register` - Register a new SaaS tenant and owner account  
   Source: openapi-completed.yaml; Tag: 1. Unified Auth

## Platform - session, profile, security, preferences

1. `POST /api/platform/v1/auth/2fa/confirm` - Confirm platform TOTP 2FA setup  
   Source: openapi-completed.yaml; Tag: 2. Platform Auth/Profile/Security
2. `POST /api/platform/v1/auth/2fa/disable` - Disable platform 2FA  
   Source: openapi-completed.yaml; Tag: 2. Platform Auth/Profile/Security
3. `POST /api/platform/v1/auth/2fa/enable` - Start platform TOTP 2FA setup  
   Source: openapi-completed.yaml; Tag: 2. Platform Auth/Profile/Security
4. `POST /api/platform/v1/auth/forgot-password` - Request platform password reset  
   Source: openapi-completed.yaml; Tag: 2. Platform Auth/Profile/Security
5. `POST /api/platform/v1/auth/logout` - Revoke current platform token  
   Source: openapi-completed.yaml; Tag: 2. Platform Auth/Profile/Security
6. `GET /api/platform/v1/auth/me` - Platform current user  
   Source: openapi-completed.yaml; Tag: 2. Platform Auth/Profile/Security
7. `POST /api/platform/v1/auth/refresh` - Rotate current platform token  
   Source: openapi-completed.yaml; Tag: 2. Platform Auth/Profile/Security
8. `POST /api/platform/v1/auth/reset-password` - Reset platform password  
   Source: openapi-completed.yaml; Tag: 2. Platform Auth/Profile/Security
9. `POST /api/platform/v1/auth/verify-email/resend` - Queue platform verification email placeholder  
   Source: openapi-completed.yaml; Tag: 2. Platform Auth/Profile/Security
10. `GET /api/platform/v1/profile` - Get platform profile  
   Source: openapi-completed.yaml; Tag: 2. Platform Auth/Profile/Security
11. `PATCH /api/platform/v1/profile` - Update platform profile  
   Source: openapi-completed.yaml; Tag: 2. Platform Auth/Profile/Security
12. `PUT /api/platform/v1/profile` - Update platform profile  
   Source: openapi-completed.yaml; Tag: 2. Platform Auth/Profile/Security
13. `PUT /api/platform/v1/profile/password` - Change platform password  
   Source: openapi-completed.yaml; Tag: 2. Platform Auth/Profile/Security
14. `GET /api/platform/v1/profile/sessions` - List platform Sanctum sessions/tokens  
   Source: openapi-completed.yaml; Tag: 2. Platform Auth/Profile/Security
15. `DELETE /api/platform/v1/profile/sessions/{session_id}` - Revoke platform session/token  
   Source: openapi-completed.yaml; Tag: 2. Platform Auth/Profile/Security
16. `GET /api/platform/v1/settings/preferences` - List platform preferences  
   Source: openapi-completed.yaml; Tag: 2. Platform Auth/Profile/Security
17. `PUT /api/platform/v1/settings/preferences` - Update platform preferences  
   Source: openapi-completed.yaml; Tag: 2. Platform Auth/Profile/Security

## Platform - API tokens

1. `GET /api/platform/v1/api-tokens` - List platform API tokens  
   Source: openapi-completed.yaml; Tag: 2. Platform API Tokens
2. `POST /api/platform/v1/api-tokens` - Create platform API token  
   Source: openapi-completed.yaml; Tag: 2. Platform API Tokens
3. `GET /api/platform/v1/api-tokens/{token_uuid}` - Show platform API token metadata  
   Source: openapi-completed.yaml; Tag: 2. Platform API Tokens
4. `POST /api/platform/v1/api-tokens/{token_uuid}/revoke` - Revoke platform API token  
   Source: openapi-completed.yaml; Tag: 2. Platform API Tokens
5. `POST /api/platform/v1/api-tokens/{token_uuid}/rotate` - Rotate platform API token and return raw token once  
   Source: openapi-completed.yaml; Tag: 2. Platform API Tokens

## Platform - dashboard after login

1. `GET /api/platform/v1/dashboard/active-alerts` - Platform dashboard active alerts  
   Source: openapi-completed.yaml; Tag: 4. Platform Admin
2. `GET /api/platform/v1/dashboard/alerts` - Platform dashboard aggregate alerts and security events  
   Source: openapi-completed.yaml; Tag: 4. Platform Admin
3. `GET /api/platform/v1/dashboard/charts` - Platform dashboard aggregate charts  
   Source: openapi-completed.yaml; Tag: 4. Platform Admin
4. `GET /api/platform/v1/dashboard/charts/{chart}` - Platform dashboard chart widget  
   Source: openapi-completed.yaml; Tag: 4. Platform Admin
5. `POST /api/platform/v1/dashboard/export` - Export platform dashboard  
   Source: openapi-completed.yaml; Tag: 4. Platform Admin
6. `GET /api/platform/v1/dashboard/overdue-invoices` - Platform dashboard overdue invoices  
   Source: openapi-completed.yaml; Tag: 4. Platform Admin
7. `GET /api/platform/v1/dashboard/recent` - Platform dashboard aggregate recent records  
   Source: openapi-completed.yaml; Tag: 4. Platform Admin
8. `GET /api/platform/v1/dashboard/recent-payments` - Platform dashboard recent payments  
   Source: openapi-completed.yaml; Tag: 4. Platform Admin
9. `GET /api/platform/v1/dashboard/recent-tenants` - Platform dashboard recent tenants  
   Source: openapi-completed.yaml; Tag: 4. Platform Admin
10. `GET /api/platform/v1/dashboard/security-events` - Platform dashboard security events  
   Source: openapi-completed.yaml; Tag: 4. Platform Admin
11. `GET /api/platform/v1/dashboard/summary` - Platform dashboard summary  
   Source: openapi-completed.yaml; Tag: 4. Platform Admin

## Platform - staff, teams, team roles

1. `GET /api/platform/v1/platform-team-roles` - List platform team roles  
   Source: openapi-completed.yaml; Tag: 4. Platform Admin
2. `POST /api/platform/v1/platform-team-roles` - Create platform team role  
   Source: openapi-completed.yaml; Tag: 4. Platform Admin
3. `GET /api/platform/v1/platform-team-roles/{role_uuid}` - View platform team role  
   Source: openapi-completed.yaml; Tag: 4. Platform Admin
4. `PATCH /api/platform/v1/platform-team-roles/{role_uuid}` - Update platform team role  
   Source: openapi-completed.yaml; Tag: 4. Platform Admin
5. `PUT /api/platform/v1/platform-team-roles/{role_uuid}` - Update platform team role  
   Source: openapi-completed.yaml; Tag: 4. Platform Admin
6. `DELETE /api/platform/v1/platform-team-roles/{role_uuid}` - Delete custom unused platform team role  
   Source: openapi-completed.yaml; Tag: 4. Platform Admin
7. `GET /api/platform/v1/platform-teams` - List platform teams  
   Source: openapi-completed.yaml; Tag: 4. Platform Admin
8. `POST /api/platform/v1/platform-teams` - Create platform team  
   Source: openapi-completed.yaml; Tag: 4. Platform Admin
9. `GET /api/platform/v1/platform-teams/{team_uuid}` - View platform team  
   Source: openapi-completed.yaml; Tag: 4. Platform Admin
10. `PATCH /api/platform/v1/platform-teams/{team_uuid}` - Update platform team  
   Source: openapi-completed.yaml; Tag: 4. Platform Admin
11. `PUT /api/platform/v1/platform-teams/{team_uuid}` - Update platform team  
   Source: openapi-completed.yaml; Tag: 4. Platform Admin
12. `DELETE /api/platform/v1/platform-teams/{team_uuid}` - Archive platform team  
   Source: openapi-completed.yaml; Tag: 4. Platform Admin
13. `GET /api/platform/v1/platform-teams/{team_uuid}/assignments` - List platform team assignments  
   Source: openapi-completed.yaml; Tag: 4. Platform Admin
14. `POST /api/platform/v1/platform-teams/{team_uuid}/assignments` - Create platform team assignment  
   Source: openapi-completed.yaml; Tag: 4. Platform Admin
15. `DELETE /api/platform/v1/platform-teams/{team_uuid}/assignments/{assignment_id}` - Release platform team assignment  
   Source: openapi-completed.yaml; Tag: 4. Platform Admin
16. `GET /api/platform/v1/platform-teams/{team_uuid}/members` - List platform team members  
   Source: openapi-completed.yaml; Tag: 4. Platform Admin
17. `POST /api/platform/v1/platform-teams/{team_uuid}/members` - Add platform team member  
   Source: openapi-completed.yaml; Tag: 4. Platform Admin
18. `PATCH /api/platform/v1/platform-teams/{team_uuid}/members/{member_id}` - Update platform team member  
   Source: openapi-completed.yaml; Tag: 4. Platform Admin
19. `PUT /api/platform/v1/platform-teams/{team_uuid}/members/{member_id}` - Update platform team member  
   Source: openapi-completed.yaml; Tag: 4. Platform Admin
20. `DELETE /api/platform/v1/platform-teams/{team_uuid}/members/{member_id}` - Remove platform team member  
   Source: openapi-completed.yaml; Tag: 4. Platform Admin
21. `GET /api/platform/v1/platform-users` - List platform staff  
   Source: openapi-completed.yaml; Tag: 4. Platform Admin
22. `POST /api/platform/v1/platform-users` - Create platform staff  
   Source: openapi-completed.yaml; Tag: 4. Platform Admin
23. `GET /api/platform/v1/platform-users/{platform_user_uuid}` - View platform staff  
   Source: openapi-completed.yaml; Tag: 4. Platform Admin
24. `PATCH /api/platform/v1/platform-users/{platform_user_uuid}` - Update platform staff  
   Source: openapi-completed.yaml; Tag: 4. Platform Admin
25. `PUT /api/platform/v1/platform-users/{platform_user_uuid}` - Update platform staff  
   Source: openapi-completed.yaml; Tag: 4. Platform Admin
26. `DELETE /api/platform/v1/platform-users/{platform_user_uuid}` - Delete platform staff  
   Source: openapi-completed.yaml; Tag: 4. Platform Admin
27. `POST /api/platform/v1/platform-users/{platform_user_uuid}/activate` - Activate platform staff  
   Source: openapi-completed.yaml; Tag: 4. Platform Admin
28. `GET /api/platform/v1/platform-users/{platform_user_uuid}/activity` - Platform staff activity  
   Source: openapi-completed.yaml; Tag: 4. Platform Admin
29. `POST /api/platform/v1/platform-users/{platform_user_uuid}/force-logout` - Force platform staff logout  
   Source: openapi-completed.yaml; Tag: 4. Platform Admin
30. `GET /api/platform/v1/platform-users/{platform_user_uuid}/permissions` - Platform staff direct permissions  
   Source: openapi-completed.yaml; Tag: 4. Platform Admin
31. `PUT /api/platform/v1/platform-users/{platform_user_uuid}/permissions` - Replace platform staff direct permissions  
   Source: openapi-completed.yaml; Tag: 4. Platform Admin
32. `POST /api/platform/v1/platform-users/{platform_user_uuid}/require-2fa` - Require platform staff 2FA  
   Source: openapi-completed.yaml; Tag: 4. Platform Admin
33. `POST /api/platform/v1/platform-users/{platform_user_uuid}/reset-password` - Send platform staff reset password email  
   Source: openapi-completed.yaml; Tag: 4. Platform Admin
34. `POST /api/platform/v1/platform-users/{platform_user_uuid}/restore` - Restore platform staff  
   Source: openapi-completed.yaml; Tag: 4. Platform Admin
35. `GET /api/platform/v1/platform-users/{platform_user_uuid}/roles` - Platform staff roles  
   Source: openapi-completed.yaml; Tag: 4. Platform Admin
36. `PUT /api/platform/v1/platform-users/{platform_user_uuid}/roles` - Replace platform staff roles  
   Source: openapi-completed.yaml; Tag: 4. Platform Admin
37. `POST /api/platform/v1/platform-users/{platform_user_uuid}/suspend` - Suspend platform staff  
   Source: openapi-completed.yaml; Tag: 4. Platform Admin
38. `POST /api/platform/v1/platform-users/export` - Export platform staff  
   Source: openapi-completed.yaml; Tag: 4. Platform Admin
39. `POST /api/platform/v1/platform-users/invite` - Invite platform staff  
   Source: openapi-completed.yaml; Tag: 4. Platform Admin

## Platform - tenants, lifecycle, modules, remote login

1. `GET /api/platform/v1/tenants` - List tenants  
   Source: openapi-completed.yaml; Tag: 4. Platform Admin
2. `POST /api/platform/v1/tenants` - Create tenant with owner office subscription settings and roles  
   Source: openapi-completed.yaml; Tag: 4. Platform Admin
3. `GET /api/platform/v1/tenants/{tenant_uuid}` - Tenant overview  
   Source: openapi-completed.yaml; Tag: 4. Platform Admin
4. `PATCH /api/platform/v1/tenants/{tenant_uuid}` - Update tenant organization  
   Source: openapi-completed.yaml; Tag: 4. Platform Admin
5. `PUT /api/platform/v1/tenants/{tenant_uuid}` - Update tenant organization  
   Source: openapi-completed.yaml; Tag: 4. Platform Admin
6. `DELETE /api/platform/v1/tenants/{tenant_uuid}` - Archive tenant  
   Source: openapi-completed.yaml; Tag: 4. Platform Admin
7. `GET /api/platform/v1/tenants/{tenant_uuid}/{tab}` - Tenant detail tab  
   Source: openapi-completed.yaml; Tag: 4. Platform Admin
8. `POST /api/platform/v1/tenants/{tenant_uuid}/activate` - Activate tenant  
   Source: openapi-completed.yaml; Tag: 4. Platform Admin
9. `POST /api/platform/v1/tenants/{tenant_uuid}/archive` - Archive tenant  
   Source: openapi-completed.yaml; Tag: 4. Platform Admin
10. `POST /api/platform/v1/tenants/{tenant_uuid}/extend-trial` - Extend tenant trial  
   Source: openapi-completed.yaml; Tag: 4. Platform Admin
11. `POST /api/platform/v1/tenants/{tenant_uuid}/impersonate` - Start remote login session  
   Source: openapi-completed.yaml; Tag: 4. Platform Admin
12. `DELETE /api/platform/v1/tenants/{tenant_uuid}/impersonate/{session_uuid}` - End remote login session  
   Source: openapi-completed.yaml; Tag: 4. Platform Admin
13. `GET /api/platform/v1/tenants/{tenant_uuid}/module-entitlements` - Tenant module entitlements  
   Source: openapi-completed.yaml; Tag: 7. Platform Billing and Catalog
14. `PUT /api/platform/v1/tenants/{tenant_uuid}/modules` - Update tenant module overrides  
   Source: openapi-completed.yaml; Tag: 4. Platform Admin
15. `PUT /api/platform/v1/tenants/{tenant_uuid}/modules/{module_code}` - Override tenant module access  
   Source: openapi-completed.yaml; Tag: 7. Platform Billing and Catalog
16. `POST /api/platform/v1/tenants/{tenant_uuid}/reactivate` - Reactivate tenant  
   Source: openapi-completed.yaml; Tag: 4. Platform Admin
17. `POST /api/platform/v1/tenants/{tenant_uuid}/restore` - Restore tenant  
   Source: openapi-completed.yaml; Tag: 4. Platform Admin
18. `POST /api/platform/v1/tenants/{tenant_uuid}/suspend` - Suspend tenant  
   Source: openapi-completed.yaml; Tag: 4. Platform Admin

## Platform - RBAC roles and permissions

1. `GET /api/platform/v1/access-control/permissions` - List platform permissions  
   Source: openapi-completed.yaml, openapi-rbac.yaml; Tag: 5. Platform Permissions
2. `POST /api/platform/v1/access-control/permissions` - Create platform permission  
   Source: openapi-completed.yaml, openapi-rbac.yaml; Tag: 5. Platform Permissions
3. `GET /api/platform/v1/access-control/permissions/{permission_uuid}` - View platform permission  
   Source: openapi-completed.yaml, openapi-rbac.yaml; Tag: 5. Platform Permissions
4. `PATCH /api/platform/v1/access-control/permissions/{permission_uuid}` - Update platform permission  
   Source: openapi-completed.yaml, openapi-rbac.yaml; Tag: 5. Platform Permissions
5. `PUT /api/platform/v1/access-control/permissions/{permission_uuid}` - Update platform permission  
   Source: openapi-completed.yaml; Tag: 5. Platform Permissions
6. `DELETE /api/platform/v1/access-control/permissions/{permission_uuid}` - Delete custom unused platform permission  
   Source: openapi-completed.yaml, openapi-rbac.yaml; Tag: 5. Platform Permissions
7. `POST /api/platform/v1/access-control/permissions/export` - Export platform permissions as queued job or immediate CSV download  
   Source: openapi-completed.yaml; Tag: 5. Platform Permissions
8. `GET /api/platform/v1/access-control/permissions/grouped` - List platform permissions grouped by module  
   Source: openapi-completed.yaml, openapi-rbac.yaml; Tag: 5. Platform Permissions
9. `GET /api/platform/v1/access-control/roles` - List platform roles  
   Source: openapi-completed.yaml, openapi-rbac.yaml; Tag: 5. Platform Roles
10. `POST /api/platform/v1/access-control/roles` - Create platform role  
   Source: openapi-completed.yaml, openapi-rbac.yaml; Tag: 5. Platform Roles
11. `GET /api/platform/v1/access-control/roles/{role_uuid}` - View platform role  
   Source: openapi-completed.yaml, openapi-rbac.yaml; Tag: 5. Platform Roles
12. `PATCH /api/platform/v1/access-control/roles/{role_uuid}` - Update platform role  
   Source: openapi-completed.yaml, openapi-rbac.yaml; Tag: 5. Platform Roles
13. `PUT /api/platform/v1/access-control/roles/{role_uuid}` - Update platform role  
   Source: openapi-completed.yaml; Tag: 5. Platform Roles
14. `DELETE /api/platform/v1/access-control/roles/{role_uuid}` - Delete custom unused platform role  
   Source: openapi-completed.yaml, openapi-rbac.yaml; Tag: 5. Platform Roles
15. `POST /api/platform/v1/access-control/roles/{role_uuid}/activate` - Activate platform role  
   Source: openapi-completed.yaml, openapi-rbac.yaml; Tag: 5. Platform Roles
16. `POST /api/platform/v1/access-control/roles/{role_uuid}/clone` - Clone platform role  
   Source: openapi-completed.yaml, openapi-rbac.yaml; Tag: 5. Platform Roles
17. `POST /api/platform/v1/access-control/roles/{role_uuid}/deactivate` - Deactivate platform role  
   Source: openapi-completed.yaml, openapi-rbac.yaml; Tag: 5. Platform Roles
18. `GET /api/platform/v1/access-control/roles/{role_uuid}/permissions` - List platform role permissions grouped by module  
   Source: openapi-completed.yaml, openapi-rbac.yaml; Tag: 5. Platform Roles
19. `PUT /api/platform/v1/access-control/roles/{role_uuid}/permissions` - Replace platform role permissions  
   Source: openapi-completed.yaml, openapi-rbac.yaml; Tag: 5. Platform Roles
20. `GET /api/platform/v1/access-control/roles/{role_uuid}/users` - List platform users assigned to role  
   Source: openapi-completed.yaml, openapi-rbac.yaml; Tag: 5. Platform Roles
21. `POST /api/platform/v1/access-control/roles/{role_uuid}/users` - Assign platform users to role  
   Source: openapi-completed.yaml, openapi-rbac.yaml; Tag: 5. Platform Roles
22. `DELETE /api/platform/v1/access-control/roles/{role_uuid}/users/{platform_user_uuid}` - Remove platform user from role  
   Source: openapi-completed.yaml, openapi-rbac.yaml; Tag: 5. Platform Roles
23. `POST /api/platform/v1/access-control/roles/export` - Export platform roles as queued job or immediate CSV download  
   Source: openapi-completed.yaml; Tag: 5. Platform Roles

## Platform - billing, subscriptions, catalog

1. `GET /api/platform/v1/addons` - List add-on plans  
   Source: openapi-completed.yaml; Tag: 7. Platform Billing and Catalog
2. `POST /api/platform/v1/addons` - Create add-on plan  
   Source: openapi-completed.yaml; Tag: 7. Platform Billing and Catalog
3. `GET /api/platform/v1/addons/{addon_uuid}` - View add-on plan  
   Source: openapi-completed.yaml; Tag: 7. Platform Billing and Catalog
4. `PATCH /api/platform/v1/addons/{addon_uuid}` - Update add-on plan  
   Source: openapi-completed.yaml; Tag: 7. Platform Billing and Catalog
5. `PUT /api/platform/v1/addons/{addon_uuid}` - Update add-on plan  
   Source: openapi-completed.yaml; Tag: 7. Platform Billing and Catalog
6. `DELETE /api/platform/v1/addons/{addon_uuid}` - Delete add-on plan  
   Source: openapi-completed.yaml; Tag: 7. Platform Billing and Catalog
7. `GET /api/platform/v1/billing/invoices` - List platform invoices  
   Source: openapi-completed.yaml; Tag: 7. Platform Billing and Catalog
8. `POST /api/platform/v1/billing/invoices` - Create manual invoice  
   Source: openapi-completed.yaml; Tag: 7. Platform Billing and Catalog
9. `GET /api/platform/v1/billing/invoices/{invoice_uuid}` - View invoice  
   Source: openapi-completed.yaml; Tag: 7. Platform Billing and Catalog
10. `PATCH /api/platform/v1/billing/invoices/{invoice_uuid}` - Update draft invoice  
   Source: openapi-completed.yaml; Tag: 7. Platform Billing and Catalog
11. `PUT /api/platform/v1/billing/invoices/{invoice_uuid}` - Update draft invoice  
   Source: openapi-completed.yaml; Tag: 7. Platform Billing and Catalog
12. `DELETE /api/platform/v1/billing/invoices/{invoice_uuid}` - Cancel invoice  
   Source: openapi-completed.yaml; Tag: 7. Platform Billing and Catalog
13. `POST /api/platform/v1/billing/invoices/{invoice_uuid}/payments` - Record invoice payment  
   Source: openapi-completed.yaml; Tag: 7. Platform Billing and Catalog
14. `GET /api/platform/v1/billing/invoices/{invoice_uuid}/pdf` - Generate invoice PDF metadata  
   Source: openapi-completed.yaml; Tag: 7. Platform Billing and Catalog
15. `POST /api/platform/v1/billing/invoices/{invoice_uuid}/send` - Send invoice  
   Source: openapi-completed.yaml; Tag: 7. Platform Billing and Catalog
16. `POST /api/platform/v1/billing/invoices/export` - Export invoices  
   Source: openapi-completed.yaml; Tag: 7. Platform Billing and Catalog
17. `GET /api/platform/v1/billing/payments` - List payments  
   Source: openapi-completed.yaml; Tag: 7. Platform Billing and Catalog
18. `POST /api/platform/v1/billing/payments` - Record payment  
   Source: openapi-completed.yaml; Tag: 7. Platform Billing and Catalog
19. `GET /api/platform/v1/billing/payments/{payment_uuid}` - View payment  
   Source: openapi-completed.yaml; Tag: 7. Platform Billing and Catalog
20. `POST /api/platform/v1/billing/payments/{payment_uuid}/reconcile` - Reconcile payment placeholder  
   Source: openapi-completed.yaml; Tag: 7. Platform Billing and Catalog
21. `POST /api/platform/v1/billing/payments/{payment_uuid}/refund` - Refund payment  
   Source: openapi-completed.yaml; Tag: 7. Platform Billing and Catalog
22. `POST /api/platform/v1/billing/payments/{payment_uuid}/retry` - Retry payment placeholder  
   Source: openapi-completed.yaml; Tag: 7. Platform Billing and Catalog
23. `POST /api/platform/v1/billing/payments/export` - Export payments  
   Source: openapi-completed.yaml; Tag: 7. Platform Billing and Catalog
24. `GET /api/platform/v1/billing/refunds` - List refunds  
   Source: openapi-completed.yaml; Tag: 7. Platform Billing and Catalog
25. `POST /api/platform/v1/billing/refunds` - Create refund  
   Source: openapi-completed.yaml; Tag: 7. Platform Billing and Catalog
26. `GET /api/platform/v1/billing/refunds/{refund_uuid}` - View refund  
   Source: openapi-completed.yaml; Tag: 7. Platform Billing and Catalog
27. `POST /api/platform/v1/billing/refunds/{refund_uuid}/retry` - Retry refund placeholder  
   Source: openapi-completed.yaml; Tag: 7. Platform Billing and Catalog
28. `POST /api/platform/v1/billing/refunds/export` - Export refunds  
   Source: openapi-completed.yaml; Tag: 7. Platform Billing and Catalog
29. `GET /api/platform/v1/coupons` - List coupons  
   Source: openapi-completed.yaml; Tag: 7. Platform Billing and Catalog
30. `POST /api/platform/v1/coupons` - Create coupon  
   Source: openapi-completed.yaml; Tag: 7. Platform Billing and Catalog
31. `GET /api/platform/v1/coupons/{coupon_uuid}` - View coupon  
   Source: openapi-completed.yaml; Tag: 7. Platform Billing and Catalog
32. `PATCH /api/platform/v1/coupons/{coupon_uuid}` - Update coupon  
   Source: openapi-completed.yaml; Tag: 7. Platform Billing and Catalog
33. `PUT /api/platform/v1/coupons/{coupon_uuid}` - Update coupon  
   Source: openapi-completed.yaml; Tag: 7. Platform Billing and Catalog
34. `DELETE /api/platform/v1/coupons/{coupon_uuid}` - Delete or archive coupon  
   Source: openapi-completed.yaml; Tag: 7. Platform Billing and Catalog
35. `POST /api/platform/v1/coupons/{coupon_uuid}/activate` - Activate coupon  
   Source: openapi-completed.yaml; Tag: 7. Platform Billing and Catalog
36. `POST /api/platform/v1/coupons/{coupon_uuid}/deactivate` - Deactivate coupon  
   Source: openapi-completed.yaml; Tag: 7. Platform Billing and Catalog
37. `PUT /api/platform/v1/coupons/{coupon_uuid}/plans` - Replace coupon plan assignments  
   Source: openapi-completed.yaml; Tag: 7. Platform Billing and Catalog
38. `GET /api/platform/v1/coupons/{coupon_uuid}/redemptions` - Coupon redemptions  
   Source: openapi-completed.yaml; Tag: 7. Platform Billing and Catalog
39. `PUT /api/platform/v1/coupons/{coupon_uuid}/tenants` - Replace coupon tenant assignments  
   Source: openapi-completed.yaml; Tag: 7. Platform Billing and Catalog
40. `POST /api/platform/v1/coupons/export` - Export coupons  
   Source: openapi-completed.yaml; Tag: 7. Platform Billing and Catalog
41. `GET /api/platform/v1/features` - List features  
   Source: openapi-completed.yaml; Tag: 7. Platform Billing and Catalog
42. `POST /api/platform/v1/features` - Create feature  
   Source: openapi-completed.yaml; Tag: 7. Platform Billing and Catalog
43. `GET /api/platform/v1/features/{feature_uuid}` - View feature  
   Source: openapi-completed.yaml; Tag: 7. Platform Billing and Catalog
44. `PATCH /api/platform/v1/features/{feature_uuid}` - Update feature  
   Source: openapi-completed.yaml; Tag: 7. Platform Billing and Catalog
45. `PUT /api/platform/v1/features/{feature_uuid}` - Update feature  
   Source: openapi-completed.yaml; Tag: 7. Platform Billing and Catalog
46. `DELETE /api/platform/v1/features/{feature_uuid}` - Delete feature  
   Source: openapi-completed.yaml; Tag: 7. Platform Billing and Catalog
47. `GET /api/platform/v1/plans` - List plans  
   Source: openapi-completed.yaml; Tag: 7. Platform Billing and Catalog
48. `POST /api/platform/v1/plans` - Create plan  
   Source: openapi-completed.yaml; Tag: 7. Platform Billing and Catalog
49. `GET /api/platform/v1/plans/{plan_uuid}` - View plan  
   Source: openapi-completed.yaml; Tag: 7. Platform Billing and Catalog
50. `PATCH /api/platform/v1/plans/{plan_uuid}` - Update plan  
   Source: openapi-completed.yaml; Tag: 7. Platform Billing and Catalog
51. `PUT /api/platform/v1/plans/{plan_uuid}` - Update plan  
   Source: openapi-completed.yaml; Tag: 7. Platform Billing and Catalog
52. `DELETE /api/platform/v1/plans/{plan_uuid}` - Delete plan  
   Source: openapi-completed.yaml; Tag: 7. Platform Billing and Catalog
53. `POST /api/platform/v1/plans/{plan_uuid}/clone` - Clone plan  
   Source: openapi-completed.yaml; Tag: 7. Platform Billing and Catalog
54. `GET /api/platform/v1/plans/{plan_uuid}/features` - Plan features  
   Source: openapi-completed.yaml; Tag: 7. Platform Billing and Catalog
55. `PUT /api/platform/v1/plans/{plan_uuid}/features` - Replace plan features  
   Source: openapi-completed.yaml; Tag: 7. Platform Billing and Catalog
56. `GET /api/platform/v1/plans/{plan_uuid}/subscriptions` - Plan subscriptions  
   Source: openapi-completed.yaml; Tag: 7. Platform Billing and Catalog
57. `POST /api/platform/v1/plans/export` - Export plans  
   Source: openapi-completed.yaml; Tag: 7. Platform Billing and Catalog
58. `GET /api/platform/v1/subscriptions` - List subscriptions  
   Source: openapi-completed.yaml; Tag: 7. Platform Billing and Catalog
59. `POST /api/platform/v1/subscriptions` - Create subscription  
   Source: openapi-completed.yaml; Tag: 7. Platform Billing and Catalog
60. `GET /api/platform/v1/subscriptions/{subscription_uuid}` - View subscription  
   Source: openapi-completed.yaml; Tag: 7. Platform Billing and Catalog
61. `PATCH /api/platform/v1/subscriptions/{subscription_uuid}` - Update subscription  
   Source: openapi-completed.yaml; Tag: 7. Platform Billing and Catalog
62. `PUT /api/platform/v1/subscriptions/{subscription_uuid}` - Update subscription  
   Source: openapi-completed.yaml; Tag: 7. Platform Billing and Catalog
63. `POST /api/platform/v1/subscriptions/{subscription_uuid}/addons` - Add subscription add-on  
   Source: openapi-completed.yaml; Tag: 7. Platform Billing and Catalog
64. `PATCH /api/platform/v1/subscriptions/{subscription_uuid}/addons/{addon_id}` - Update subscription add-on  
   Source: openapi-completed.yaml; Tag: 7. Platform Billing and Catalog
65. `PUT /api/platform/v1/subscriptions/{subscription_uuid}/addons/{addon_id}` - Update subscription add-on  
   Source: openapi-completed.yaml; Tag: 7. Platform Billing and Catalog
66. `DELETE /api/platform/v1/subscriptions/{subscription_uuid}/addons/{addon_id}` - Cancel subscription add-on  
   Source: openapi-completed.yaml; Tag: 7. Platform Billing and Catalog
67. `POST /api/platform/v1/subscriptions/{subscription_uuid}/apply-coupon` - Apply coupon  
   Source: openapi-completed.yaml; Tag: 7. Platform Billing and Catalog
68. `POST /api/platform/v1/subscriptions/{subscription_uuid}/cancel` - Cancel subscription  
   Source: openapi-completed.yaml; Tag: 7. Platform Billing and Catalog
69. `DELETE /api/platform/v1/subscriptions/{subscription_uuid}/coupons/{coupon_uuid}` - Remove coupon  
   Source: openapi-completed.yaml; Tag: 7. Platform Billing and Catalog
70. `POST /api/platform/v1/subscriptions/{subscription_uuid}/downgrade` - Downgrade subscription  
   Source: openapi-completed.yaml; Tag: 7. Platform Billing and Catalog
71. `GET /api/platform/v1/subscriptions/{subscription_uuid}/history` - Subscription versions and renewals  
   Source: openapi-completed.yaml; Tag: 7. Platform Billing and Catalog
72. `POST /api/platform/v1/subscriptions/{subscription_uuid}/invoice` - Create subscription invoice  
   Source: openapi-completed.yaml; Tag: 7. Platform Billing and Catalog
73. `POST /api/platform/v1/subscriptions/{subscription_uuid}/pause` - Pause subscription  
   Source: openapi-completed.yaml; Tag: 7. Platform Billing and Catalog
74. `POST /api/platform/v1/subscriptions/{subscription_uuid}/renew` - Renew subscription  
   Source: openapi-completed.yaml; Tag: 7. Platform Billing and Catalog
75. `POST /api/platform/v1/subscriptions/{subscription_uuid}/resume` - Resume subscription  
   Source: openapi-completed.yaml; Tag: 7. Platform Billing and Catalog
76. `POST /api/platform/v1/subscriptions/{subscription_uuid}/upgrade` - Upgrade subscription  
   Source: openapi-completed.yaml; Tag: 7. Platform Billing and Catalog
77. `GET /api/platform/v1/subscriptions/{subscription_uuid}/usage` - Subscription usage  
   Source: openapi-completed.yaml; Tag: 7. Platform Billing and Catalog
78. `POST /api/platform/v1/subscriptions/export` - Export subscriptions  
   Source: openapi-completed.yaml; Tag: 7. Platform Billing and Catalog

## Platform - modules and feature controls

1. `GET /api/platform/v1/modules` - List modules  
   Source: openapi-completed.yaml; Tag: 7. Platform Billing and Catalog
2. `POST /api/platform/v1/modules` - Create module  
   Source: openapi-completed.yaml; Tag: 7. Platform Billing and Catalog
3. `GET /api/platform/v1/modules/{module_uuid}` - View module  
   Source: openapi-completed.yaml; Tag: 7. Platform Billing and Catalog
4. `PATCH /api/platform/v1/modules/{module_uuid}` - Update module  
   Source: openapi-completed.yaml; Tag: 7. Platform Billing and Catalog
5. `PUT /api/platform/v1/modules/{module_uuid}` - Update module  
   Source: openapi-completed.yaml; Tag: 7. Platform Billing and Catalog
6. `POST /api/platform/v1/modules/{module_uuid}/disable` - Disable module  
   Source: openapi-completed.yaml; Tag: 7. Platform Billing and Catalog
7. `POST /api/platform/v1/modules/{module_uuid}/enable` - Enable module  
   Source: openapi-completed.yaml; Tag: 7. Platform Billing and Catalog
8. `GET /api/platform/v1/modules/{module_uuid}/features` - Module features  
   Source: openapi-completed.yaml; Tag: 7. Platform Billing and Catalog
9. `PUT /api/platform/v1/modules/{module_uuid}/features` - Replace module feature assignments  
   Source: openapi-completed.yaml; Tag: 7. Platform Billing and Catalog
10. `GET /api/platform/v1/modules/{module_uuid}/tenants` - Module tenants  
   Source: openapi-completed.yaml; Tag: 7. Platform Billing and Catalog

## Platform - support, knowledge base, remote login

1. `GET /api/platform/v1/support/knowledge-base/articles` - List KB articles  
   Source: openapi-completed.yaml; Tag: 8. Remaining Platform Admin
2. `POST /api/platform/v1/support/knowledge-base/articles` - Create KB article  
   Source: openapi-completed.yaml; Tag: 8. Remaining Platform Admin
3. `GET /api/platform/v1/support/knowledge-base/articles/{article_uuid}` - View KB article  
   Source: openapi-completed.yaml; Tag: 8. Remaining Platform Admin
4. `PATCH /api/platform/v1/support/knowledge-base/articles/{article_uuid}` - Update KB article  
   Source: openapi-completed.yaml; Tag: 8. Remaining Platform Admin
5. `PUT /api/platform/v1/support/knowledge-base/articles/{article_uuid}` - Update KB article  
   Source: openapi-completed.yaml; Tag: 8. Remaining Platform Admin
6. `POST /api/platform/v1/support/knowledge-base/articles/{article_uuid}/archive` - Archive KB article  
   Source: openapi-completed.yaml; Tag: 8. Remaining Platform Admin
7. `POST /api/platform/v1/support/knowledge-base/articles/{article_uuid}/publish` - Publish KB article  
   Source: openapi-completed.yaml; Tag: 8. Remaining Platform Admin
8. `POST /api/platform/v1/support/knowledge-base/articles/{article_uuid}/unpublish` - Unpublish KB article  
   Source: openapi-completed.yaml; Tag: 8. Remaining Platform Admin
9. `GET /api/platform/v1/support/knowledge-base/categories` - List KB categories  
   Source: openapi-completed.yaml; Tag: 8. Remaining Platform Admin
10. `POST /api/platform/v1/support/knowledge-base/categories` - Create KB category  
   Source: openapi-completed.yaml; Tag: 8. Remaining Platform Admin
11. `PATCH /api/platform/v1/support/knowledge-base/categories/{category_uuid}` - Update KB category  
   Source: openapi-completed.yaml; Tag: 8. Remaining Platform Admin
12. `PUT /api/platform/v1/support/knowledge-base/categories/{category_uuid}` - Update KB category  
   Source: openapi-completed.yaml; Tag: 8. Remaining Platform Admin
13. `GET /api/platform/v1/support/remote-login-sessions` - List remote login sessions  
   Source: openapi-completed.yaml; Tag: 8. Remaining Platform Admin
14. `GET /api/platform/v1/support/remote-login-sessions/{session_uuid}` - Remote login session detail  
   Source: openapi-completed.yaml; Tag: 8. Remaining Platform Admin
15. `POST /api/platform/v1/support/remote-login-sessions/{session_uuid}/end` - End remote login session  
   Source: openapi-completed.yaml; Tag: 8. Remaining Platform Admin
16. `GET /api/platform/v1/support/tickets` - List platform support tickets  
   Source: openapi-completed.yaml; Tag: 8. Remaining Platform Admin
17. `POST /api/platform/v1/support/tickets` - Create platform support ticket  
   Source: openapi-completed.yaml; Tag: 8. Remaining Platform Admin
18. `GET /api/platform/v1/support/tickets/{ticket_uuid}` - Support ticket detail  
   Source: openapi-completed.yaml; Tag: 8. Remaining Platform Admin
19. `PATCH /api/platform/v1/support/tickets/{ticket_uuid}` - Update support ticket  
   Source: openapi-completed.yaml; Tag: 8. Remaining Platform Admin
20. `PUT /api/platform/v1/support/tickets/{ticket_uuid}` - Update support ticket  
   Source: openapi-completed.yaml; Tag: 8. Remaining Platform Admin
21. `POST /api/platform/v1/support/tickets/{ticket_uuid}/assign` - Assign support ticket  
   Source: openapi-completed.yaml; Tag: 8. Remaining Platform Admin
22. `POST /api/platform/v1/support/tickets/{ticket_uuid}/attachments` - Attach existing file to ticket  
   Source: openapi-completed.yaml; Tag: 8. Remaining Platform Admin
23. `POST /api/platform/v1/support/tickets/{ticket_uuid}/close` - Close support ticket  
   Source: openapi-completed.yaml; Tag: 8. Remaining Platform Admin
24. `POST /api/platform/v1/support/tickets/{ticket_uuid}/comments` - Add ticket comment or internal note  
   Source: openapi-completed.yaml; Tag: 8. Remaining Platform Admin
25. `POST /api/platform/v1/support/tickets/{ticket_uuid}/reopen` - Reopen support ticket  
   Source: openapi-completed.yaml; Tag: 8. Remaining Platform Admin
26. `POST /api/platform/v1/support/tickets/export` - Export support tickets  
   Source: openapi-completed.yaml; Tag: 8. Remaining Platform Admin

## Platform - reports

1. `GET /api/platform/v1/reports/{report_code}` - Run platform report  
   Source: openapi-completed.yaml; Tag: 8. Remaining Platform Admin
2. `POST /api/platform/v1/reports/{report_code}/export` - Queue report export  
   Source: openapi-completed.yaml; Tag: 8. Remaining Platform Admin
3. `GET /api/platform/v1/reports/export-jobs` - List report export jobs  
   Source: openapi-completed.yaml; Tag: 8. Remaining Platform Admin
4. `GET /api/platform/v1/reports/export-jobs/{job_uuid}` - Report export job detail  
   Source: openapi-completed.yaml; Tag: 8. Remaining Platform Admin

## Platform - monitoring, jobs, logs, alerts, incidents

1. `GET /api/platform/v1/monitoring/alerts` - Monitoring alerts  
   Source: openapi-completed.yaml; Tag: 8. Remaining Platform Admin
2. `POST /api/platform/v1/monitoring/alerts/{alert_id}/resolve` - Resolve alert with notes and actor  
   Source: openapi-completed.yaml; Tag: 8. Remaining Platform Admin
3. `GET /api/platform/v1/monitoring/api-request-logs` - API request logs  
   Source: openapi-completed.yaml; Tag: 8. Remaining Platform Admin
4. `GET /api/platform/v1/monitoring/incidents` - List incidents  
   Source: openapi-completed.yaml; Tag: 8. Remaining Platform Admin
5. `POST /api/platform/v1/monitoring/incidents` - Create incident  
   Source: openapi-completed.yaml; Tag: 8. Remaining Platform Admin
6. `GET /api/platform/v1/monitoring/incidents/{incident_id}` - Incident detail  
   Source: openapi-completed.yaml; Tag: 8. Remaining Platform Admin
7. `PATCH /api/platform/v1/monitoring/incidents/{incident_id}` - Update incident  
   Source: openapi-completed.yaml; Tag: 8. Remaining Platform Admin
8. `PUT /api/platform/v1/monitoring/incidents/{incident_id}` - Update incident  
   Source: openapi-completed.yaml; Tag: 8. Remaining Platform Admin
9. `POST /api/platform/v1/monitoring/incidents/{incident_id}/resolve` - Resolve incident with notes and actor  
   Source: openapi-completed.yaml; Tag: 8. Remaining Platform Admin
10. `GET /api/platform/v1/monitoring/queue-jobs` - Queue job logs  
   Source: openapi-completed.yaml; Tag: 8. Remaining Platform Admin
11. `DELETE /api/platform/v1/monitoring/queue-jobs/{job_id}` - Mark queue job deleted  
   Source: openapi-completed.yaml; Tag: 8. Remaining Platform Admin
12. `POST /api/platform/v1/monitoring/queue-jobs/{job_id}/retry` - Retry queue job  
   Source: openapi-completed.yaml; Tag: 8. Remaining Platform Admin
13. `GET /api/platform/v1/monitoring/scheduler-logs` - Scheduler logs  
   Source: openapi-completed.yaml; Tag: 8. Remaining Platform Admin
14. `GET /api/platform/v1/monitoring/services` - List monitoring services  
   Source: openapi-completed.yaml; Tag: 8. Remaining Platform Admin
15. `GET /api/platform/v1/monitoring/services/{service_code}/logs` - Monitoring service logs  
   Source: openapi-completed.yaml; Tag: 8. Remaining Platform Admin
16. `GET /api/platform/v1/monitoring/tenant-usage-snapshots` - Tenant usage snapshots  
   Source: openapi-completed.yaml; Tag: 8. Remaining Platform Admin

## Platform - integrations, webhooks, sync jobs

1. `GET /api/platform/v1/integrations/providers` - List integration providers  
   Source: openapi-completed.yaml; Tag: 8. Remaining Platform Admin
2. `POST /api/platform/v1/integrations/providers` - Create integration provider  
   Source: openapi-completed.yaml; Tag: 8. Remaining Platform Admin
3. `PATCH /api/platform/v1/integrations/providers/{provider_code}` - Update integration provider  
   Source: openapi-completed.yaml; Tag: 8. Remaining Platform Admin
4. `PUT /api/platform/v1/integrations/providers/{provider_code}` - Update integration provider  
   Source: openapi-completed.yaml; Tag: 8. Remaining Platform Admin
5. `GET /api/platform/v1/integrations/sync-jobs` - Integration sync jobs  
   Source: openapi-completed.yaml; Tag: 8. Remaining Platform Admin
6. `POST /api/platform/v1/integrations/sync-jobs/{job_id}/retry` - Retry integration sync job  
   Source: openapi-completed.yaml; Tag: 8. Remaining Platform Admin
7. `GET /api/platform/v1/integrations/tenant-integrations` - List tenant integrations  
   Source: openapi-completed.yaml; Tag: 8. Remaining Platform Admin
8. `POST /api/platform/v1/integrations/tenant-integrations` - Create tenant integration with encrypted credentials  
   Source: openapi-completed.yaml; Tag: 8. Remaining Platform Admin
9. `GET /api/platform/v1/integrations/tenant-integrations/{integration_uuid}` - Tenant integration detail  
   Source: openapi-completed.yaml; Tag: 8. Remaining Platform Admin
10. `PATCH /api/platform/v1/integrations/tenant-integrations/{integration_uuid}` - Update tenant integration  
   Source: openapi-completed.yaml; Tag: 8. Remaining Platform Admin
11. `PUT /api/platform/v1/integrations/tenant-integrations/{integration_uuid}` - Update tenant integration  
   Source: openapi-completed.yaml; Tag: 8. Remaining Platform Admin
12. `POST /api/platform/v1/integrations/tenant-integrations/{integration_uuid}/credentials` - Rotate write-only encrypted credentials  
   Source: openapi-completed.yaml; Tag: 8. Remaining Platform Admin
13. `POST /api/platform/v1/integrations/tenant-integrations/{integration_uuid}/disconnect` - Disconnect tenant integration  
   Source: openapi-completed.yaml; Tag: 8. Remaining Platform Admin
14. `GET /api/platform/v1/integrations/tenant-integrations/{integration_uuid}/mappings` - Integration field mappings  
   Source: openapi-completed.yaml; Tag: 8. Remaining Platform Admin
15. `PUT /api/platform/v1/integrations/tenant-integrations/{integration_uuid}/mappings` - Replace integration field mappings  
   Source: openapi-completed.yaml; Tag: 8. Remaining Platform Admin
16. `GET /api/platform/v1/integrations/tenant-integrations/{integration_uuid}/rate-limits` - Integration rate limits  
   Source: openapi-completed.yaml; Tag: 8. Remaining Platform Admin
17. `POST /api/platform/v1/integrations/tenant-integrations/{integration_uuid}/test` - Test tenant integration  
   Source: openapi-completed.yaml; Tag: 8. Remaining Platform Admin
18. `POST /api/platform/v1/integrations/webhook-logs/{log_id}/retry` - Retry webhook log idempotently  
   Source: openapi-completed.yaml; Tag: 8. Remaining Platform Admin
19. `GET /api/platform/v1/integrations/webhooks` - List integration webhooks  
   Source: openapi-completed.yaml; Tag: 8. Remaining Platform Admin
20. `POST /api/platform/v1/integrations/webhooks` - Create integration webhook  
   Source: openapi-completed.yaml; Tag: 8. Remaining Platform Admin
21. `GET /api/platform/v1/integrations/webhooks/{webhook_id}` - Integration webhook detail  
   Source: openapi-completed.yaml; Tag: 8. Remaining Platform Admin
22. `PATCH /api/platform/v1/integrations/webhooks/{webhook_id}` - Update integration webhook  
   Source: openapi-completed.yaml; Tag: 8. Remaining Platform Admin
23. `PUT /api/platform/v1/integrations/webhooks/{webhook_id}` - Update integration webhook  
   Source: openapi-completed.yaml; Tag: 8. Remaining Platform Admin
24. `DELETE /api/platform/v1/integrations/webhooks/{webhook_id}` - Disable integration webhook  
   Source: openapi-completed.yaml; Tag: 8. Remaining Platform Admin
25. `GET /api/platform/v1/integrations/webhooks/{webhook_id}/logs` - Integration webhook logs with masked payloads  
   Source: openapi-completed.yaml; Tag: 8. Remaining Platform Admin

## Platform - settings, notifications, backups

1. `GET /api/platform/v1/settings/backups` - Backup settings  
   Source: openapi-completed.yaml; Tag: 8. Remaining Platform Admin
2. `PUT /api/platform/v1/settings/backups` - Update backup settings  
   Source: openapi-completed.yaml; Tag: 8. Remaining Platform Admin
3. `POST /api/platform/v1/settings/backups/run` - Queue manual backup  
   Source: openapi-completed.yaml; Tag: 8. Remaining Platform Admin
4. `GET /api/platform/v1/settings/backups/runs` - Backup run history  
   Source: openapi-completed.yaml; Tag: 8. Remaining Platform Admin
5. `GET /api/platform/v1/settings/backups/runs/{run_uuid}` - Backup run detail  
   Source: openapi-completed.yaml; Tag: 8. Remaining Platform Admin
6. `GET /api/platform/v1/settings/backups/runs/{run_uuid}/download` - Backup download metadata  
   Source: openapi-completed.yaml; Tag: 8. Remaining Platform Admin
7. `GET /api/platform/v1/settings/notification-templates` - List notification templates  
   Source: openapi-completed.yaml; Tag: 8. Remaining Platform Admin
8. `POST /api/platform/v1/settings/notification-templates` - Create notification template  
   Source: openapi-completed.yaml; Tag: 8. Remaining Platform Admin
9. `PATCH /api/platform/v1/settings/notification-templates/{template_uuid}` - Update notification template  
   Source: openapi-completed.yaml; Tag: 8. Remaining Platform Admin
10. `PUT /api/platform/v1/settings/notification-templates/{template_uuid}` - Update notification template  
   Source: openapi-completed.yaml; Tag: 8. Remaining Platform Admin
11. `GET /api/platform/v1/settings/platform` - Platform settings  
   Source: openapi-completed.yaml; Tag: 8. Remaining Platform Admin
12. `PUT /api/platform/v1/settings/platform` - Update platform settings  
   Source: openapi-completed.yaml; Tag: 8. Remaining Platform Admin

## Platform - audit logs and exports

1. `GET /api/platform/v1/audit/activity-logs` - Activity logs  
   Source: openapi-completed.yaml; Tag: 8. Remaining Platform Admin
2. `POST /api/platform/v1/audit/export` - Export audit logs  
   Source: openapi-completed.yaml; Tag: 8. Remaining Platform Admin
3. `GET /api/platform/v1/audit/security-events` - Security events with masked metadata  
   Source: openapi-completed.yaml; Tag: 8. Remaining Platform Admin
4. `POST /api/platform/v1/audit/security-events/{event_id}/review` - Review security event  
   Source: openapi-completed.yaml; Tag: 8. Remaining Platform Admin

## Platform - onboarding, trials, legal, announcements, outbound webhooks

1. `GET /api/platform/v1/announcements` - List announcements  
   Source: openapi-completed.yaml; Tag: 8. Remaining Platform Admin
2. `POST /api/platform/v1/announcements` - Create announcement  
   Source: openapi-completed.yaml; Tag: 8. Remaining Platform Admin
3. `GET /api/platform/v1/announcements/{announcement_uuid}` - Announcement detail  
   Source: openapi-completed.yaml; Tag: 8. Remaining Platform Admin
4. `PATCH /api/platform/v1/announcements/{announcement_uuid}` - Update announcement  
   Source: openapi-completed.yaml; Tag: 8. Remaining Platform Admin
5. `PUT /api/platform/v1/announcements/{announcement_uuid}` - Update announcement  
   Source: openapi-completed.yaml; Tag: 8. Remaining Platform Admin
6. `DELETE /api/platform/v1/announcements/{announcement_uuid}` - Delete draft/archive announcement  
   Source: openapi-completed.yaml; Tag: 8. Remaining Platform Admin
7. `POST /api/platform/v1/announcements/{announcement_uuid}/archive` - Archive announcement  
   Source: openapi-completed.yaml; Tag: 8. Remaining Platform Admin
8. `POST /api/platform/v1/announcements/{announcement_uuid}/publish` - Publish announcement  
   Source: openapi-completed.yaml; Tag: 8. Remaining Platform Admin
9. `GET /api/platform/v1/legal/documents` - List legal documents  
   Source: openapi-completed.yaml; Tag: 8. Remaining Platform Admin
10. `POST /api/platform/v1/legal/documents` - Create legal document  
   Source: openapi-completed.yaml; Tag: 8. Remaining Platform Admin
11. `GET /api/platform/v1/legal/documents/{document_uuid}` - Legal document detail  
   Source: openapi-completed.yaml; Tag: 8. Remaining Platform Admin
12. `PATCH /api/platform/v1/legal/documents/{document_uuid}` - Update legal document  
   Source: openapi-completed.yaml; Tag: 8. Remaining Platform Admin
13. `PUT /api/platform/v1/legal/documents/{document_uuid}` - Update legal document  
   Source: openapi-completed.yaml; Tag: 8. Remaining Platform Admin
14. `GET /api/platform/v1/legal/documents/{document_uuid}/acceptances` - Legal document acceptances  
   Source: openapi-completed.yaml; Tag: 8. Remaining Platform Admin
15. `POST /api/platform/v1/legal/documents/{document_uuid}/publish` - Publish legal document  
   Source: openapi-completed.yaml; Tag: 8. Remaining Platform Admin
16. `GET /api/platform/v1/onboarding/tenants` - Tenant onboarding progress  
   Source: openapi-completed.yaml; Tag: 8. Remaining Platform Admin
17. `GET /api/platform/v1/onboarding/tenants/{tenant_uuid}` - Tenant onboarding detail  
   Source: openapi-completed.yaml; Tag: 8. Remaining Platform Admin
18. `PUT /api/platform/v1/onboarding/tenants/{tenant_uuid}/steps/{step_code}` - Update onboarding step  
   Source: openapi-completed.yaml; Tag: 8. Remaining Platform Admin
19. `GET /api/platform/v1/trials` - List trial tenants  
   Source: openapi-completed.yaml; Tag: 8. Remaining Platform Admin
20. `POST /api/platform/v1/trials/{tenant_uuid}/convert` - Convert trial tenant  
   Source: openapi-completed.yaml; Tag: 8. Remaining Platform Admin
21. `POST /api/platform/v1/trials/{tenant_uuid}/extend` - Extend tenant trial  
   Source: openapi-completed.yaml; Tag: 8. Remaining Platform Admin
22. `GET /api/platform/v1/webhook-deliveries/{delivery_uuid}` - Outbound webhook delivery detail with masked payload  
   Source: openapi-completed.yaml; Tag: 8. Remaining Platform Admin
23. `POST /api/platform/v1/webhook-deliveries/{delivery_uuid}/retry` - Retry outbound webhook delivery  
   Source: openapi-completed.yaml; Tag: 8. Remaining Platform Admin
24. `GET /api/platform/v1/webhook-endpoints` - List outbound webhook endpoints  
   Source: openapi-completed.yaml; Tag: 8. Remaining Platform Admin
25. `POST /api/platform/v1/webhook-endpoints` - Create outbound webhook endpoint  
   Source: openapi-completed.yaml; Tag: 8. Remaining Platform Admin
26. `GET /api/platform/v1/webhook-endpoints/{endpoint_uuid}` - Outbound webhook endpoint detail  
   Source: openapi-completed.yaml; Tag: 8. Remaining Platform Admin
27. `PATCH /api/platform/v1/webhook-endpoints/{endpoint_uuid}` - Update outbound webhook endpoint  
   Source: openapi-completed.yaml; Tag: 8. Remaining Platform Admin
28. `PUT /api/platform/v1/webhook-endpoints/{endpoint_uuid}` - Update outbound webhook endpoint  
   Source: openapi-completed.yaml; Tag: 8. Remaining Platform Admin
29. `DELETE /api/platform/v1/webhook-endpoints/{endpoint_uuid}` - Delete outbound webhook endpoint  
   Source: openapi-completed.yaml; Tag: 8. Remaining Platform Admin
30. `GET /api/platform/v1/webhook-endpoints/{endpoint_uuid}/deliveries` - Outbound webhook deliveries  
   Source: openapi-completed.yaml; Tag: 8. Remaining Platform Admin

## Tenant - session, profile, security, preferences, API tokens

1. `POST /api/tenant/v1/auth/2fa/confirm` - Confirm tenant TOTP 2FA setup  
   Source: openapi-completed.yaml; Tag: 3. Tenant Auth/Profile/Security
2. `POST /api/tenant/v1/auth/2fa/disable` - Disable tenant 2FA  
   Source: openapi-completed.yaml; Tag: 3. Tenant Auth/Profile/Security
3. `POST /api/tenant/v1/auth/2fa/enable` - Start tenant TOTP 2FA setup  
   Source: openapi-completed.yaml; Tag: 3. Tenant Auth/Profile/Security
4. `POST /api/tenant/v1/auth/forgot-password` - Request tenant password reset  
   Source: openapi-completed.yaml; Tag: 3. Tenant Auth/Profile/Security
5. `POST /api/tenant/v1/auth/logout` - Revoke current tenant token  
   Source: openapi-completed.yaml; Tag: 3. Tenant Auth/Profile/Security
6. `GET /api/tenant/v1/auth/me` - Tenant current user  
   Source: openapi-completed.yaml; Tag: 3. Tenant Auth/Profile/Security
7. `POST /api/tenant/v1/auth/refresh` - Rotate current tenant token  
   Source: openapi-completed.yaml; Tag: 3. Tenant Auth/Profile/Security
8. `POST /api/tenant/v1/auth/reset-password` - Reset tenant password  
   Source: openapi-completed.yaml; Tag: 3. Tenant Auth/Profile/Security
9. `POST /api/tenant/v1/auth/verify-email/resend` - Queue tenant verification email placeholder  
   Source: openapi-completed.yaml; Tag: 3. Tenant Auth/Profile/Security
10. `GET /api/tenant/v1/profile` - Get tenant profile  
   Source: openapi-completed.yaml; Tag: 3. Tenant Auth/Profile/Security
11. `PATCH /api/tenant/v1/profile` - Update tenant profile  
   Source: openapi-completed.yaml; Tag: 3. Tenant Auth/Profile/Security
12. `PUT /api/tenant/v1/profile` - Update tenant profile  
   Source: openapi-completed.yaml; Tag: 3. Tenant Auth/Profile/Security
13. `GET /api/tenant/v1/profile/api-tokens` - List tenant API tokens  
   Source: openapi-completed.yaml; Tag: 3. Tenant API Tokens
14. `POST /api/tenant/v1/profile/api-tokens` - Create tenant API token  
   Source: openapi-completed.yaml; Tag: 3. Tenant API Tokens
15. `POST /api/tenant/v1/profile/api-tokens/{token_uuid}/revoke` - Revoke tenant API token  
   Source: openapi-completed.yaml; Tag: 3. Tenant API Tokens
16. `POST /api/tenant/v1/profile/api-tokens/{token_uuid}/rotate` - Rotate tenant API token and return raw token once  
   Source: openapi-completed.yaml; Tag: 3. Tenant API Tokens
17. `PUT /api/tenant/v1/profile/password` - Change tenant password  
   Source: openapi-completed.yaml; Tag: 3. Tenant Auth/Profile/Security
18. `GET /api/tenant/v1/profile/preferences` - List tenant preferences  
   Source: openapi-completed.yaml; Tag: 3. Tenant Auth/Profile/Security
19. `PUT /api/tenant/v1/profile/preferences` - Update tenant preferences  
   Source: openapi-completed.yaml; Tag: 3. Tenant Auth/Profile/Security
20. `GET /api/tenant/v1/profile/sessions` - List tenant Sanctum sessions/tokens  
   Source: openapi-completed.yaml; Tag: 3. Tenant Auth/Profile/Security
21. `DELETE /api/tenant/v1/profile/sessions/{session_id}` - Revoke tenant session/token  
   Source: openapi-completed.yaml; Tag: 3. Tenant Auth/Profile/Security

## Tenant - navigation and dashboard

1. `GET /api/tenant/v1/dashboard/{widget}` - Tenant dashboard table widget  
   Source: openapi-completed.yaml; Tag: 9. Tenant Workspace Admin
2. `GET /api/tenant/v1/dashboard/charts/{chart}` - Tenant dashboard chart  
   Source: openapi-completed.yaml; Tag: 9. Tenant Workspace Admin
3. `POST /api/tenant/v1/dashboard/export` - Queue dashboard export  
   Source: openapi-completed.yaml; Tag: 9. Tenant Workspace Admin
4. `GET /api/tenant/v1/dashboard/recent-activities` - Tenant dashboard recent activities  
   Source: openapi-completed.yaml; Tag: 9. Tenant Workspace Admin
5. `GET /api/tenant/v1/dashboard/summary` - Tenant dashboard KPI summary  
   Source: openapi-completed.yaml; Tag: 9. Tenant Workspace Admin
6. `GET /api/tenant/v1/dashboard/widgets` - Tenant dashboard widget preferences  
   Source: openapi-completed.yaml; Tag: 9. Tenant Workspace Admin
7. `PUT /api/tenant/v1/dashboard/widgets` - Tenant dashboard widget preferences  
   Source: openapi-completed.yaml; Tag: 9. Tenant Workspace Admin
8. `GET /api/tenant/v1/navigation/sidebar` - Tenant navigation sidebar  
   Source: openapi-completed.yaml; Tag: 9. Tenant Workspace Admin

## Tenant - RBAC roles and permissions

1. `GET /api/tenant/v1/access-control/permissions` - List tenant permissions  
   Source: openapi-completed.yaml, openapi-rbac.yaml; Tag: 6. Tenant Permissions
2. `GET /api/tenant/v1/access-control/permissions/{permission_uuid}` - View tenant permission  
   Source: openapi-completed.yaml, openapi-rbac.yaml; Tag: 6. Tenant Permissions
3. `GET /api/tenant/v1/access-control/permissions/grouped` - List tenant permissions grouped by module  
   Source: openapi-completed.yaml, openapi-rbac.yaml; Tag: 6. Tenant Permissions
4. `GET /api/tenant/v1/access-control/roles` - List tenant roles  
   Source: openapi-completed.yaml, openapi-rbac.yaml; Tag: 6. Tenant Roles
5. `POST /api/tenant/v1/access-control/roles` - Create tenant role  
   Source: openapi-completed.yaml, openapi-rbac.yaml; Tag: 6. Tenant Roles
6. `GET /api/tenant/v1/access-control/roles/{role_uuid}` - View tenant role  
   Source: openapi-completed.yaml, openapi-rbac.yaml; Tag: 6. Tenant Roles
7. `PATCH /api/tenant/v1/access-control/roles/{role_uuid}` - Update tenant role  
   Source: openapi-completed.yaml, openapi-rbac.yaml; Tag: 6. Tenant Roles
8. `PUT /api/tenant/v1/access-control/roles/{role_uuid}` - Update tenant role  
   Source: openapi-completed.yaml; Tag: 6. Tenant Roles
9. `DELETE /api/tenant/v1/access-control/roles/{role_uuid}` - Delete custom unused tenant role  
   Source: openapi-completed.yaml, openapi-rbac.yaml; Tag: 6. Tenant Roles
10. `POST /api/tenant/v1/access-control/roles/{role_uuid}/activate` - Activate tenant role  
   Source: openapi-completed.yaml, openapi-rbac.yaml; Tag: 6. Tenant Roles
11. `POST /api/tenant/v1/access-control/roles/{role_uuid}/clone` - Clone tenant role  
   Source: openapi-completed.yaml, openapi-rbac.yaml; Tag: 6. Tenant Roles
12. `POST /api/tenant/v1/access-control/roles/{role_uuid}/deactivate` - Deactivate tenant role  
   Source: openapi-completed.yaml, openapi-rbac.yaml; Tag: 6. Tenant Roles
13. `GET /api/tenant/v1/access-control/roles/{role_uuid}/permissions` - List tenant role permissions grouped by module  
   Source: openapi-completed.yaml, openapi-rbac.yaml; Tag: 6. Tenant Roles
14. `PUT /api/tenant/v1/access-control/roles/{role_uuid}/permissions` - Replace tenant role permissions  
   Source: openapi-completed.yaml, openapi-rbac.yaml; Tag: 6. Tenant Roles
15. `GET /api/tenant/v1/access-control/roles/{role_uuid}/users` - List tenant users assigned to role  
   Source: openapi-completed.yaml, openapi-rbac.yaml; Tag: 6. Tenant Roles
16. `POST /api/tenant/v1/access-control/roles/{role_uuid}/users` - Assign tenant users to role  
   Source: openapi-completed.yaml, openapi-rbac.yaml; Tag: 6. Tenant Roles
17. `DELETE /api/tenant/v1/access-control/roles/{role_uuid}/users/{user_uuid}` - Remove tenant user from role  
   Source: openapi-completed.yaml, openapi-rbac.yaml; Tag: 6. Tenant Roles

## Tenant - teams, users, staff

1. `GET /api/tenant/v1/staff` - Staff profiles  
   Source: openapi-completed.yaml; Tag: 9. Tenant Workspace Admin
2. `POST /api/tenant/v1/staff` - Staff profiles  
   Source: openapi-completed.yaml; Tag: 9. Tenant Workspace Admin
3. `GET /api/tenant/v1/staff/{staff_uuid}` - Staff profile  
   Source: openapi-completed.yaml; Tag: 9. Tenant Workspace Admin
4. `PATCH /api/tenant/v1/staff/{staff_uuid}` - Staff profile  
   Source: openapi-completed.yaml; Tag: 9. Tenant Workspace Admin
5. `DELETE /api/tenant/v1/staff/{staff_uuid}` - Staff profile  
   Source: openapi-completed.yaml; Tag: 9. Tenant Workspace Admin
6. `GET /api/tenant/v1/staff/{staff_uuid}/{resource}` - Staff child resource  
   Source: openapi-completed.yaml; Tag: 9. Tenant Workspace Admin
7. `POST /api/tenant/v1/staff/{staff_uuid}/{resource}` - Staff child resource  
   Source: openapi-completed.yaml; Tag: 9. Tenant Workspace Admin
8. `PATCH /api/tenant/v1/staff/{staff_uuid}/{resource}/{id}` - Staff child resource item  
   Source: openapi-completed.yaml; Tag: 9. Tenant Workspace Admin
9. `DELETE /api/tenant/v1/staff/{staff_uuid}/{resource}/{id}` - Staff child resource item  
   Source: openapi-completed.yaml; Tag: 9. Tenant Workspace Admin
10. `GET /api/tenant/v1/staff/{staff_uuid}/activity` - Staff activity  
   Source: openapi-completed.yaml; Tag: 9. Tenant Workspace Admin
11. `POST /api/tenant/v1/staff/{staff_uuid}/restore` - Restore staff profile  
   Source: openapi-completed.yaml; Tag: 9. Tenant Workspace Admin
12. `GET /api/tenant/v1/staff/dashboard` - Staff dashboard  
   Source: openapi-completed.yaml; Tag: 9. Tenant Workspace Admin
13. `POST /api/tenant/v1/staff/export` - Queue staff export  
   Source: openapi-completed.yaml; Tag: 9. Tenant Workspace Admin
14. `GET /api/tenant/v1/staff/grid` - Staff grid  
   Source: openapi-completed.yaml; Tag: 9. Tenant Workspace Admin
15. `POST /api/tenant/v1/staff/import` - Queue staff import  
   Source: openapi-completed.yaml; Tag: 9. Tenant Workspace Admin
16. `GET /api/tenant/v1/team-roles` - Tenant team roles  
   Source: openapi-completed.yaml; Tag: 9. Tenant Workspace Admin
17. `POST /api/tenant/v1/team-roles` - Tenant team roles  
   Source: openapi-completed.yaml; Tag: 9. Tenant Workspace Admin
18. `PATCH /api/tenant/v1/team-roles/{team_role_uuid}` - Tenant team role  
   Source: openapi-completed.yaml; Tag: 9. Tenant Workspace Admin
19. `DELETE /api/tenant/v1/team-roles/{team_role_uuid}` - Tenant team role  
   Source: openapi-completed.yaml; Tag: 9. Tenant Workspace Admin
20. `GET /api/tenant/v1/teams` - Tenant teams  
   Source: openapi-completed.yaml; Tag: 9. Tenant Workspace Admin
21. `POST /api/tenant/v1/teams` - Tenant teams  
   Source: openapi-completed.yaml; Tag: 9. Tenant Workspace Admin
22. `GET /api/tenant/v1/teams/{team_uuid}` - Tenant team  
   Source: openapi-completed.yaml; Tag: 9. Tenant Workspace Admin
23. `PATCH /api/tenant/v1/teams/{team_uuid}` - Tenant team  
   Source: openapi-completed.yaml; Tag: 9. Tenant Workspace Admin
24. `DELETE /api/tenant/v1/teams/{team_uuid}` - Tenant team  
   Source: openapi-completed.yaml; Tag: 9. Tenant Workspace Admin
25. `GET /api/tenant/v1/teams/{team_uuid}/assignments` - Team assignments  
   Source: openapi-completed.yaml; Tag: 9. Tenant Workspace Admin
26. `POST /api/tenant/v1/teams/{team_uuid}/assignments` - Team assignments  
   Source: openapi-completed.yaml; Tag: 9. Tenant Workspace Admin
27. `DELETE /api/tenant/v1/teams/{team_uuid}/assignments/{assignment_id}` - Team assignment release  
   Source: openapi-completed.yaml; Tag: 9. Tenant Workspace Admin
28. `GET /api/tenant/v1/teams/{team_uuid}/members` - Team members  
   Source: openapi-completed.yaml; Tag: 9. Tenant Workspace Admin
29. `POST /api/tenant/v1/teams/{team_uuid}/members` - Team members  
   Source: openapi-completed.yaml; Tag: 9. Tenant Workspace Admin
30. `PATCH /api/tenant/v1/teams/{team_uuid}/members/{member_uuid}` - Team member  
   Source: openapi-completed.yaml; Tag: 9. Tenant Workspace Admin
31. `DELETE /api/tenant/v1/teams/{team_uuid}/members/{member_uuid}` - Team member  
   Source: openapi-completed.yaml; Tag: 9. Tenant Workspace Admin
32. `GET /api/tenant/v1/teams/{team_uuid}/permissions` - Team permissions  
   Source: openapi-completed.yaml; Tag: 9. Tenant Workspace Admin
33. `PUT /api/tenant/v1/teams/{team_uuid}/permissions` - Team permissions  
   Source: openapi-completed.yaml; Tag: 9. Tenant Workspace Admin
34. `GET /api/tenant/v1/teams/{team_uuid}/settings` - Team settings  
   Source: openapi-completed.yaml; Tag: 9. Tenant Workspace Admin
35. `PUT /api/tenant/v1/teams/{team_uuid}/settings` - Team settings  
   Source: openapi-completed.yaml; Tag: 9. Tenant Workspace Admin
36. `POST /api/tenant/v1/teams/export` - Queue teams export  
   Source: openapi-completed.yaml; Tag: 9. Tenant Workspace Admin
37. `GET /api/tenant/v1/users` - Tenant login users  
   Source: openapi-completed.yaml; Tag: 9. Tenant Workspace Admin
38. `GET /api/tenant/v1/users/{user_uuid}` - Tenant login user  
   Source: openapi-completed.yaml; Tag: 9. Tenant Workspace Admin
39. `PATCH /api/tenant/v1/users/{user_uuid}` - Tenant login user  
   Source: openapi-completed.yaml; Tag: 9. Tenant Workspace Admin
40. `POST /api/tenant/v1/users/{user_uuid}/activate` - Activate tenant login user  
   Source: openapi-completed.yaml; Tag: 9. Tenant Workspace Admin
41. `POST /api/tenant/v1/users/{user_uuid}/reset-password` - Reset tenant login user password  
   Source: openapi-completed.yaml; Tag: 9. Tenant Workspace Admin
42. `PUT /api/tenant/v1/users/{user_uuid}/roles` - Replace tenant login user roles  
   Source: openapi-completed.yaml; Tag: 9. Tenant Workspace Admin
43. `POST /api/tenant/v1/users/{user_uuid}/suspend` - Suspend tenant login user  
   Source: openapi-completed.yaml; Tag: 9. Tenant Workspace Admin
44. `POST /api/tenant/v1/users/invite` - Invite tenant login user  
   Source: openapi-completed.yaml; Tag: 9. Tenant Workspace Admin

## Tenant CRM - clients

1. `GET /api/tenant/v1/clients` - Tenant clients  
   Source: openapi-completed.yaml; Tag: 10. Tenant CRM
2. `POST /api/tenant/v1/clients` - Tenant clients  
   Source: openapi-completed.yaml; Tag: 10. Tenant CRM
3. `GET /api/tenant/v1/clients/{client_uuid}` - Tenant client  
   Source: openapi-completed.yaml; Tag: 10. Tenant CRM
4. `PATCH /api/tenant/v1/clients/{client_uuid}` - Tenant client  
   Source: openapi-completed.yaml; Tag: 10. Tenant CRM
5. `DELETE /api/tenant/v1/clients/{client_uuid}` - Tenant client  
   Source: openapi-completed.yaml; Tag: 10. Tenant CRM
6. `GET /api/tenant/v1/clients/{client_uuid}/{resource}` - Client related projects, invoices, payments, renewals, or issues  
   Source: openapi-completed.yaml; Tag: 10. Tenant CRM
7. `GET /api/tenant/v1/clients/{client_uuid}/activity` - Client activity timeline  
   Source: openapi-completed.yaml; Tag: 10. Tenant CRM
8. `GET /api/tenant/v1/clients/{client_uuid}/addresses` - Client addresses  
   Source: openapi-completed.yaml; Tag: 10. Tenant CRM
9. `POST /api/tenant/v1/clients/{client_uuid}/addresses` - Client addresses  
   Source: openapi-completed.yaml; Tag: 10. Tenant CRM
10. `PATCH /api/tenant/v1/clients/{client_uuid}/addresses/{address_id}` - Client address  
   Source: openapi-completed.yaml; Tag: 10. Tenant CRM
11. `DELETE /api/tenant/v1/clients/{client_uuid}/addresses/{address_id}` - Client address  
   Source: openapi-completed.yaml; Tag: 10. Tenant CRM
12. `GET /api/tenant/v1/clients/{client_uuid}/contacts` - Client contacts  
   Source: openapi-completed.yaml; Tag: 10. Tenant CRM
13. `POST /api/tenant/v1/clients/{client_uuid}/contacts` - Client contacts  
   Source: openapi-completed.yaml; Tag: 10. Tenant CRM
14. `PATCH /api/tenant/v1/clients/{client_uuid}/contacts/{contact_uuid}` - Client contact  
   Source: openapi-completed.yaml; Tag: 10. Tenant CRM
15. `DELETE /api/tenant/v1/clients/{client_uuid}/contacts/{contact_uuid}` - Client contact  
   Source: openapi-completed.yaml; Tag: 10. Tenant CRM
16. `POST /api/tenant/v1/clients/{client_uuid}/restore` - Restore tenant client  
   Source: openapi-completed.yaml; Tag: 10. Tenant CRM
17. `POST /api/tenant/v1/clients/export` - Queue client export  
   Source: openapi-completed.yaml; Tag: 10. Tenant CRM
18. `POST /api/tenant/v1/clients/import` - Queue client import  
   Source: openapi-completed.yaml; Tag: 10. Tenant CRM
19. `POST /api/tenant/v1/clients/merge` - Merge duplicate clients  
   Source: openapi-completed.yaml; Tag: 10. Tenant CRM

## Tenant CRM - vendors

1. `GET /api/tenant/v1/vendors` - Tenant vendors  
   Source: openapi-completed.yaml; Tag: 10. Tenant CRM
2. `POST /api/tenant/v1/vendors` - Tenant vendors  
   Source: openapi-completed.yaml; Tag: 10. Tenant CRM
3. `GET /api/tenant/v1/vendors/{vendor_uuid}` - Tenant vendor  
   Source: openapi-completed.yaml; Tag: 10. Tenant CRM
4. `PATCH /api/tenant/v1/vendors/{vendor_uuid}` - Tenant vendor  
   Source: openapi-completed.yaml; Tag: 10. Tenant CRM
5. `DELETE /api/tenant/v1/vendors/{vendor_uuid}` - Tenant vendor  
   Source: openapi-completed.yaml; Tag: 10. Tenant CRM
6. `GET /api/tenant/v1/vendors/{vendor_uuid}/{resource}` - Vendor related expenses or renewals  
   Source: openapi-completed.yaml; Tag: 10. Tenant CRM
7. `GET /api/tenant/v1/vendors/{vendor_uuid}/activity` - Vendor activity timeline  
   Source: openapi-completed.yaml; Tag: 10. Tenant CRM
8. `GET /api/tenant/v1/vendors/{vendor_uuid}/addresses` - Vendor addresses  
   Source: openapi-completed.yaml; Tag: 10. Tenant CRM
9. `POST /api/tenant/v1/vendors/{vendor_uuid}/addresses` - Vendor addresses  
   Source: openapi-completed.yaml; Tag: 10. Tenant CRM
10. `PATCH /api/tenant/v1/vendors/{vendor_uuid}/addresses/{address_id}` - Vendor address  
   Source: openapi-completed.yaml; Tag: 10. Tenant CRM
11. `GET /api/tenant/v1/vendors/{vendor_uuid}/bank-accounts` - Vendor bank accounts  
   Source: openapi-completed.yaml; Tag: 10. Tenant CRM
12. `POST /api/tenant/v1/vendors/{vendor_uuid}/bank-accounts` - Vendor bank accounts  
   Source: openapi-completed.yaml; Tag: 10. Tenant CRM
13. `GET /api/tenant/v1/vendors/{vendor_uuid}/contacts` - Vendor contacts  
   Source: openapi-completed.yaml; Tag: 10. Tenant CRM
14. `POST /api/tenant/v1/vendors/{vendor_uuid}/contacts` - Vendor contacts  
   Source: openapi-completed.yaml; Tag: 10. Tenant CRM
15. `PATCH /api/tenant/v1/vendors/{vendor_uuid}/contacts/{contact_uuid}` - Vendor contact  
   Source: openapi-completed.yaml; Tag: 10. Tenant CRM
16. `POST /api/tenant/v1/vendors/export` - Queue vendor export  
   Source: openapi-completed.yaml; Tag: 10. Tenant CRM
17. `POST /api/tenant/v1/vendors/import` - Queue vendor import  
   Source: openapi-completed.yaml; Tag: 10. Tenant CRM

## Tenant CRM - leads

1. `GET /api/tenant/v1/leads` - Tenant leads  
   Source: openapi-completed.yaml; Tag: 10. Tenant CRM
2. `POST /api/tenant/v1/leads` - Tenant leads  
   Source: openapi-completed.yaml; Tag: 10. Tenant CRM
3. `GET /api/tenant/v1/leads/{lead_uuid}` - Tenant lead  
   Source: openapi-completed.yaml; Tag: 10. Tenant CRM
4. `PATCH /api/tenant/v1/leads/{lead_uuid}` - Tenant lead  
   Source: openapi-completed.yaml; Tag: 10. Tenant CRM
5. `DELETE /api/tenant/v1/leads/{lead_uuid}` - Tenant lead  
   Source: openapi-completed.yaml; Tag: 10. Tenant CRM
6. `GET /api/tenant/v1/leads/{lead_uuid}/activities` - Lead activities and follow-ups  
   Source: openapi-completed.yaml; Tag: 10. Tenant CRM
7. `POST /api/tenant/v1/leads/{lead_uuid}/activities` - Lead activities and follow-ups  
   Source: openapi-completed.yaml; Tag: 10. Tenant CRM
8. `PATCH /api/tenant/v1/leads/{lead_uuid}/activities/{activity_uuid}` - Lead activity  
   Source: openapi-completed.yaml; Tag: 10. Tenant CRM
9. `GET /api/tenant/v1/leads/{lead_uuid}/activity` - Lead audit timeline  
   Source: openapi-completed.yaml; Tag: 10. Tenant CRM
10. `POST /api/tenant/v1/leads/{lead_uuid}/convert` - Convert lead to client  
   Source: openapi-completed.yaml; Tag: 10. Tenant CRM
11. `POST /api/tenant/v1/leads/{lead_uuid}/duplicate` - Duplicate lead  
   Source: openapi-completed.yaml; Tag: 10. Tenant CRM
12. `POST /api/tenant/v1/leads/{lead_uuid}/mark-lost` - Mark lead lost  
   Source: openapi-completed.yaml; Tag: 10. Tenant CRM
13. `GET /api/tenant/v1/leads/dashboard` - Lead dashboard  
   Source: openapi-completed.yaml; Tag: 10. Tenant CRM
14. `POST /api/tenant/v1/leads/export` - Queue lead export  
   Source: openapi-completed.yaml; Tag: 10. Tenant CRM
15. `POST /api/tenant/v1/leads/import` - Queue lead import  
   Source: openapi-completed.yaml; Tag: 10. Tenant CRM
16. `GET /api/tenant/v1/leads/kanban` - Lead Kanban pipeline  
   Source: openapi-completed.yaml; Tag: 10. Tenant CRM
17. `POST /api/tenant/v1/leads/merge` - Merge duplicate leads  
   Source: openapi-completed.yaml; Tag: 10. Tenant CRM

