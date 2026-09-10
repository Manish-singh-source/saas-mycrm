# API Endpoints

Generated from the current Laravel route declarations:

- `backend/routes/api.php`
- `backend/routes/api-platform.php`
- `backend/routes/api-tenant.php`

This is the registered endpoint inventory. Controller behavior, validation, response schemas, and permissions are defined in the implementation referenced by each route. `{name}` values are route parameters; `PUT,PATCH` means both methods are registered.

## Complete endpoint inventory


============================= Implemented APIs ===============================
| Method | Endpoint | Source |
|---|---|---|
| `GET` | `/api/common/v1/locations/countries` | backend/routes/api.php:16 |
| `GET` | `/api/common/v1/locations/states` | backend/routes/api.php:17 |
| `GET` | `/api/common/v1/locations/cities` | backend/routes/api.php:18 |

| `GET` | `/api/platform/v1/health` | backend/routes/api-platform.php:26 |
| `POST` | `/api/auth/v1/accounts/discover` | backend/routes/api.php:27 |
| `POST` | `/api/auth/v1/accounts/login` | backend/routes/api.php:28 |
| `POST` | `/api/auth/v1/accounts/login/2fa` | backend/routes/api.php:29 |

| `GET` | `/api/auth/v1/me` | backend/routes/api.php:34 |
| `POST` | `/api/auth/v1/logout` | backend/routes/api.php:35 |
| `POST` | `/api/platform/v1/refresh` | backend/routes/api-platform.php:40 |

| `GET` | `/api/platform/v1/profile` | backend/routes/api-platform.php:48 |
| `PUT,PATCH` | `/api/platform/v1/profile` | backend/routes/api-platform.php:49 |
| `PUT` | `/api/platform/v1/profile/password` | backend/routes/api-platform.php:51 |

| `GET` | `/api/platform/v1/profile/sessions` | backend/routes/api-platform.php:55 |
| `DELETE` | `/api/platform/v1/profile/sessions/{session_id}` | backend/routes/api-platform.php:56 |


============================= Common Forgot APIs ===============================
| `POST` | `/api/auth/v1/password/forgot` | backend/routes/api.php:30 |
| `POST` | `/api/auth/v1/password/reset` | backend/routes/api.php:31 |
============================= Common Forgot APIs End ===============================

=========================== Platform Verify Email and 2FA APIs=============================
| `POST` | `/api/platform/v1/verify-email/resend` | backend/routes/api-platform.php:41 |

| `POST` | `/api/platform/v1/2fa/enable` | backend/routes/api-platform.php:43 |
| `POST` | `/api/platform/v1/2fa/confirm` | backend/routes/api-platform.php:44 |
| `POST` | `/api/platform/v1/2fa/disable` | backend/routes/api-platform.php:45 |
======================== Platform Verify Email and 2FA APIs End ========================

--------- platform settings apis -------------
| `GET` | `/api/platform/v1/settings/preferences` | backend/routes/api-platform.php:52 |
| `PUT` | `/api/platform/v1/settings/preferences` | backend/routes/api-platform.php:53 |
--------- platform settings apis end -------------

--------- platform permissions apis -------------
| `GET` | `/api/platform/v1/permissions/grouped` | backend/routes/api-platform.php:259 |
| `GET` | `/api/platform/v1/permissions` | backend/routes/api-platform.php:260 |
| `POST` | `/api/platform/v1/permissions/export` | backend/routes/api-platform.php:261 |
| `POST` | `/api/platform/v1/permissions` | backend/routes/api-platform.php:262 |
| `GET` | `/api/platform/v1/permissions/{permission_uuid}` | backend/routes/api-platform.php:263 |
| `PUT,PATCH` | `/api/platform/v1/permissions/{permission_uuid}` | backend/routes/api-platform.php:264 |
| `DELETE` | `/api/platform/v1/permissions/{permission_uuid}` | backend/routes/api-platform.php:265 |
--------- platform permissions apis end -------------



--------- platform roles apis -------------
-------------- Main CRUD APIs ---------------
| `GET` | `/api/platform/v1/roles` |
| `POST` | `/api/platform/v1/roles` |
| `GET` | `/api/platform/v1/roles/{role_uuid}` |
| `PUT,PATCH` | `/api/platform/v1/roles/{role_uuid}` |
| `DELETE` | `/api/platform/v1/roles/{role_uuid}` |
-------------- Main CRUD APIs ---------------

-------------- Extra Functionalities APIs ---------------
| `POST` | `/api/platform/v1/roles/{role_uuid}/clone` |
| `POST` | `/api/platform/v1/roles/{role_uuid}/activate` |
| `POST` | `/api/platform/v1/roles/{role_uuid}/deactivate` |
-------------- Extra Functionalities APIs ---------------

-------------- List permissions assigned to role APIs ---------------
| `GET` | `/api/platform/v1/roles/{role_uuid}/permissions` |
-------------- List permissions assigned to role APIs ---------------
-------------- Update permissions assigned to role APIs ---------------
| `PUT` | `/api/platform/v1/roles/{role_uuid}/permissions` |
-------------- Update permissions assigned to role APIs ---------------

| `POST` | `/api/platform/v1/roles/export` |

-------------- List Users/Staff assigned to role APIs ---------------
| `GET` | `/api/platform/v1/roles/{role_uuid}/users` |
-------------- List Users/Staff assigned to role APIs ---------------
-------------- Update Users/Staff assigned to role APIs ---------------
| `POST` | `/api/platform/v1/roles/{role_uuid}/users` |
-------------- Update Users/Staff assigned to role APIs ---------------
-------------- Delete/Remove Users/Staff assigned to role APIs ---------------
| `DELETE` | `/api/platform/v1/roles/{role_uuid}/users/{platform_user_uuid}` |
-------------- Delete/Remove Users/Staff assigned to role APIs ---------------

--------- platform roles apis end -------------



--------- platform users/staff apis -------------
| `POST` | `/api/platform/v1/platform-users/export` | backend/routes/api-platform.php:75 |
| `POST` | `/api/platform/v1/platform-users/invite` | backend/routes/api-platform.php:76 |

| `GET` | `/api/platform/v1/platform-users` | backend/routes/api-platform.php:77 |
| `POST` | `/api/platform/v1/platform-users` | backend/routes/api-platform.php:78 |
| `GET` | `/api/platform/v1/platform-users/{platform_user_uuid}` | backend/routes/api-platform.php:79 |
| `PUT,PATCH` | `/api/platform/v1/platform-users/{platform_user_uuid}` | backend/routes/api-platform.php:80 |
| `DELETE` | `/api/platform/v1/platform-users/{platform_user_uuid}` | backend/routes/api-platform.php:81 |

| `POST` | `/api/platform/v1/platform-users/{platform_user_uuid}/restore` | backend/routes/api-platform.php:82 |
| `POST` | `/api/platform/v1/platform-users/{platform_user_uuid}/suspend` | backend/routes/api-platform.php:83 |
| `POST` | `/api/platform/v1/platform-users/{platform_user_uuid}/activate` | backend/routes/api-platform.php:84 |

| `POST` | `/api/platform/v1/platform-users/{platform_user_uuid}/reset-password` | backend/routes/api-platform.php:85 |
| `POST` | `/api/platform/v1/platform-users/{platform_user_uuid}/force-logout` | backend/routes/api-platform.php:86 |
| `POST` | `/api/platform/v1/platform-users/{platform_user_uuid}/require-2fa` | backend/routes/api-platform.php:87 |

| `GET` | `/api/platform/v1/platform-users/{platform_user_uuid}/roles` | backend/routes/api-platform.php:88 |
| `PUT` | `/api/platform/v1/platform-users/{platform_user_uuid}/roles` | backend/routes/api-platform.php:89 |

| `GET` | `/api/platform/v1/platform-users/{platform_user_uuid}/teams` | backend/routes/api-platform.php:90 |
| `PUT` | `/api/platform/v1/platform-users/{platform_user_uuid}/teams` | backend/routes/api-platform.php:91 |

| `GET` | `/api/platform/v1/platform-users/{platform_user_uuid}/permissions` | backend/routes/api-platform.php:92 |
| `PUT` | `/api/platform/v1/platform-users/{platform_user_uuid}/permissions` | backend/routes/api-platform.php:93 |

| `GET` | `/api/platform/v1/platform-users/{platform_user_uuid}/activity` | backend/routes/api-platform.php:94 |
--------- platform users/staff apis end -------------


--------- platform teams apis -------------
| `GET` | `/api/platform/v1/platform-teams` || `POST` | `/api/platform/v1/platform-teams` || `GET` | `/api/platform/v1/platform-teams/{team_uuid}` || `PUT,PATCH` | `/api/platform/v1/platform-teams/{team_uuid}` || `DELETE` | `/api/platform/v1/platform-teams/{team_uuid}` |

| `GET` | `/api/platform/v1/platform-teams/{team_uuid}/members` |
| `POST` | `/api/platform/v1/platform-teams/{team_uuid}/members` |
| `PUT,PATCH` | `/api/platform/v1/platform-teams/{team_uuid}/members/{member_id}` |
| `DELETE` | `/api/platform/v1/platform-teams/{team_uuid}/members/{member_id}` |

| `GET` | `/api/platform/v1/platform-teams/{team_uuid}/assignments` |
| `POST` | `/api/platform/v1/platform-teams/{team_uuid}/assignments` |
| `DELETE` | `/api/platform/v1/platform-teams/{team_uuid}/assignments/{assignment_id}` |
--------- platform teams apis end -------------


--------- platform team roles apis -------------
| `GET` | `/api/platform/v1/platform-team-roles` |
| `POST` | `/api/platform/v1/platform-team-roles` |
| `GET` | `/api/platform/v1/platform-team-roles/{role_uuid}` |
| `PUT,PATCH` | `/api/platform/v1/platform-team-roles/{role_uuid}` |
| `DELETE` | `/api/platform/v1/platform-team-roles/{role_uuid}` |
--------- platform team roles apis end -------------



--------- platform features apis -------------
| `GET` | `/api/platform/v1/features` | backend/routes/api-platform.php:172 |
| `POST` | `/api/platform/v1/features` | backend/routes/api-platform.php:173 |
| `POST` | `/api/platform/v1/features/export` | backend/routes/api-platform.php:174 |
| `POST` | `/api/platform/v1/features/import` | backend/routes/api-platform.php:175 |
| `DELETE` | `/api/platform/v1/features/bulk` | backend/routes/api-platform.php:176 |
| `GET` | `/api/platform/v1/features/{feature_uuid}` | backend/routes/api-platform.php:177 |
| `PUT,PATCH` | `/api/platform/v1/features/{feature_uuid}` | backend/routes/api-platform.php:178 |
| `DELETE` | `/api/platform/v1/features/{feature_uuid}` | backend/routes/api-platform.php:179 |
--------- platform features apis end -------------



--------- platform modules apis -------------
| `GET` | `/api/platform/v1/modules` | backend/routes/api-platform.php:228 |
| `POST` | `/api/platform/v1/modules` | backend/routes/api-platform.php:229 |
| `POST` | `/api/platform/v1/modules/export` | backend/routes/api-platform.php:230 |
| `POST` | `/api/platform/v1/modules/import` | backend/routes/api-platform.php:231 |
| `DELETE` | `/api/platform/v1/modules/bulk` | backend/routes/api-platform.php:232 |
| `GET` | `/api/platform/v1/modules/{module_uuid}` | backend/routes/api-platform.php:233 |
| `PUT,PATCH` | `/api/platform/v1/modules/{module_uuid}` | backend/routes/api-platform.php:234 |
| `DELETE` | `/api/platform/v1/modules/{module_uuid}` | backend/routes/api-platform.php:235 |
| `POST` | `/api/platform/v1/modules/{module_uuid}/enable` | backend/routes/api-platform.php:236 |
| `POST` | `/api/platform/v1/modules/{module_uuid}/disable` | backend/routes/api-platform.php:237 |
| `GET` | `/api/platform/v1/modules/{module_uuid}/features` | backend/routes/api-platform.php:238 |
| `PUT` | `/api/platform/v1/modules/{module_uuid}/features` | backend/routes/api-platform.php:239 |
--------- platform modules apis end -------------


--------- platform coupons apis -------------
| `GET` | `/api/platform/v1/coupons` | backend/routes/api-platform.php:213 |
| `POST` | `/api/platform/v1/coupons` | backend/routes/api-platform.php:214 |
| `POST` | `/api/platform/v1/coupons/export` | backend/routes/api-platform.php:215 |
| `POST` | `/api/platform/v1/coupons/import` | backend/routes/api-platform.php:216 |
| `DELETE` | `/api/platform/v1/coupons/bulk` | backend/routes/api-platform.php:217 |
| `GET` | `/api/platform/v1/coupons/{coupon_uuid}` | backend/routes/api-platform.php:218 |
| `PUT,PATCH` | `/api/platform/v1/coupons/{coupon_uuid}` | backend/routes/api-platform.php:219 |
| `DELETE` | `/api/platform/v1/coupons/{coupon_uuid}` | backend/routes/api-platform.php:220 |
| `POST` | `/api/platform/v1/coupons/{coupon_uuid}/activate` | backend/routes/api-platform.php:221 |
| `POST` | `/api/platform/v1/coupons/{coupon_uuid}/deactivate` | backend/routes/api-platform.php:222 |
--------- platform coupons apis end -------------

--------- platform add-ons apis -------------
| `GET` | `/api/platform/v1/addons` | backend/routes/api-platform.php:181 |
| `POST` | `/api/platform/v1/addons` | backend/routes/api-platform.php:182 |
| `POST` | `/api/platform/v1/addons/export` | backend/routes/api-platform.php:183 |
| `POST` | `/api/platform/v1/addons/import` | backend/routes/api-platform.php:184 |
| `DELETE` | `/api/platform/v1/addons/bulk` | backend/routes/api-platform.php:185 |
| `GET` | `/api/platform/v1/addons/{addon_uuid}` | backend/routes/api-platform.php:186 |
| `PUT,PATCH` | `/api/platform/v1/addons/{addon_uuid}` | backend/routes/api-platform.php:187 |
| `DELETE` | `/api/platform/v1/addons/{addon_uuid}` | backend/routes/api-platform.php:188 |
--------- platform add-ons apis end -------------

--------- platform plans apis -------------
| `GET` | `/api/platform/v1/plans` | backend/routes/api-platform.php:155 |
| `POST` | `/api/platform/v1/plans` | backend/routes/api-platform.php:156 |
| `POST` | `/api/platform/v1/plans/export` | backend/routes/api-platform.php:157 |
| `POST` | `/api/platform/v1/plans/import` | backend/routes/api-platform.php:158 |
| `DELETE` | `/api/platform/v1/plans/bulk` | backend/routes/api-platform.php:159 |
| `GET` | `/api/platform/v1/plans/{plan_uuid}` | backend/routes/api-platform.php:160 |
| `PUT,PATCH` | `/api/platform/v1/plans/{plan_uuid}` | backend/routes/api-platform.php:161 |
| `DELETE` | `/api/platform/v1/plans/{plan_uuid}` | backend/routes/api-platform.php:162 |
| `POST` | `/api/platform/v1/plans/{plan_uuid}/clone` | backend/routes/api-platform.php:163 |
| `POST` | `/api/platform/v1/plans/{plan_uuid}/activate` | backend/routes/api-platform.php:164 |
| `POST` | `/api/platform/v1/plans/{plan_uuid}/deactivate` | backend/routes/api-platform.php:165 |
| `GET` | `/api/platform/v1/plans/{plan_uuid}/features` | backend/routes/api-platform.php:166 |
| `PUT` | `/api/platform/v1/plans/{plan_uuid}/features` | backend/routes/api-platform.php:167 |
| `GET` | `/api/platform/v1/plans/{plan_uuid}/addons` | backend/routes/api-platform.php:168 |
| `PUT` | `/api/platform/v1/plans/{plan_uuid}/addons` | backend/routes/api-platform.php:169 |
| `GET` | `/api/platform/v1/plans/{plan_uuid}/subscriptions` | backend/routes/api-platform.php:170 |
--------- platform plans apis end -------------



--------- After plans apis -------------
| `PUT` | `/api/platform/v1/coupons/{coupon_uuid}/plans` | backend/routes/api-platform.php:224 |
--------- After plans apis end -------------


--------- Knowledge base apis -------------
| `GET` | `/api/platform/v1/knowledge-base/categories` | backend/routes/api-platform.php:294 |
| `POST` | `/api/platform/v1/knowledge-base/categories` | backend/routes/api-platform.php:295 |
| `PUT,PATCH` | `/api/platform/v1/knowledge-base/categories/{category_uuid}` | backend/routes/api-platform.php:296 |
| `GET` | `/api/platform/v1/knowledge-base/articles` | backend/routes/api-platform.php:297 |
| `POST` | `/api/platform/v1/knowledge-base/articles` | backend/routes/api-platform.php:298 |
| `GET` | `/api/platform/v1/knowledge-base/articles/{article_uuid}` | backend/routes/api-platform.php:299 |
| `PUT,PATCH` | `/api/platform/v1/knowledge-base/articles/{article_uuid}` | backend/routes/api-platform.php:300 |
| `POST` | `/api/platform/v1/knowledge-base/articles/{article_uuid}/publish` | backend/routes/api-platform.php:301 |
| `POST` | `/api/platform/v1/knowledge-base/articles/{article_uuid}/unpublish` | backend/routes/api-platform.php:302 |
| `POST` | `/api/platform/v1/knowledge-base/articles/{article_uuid}/archive` | backend/routes/api-platform.php:303 |

============================= Knowledge base apis End ===============================


================================= Monitoring ===============================
| `GET` | `/api/platform/v1/services` | backend/routes/api-platform.php:315 |
| `GET` | `/api/platform/v1/services/{service_code}/logs` | backend/routes/api-platform.php:316 |

| `GET` | `/api/platform/v1/api-request-logs` | backend/routes/api-platform.php:317 |

| `GET` | `/api/platform/v1/queue-jobs` | backend/routes/api-platform.php:318 |
| `POST` | `/api/platform/v1/queue-jobs/{job_id}/retry` | backend/routes/api-platform.php:319 |
| `DELETE` | `/api/platform/v1/queue-jobs/{job_id}` | backend/routes/api-platform.php:320 |

| `GET` | `/api/platform/v1/scheduler-logs` | backend/routes/api-platform.php:321 |
| `GET` | `/api/platform/v1/alerts` | backend/routes/api-platform.php:322 |
| `POST` | `/api/platform/v1/alerts/{alert_id}/resolve` | backend/routes/api-platform.php:323 |

| `GET` | `/api/platform/v1/incidents` | backend/routes/api-platform.php:324 |
| `POST` | `/api/platform/v1/incidents` | backend/routes/api-platform.php:325 |
| `GET` | `/api/platform/v1/incidents/{incident_id}` | backend/routes/api-platform.php:326 |
| `PUT,PATCH` | `/api/platform/v1/incidents/{incident_id}` | backend/routes/api-platform.php:327 |
| `POST` | `/api/platform/v1/incidents/{incident_id}/resolve` | backend/routes/api-platform.php:328 |

| `GET` | `/api/platform/v1/tenant-usage-snapshots` | backend/routes/api-platform.php:329 |
================================= Monitoring ===============================

================================= Integrations Part 1 ===============================
| `GET` | `/api/platform/v1/providers` | backend/routes/api-platform.php:333 |
| `POST` | `/api/platform/v1/providers` | backend/routes/api-platform.php:334 |
| `PUT,PATCH` | `/api/platform/v1/providers/{provider_code}` | backend/routes/api-platform.php:335 |
================================= Integrations Part 1 ===============================


================================= Settings ===============================
| `GET` | `/api/platform/v1/platform` | backend/routes/api-platform.php:358 |
| `PUT` | `/api/platform/v1/platform` | backend/routes/api-platform.php:359 |


| `GET` | `/api/platform/v1/notification-templates` | backend/routes/api-platform.php:360 |
| `POST` | `/api/platform/v1/notification-templates` | backend/routes/api-platform.php:361 |
| `PUT,PATCH` | `/api/platform/v1/notification-templates/{template_uuid}` | backend/routes/api-platform.php:362 |


| `GET` | `/api/platform/v1/backups` | backend/routes/api-platform.php:363 |
| `PUT` | `/api/platform/v1/backups` | backend/routes/api-platform.php:364 |
| `POST` | `/api/platform/v1/backups/run` | backend/routes/api-platform.php:365 |
| `GET` | `/api/platform/v1/backups/runs` | backend/routes/api-platform.php:366 |
| `GET` | `/api/platform/v1/backups/runs/{run_uuid}` | backend/routes/api-platform.php:367 |
| `GET` | `/api/platform/v1/backups/runs/{run_uuid}/download` | backend/routes/api-platform.php:368 |
================================= Settings ===============================


================================= Activity Logs ===============================
| `GET` | `/api/platform/v1/audit/activity-logs` | backend/routes/api-platform.php:371 |
| `GET` | `/api/platform/v1/audit/security-events` | backend/routes/api-platform.php:372 |
| `POST` | `/api/platform/v1/audit/security-events/{event_id}/review` | backend/routes/api-platform.php:373 |
| `POST` | `/api/platform/v1/audit/export` | backend/routes/api-platform.php:374 |
================================= Activity Logs ===============================

================================= Legal Documents ===============================
| `GET` | `/api/platform/v1/legal/documents` | backend/routes/api-platform.php:383 |
| `POST` | `/api/platform/v1/legal/documents` | backend/routes/api-platform.php:384 |
| `GET` | `/api/platform/v1/legal/documents/{document_uuid}` | backend/routes/api-platform.php:385 |
| `PUT,PATCH` | `/api/platform/v1/legal/documents/{document_uuid}` | backend/routes/api-platform.php:386 |
| `POST` | `/api/platform/v1/legal/documents/{document_uuid}/publish` | backend/routes/api-platform.php:387 |
| `GET` | `/api/platform/v1/legal/documents/{document_uuid}/acceptances` | backend/routes/api-platform.php:388 |
================================= Legal Documents ===============================

================================= Announcements ===============================
| `GET` | `/api/platform/v1/announcements` | backend/routes/api-platform.php:390 |
| `POST` | `/api/platform/v1/announcements` | backend/routes/api-platform.php:391 |
| `GET` | `/api/platform/v1/announcements/{announcement_uuid}` | backend/routes/api-platform.php:392 |
| `PUT,PATCH` | `/api/platform/v1/announcements/{announcement_uuid}` | backend/routes/api-platform.php:393 |
| `POST` | `/api/platform/v1/announcements/{announcement_uuid}/publish` | backend/routes/api-platform.php:394 |
| `POST` | `/api/platform/v1/announcements/{announcement_uuid}/archive` | backend/routes/api-platform.php:395 |
| `DELETE` | `/api/platform/v1/announcements/{announcement_uuid}` | backend/routes/api-platform.php:396 |
================================= Announcements ===============================


================================= Webhooks ===============================
| `GET` | `/api/platform/v1/webhook-endpoints` | backend/routes/api-platform.php:398 |
| `POST` | `/api/platform/v1/webhook-endpoints` | backend/routes/api-platform.php:399 |
| `GET` | `/api/platform/v1/webhook-endpoints/{endpoint_uuid}` | backend/routes/api-platform.php:400 |
| `PUT,PATCH` | `/api/platform/v1/webhook-endpoints/{endpoint_uuid}` | backend/routes/api-platform.php:401 |
| `DELETE` | `/api/platform/v1/webhook-endpoints/{endpoint_uuid}` | backend/routes/api-platform.php:402 |
| `GET` | `/api/platform/v1/webhook-endpoints/{endpoint_uuid}/deliveries` | backend/routes/api-platform.php:403 |
| `GET` | `/api/platform/v1/webhook-deliveries/{delivery_uuid}` | backend/routes/api-platform.php:404 |
| `POST` | `/api/platform/v1/webhook-deliveries/{delivery_uuid}/retry` | backend/routes/api-platform.php:405 |
================================= Webhooks ===============================

================================= API Tokens ===============================
| `GET` | `/api/platform/v1/api-tokens` | backend/routes/api-platform.php:406 |
| `POST` | `/api/platform/v1/api-tokens` | backend/routes/api-platform.php:407 |
| `GET` | `/api/platform/v1/api-tokens/{token_uuid}` | backend/routes/api-platform.php:408 |
| `POST` | `/api/platform/v1/api-tokens/{token_uuid}/rotate` | backend/routes/api-platform.php:409 |
| `POST` | `/api/platform/v1/api-tokens/{token_uuid}/revoke` | backend/routes/api-platform.php:410 |
================================= API Tokens ===============================



--------- After tenants apis -------------
| `GET` | `/api/platform/v1/modules/{module_uuid}/tenants` | backend/routes/api-platform.php:240 |
| `GET` | `/api/platform/v1/tenants/{tenant_uuid}/module-entitlements` | backend/routes/api-platform.php:241 |
| `PUT` | `/api/platform/v1/tenants/{tenant_uuid}/modules/{module_code}` | backend/routes/api-platform.php:242 |
| `GET` | `/api/platform/v1/coupons/{coupon_uuid}/redemptions` | backend/routes/api-platform.php:223 |
| `PUT` | `/api/platform/v1/coupons/{coupon_uuid}/tenants` | backend/routes/api-platform.php:225 |
--------- After tenants apis end -------------


--------- tenant registration apis -------------
| `GET` | `/api/auth/v1/tenants/plans` | backend/routes/api.php:25 |
| `POST` | `/api/auth/v1/tenants/register` | backend/routes/api.php:26 |
--------- tenant registration apis end -------------

--------- platform tenants apis -------------
| `GET` | `/api/platform/v1/tenants` | backend/routes/api-platform.php:114 |
| `POST` | `/api/platform/v1/tenants` | backend/routes/api-platform.php:115 |
| `GET` | `/api/platform/v1/tenants/{tenant_uuid}` | backend/routes/api-platform.php:116 |
| `PUT,PATCH` | `/api/platform/v1/tenants/{tenant_uuid}` | backend/routes/api-platform.php:117 |
| `DELETE` | `/api/platform/v1/tenants/bulk` | backend/routes/api-platform.php:119 |
| `DELETE` | `/api/platform/v1/tenants/{tenant_uuid}` | backend/routes/api-platform.php:120 |
| `POST` | `/api/platform/v1/tenants/{tenant_uuid}/restore` | backend/routes/api-platform.php:121 |
| `POST` | `/api/platform/v1/tenants/{tenant_uuid}/activate` | backend/routes/api-platform.php:122 |
| `POST` | `/api/platform/v1/tenants/{tenant_uuid}/suspend` | backend/routes/api-platform.php:123 |
| `POST` | `/api/platform/v1/tenants/{tenant_uuid}/reactivate` | backend/routes/api-platform.php:124 |
| `POST` | `/api/platform/v1/tenants/{tenant_uuid}/archive` | backend/routes/api-platform.php:125 |
| `POST` | `/api/platform/v1/tenants/{tenant_uuid}/extend-trial` | backend/routes/api-platform.php:126 |
| `POST` | `/api/platform/v1/tenants/{tenant_uuid}/change-plan` | backend/routes/api-platform.php:127 |
| `POST` | `/api/platform/v1/tenants/{tenant_uuid}/reset-owner-password` | backend/routes/api-platform.php:128 |
| `POST` | `/api/platform/v1/tenants/{tenant_uuid}/payment-order` | backend/routes/api-platform.php:129 |
| `POST` | `/api/platform/v1/tenants/{tenant_uuid}/impersonate` | backend/routes/api-platform.php:130 |
| `DELETE` | `/api/platform/v1/tenants/{tenant_uuid}/impersonate/{session_uuid}` | backend/routes/api-platform.php:131 |
| `GET` | `/api/platform/v1/tenants/{tenant_uuid}/{tab}` | backend/routes/api-platform.php:132 |
| `PUT` | `/api/platform/v1/tenants/{tenant_uuid}/modules` | backend/routes/api-platform.php:133 |
--------- platform tenants apis end -------------



















































--------- platform dashboard apis -------------
| `GET` | `/api/platform/v1/summary` | backend/routes/api-platform.php:62 |
| `GET` | `/api/platform/v1/charts` | backend/routes/api-platform.php:63 |
| `GET` | `/api/platform/v1/charts/{chart}` | backend/routes/api-platform.php:64 |
| `GET` | `/api/platform/v1/recent` | backend/routes/api-platform.php:65 |
| `GET` | `/api/platform/v1/recent-tenants` | backend/routes/api-platform.php:66 |
| `GET` | `/api/platform/v1/recent-payments` | backend/routes/api-platform.php:67 |
| `GET` | `/api/platform/v1/overdue-invoices` | backend/routes/api-platform.php:68 |
| `GET` | `/api/platform/v1/alerts` | backend/routes/api-platform.php:69 |
| `GET` | `/api/platform/v1/active-alerts` | backend/routes/api-platform.php:70 |
| `GET` | `/api/platform/v1/security-events` | backend/routes/api-platform.php:71 |
| `POST` | `/api/platform/v1/export` | backend/routes/api-platform.php:72 |
--------- platform dashboard apis end -------------








--------- platform subscriptions apis -------------
| `GET` | `/api/platform/v1/subscriptions` | backend/routes/api-platform.php:134 |
| `POST` | `/api/platform/v1/subscriptions` | backend/routes/api-platform.php:135 |
| `POST` | `/api/platform/v1/subscriptions/export` | backend/routes/api-platform.php:136 |
| `GET` | `/api/platform/v1/subscriptions/{subscription_uuid}` | backend/routes/api-platform.php:137 |
| `PUT,PATCH` | `/api/platform/v1/subscriptions/{subscription_uuid}` | backend/routes/api-platform.php:138 |
| `POST` | `/api/platform/v1/subscriptions/{subscription_uuid}/upgrade` | backend/routes/api-platform.php:139 |
| `POST` | `/api/platform/v1/subscriptions/{subscription_uuid}/downgrade` | backend/routes/api-platform.php:140 |
| `POST` | `/api/platform/v1/subscriptions/{subscription_uuid}/renew` | backend/routes/api-platform.php:141 |
| `POST` | `/api/platform/v1/subscriptions/{subscription_uuid}/pause` | backend/routes/api-platform.php:142 |
| `POST` | `/api/platform/v1/subscriptions/{subscription_uuid}/resume` | backend/routes/api-platform.php:143 |
| `POST` | `/api/platform/v1/subscriptions/{subscription_uuid}/cancel` | backend/routes/api-platform.php:144 |
| `POST` | `/api/platform/v1/subscriptions/{subscription_uuid}/addons` | backend/routes/api-platform.php:145 |
| `PUT,PATCH` | `/api/platform/v1/subscriptions/{subscription_uuid}/addons/{addon_id}` | backend/routes/api-platform.php:146 |
| `DELETE` | `/api/platform/v1/subscriptions/{subscription_uuid}/addons/{addon_id}` | backend/routes/api-platform.php:147 |
| `POST` | `/api/platform/v1/subscriptions/{subscription_uuid}/apply-coupon` | backend/routes/api-platform.php:148 |
| `DELETE` | `/api/platform/v1/subscriptions/{subscription_uuid}/coupons/{coupon_uuid}` | backend/routes/api-platform.php:149 |
| `GET` | `/api/platform/v1/subscriptions/{subscription_uuid}/usage` | backend/routes/api-platform.php:150 |
| `POST` | `/api/platform/v1/subscriptions/{subscription_uuid}/invoice` | backend/routes/api-platform.php:151 |
| `GET` | `/api/platform/v1/subscriptions/{subscription_uuid}/history` | backend/routes/api-platform.php:152 |
--------- platform subscriptions apis end -------------




--------- platform invoices apis -------------
| `GET` | `/api/platform/v1/billing/invoices` | backend/routes/api-platform.php:190 |
| `POST` | `/api/platform/v1/billing/invoices` | backend/routes/api-platform.php:191 |
| `POST` | `/api/platform/v1/billing/invoices/export` | backend/routes/api-platform.php:192 |
| `GET` | `/api/platform/v1/billing/invoices/{invoice_uuid}` | backend/routes/api-platform.php:193 |
| `PUT,PATCH` | `/api/platform/v1/billing/invoices/{invoice_uuid}` | backend/routes/api-platform.php:194 |
| `DELETE` | `/api/platform/v1/billing/invoices/{invoice_uuid}` | backend/routes/api-platform.php:195 |
| `POST` | `/api/platform/v1/billing/invoices/{invoice_uuid}/send` | backend/routes/api-platform.php:196 |
| `GET` | `/api/platform/v1/billing/invoices/{invoice_uuid}/pdf` | backend/routes/api-platform.php:197 |
| `POST` | `/api/platform/v1/billing/invoices/{invoice_uuid}/payments` | backend/routes/api-platform.php:198 |
--------- platform invoices apis end -------------


--------- platform payments apis -------------
| `GET` | `/api/platform/v1/billing/payments` | backend/routes/api-platform.php:199 |
| `POST` | `/api/platform/v1/billing/payments` | backend/routes/api-platform.php:200 |
| `POST` | `/api/platform/v1/billing/payments/export` | backend/routes/api-platform.php:201 |
| `GET` | `/api/platform/v1/billing/payments/{payment_uuid}` | backend/routes/api-platform.php:202 |
| `POST` | `/api/platform/v1/billing/payments/{payment_uuid}/retry` | backend/routes/api-platform.php:203 |
| `POST` | `/api/platform/v1/billing/payments/{payment_uuid}/reconcile` | backend/routes/api-platform.php:204 |
| `POST` | `/api/platform/v1/billing/payments/{payment_uuid}/refund` | backend/routes/api-platform.php:205 |
--------- platform payments apis end -------------


--------- platform refunds apis -------------
| `GET` | `/api/platform/v1/billing/refunds` | backend/routes/api-platform.php:206 |
| `POST` | `/api/platform/v1/billing/refunds` | backend/routes/api-platform.php:207 |
| `POST` | `/api/platform/v1/billing/refunds/export` | backend/routes/api-platform.php:208 |
| `GET` | `/api/platform/v1/billing/refunds/{refund_uuid}` | backend/routes/api-platform.php:209 |
| `POST` | `/api/platform/v1/billing/refunds/{refund_uuid}/retry` | backend/routes/api-platform.php:210 |
--------- platform refunds apis end -------------




--------- Tenant Auth & Profile apis -------------

| `GET` | `/api/tenant/v1/health` | backend/routes/api-tenant.php:23 |
| `POST` | `/api/tenant/v1/forgot-password` | backend/routes/api-tenant.php:27 |
| `POST` | `/api/tenant/v1/reset-password` | backend/routes/api-tenant.php:28 |
| `POST` | `/api/tenant/v1/logout` | backend/routes/api-tenant.php:34 |
| `POST` | `/api/tenant/v1/refresh` | backend/routes/api-tenant.php:35 |

| `GET` | `/api/tenant/v1/me` | backend/routes/api-tenant.php:36 |
| `POST` | `/api/tenant/v1/verify-email/resend` | backend/routes/api-tenant.php:37 |
| `POST` | `/api/tenant/v1/2fa/enable` | backend/routes/api-tenant.php:38 |
| `POST` | `/api/tenant/v1/2fa/confirm` | backend/routes/api-tenant.php:39 |
| `POST` | `/api/tenant/v1/2fa/disable` | backend/routes/api-tenant.php:40 |
| `GET` | `/api/tenant/v1/profile` | backend/routes/api-tenant.php:43 |
| `PUT,PATCH` | `/api/tenant/v1/profile` | backend/routes/api-tenant.php:44 |
| `PUT` | `/api/tenant/v1/profile/password` | backend/routes/api-tenant.php:45 |
| `GET` | `/api/tenant/v1/profile/preferences` | backend/routes/api-tenant.php:46 |
| `PUT` | `/api/tenant/v1/profile/preferences` | backend/routes/api-tenant.php:47 |
| `GET` | `/api/tenant/v1/profile/sessions` | backend/routes/api-tenant.php:48 |
| `DELETE` | `/api/tenant/v1/profile/sessions/{session_id}` | backend/routes/api-tenant.php:49 |

--------- Tenant Auth & Profile apis end -------------





--------- Tenant Permissions & Roles apis -------------
| `GET` | `/api/tenant/v1/permissions/grouped` | backend/routes/api-tenant.php:80 |
| `GET` | `/api/tenant/v1/permissions` | backend/routes/api-tenant.php:81 |
| `GET` | `/api/tenant/v1/permissions/{permission_uuid}` | backend/routes/api-tenant.php:82 |


| `GET` | `/api/tenant/v1/roles` | backend/routes/api-tenant.php:64 |
| `POST` | `/api/tenant/v1/roles` | backend/routes/api-tenant.php:65 |
| `GET` | `/api/tenant/v1/roles/{role_uuid}` | backend/routes/api-tenant.php:67 |
| `PUT,PATCH` | `/api/tenant/v1/roles/{role_uuid}` | backend/routes/api-tenant.php:68 |
| `DELETE` | `/api/tenant/v1/roles/{role_uuid}` | backend/routes/api-tenant.php:69 |
| `DELETE` | `/api/tenant/v1/roles/bulk` | backend/routes/api-tenant.php:66 |
| `POST` | `/api/tenant/v1/roles/{role_uuid}/clone` | backend/routes/api-tenant.php:70 |
| `POST` | `/api/tenant/v1/roles/{role_uuid}/activate` | backend/routes/api-tenant.php:71 |
| `POST` | `/api/tenant/v1/roles/{role_uuid}/deactivate` | backend/routes/api-tenant.php:72 |
| `GET` | `/api/tenant/v1/roles/{role_uuid}/permissions` | backend/routes/api-tenant.php:73 |
| `PUT` | `/api/tenant/v1/roles/{role_uuid}/permissions` | backend/routes/api-tenant.php:74 |



| `GET` | `/api/tenant/v1/roles/{role_uuid}/users` | backend/routes/api-tenant.php:75 |
| `POST` | `/api/tenant/v1/roles/{role_uuid}/users` | backend/routes/api-tenant.php:76 |
| `PUT` | `/api/tenant/v1/roles/{role_uuid}/users` | backend/routes/api-tenant.php:77 |
| `DELETE` | `/api/tenant/v1/roles/{role_uuid}/users/{user_uuid}` | backend/routes/api-tenant.php:78 |

--------- Tenant Permissions & Roles apis end -------------


--------- Tenant Teams & Team roles apis -------------
| `GET` | `/api/tenant/v1/team-roles` | backend/routes/api-tenant.php:86 |
| `POST` | `/api/tenant/v1/team-roles` | backend/routes/api-tenant.php:87 |
| `PUT,PATCH` | `/api/tenant/v1/team-roles/{team_role_uuid}` | backend/routes/api-tenant.php:88 |
| `DELETE` | `/api/tenant/v1/team-roles/{team_role_uuid}` | backend/routes/api-tenant.php:89 |

--------- Tenant Teams & Team roles apis end -------------


--------- Tenant staff apis -------------
| `GET` | `/api/tenant/v1/users` | backend/routes/api-tenant.php:113 |
| `POST` | `/api/tenant/v1/users/invite` | backend/routes/api-tenant.php:114 |
| `GET` | `/api/tenant/v1/users/{user_uuid}` | backend/routes/api-tenant.php:115 |
| `PUT,PATCH` | `/api/tenant/v1/users/{user_uuid}` | backend/routes/api-tenant.php:116 |
| `PUT` | `/api/tenant/v1/users/{user_uuid}/roles` | backend/routes/api-tenant.php:117 |
| `POST` | `/api/tenant/v1/users/{user_uuid}/suspend` | backend/routes/api-tenant.php:118 |
| `POST` | `/api/tenant/v1/users/{user_uuid}/activate` | backend/routes/api-tenant.php:119 |
| `POST` | `/api/tenant/v1/users/{user_uuid}/reset-password` | backend/routes/api-tenant.php:120 |
| `POST` | `/api/tenant/v1/users/{user_uuid}/force-logout` | backend/routes/api-tenant.php:121 |
| `POST` | `/api/tenant/v1/users/{user_uuid}/require-2fa` | backend/routes/api-tenant.php:122 |




| `GET` | `/api/tenant/v1/staff/dashboard` | backend/routes/api-tenant.php:124 |
| `GET` | `/api/tenant/v1/staff/grid` | backend/routes/api-tenant.php:125 |
| `POST` | `/api/tenant/v1/staff/import` | backend/routes/api-tenant.php:126 |
| `POST` | `/api/tenant/v1/staff/export` | backend/routes/api-tenant.php:127 |
| `GET` | `/api/tenant/v1/staff` | backend/routes/api-tenant.php:128 |
| `POST` | `/api/tenant/v1/staff` | backend/routes/api-tenant.php:129 |
| `DELETE` | `/api/tenant/v1/staff/bulk` | backend/routes/api-tenant.php:130 |
| `GET` | `/api/tenant/v1/staff/{staff_uuid}/roles` | backend/routes/api-tenant.php:131 |
| `PUT` | `/api/tenant/v1/staff/{staff_uuid}/roles` | backend/routes/api-tenant.php:132 |
| `GET` | `/api/tenant/v1/staff/{staff_uuid}/teams` | backend/routes/api-tenant.php:133 |
| `PUT` | `/api/tenant/v1/staff/{staff_uuid}/teams` | backend/routes/api-tenant.php:134 |
| `GET` | `/api/tenant/v1/staff/{staff_uuid}/projects` | backend/routes/api-tenant.php:135 |
| `PUT` | `/api/tenant/v1/staff/{staff_uuid}/projects` | backend/routes/api-tenant.php:136 |
| `GET` | `/api/tenant/v1/staff/{staff_uuid}/tasks` | backend/routes/api-tenant.php:137 |
| `PUT` | `/api/tenant/v1/staff/{staff_uuid}/tasks` | backend/routes/api-tenant.php:138 |
| `GET` | `/api/tenant/v1/staff/{staff_uuid}` | backend/routes/api-tenant.php:139 |
| `PUT,PATCH` | `/api/tenant/v1/staff/{staff_uuid}` | backend/routes/api-tenant.php:140 |
| `DELETE` | `/api/tenant/v1/staff/{staff_uuid}` | backend/routes/api-tenant.php:141 |
| `POST` | `/api/tenant/v1/staff/{staff_uuid}/restore` | backend/routes/api-tenant.php:142 |
| `GET` | `/api/tenant/v1/staff/{staff_uuid}/activity` | backend/routes/api-tenant.php:143 |
| `GET` | `/api/tenant/v1/staff/{staff_uuid}/tabs/{tab}` | backend/routes/api-tenant.php:144 |
| `GET` | `/api/tenant/v1/staff/{staff_uuid}/bank-accounts` | backend/routes/api-tenant.php:145 |
| `POST` | `/api/tenant/v1/staff/{staff_uuid}/bank-accounts` | backend/routes/api-tenant.php:146 |
| `PUT,PATCH` | `/api/tenant/v1/staff/{staff_uuid}/bank-accounts/{id}` | backend/routes/api-tenant.php:147 |
| `DELETE` | `/api/tenant/v1/staff/{staff_uuid}/bank-accounts/{id}` | backend/routes/api-tenant.php:148 |
| `GET` | `/api/tenant/v1/staff/{staff_uuid}/salary-structures` | backend/routes/api-tenant.php:149 |
| `POST` | `/api/tenant/v1/staff/{staff_uuid}/salary-structures` | backend/routes/api-tenant.php:150 |
| `PUT,PATCH` | `/api/tenant/v1/staff/{staff_uuid}/salary-structures/{id}` | backend/routes/api-tenant.php:151 |
| `GET` | `/api/tenant/v1/staff/{staff_uuid}/` | backend/routes/api-tenant.php:154 |
| `POST` | `/api/tenant/v1/staff/{staff_uuid}/` | backend/routes/api-tenant.php:155 |
| `DELETE` | `/api/tenant/v1/staff/{staff_uuid}/` | backend/routes/api-tenant.php:156 |
--------- Tenant staff apis end -------------




--------- Tenant teams extended apis -------------

| `POST` | `/api/tenant/v1/teams/export` | backend/routes/api-tenant.php:90 |
| `GET` | `/api/tenant/v1/teams` | backend/routes/api-tenant.php:91 |
| `POST` | `/api/tenant/v1/teams` | backend/routes/api-tenant.php:92 |
| `GET` | `/api/tenant/v1/teams/{team_uuid}` | backend/routes/api-tenant.php:93 |
| `PUT,PATCH` | `/api/tenant/v1/teams/{team_uuid}` | backend/routes/api-tenant.php:94 |
| `DELETE` | `/api/tenant/v1/teams/bulk` | backend/routes/api-tenant.php:95 |
| `DELETE` | `/api/tenant/v1/teams/{team_uuid}` | backend/routes/api-tenant.php:96 |
| `GET` | `/api/tenant/v1/teams/{team_uuid}/permissions` | backend/routes/api-tenant.php:104 |
| `PUT` | `/api/tenant/v1/teams/{team_uuid}/permissions` | backend/routes/api-tenant.php:105 |


| `GET` | `/api/tenant/v1/teams/{team_uuid}/assignments` | backend/routes/api-tenant.php:108 |
| `POST` | `/api/tenant/v1/teams/{team_uuid}/assignments` | backend/routes/api-tenant.php:109 |
| `DELETE` | `/api/tenant/v1/teams/{team_uuid}/assignments/{assignment_id}` | backend/routes/api-tenant.php:110 |

| `GET` | `/api/tenant/v1/teams/{team_uuid}/members` | backend/routes/api-tenant.php:97 |
| `POST` | `/api/tenant/v1/teams/{team_uuid}/members` | backend/routes/api-tenant.php:101 |
| `PUT,PATCH` | `/api/tenant/v1/teams/{team_uuid}/members/{member_uuid}` | backend/routes/api-tenant.php:102 |
| `DELETE` | `/api/tenant/v1/teams/{team_uuid}/members/{member_uuid}` | backend/routes/api-tenant.php:103 |

| `GET` | `/api/tenant/v1/teams/{team_uuid}/projects` | backend/routes/api-tenant.php:98 |
| `GET` | `/api/tenant/v1/teams/{team_uuid}/tasks` | backend/routes/api-tenant.php:99 |
| `GET` | `/api/tenant/v1/teams/{team_uuid}/activity` | backend/routes/api-tenant.php:100 |
| `GET` | `/api/tenant/v1/teams/{team_uuid}/settings` | backend/routes/api-tenant.php:106 |
| `PUT` | `/api/tenant/v1/teams/{team_uuid}/settings` | backend/routes/api-tenant.php:107 |
--------- Tenant teams extended apis end -------------


--------- Tenant to do lists apis -------------
| `GET` | `/api/tenant/v1/todo-lists/dashboard` | backend/routes/api-tenant.php:357 |
| `GET` | `/api/tenant/v1/todo-lists/kanban` | backend/routes/api-tenant.php:358 |
| `GET` | `/api/tenant/v1/todo-lists/calendar` | backend/routes/api-tenant.php:359 |
| `POST` | `/api/tenant/v1/todo-lists/export` | backend/routes/api-tenant.php:360 |
| `GET` | `/api/tenant/v1/todo-lists` | backend/routes/api-tenant.php:361 |
| `POST` | `/api/tenant/v1/todo-lists` | backend/routes/api-tenant.php:362 |
| `GET` | `/api/tenant/v1/todo-lists/{todo_list_uuid}` | backend/routes/api-tenant.php:363 |
| `PUT,PATCH` | `/api/tenant/v1/todo-lists/{todo_list_uuid}` | backend/routes/api-tenant.php:364 |
| `DELETE` | `/api/tenant/v1/todo-lists/{todo_list_uuid}` | backend/routes/api-tenant.php:365 |
| `GET` | `/api/tenant/v1/todo-lists/{todo_list_uuid}/tasks` | backend/routes/api-tenant.php:366 |
--------- Tenant to do lists apis end -------------


--------- Tenant clients apis -------------
| `POST` | `/api/tenant/v1/clients/import` | backend/routes/api-tenant.php:239 |
| `POST` | `/api/tenant/v1/clients/export` | backend/routes/api-tenant.php:240 |
| `POST` | `/api/tenant/v1/clients/merge` | backend/routes/api-tenant.php:241 |
| `GET` | `/api/tenant/v1/clients` | backend/routes/api-tenant.php:242 |
| `POST` | `/api/tenant/v1/clients` | backend/routes/api-tenant.php:243 |
| `GET` | `/api/tenant/v1/clients/{client_uuid}` | backend/routes/api-tenant.php:244 |
| `PUT,PATCH` | `/api/tenant/v1/clients/{client_uuid}` | backend/routes/api-tenant.php:245 |
| `DELETE` | `/api/tenant/v1/clients/{client_uuid}` | backend/routes/api-tenant.php:246 |
| `POST` | `/api/tenant/v1/clients/{client_uuid}/restore` | backend/routes/api-tenant.php:247 |
| `GET` | `/api/tenant/v1/clients/{client_uuid}/contacts` | backend/routes/api-tenant.php:248 |
| `POST` | `/api/tenant/v1/clients/{client_uuid}/contacts` | backend/routes/api-tenant.php:249 |
| `PUT,PATCH` | `/api/tenant/v1/clients/{client_uuid}/contacts/{contact_uuid}` | backend/routes/api-tenant.php:250 |
| `DELETE` | `/api/tenant/v1/clients/{client_uuid}/contacts/{contact_uuid}` | backend/routes/api-tenant.php:251 |
| `GET` | `/api/tenant/v1/clients/{client_uuid}/addresses` | backend/routes/api-tenant.php:252 |
| `POST` | `/api/tenant/v1/clients/{client_uuid}/addresses` | backend/routes/api-tenant.php:253 |
| `PUT,PATCH` | `/api/tenant/v1/clients/{client_uuid}/addresses/{address_id}` | backend/routes/api-tenant.php:254 |
| `DELETE` | `/api/tenant/v1/clients/{client_uuid}/addresses/{address_id}` | backend/routes/api-tenant.php:255 |
| `GET` | `/api/tenant/v1/clients/{client_uuid}/{resource}` | backend/routes/api-tenant.php:256 |
| `GET` | `/api/tenant/v1/clients/{client_uuid}/activity` | backend/routes/api-tenant.php:257 |
--------- Tenant clients apis end -------------




--------- Tenant vendors apis -------------
| `POST` | `/api/tenant/v1/vendors/import` | backend/routes/api-tenant.php:259 |
| `POST` | `/api/tenant/v1/vendors/export` | backend/routes/api-tenant.php:260 |
| `GET` | `/api/tenant/v1/vendors` | backend/routes/api-tenant.php:261 |
| `POST` | `/api/tenant/v1/vendors` | backend/routes/api-tenant.php:262 |
| `GET` | `/api/tenant/v1/vendors/{vendor_uuid}` | backend/routes/api-tenant.php:263 |
| `PUT,PATCH` | `/api/tenant/v1/vendors/{vendor_uuid}` | backend/routes/api-tenant.php:264 |
| `DELETE` | `/api/tenant/v1/vendors/{vendor_uuid}` | backend/routes/api-tenant.php:265 |
| `GET` | `/api/tenant/v1/vendors/{vendor_uuid}/contacts` | backend/routes/api-tenant.php:266 |
| `POST` | `/api/tenant/v1/vendors/{vendor_uuid}/contacts` | backend/routes/api-tenant.php:267 |
| `PUT,PATCH` | `/api/tenant/v1/vendors/{vendor_uuid}/contacts/{contact_uuid}` | backend/routes/api-tenant.php:268 |
| `DELETE` | `/api/tenant/v1/vendors/{vendor_uuid}/contacts/{contact_uuid}` | backend/routes/api-tenant.php:269 |
| `GET` | `/api/tenant/v1/vendors/{vendor_uuid}/addresses` | backend/routes/api-tenant.php:270 |
| `POST` | `/api/tenant/v1/vendors/{vendor_uuid}/addresses` | backend/routes/api-tenant.php:271 |
| `PUT,PATCH` | `/api/tenant/v1/vendors/{vendor_uuid}/addresses/{address_id}` | backend/routes/api-tenant.php:272 |
| `DELETE` | `/api/tenant/v1/vendors/{vendor_uuid}/addresses/{address_id}` | backend/routes/api-tenant.php:273 |
| `GET` | `/api/tenant/v1/vendors/{vendor_uuid}/bank-accounts` | backend/routes/api-tenant.php:274 |
| `POST` | `/api/tenant/v1/vendors/{vendor_uuid}/bank-accounts` | backend/routes/api-tenant.php:275 |
| `PUT,PATCH` | `/api/tenant/v1/vendors/{vendor_uuid}/bank-accounts/{account_id}` | backend/routes/api-tenant.php:276 |
| `DELETE` | `/api/tenant/v1/vendors/{vendor_uuid}/bank-accounts/{account_id}` | backend/routes/api-tenant.php:277 |
| `GET` | `/api/tenant/v1/vendors/{vendor_uuid}/{resource}` | backend/routes/api-tenant.php:278 |
| `GET` | `/api/tenant/v1/vendors/{vendor_uuid}/activity` | backend/routes/api-tenant.php:279 |
--------- Tenant vendors apis end -------------



--------- Tenant client/vendor renewals apis -------------
| `GET` | `/api/tenant/v1/renewals/dashboard` | backend/routes/api-tenant.php:386 |
| `GET` | `/api/tenant/v1/renewals/calendar` | backend/routes/api-tenant.php:387 |
| `GET` | `/api/tenant/v1/client-renewals` | backend/routes/api-tenant.php:388 |
| `GET` | `/api/tenant/v1/vendor-renewals` | backend/routes/api-tenant.php:389 |
| `POST` | `/api/tenant/v1/renewals/export` | backend/routes/api-tenant.php:390 |
| `GET` | `/api/tenant/v1/renewals` | backend/routes/api-tenant.php:391 |
| `POST` | `/api/tenant/v1/renewals` | backend/routes/api-tenant.php:392 |
| `GET` | `/api/tenant/v1/renewals/{renewal_uuid}` | backend/routes/api-tenant.php:393 |
| `PUT,PATCH` | `/api/tenant/v1/renewals/{renewal_uuid}` | backend/routes/api-tenant.php:394 |
| `DELETE` | `/api/tenant/v1/renewals/{renewal_uuid}` | backend/routes/api-tenant.php:395 |
| `POST` | `/api/tenant/v1/renewals/{renewal_uuid}/renew` | backend/routes/api-tenant.php:396 |
| `POST` | `/api/tenant/v1/renewals/{renewal_uuid}/cancel` | backend/routes/api-tenant.php:397 |
| `GET` | `/api/tenant/v1/renewals/{renewal_uuid}/` | backend/routes/api-tenant.php:399 |
| `POST` | `/api/tenant/v1/renewals/{renewal_uuid}/` | backend/routes/api-tenant.php:400 |
| `PUT,PATCH` | `/api/tenant/v1/renewals/{renewal_uuid}/` | backend/routes/api-tenant.php:401 |
| `DELETE` | `/api/tenant/v1/renewals/{renewal_uuid}/items/{id}` | backend/routes/api-tenant.php:403 |
| `GET` | `/api/tenant/v1/renewals/{renewal_uuid}/history` | backend/routes/api-tenant.php:404 |
| `POST` | `/api/tenant/v1/renewals/{renewal_uuid}/send-reminder` | backend/routes/api-tenant.php:405 |
--------- Tenant client/vendor renewals apis end -------------


--------- Tenant projects apis -------------
| `GET` | `/api/tenant/v1/projects/dashboard` | backend/routes/api-tenant.php:308 |
| `GET` | `/api/tenant/v1/projects/kanban` | backend/routes/api-tenant.php:309 |
| `GET` | `/api/tenant/v1/projects/gantt` | backend/routes/api-tenant.php:310 |
| `GET` | `/api/tenant/v1/projects/calendar` | backend/routes/api-tenant.php:311 |
| `POST` | `/api/tenant/v1/projects/export` | backend/routes/api-tenant.php:312 |
| `GET` | `/api/tenant/v1/projects` | backend/routes/api-tenant.php:313 |
| `POST` | `/api/tenant/v1/projects` | backend/routes/api-tenant.php:314 |
| `GET` | `/api/tenant/v1/projects/{project_uuid}` | backend/routes/api-tenant.php:315 |
| `PUT,PATCH` | `/api/tenant/v1/projects/{project_uuid}` | backend/routes/api-tenant.php:316 |
| `DELETE` | `/api/tenant/v1/projects/{project_uuid}` | backend/routes/api-tenant.php:317 |
| `POST` | `/api/tenant/v1/projects/{project_uuid}/archive` | backend/routes/api-tenant.php:318 |
| `GET` | `/api/tenant/v1/projects/{project_uuid}/` | backend/routes/api-tenant.php:320 |
| `POST` | `/api/tenant/v1/projects/{project_uuid}/` | backend/routes/api-tenant.php:321 |
| `PUT,PATCH` | `/api/tenant/v1/projects/{project_uuid}/` | backend/routes/api-tenant.php:322 |
| `DELETE` | `/api/tenant/v1/projects/{project_uuid}/` | backend/routes/api-tenant.php:323 |
| `POST` | `/api/tenant/v1/projects/{project_uuid}/milestones/{milestone_id}/complete` | backend/routes/api-tenant.php:325 |
| `GET` | `/api/tenant/v1/projects/{project_uuid}/tasks` | backend/routes/api-tenant.php:326 |
| `POST` | `/api/tenant/v1/projects/{project_uuid}/tasks` | backend/routes/api-tenant.php:327 |
--------- Tenant projects apis end -------------



--------- Tenant tasks apis -------------
| `GET` | `/api/tenant/v1/tasks/dashboard` | backend/routes/api-tenant.php:329 |
| `GET` | `/api/tenant/v1/tasks/kanban` | backend/routes/api-tenant.php:330 |
| `GET` | `/api/tenant/v1/tasks/calendar` | backend/routes/api-tenant.php:331 |
| `GET` | `/api/tenant/v1/tasks/my` | backend/routes/api-tenant.php:332 |
| `GET` | `/api/tenant/v1/tasks/team` | backend/routes/api-tenant.php:333 |
| `POST` | `/api/tenant/v1/tasks/bulk/update` | backend/routes/api-tenant.php:334 |
| `POST` | `/api/tenant/v1/tasks/export` | backend/routes/api-tenant.php:335 |
| `GET` | `/api/tenant/v1/tasks` | backend/routes/api-tenant.php:336 |
| `POST` | `/api/tenant/v1/tasks` | backend/routes/api-tenant.php:337 |
| `GET` | `/api/tenant/v1/tasks/{task_uuid}` | backend/routes/api-tenant.php:338 |
| `PUT,PATCH` | `/api/tenant/v1/tasks/{task_uuid}` | backend/routes/api-tenant.php:339 |
| `DELETE` | `/api/tenant/v1/tasks/{task_uuid}` | backend/routes/api-tenant.php:340 |
| `POST` | `/api/tenant/v1/tasks/{task_uuid}/assign` | backend/routes/api-tenant.php:341 |
| `POST` | `/api/tenant/v1/tasks/{task_uuid}/status` | backend/routes/api-tenant.php:342 |
| `POST` | `/api/tenant/v1/tasks/{task_uuid}/complete` | backend/routes/api-tenant.php:343 |
| `POST` | `/api/tenant/v1/tasks/{task_uuid}/clone` | backend/routes/api-tenant.php:344 |
| `GET` | `/api/tenant/v1/tasks/{task_uuid}/` | backend/routes/api-tenant.php:346 |
| `POST` | `/api/tenant/v1/tasks/{task_uuid}/` | backend/routes/api-tenant.php:347 |
| `POST` | `/api/tenant/v1/tasks/{task_uuid}/checklists/{checklist_id}/items` | backend/routes/api-tenant.php:349 |
| `PUT,PATCH` | `/api/tenant/v1/tasks/{task_uuid}/checklist-items/{item_id}` | backend/routes/api-tenant.php:350 |
| `POST` | `/api/tenant/v1/tasks/{task_uuid}/checklist-items/{item_id}/complete` | backend/routes/api-tenant.php:351 |
| `PUT,PATCH` | `/api/tenant/v1/tasks/{task_uuid}/{resource}/{id}` | backend/routes/api-tenant.php:352 |
| `DELETE` | `/api/tenant/v1/tasks/{task_uuid}/{resource}/{id}` | backend/routes/api-tenant.php:353 |
| `DELETE` | `/api/tenant/v1/tasks/{task_uuid}/watchers/{user_uuid}` | backend/routes/api-tenant.php:354 |
--------- Tenant tasks apis end -------------



--------- Tenant issues apis -------------
| `GET` | `/api/tenant/v1/issues/dashboard` | backend/routes/api-tenant.php:368 |
| `GET` | `/api/tenant/v1/issues/kanban` | backend/routes/api-tenant.php:369 |
| `POST` | `/api/tenant/v1/issues/export` | backend/routes/api-tenant.php:370 |
| `GET` | `/api/tenant/v1/issues` | backend/routes/api-tenant.php:371 |
| `POST` | `/api/tenant/v1/issues` | backend/routes/api-tenant.php:372 |
| `GET` | `/api/tenant/v1/issues/{issue_uuid}` | backend/routes/api-tenant.php:373 |
| `PUT,PATCH` | `/api/tenant/v1/issues/{issue_uuid}` | backend/routes/api-tenant.php:374 |
| `DELETE` | `/api/tenant/v1/issues/{issue_uuid}` | backend/routes/api-tenant.php:375 |
| `POST` | `/api/tenant/v1/issues/{issue_uuid}/assign` | backend/routes/api-tenant.php:376 |
| `POST` | `/api/tenant/v1/issues/{issue_uuid}/status` | backend/routes/api-tenant.php:377 |
| `POST` | `/api/tenant/v1/issues/{issue_uuid}/resolve` | backend/routes/api-tenant.php:378 |
| `POST` | `/api/tenant/v1/issues/{issue_uuid}/close` | backend/routes/api-tenant.php:379 |
| `POST` | `/api/tenant/v1/issues/{issue_uuid}/reopen` | backend/routes/api-tenant.php:380 |
| `GET` | `/api/tenant/v1/issues/{issue_uuid}/time-logs` | backend/routes/api-tenant.php:381 |
| `POST` | `/api/tenant/v1/issues/{issue_uuid}/time-logs` | backend/routes/api-tenant.php:382 |
| `POST` | `/api/tenant/v1/issues/{issue_uuid}/create-task` | backend/routes/api-tenant.php:383 |
| `GET` | `/api/tenant/v1/issues/{issue_uuid}/activity` | backend/routes/api-tenant.php:384 |
--------- Tenant issues apis end -------------


--------- Tenant leads apis -------------
| `GET` | `/api/tenant/v1/leads/dashboard` | backend/routes/api-tenant.php:281 |
| `GET` | `/api/tenant/v1/leads/kanban` | backend/routes/api-tenant.php:282 |
| `POST` | `/api/tenant/v1/leads/import` | backend/routes/api-tenant.php:283 |
| `POST` | `/api/tenant/v1/leads/export` | backend/routes/api-tenant.php:284 |
| `POST` | `/api/tenant/v1/leads/merge` | backend/routes/api-tenant.php:285 |
| `GET` | `/api/tenant/v1/leads` | backend/routes/api-tenant.php:286 |
| `POST` | `/api/tenant/v1/leads` | backend/routes/api-tenant.php:287 |
| `GET` | `/api/tenant/v1/leads/{lead_uuid}` | backend/routes/api-tenant.php:288 |
| `PUT,PATCH` | `/api/tenant/v1/leads/{lead_uuid}` | backend/routes/api-tenant.php:289 |
| `DELETE` | `/api/tenant/v1/leads/{lead_uuid}` | backend/routes/api-tenant.php:290 |
| `POST` | `/api/tenant/v1/leads/{lead_uuid}/duplicate` | backend/routes/api-tenant.php:291 |
| `POST` | `/api/tenant/v1/leads/{lead_uuid}/convert` | backend/routes/api-tenant.php:292 |
| `POST` | `/api/tenant/v1/leads/{lead_uuid}/mark-lost` | backend/routes/api-tenant.php:293 |
| `GET` | `/api/tenant/v1/leads/{lead_uuid}/contacts` | backend/routes/api-tenant.php:294 |
| `POST` | `/api/tenant/v1/leads/{lead_uuid}/contacts` | backend/routes/api-tenant.php:295 |
| `PUT,PATCH` | `/api/tenant/v1/leads/{lead_uuid}/contacts/{contact_uuid}` | backend/routes/api-tenant.php:296 |
| `DELETE` | `/api/tenant/v1/leads/{lead_uuid}/contacts/{contact_uuid}` | backend/routes/api-tenant.php:297 |
| `GET` | `/api/tenant/v1/leads/{lead_uuid}/addresses` | backend/routes/api-tenant.php:298 |
| `POST` | `/api/tenant/v1/leads/{lead_uuid}/addresses` | backend/routes/api-tenant.php:299 |
| `PUT,PATCH` | `/api/tenant/v1/leads/{lead_uuid}/addresses/{address_id}` | backend/routes/api-tenant.php:300 |
| `DELETE` | `/api/tenant/v1/leads/{lead_uuid}/addresses/{address_id}` | backend/routes/api-tenant.php:301 |
| `GET` | `/api/tenant/v1/leads/{lead_uuid}/activities` | backend/routes/api-tenant.php:302 |
| `POST` | `/api/tenant/v1/leads/{lead_uuid}/activities` | backend/routes/api-tenant.php:303 |
| `PUT,PATCH` | `/api/tenant/v1/leads/{lead_uuid}/activities/{activity_uuid}` | backend/routes/api-tenant.php:304 |
| `GET` | `/api/tenant/v1/leads/{lead_uuid}/activity` | backend/routes/api-tenant.php:305 |
--------- Tenant leads apis end -------------



--------- Tenant settings apis -------------
| `GET` | `/api/tenant/v1/settings/` | backend/routes/api-tenant.php:471 |
| `PUT,PATCH` | `/api/tenant/v1/settings/` | backend/routes/api-tenant.php:472 |
| `GET` | `/api/tenant/v1/settings/lookups` | backend/routes/api-tenant.php:474 |
| `PUT` | `/api/tenant/v1/settings/lookups/reorder` | backend/routes/api-tenant.php:475 |
| `DELETE` | `/api/tenant/v1/settings/lookups/{lookup_uuid}` | backend/routes/api-tenant.php:476 |
| `GET` | `/api/tenant/v1/settings/notification-templates` | backend/routes/api-tenant.php:477 |
| `POST` | `/api/tenant/v1/settings/notification-templates` | backend/routes/api-tenant.php:478 |
| `PUT,PATCH` | `/api/tenant/v1/settings/notification-templates/{template_uuid}` | backend/routes/api-tenant.php:479 |
| `POST` | `/api/tenant/v1/settings/notification-templates/{template_uuid}/test-send` | backend/routes/api-tenant.php:480 |
| `GET` | `/api/tenant/v1/settings/backups/runs` | backend/routes/api-tenant.php:481 |
| `POST` | `/api/tenant/v1/settings/backups/run` | backend/routes/api-tenant.php:482 |
| `POST` | `/api/tenant/v1/settings/backups/restore` | backend/routes/api-tenant.php:483 |
--------- Tenant settings apis end -------------



--------- Tenant calendars apis -------------
| `GET` | `/api/tenant/v1/calendars` | backend/routes/api-tenant.php:407 |
| `POST` | `/api/tenant/v1/calendars` | backend/routes/api-tenant.php:408 |
| `GET` | `/api/tenant/v1/calendars/{calendar_uuid}` | backend/routes/api-tenant.php:409 |
| `PUT,PATCH` | `/api/tenant/v1/calendars/{calendar_uuid}` | backend/routes/api-tenant.php:410 |
| `DELETE` | `/api/tenant/v1/calendars/{calendar_uuid}` | backend/routes/api-tenant.php:411 |
| `GET` | `/api/tenant/v1/calendar-events` | backend/routes/api-tenant.php:412 |
| `POST` | `/api/tenant/v1/calendar-events` | backend/routes/api-tenant.php:413 |
| `GET` | `/api/tenant/v1/calendar-events/{event_uuid}` | backend/routes/api-tenant.php:414 |
| `PUT,PATCH` | `/api/tenant/v1/calendar-events/{event_uuid}` | backend/routes/api-tenant.php:415 |
| `DELETE` | `/api/tenant/v1/calendar-events/{event_uuid}` | backend/routes/api-tenant.php:416 |
| `POST` | `/api/tenant/v1/calendar-events/{event_uuid}/reschedule` | backend/routes/api-tenant.php:417 |
| `GET` | `/api/tenant/v1/calendar-events/{event_uuid}/` | backend/routes/api-tenant.php:419 |
| `POST` | `/api/tenant/v1/calendar-events/{event_uuid}/` | backend/routes/api-tenant.php:420 |
| `PUT,PATCH` | `/api/tenant/v1/calendar-events/{event_uuid}/attendees/{id}` | backend/routes/api-tenant.php:422 |
| `POST` | `/api/tenant/v1/calendar-events/{event_uuid}/video-meeting` | backend/routes/api-tenant.php:423 |
| `POST` | `/api/tenant/v1/calendar-events/{event_uuid}/room-booking` | backend/routes/api-tenant.php:424 |


| `GET` | `/api/tenant/v1/meeting-rooms` | backend/routes/api-tenant.php:425 |
| `POST` | `/api/tenant/v1/meeting-rooms` | backend/routes/api-tenant.php:426 |
| `PUT,PATCH` | `/api/tenant/v1/meeting-rooms/{room_id}` | backend/routes/api-tenant.php:427 |
--------- Tenant calendars apis end -------------


--------- Tenant integrations apis -------------
| `GET` | `/api/tenant/v1/integrations/providers` | backend/routes/api-tenant.php:484 |
| `GET` | `/api/tenant/v1/integrations` | backend/routes/api-tenant.php:485 |
| `POST` | `/api/tenant/v1/integrations` | backend/routes/api-tenant.php:486 |
| `GET` | `/api/tenant/v1/integrations/{integration_uuid}` | backend/routes/api-tenant.php:487 |
| `PUT,PATCH` | `/api/tenant/v1/integrations/{integration_uuid}` | backend/routes/api-tenant.php:488 |
| `POST` | `/api/tenant/v1/integrations/{integration_uuid}/credentials/rotate` | backend/routes/api-tenant.php:489 |
| `POST` | `/api/tenant/v1/integrations/{integration_uuid}/disconnect` | backend/routes/api-tenant.php:490 |
| `GET` | `/api/tenant/v1/integrations/webhooks` | backend/routes/api-tenant.php:491 |
| `GET` | `/api/tenant/v1/integrations/sync-jobs` | backend/routes/api-tenant.php:492 |
| `POST` | `/api/tenant/v1/integrations/sync-jobs/{job_id}/retry` | backend/routes/api-tenant.php:493 |
| `GET` | `/api/tenant/v1/integrations/{integration_uuid}/field-mappings` | backend/routes/api-tenant.php:494 |
| `PUT` | `/api/tenant/v1/integrations/{integration_uuid}/field-mappings` | backend/routes/api-tenant.php:495 |
| `GET` | `/api/tenant/v1/integrations/{integration_uuid}/rate-limits` | backend/routes/api-tenant.php:496 |
--------- Tenant integrations apis end -------------


--------- Tenant audit apis -------------
| `GET` | `/api/tenant/v1/audit/{type}` | backend/routes/api-tenant.php:497 |
| `GET` | `/api/tenant/v1/audit/activity-logs/{activity_id}/compare` | backend/routes/api-tenant.php:498 |
| `POST` | `/api/tenant/v1/audit/export` | backend/routes/api-tenant.php:499 |
| `GET` | `/api/tenant/v1/business/selectors` | backend/routes/api-tenant.php:500 |

| `GET` | `/api/tenant/v1/activity-logs` | backend/routes/api-tenant.php:513 |
| `GET` | `/api/tenant/v1/activity-logs/{activity_id}/compare` | backend/routes/api-tenant.php:514 |
--------- Tenant audit apis end -------------


--------- Tenant Shared Documents, Files, Metadata, and Custom Fields APIs -------------
| `GET` | `/api/tenant/v1/documents/dashboard` | backend/routes/api-tenant.php:459 |
| `GET` | `/api/tenant/v1/document-folders` | backend/routes/api-tenant.php:460 |
| `POST` | `/api/tenant/v1/document-folders` | backend/routes/api-tenant.php:461 |
| `POST` | `/api/tenant/v1/document-folders/{folder_uuid}/files` | backend/routes/api-tenant.php:462 |

| `GET` | `/api/tenant/v1/files` | backend/routes/api-tenant.php:501 |
| `POST` | `/api/tenant/v1/files` | backend/routes/api-tenant.php:502 |
| `GET` | `/api/tenant/v1/files/{file_uuid}` | backend/routes/api-tenant.php:503 |
| `GET` | `/api/tenant/v1/files/{file_uuid}/download` | backend/routes/api-tenant.php:504 |
| `DELETE` | `/api/tenant/v1/files/{file_uuid}` | backend/routes/api-tenant.php:505 |

| `GET` | `/api/tenant/v1/attachments` | backend/routes/api-tenant.php:506 |
| `POST` | `/api/tenant/v1/attachments` | backend/routes/api-tenant.php:507 |
| `DELETE` | `/api/tenant/v1/attachments/{attachment_id}` | backend/routes/api-tenant.php:508 |

| `GET` | `/api/tenant/v1/notes` | backend/routes/api-tenant.php:509 |
| `POST` | `/api/tenant/v1/notes` | backend/routes/api-tenant.php:510 |
| `PUT,PATCH` | `/api/tenant/v1/notes/{note_uuid}` | backend/routes/api-tenant.php:511 |
| `DELETE` | `/api/tenant/v1/notes/{note_uuid}` | backend/routes/api-tenant.php:512 |

| `GET` | `/api/tenant/v1/tags` | backend/routes/api-tenant.php:515 |
| `GET` | `/api/tenant/v1/lookups` | backend/routes/api-tenant.php:516 |
| `POST` | `/api/tenant/v1/tags` | backend/routes/api-tenant.php:517 |
| `PUT,PATCH` | `/api/tenant/v1/tags/{tag_uuid}` | backend/routes/api-tenant.php:518 |
| `DELETE` | `/api/tenant/v1/tags/{tag_uuid}` | backend/routes/api-tenant.php:519 |
| `POST` | `/api/tenant/v1/taggables` | backend/routes/api-tenant.php:520 |
| `DELETE` | `/api/tenant/v1/taggables` | backend/routes/api-tenant.php:521 |
| `GET` | `/api/tenant/v1/custom-fields` | backend/routes/api-tenant.php:522 |
| `POST` | `/api/tenant/v1/custom-fields` | backend/routes/api-tenant.php:523 |
| `PUT,PATCH` | `/api/tenant/v1/custom-fields/{field_uuid}` | backend/routes/api-tenant.php:524 |
| `DELETE` | `/api/tenant/v1/custom-fields/{field_uuid}` | backend/routes/api-tenant.php:525 |
| `GET` | `/api/tenant/v1/custom-field-values` | backend/routes/api-tenant.php:526 |
| `PUT` | `/api/tenant/v1/custom-field-values` | backend/routes/api-tenant.php:527 |
------- Tenant Shared Documents, Files, Metadata, and Custom Fields APIs end -----------

------- Tenant reminders APIs -----------
| `GET` | `/api/tenant/v1/reminders` | backend/routes/api-tenant.php:528 |
| `POST` | `/api/tenant/v1/reminders` | backend/routes/api-tenant.php:529 |
| `PUT,PATCH` | `/api/tenant/v1/reminders/{reminder_uuid}` | backend/routes/api-tenant.php:530 |
| `DELETE` | `/api/tenant/v1/reminders/{reminder_uuid}` | backend/routes/api-tenant.php:531 |
------- Tenant reminders APIs end -----------

------- Tenant notifications APIs -----------
| `GET` | `/api/tenant/v1/notifications` | backend/routes/api-tenant.php:533 |
| `GET` | `/api/tenant/v1/notifications/unread-count` | backend/routes/api-tenant.php:534 |
| `GET` | `/api/tenant/v1/notifications/{notification_id}` | backend/routes/api-tenant.php:535 |
| `POST` | `/api/tenant/v1/notifications/bulk/read` | backend/routes/api-tenant.php:536 |
| `POST` | `/api/tenant/v1/notifications/{notification_id}/read` | backend/routes/api-tenant.php:537 |
| `POST` | `/api/tenant/v1/notifications/{notification_id}/unread` | backend/routes/api-tenant.php:538 |
| `DELETE` | `/api/tenant/v1/notifications/{notification_id}` | backend/routes/api-tenant.php:539 |
------- Tenant notifications APIs end -----------

------- Tenant communication APIs -----------
| `GET` | `/api/tenant/v1/communication/logs` | backend/routes/api-tenant.php:540 |
| `POST` | `/api/tenant/v1/communication/email` | backend/routes/api-tenant.php:541 |
| `POST` | `/api/tenant/v1/communication/sms` | backend/routes/api-tenant.php:542 |
| `POST` | `/api/tenant/v1/communication/whatsapp` | backend/routes/api-tenant.php:543 |
| `POST` | `/api/tenant/v1/communication/push` | backend/routes/api-tenant.php:544 |
| `POST` | `/api/tenant/v1/communication/logs/{log_uuid}/retry` | backend/routes/api-tenant.php:545 |
------- Tenant communication APIs end -----------

------- Tenant help APIs -----------
| `GET` | `/api/tenant/v1/help/articles` | backend/routes/api-tenant.php:546 |
| `GET` | `/api/tenant/v1/help/articles/{slug}` | backend/routes/api-tenant.php:547 |
| `GET` | `/api/tenant/v1/help/faqs` | backend/routes/api-tenant.php:548 |
| `GET` | `/api/tenant/v1/help/release-notes` | backend/routes/api-tenant.php:549 |
| `POST` | `/api/tenant/v1/help/contact-support` | backend/routes/api-tenant.php:550 |
| `GET` | `/api/tenant/v1/help/system-status` | backend/routes/api-tenant.php:551 |
------- Tenant help APIs end -----------

------- Tenant api-tokens APIs -----------
| `GET` | `/api/tenant/v1/profile/api-tokens` | backend/routes/api-tenant.php:552 |
| `POST` | `/api/tenant/v1/profile/api-tokens` | backend/routes/api-tenant.php:553 |
| `POST` | `/api/tenant/v1/profile/api-tokens/{token_uuid}/rotate` | backend/routes/api-tenant.php:554 |
| `POST` | `/api/tenant/v1/profile/api-tokens/{token_uuid}/revoke` | backend/routes/api-tenant.php:555 |
------- Tenant api-tokens APIs end -----------



------- Tenant finance & invoices APIs -----------
| `GET` | `/api/tenant/v1/finance/dashboard` | backend/routes/api-tenant.php:429 |
| `POST` | `/api/tenant/v1/invoices/export` | backend/routes/api-tenant.php:430 |
| `GET` | `/api/tenant/v1/invoices` | backend/routes/api-tenant.php:431 |
| `POST` | `/api/tenant/v1/invoices` | backend/routes/api-tenant.php:432 |
| `GET` | `/api/tenant/v1/invoices/{invoice_uuid}` | backend/routes/api-tenant.php:433 |
| `PUT,PATCH` | `/api/tenant/v1/invoices/{invoice_uuid}` | backend/routes/api-tenant.php:434 |
| `POST` | `/api/tenant/v1/invoices/{invoice_uuid}/items` | backend/routes/api-tenant.php:435 |
| `PUT,PATCH` | `/api/tenant/v1/invoices/{invoice_uuid}/items/{item_id}` | backend/routes/api-tenant.php:436 |
| `DELETE` | `/api/tenant/v1/invoices/{invoice_uuid}/items/{item_id}` | backend/routes/api-tenant.php:437 |
| `POST` | `/api/tenant/v1/invoices/{invoice_uuid}/send` | backend/routes/api-tenant.php:438 |
| `POST` | `/api/tenant/v1/invoices/{invoice_uuid}/cancel` | backend/routes/api-tenant.php:439 |
| `GET` | `/api/tenant/v1/invoices/{invoice_uuid}/pdf` | backend/routes/api-tenant.php:440 |
------- Tenant finance & invoices APIs end -----------

------- Tenant payments APIs -----------
| `POST` | `/api/tenant/v1/payments/export` | backend/routes/api-tenant.php:441 |
| `GET` | `/api/tenant/v1/payments` | backend/routes/api-tenant.php:442 |
| `POST` | `/api/tenant/v1/payments` | backend/routes/api-tenant.php:443 |
| `GET` | `/api/tenant/v1/payments/{payment_uuid}` | backend/routes/api-tenant.php:444 |
| `POST` | `/api/tenant/v1/payments/{payment_uuid}/void` | backend/routes/api-tenant.php:445 |
| `GET` | `/api/tenant/v1/payments/{payment_uuid}/receipt` | backend/routes/api-tenant.php:446 |
------- Tenant payments APIs end -----------

------- Tenant expenses APIs -----------
| `POST` | `/api/tenant/v1/expenses/export` | backend/routes/api-tenant.php:447 |
| `GET` | `/api/tenant/v1/expenses` | backend/routes/api-tenant.php:448 |
| `POST` | `/api/tenant/v1/expenses` | backend/routes/api-tenant.php:449 |
| `GET` | `/api/tenant/v1/expenses/{expense_uuid}` | backend/routes/api-tenant.php:450 |
| `PUT,PATCH` | `/api/tenant/v1/expenses/{expense_uuid}` | backend/routes/api-tenant.php:451 |
| `POST` | `/api/tenant/v1/expenses/{expense_uuid}/approve` | backend/routes/api-tenant.php:452 |
| `POST` | `/api/tenant/v1/expenses/{expense_uuid}/reject` | backend/routes/api-tenant.php:453 |
------- Tenant expenses APIs end -----------


================================= Tickets ===============================
| `GET` | `/api/platform/v1/tickets` | backend/routes/api-platform.php:284 |
| `POST` | `/api/platform/v1/tickets` | backend/routes/api-platform.php:285 |
| `POST` | `/api/platform/v1/tickets/export` | backend/routes/api-platform.php:286 |
| `GET` | `/api/platform/v1/tickets/{ticket_uuid}` | backend/routes/api-platform.php:287 |
| `PUT,PATCH` | `/api/platform/v1/tickets/{ticket_uuid}` | backend/routes/api-platform.php:288 |
| `POST` | `/api/platform/v1/tickets/{ticket_uuid}/assign` | backend/routes/api-platform.php:289 |
| `POST` | `/api/platform/v1/tickets/{ticket_uuid}/comments` | backend/routes/api-platform.php:290 |
| `POST` | `/api/platform/v1/tickets/{ticket_uuid}/attachments` | backend/routes/api-platform.php:291 |
| `POST` | `/api/platform/v1/tickets/{ticket_uuid}/close` | backend/routes/api-platform.php:292 |
| `POST` | `/api/platform/v1/tickets/{ticket_uuid}/reopen` | backend/routes/api-platform.php:293 |
================================= Tickets ===============================


================================= Onboarding ===============================
| `GET` | `/api/platform/v1/onboarding/tenants` | backend/routes/api-platform.php:376 |
| `GET` | `/api/platform/v1/onboarding/tenants/{tenant_uuid}` | backend/routes/api-platform.php:377 |
| `PUT` | `/api/platform/v1/onboarding/tenants/{tenant_uuid}/steps/{step_code}` | backend/routes/api-platform.php:378 |
================================= Onboarding ===============================


================================= Trials ===============================
| `GET` | `/api/platform/v1/trials` | backend/routes/api-platform.php:379 |
| `POST` | `/api/platform/v1/trials/{tenant_uuid}/extend` | backend/routes/api-platform.php:380 |
| `POST` | `/api/platform/v1/trials/{tenant_uuid}/convert` | backend/routes/api-platform.php:381 |
================================= Trials ===============================

================================= Integrations Part 2 ===============================

| `GET` | `/api/platform/v1/tenant-integrations` | backend/routes/api-platform.php:336 |
| `POST` | `/api/platform/v1/tenant-integrations` | backend/routes/api-platform.php:337 |
| `GET` | `/api/platform/v1/tenant-integrations/{integration_uuid}` | backend/routes/api-platform.php:338 |
| `PUT,PATCH` | `/api/platform/v1/tenant-integrations/{integration_uuid}` | backend/routes/api-platform.php:339 |
| `POST` | `/api/platform/v1/tenant-integrations/{integration_uuid}/credentials` | backend/routes/api-platform.php:340 |
| `POST` | `/api/platform/v1/tenant-integrations/{integration_uuid}/test` | backend/routes/api-platform.php:341 |
| `POST` | `/api/platform/v1/tenant-integrations/{integration_uuid}/disconnect` | backend/routes/api-platform.php:342 |
| `GET` | `/api/platform/v1/tenant-integrations/{integration_uuid}/mappings` | backend/routes/api-platform.php:343 |
| `PUT` | `/api/platform/v1/tenant-integrations/{integration_uuid}/mappings` | backend/routes/api-platform.php:344 |
| `GET` | `/api/platform/v1/tenant-integrations/{integration_uuid}/rate-limits` | backend/routes/api-platform.php:345 |


| `GET` | `/api/platform/v1/webhooks` | backend/routes/api-platform.php:346 |
| `POST` | `/api/platform/v1/webhooks` | backend/routes/api-platform.php:347 |
| `GET` | `/api/platform/v1/webhooks/{webhook_id}` | backend/routes/api-platform.php:348 |
| `PUT,PATCH` | `/api/platform/v1/webhooks/{webhook_id}` | backend/routes/api-platform.php:349 |
| `DELETE` | `/api/platform/v1/webhooks/{webhook_id}` | backend/routes/api-platform.php:350 |
| `GET` | `/api/platform/v1/webhooks/{webhook_id}/logs` | backend/routes/api-platform.php:351 |
| `POST` | `/api/platform/v1/webhook-logs/{log_id}/retry` | backend/routes/api-platform.php:352 |


| `GET` | `/api/platform/v1/sync-jobs` | backend/routes/api-platform.php:353 |
| `POST` | `/api/platform/v1/sync-jobs/{job_id}/retry` | backend/routes/api-platform.php:354 |
================================= Integrations Part 2 ===============================

================================= Remote Login ===============================
| `GET` | `/api/platform/v1/remote-login-sessions` | backend/routes/api-platform.php:304 |
| `GET` | `/api/platform/v1/remote-login-sessions/{session_uuid}` | backend/routes/api-platform.php:305 |
| `POST` | `/api/platform/v1/remote-login-sessions/{session_uuid}/end` | backend/routes/api-platform.php:306 |
================================= Remote Login ===============================

================================= Reports ===============================
| `GET` | `/api/platform/v1/reports/export-jobs` | backend/routes/api-platform.php:309 |
| `GET` | `/api/platform/v1/reports/export-jobs/{job_uuid}` | backend/routes/api-platform.php:310 |
| `GET` | `/api/platform/v1/reports/{report_code}` | backend/routes/api-platform.php:311 |
| `POST` | `/api/platform/v1/reports/{report_code}/export` | backend/routes/api-platform.php:312 |
================================= Reports ===============================


================================= Attendance ===============================
| `GET` | `/api/tenant/v1/attendance/dashboard` | backend/routes/api-tenant.php:160 |
| `GET` | `/api/tenant/v1/attendance/daily` | backend/routes/api-tenant.php:161 |
| `GET` | `/api/tenant/v1/attendance/monthly` | backend/routes/api-tenant.php:162 |
| `POST` | `/api/tenant/v1/attendance/check-in` | backend/routes/api-tenant.php:163 |
| `POST` | `/api/tenant/v1/attendance/check-out` | backend/routes/api-tenant.php:164 |
| `POST` | `/api/tenant/v1/attendance/records` | backend/routes/api-tenant.php:165 |
| `GET` | `/api/tenant/v1/attendance/records/{record_id}` | backend/routes/api-tenant.php:166 |
| `PUT,PATCH` | `/api/tenant/v1/attendance/records/{record_id}` | backend/routes/api-tenant.php:167 |
| `POST` | `/api/tenant/v1/attendance/import` | backend/routes/api-tenant.php:168 |
| `POST` | `/api/tenant/v1/attendance/export` | backend/routes/api-tenant.php:169 |
| `GET` | `/api/tenant/v1/attendance/requests` | backend/routes/api-tenant.php:170 |
| `POST` | `/api/tenant/v1/attendance/requests` | backend/routes/api-tenant.php:171 |
| `GET` | `/api/tenant/v1/attendance/requests/{request_uuid}` | backend/routes/api-tenant.php:172 |
| `POST` | `/api/tenant/v1/attendance/requests/{request_uuid}/approve` | backend/routes/api-tenant.php:173 |
| `POST` | `/api/tenant/v1/attendance/requests/{request_uuid}/reject` | backend/routes/api-tenant.php:174 |
================================= Attendance end ===============================



================================= leave ===============================
| `GET` | `/api/tenant/v1/leave/dashboard` | backend/routes/api-tenant.php:176 |
| `GET` | `/api/tenant/v1/leave/requests` | backend/routes/api-tenant.php:177 |
| `POST` | `/api/tenant/v1/leave/requests` | backend/routes/api-tenant.php:178 |
| `GET` | `/api/tenant/v1/leave/requests/{request_id}` | backend/routes/api-tenant.php:179 |
| `POST` | `/api/tenant/v1/leave/requests/{request_id}/approve` | backend/routes/api-tenant.php:180 |
| `POST` | `/api/tenant/v1/leave/requests/{request_id}/reject` | backend/routes/api-tenant.php:181 |
| `POST` | `/api/tenant/v1/leave/requests/{request_id}/cancel` | backend/routes/api-tenant.php:182 |
| `GET` | `/api/tenant/v1/leave/balances` | backend/routes/api-tenant.php:183 |
| `POST` | `/api/tenant/v1/leave/balances/adjust` | backend/routes/api-tenant.php:184 |
| `GET` | `/api/tenant/v1/leave/calendar` | backend/routes/api-tenant.php:185 |
| `GET` | `/api/tenant/v1/leave/types` | backend/routes/api-tenant.php:186 |
| `POST` | `/api/tenant/v1/leave/types` | backend/routes/api-tenant.php:187 |
================================= leave end ===============================


================================= payroll ===============================
| `GET` | `/api/tenant/v1/payroll/dashboard` | backend/routes/api-tenant.php:189 |
| `GET` | `/api/tenant/v1/payroll/cycles` | backend/routes/api-tenant.php:190 |
| `POST` | `/api/tenant/v1/payroll/cycles` | backend/routes/api-tenant.php:191 |
| `GET` | `/api/tenant/v1/payroll/cycles/{cycle_uuid}` | backend/routes/api-tenant.php:192 |
| `PUT,PATCH` | `/api/tenant/v1/payroll/cycles/{cycle_uuid}` | backend/routes/api-tenant.php:193 |
| `POST` | `/api/tenant/v1/payroll/cycles/{cycle_uuid}/generate-preview` | backend/routes/api-tenant.php:194 |
| `POST` | `/api/tenant/v1/payroll/cycles/{cycle_uuid}/generate` | backend/routes/api-tenant.php:195 |
| `POST` | `/api/tenant/v1/payroll/cycles/{cycle_uuid}/` | backend/routes/api-tenant.php:197 |
| `GET` | `/api/tenant/v1/payroll/payrolls` | backend/routes/api-tenant.php:199 |
| `GET` | `/api/tenant/v1/payroll/payrolls/{payroll_uuid}` | backend/routes/api-tenant.php:200 |
| `PUT,PATCH` | `/api/tenant/v1/payroll/payrolls/{payroll_uuid}` | backend/routes/api-tenant.php:201 |
| `GET` | `/api/tenant/v1/payroll/payrolls/{payroll_uuid}/items` | backend/routes/api-tenant.php:202 |
| `GET` | `/api/tenant/v1/payroll/payslips` | backend/routes/api-tenant.php:203 |
| `POST` | `/api/tenant/v1/payroll/payslips/generate` | backend/routes/api-tenant.php:204 |
| `POST` | `/api/tenant/v1/payroll/payslips/email` | backend/routes/api-tenant.php:205 |
| `GET` | `/api/tenant/v1/payroll/payslips/{payslip_id}/download` | backend/routes/api-tenant.php:206 |
| `GET,POST` | `/api/tenant/v1/payroll/component-types` | backend/routes/api-tenant.php:207 |
| `GET,POST` | `/api/tenant/v1/payroll/components` | backend/routes/api-tenant.php:208 |
| `PUT,PATCH` | `/api/tenant/v1/payroll/components/{component_id}` | backend/routes/api-tenant.php:209 |
| `GET,POST` | `/api/tenant/v1/payroll/component-assignments` | backend/routes/api-tenant.php:210 |
| `GET,POST` | `/api/tenant/v1/payroll/loans` | backend/routes/api-tenant.php:211 |
| `PUT,PATCH` | `/api/tenant/v1/payroll/loans/{loan_id}` | backend/routes/api-tenant.php:212 |
| `GET,POST` | `/api/tenant/v1/payroll/reimbursements` | backend/routes/api-tenant.php:213 |
| `POST` | `/api/tenant/v1/payroll/reimbursements/{reimbursement_id}/approve` | backend/routes/api-tenant.php:214 |
| `GET,POST` | `/api/tenant/v1/payroll/bank-transfers` | backend/routes/api-tenant.php:215 |
| `POST` | `/api/tenant/v1/payroll/bank-transfers/{transfer_id}/mark-paid` | backend/routes/api-tenant.php:216 |
| `GET,POST` | `/api/tenant/v1/payroll/tax-slabs` | backend/routes/api-tenant.php:217 |
| `GET,PUT` | `/api/tenant/v1/payroll/pf-settings` | backend/routes/api-tenant.php:218 |
| `GET,PUT` | `/api/tenant/v1/payroll/esi-settings` | backend/routes/api-tenant.php:219 |
| `POST` | `/api/tenant/v1/payroll/export` | backend/routes/api-tenant.php:220 |
================================= payroll end ===============================

================================= holidays ===============================
| `GET` | `/api/tenant/v1/holidays` | backend/routes/api-tenant.php:222 |
| `POST` | `/api/tenant/v1/holidays` | backend/routes/api-tenant.php:223 |
| `GET` | `/api/tenant/v1/holidays/{holiday_uuid}` | backend/routes/api-tenant.php:224 |
| `PUT,PATCH` | `/api/tenant/v1/holidays/{holiday_uuid}` | backend/routes/api-tenant.php:225 |
| `DELETE` | `/api/tenant/v1/holidays/{holiday_uuid}` | backend/routes/api-tenant.php:226 |
| `POST` | `/api/tenant/v1/holidays/{holiday_uuid}/duplicate-next-year` | backend/routes/api-tenant.php:227 |
| `POST` | `/api/tenant/v1/holidays/import` | backend/routes/api-tenant.php:228 |
| `POST` | `/api/tenant/v1/holidays/export` | backend/routes/api-tenant.php:229 |
================================= holidays end ===============================



================================= holidays calendars ===============================
| `GET,POST` | `/api/tenant/v1/holiday-calendars` | backend/routes/api-tenant.php:230 |
| `GET` | `/api/tenant/v1/holiday-calendars/{calendar_uuid}` | backend/routes/api-tenant.php:231 |
| `PUT,PATCH` | `/api/tenant/v1/holiday-calendars/{calendar_uuid}` | backend/routes/api-tenant.php:232 |
| `DELETE` | `/api/tenant/v1/holiday-calendars/{calendar_uuid}` | backend/routes/api-tenant.php:233 |
| `GET,POST` | `/api/tenant/v1/holiday-groups` | backend/routes/api-tenant.php:234 |
| `PUT,PATCH` | `/api/tenant/v1/holiday-groups/{group_uuid}` | backend/routes/api-tenant.php:235 |
| `GET,POST` | `/api/tenant/v1/holiday-groups/{group_uuid}/members` | backend/routes/api-tenant.php:236 |
| `DELETE` | `/api/tenant/v1/holiday-groups/{group_uuid}/members/{staff_uuid}` | backend/routes/api-tenant.php:237 |
================================= holidays calendars end ===============================


================================= bank accounts ===============================
| `GET` | `/api/tenant/v1/bank-accounts` | backend/routes/api-tenant.php:454 |
| `POST` | `/api/tenant/v1/bank-accounts` | backend/routes/api-tenant.php:455 |
| `PUT,PATCH` | `/api/tenant/v1/bank-accounts/{account_id}` | backend/routes/api-tenant.php:456 |
| `DELETE` | `/api/tenant/v1/bank-accounts/{account_id}` | backend/routes/api-tenant.php:457 |
| `POST` | `/api/tenant/v1/bank-accounts/{account_id}/set-primary` | backend/routes/api-tenant.php:458 |
================================= bank accounts end ===============================


================================= Tenant Dashboard ===============================
| `GET` | `/api/tenant/v1/navigation/sidebar` | backend/routes/api-tenant.php:53 |
| `GET` | `/api/tenant/v1/dashboard/summary` | backend/routes/api-tenant.php:54 |
| `GET` | `/api/tenant/v1/dashboard/charts/{chart}` | backend/routes/api-tenant.php:55 |
| `GET` | `/api/tenant/v1/dashboard/recent-activities` | backend/routes/api-tenant.php:56 |
| `GET` | `/api/tenant/v1/dashboard/widgets` | backend/routes/api-tenant.php:57 |
| `PUT` | `/api/tenant/v1/dashboard/widgets` | backend/routes/api-tenant.php:58 |
| `POST` | `/api/tenant/v1/dashboard/export` | backend/routes/api-tenant.php:59 |
| `GET` | `/api/tenant/v1/dashboard/{widget}` | backend/routes/api-tenant.php:60 |
================================= Tenant Dashboard end ===============================

================================= Tenant reports ===============================
| `GET` | `/api/tenant/v1/reports/dashboard` | backend/routes/api-tenant.php:464 |
| `GET` | `/api/tenant/v1/reports/custom` | backend/routes/api-tenant.php:465 |
| `POST` | `/api/tenant/v1/reports/custom` | backend/routes/api-tenant.php:466 |
| `POST` | `/api/tenant/v1/reports/custom/{report_uuid}/run` | backend/routes/api-tenant.php:467 |
| `POST` | `/api/tenant/v1/reports/{report_code}/export` | backend/routes/api-tenant.php:468 |
| `GET` | `/api/tenant/v1/reports/{report_code}` | backend/routes/api-tenant.php:469 |
================================= Tenant reports end ===============================






















































































































## Scope summary

| Scope | Base path | Route file |
|---|---|---|
| Shared | `/api` | `backend/routes/api.php` |
| Common | `/api/common/v1` | `backend/routes/api.php` |
| Authentication | `/api/auth/v1` | `backend/routes/api.php` |
| Platform | `/api/platform/v1` | `backend/routes/api-platform.php` |
| Tenant | `/api/tenant/v1` | `backend/routes/api-tenant.php` |

## Maintenance

The Laravel route declarations are authoritative. Regenerate this file whenever any of the three route files changes.













=========================== Removed APIs ======================

| `GET` | `/api/platform/v1/me` | backend/routes/api-platform.php:37 |
| `POST` | `/api/platform/v1/logout` | backend/routes/api-platform.php:38 |
| `GET` | `/api/files/signed-download/{file_uuid}` | backend/routes/api.php:10 |

| `POST` | `/api/platform/v1/forgot-password` | backend/routes/api-platform.php:30 |
| `POST` | `/api/platform/v1/reset-password` | backend/routes/api-platform.php:31 |



| `GET` | `/api/platform/v1/files` | backend/routes/api-platform.php:268 |
| `POST` | `/api/platform/v1/files` | backend/routes/api-platform.php:269 |
| `GET` | `/api/platform/v1/files/{file_uuid}` | backend/routes/api-platform.php:270 |
| `GET` | `/api/platform/v1/files/{file_uuid}/download` | backend/routes/api-platform.php:271 |
| `DELETE` | `/api/platform/v1/files/{file_uuid}` | backend/routes/api-platform.php:272 |


| `GET` | `/api/platform/v1/attachments` | backend/routes/api-platform.php:273 |
| `POST` | `/api/platform/v1/attachments` | backend/routes/api-platform.php:274 |
| `DELETE` | `/api/platform/v1/attachments/{attachment_id}` | backend/routes/api-platform.php:275 |


| `GET` | `/api/platform/v1/notes` | backend/routes/api-platform.php:276 |
| `POST` | `/api/platform/v1/notes` | backend/routes/api-platform.php:277 |
| `PUT,PATCH` | `/api/platform/v1/notes/{note_uuid}` | backend/routes/api-platform.php:278 |
| `DELETE` | `/api/platform/v1/notes/{note_uuid}` | backend/routes/api-platform.php:279 |



| `GET` | `/api/platform/v1/activity-logs` | backend/routes/api-platform.php:280 |
| `GET` | `/api/platform/v1/activity-logs/{activity_id}/compare` | backend/routes/api-platform.php:281 |

=========================== Removed APIs End ======================

