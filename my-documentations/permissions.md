# Platform and Tenant Permissions

Source: `backend/database/seeders/PlatformPermissionMapSeeder.php` and `backend/database/seeders/TenantPermissionMapSeeder.php`. Both are called by `DatabaseSeeder.php` before the role seeders.

## Platform permissions (76)

| Module | Permissions |
| --- | --- |
| dashboard | `dashboard.view` |
| platform_user | `platform_user.view`, `platform_user.create`, `platform_user.edit`, `platform_user.delete`, `platform_user.suspend` |
| platform_role | `platform_role.view`, `platform_role.create`, `platform_role.edit`, `platform_role.delete` |
| platform_permission | `platform_permission.view`, `platform_permission.create`, `platform_permission.edit`, `platform_permission.delete` |
| platform_team | `platform_team.view`, `platform_team.create`, `platform_team.edit`, `platform_team.delete`, `platform_team.assign` |
| tenant | `tenant.view`, `tenant.create`, `tenant.edit`, `tenant.suspend`, `tenant.activate`, `tenant.delete`, `tenant.impersonate` |
| subscription | `subscription.view`, `subscription.create`, `subscription.edit`, `subscription.upgrade`, `subscription.downgrade`, `subscription.renew`, `subscription.cancel` |
| plan | `plan.view`, `plan.create`, `plan.edit`, `plan.delete` |
| feature | `feature.view`, `feature.create`, `feature.edit`, `feature.delete` |
| billing | `billing.invoice.view`, `billing.invoice.create`, `billing.invoice.edit`, `billing.invoice.send`, `billing.invoice.cancel`, `billing.payment.view`, `billing.payment.create`, `billing.payment.refund` |
| coupon | `coupon.view`, `coupon.create`, `coupon.edit`, `coupon.delete` |
| module | `module.view`, `module.edit` |
| support | `support.ticket.view`, `support.ticket.reply`, `support.ticket.assign`, `support.ticket.close`, `support.knowledge_base.view`, `support.knowledge_base.create`, `support.knowledge_base.edit`, `support.knowledge_base.publish` |
| monitoring | `monitoring.view`, `monitoring.manage` |
| integration | `integration.view`, `integration.create`, `integration.edit`, `integration.delete`, `integration.test` |
| setting | `setting.view`, `setting.edit` |
| audit_log | `audit_log.view`, `audit_log.export` |
| report | `report.view`, `report.export` |

## Tenant permissions (125)

| Module | Permissions |
| --- | --- |
| dashboard | `dashboard.view`, `dashboard.customize` |
| notification | `notification.view`, `notification.manage` |
| activity_log | `activity_log.view`, `activity_log.export` |
| role | `role.view`, `role.create`, `role.edit`, `role.delete`, `role.assign_permissions` |
| permission | `permission.view` |
| team | `team.view`, `team.create`, `team.edit`, `team.delete`, `team.assign` |
| staff | `staff.view`, `staff.create`, `staff.edit`, `staff.delete`, `staff.import`, `staff.export`, `staff.manage_salary`, `staff.manage_bank` |
| client | `client.view`, `client.create`, `client.edit`, `client.delete`, `client.import`, `client.export`, `client.merge` |
| vendor | `vendor.view`, `vendor.create`, `vendor.edit`, `vendor.delete`, `vendor.import`, `vendor.export` |
| lead | `lead.view`, `lead.create`, `lead.edit`, `lead.delete`, `lead.import`, `lead.export`, `lead.convert` |
| renewal | `renewal.view`, `renewal.create`, `renewal.edit`, `renewal.delete`, `renewal.renew` |
| project | `project.view`, `project.create`, `project.edit`, `project.delete`, `project.archive` |
| task | `task.view`, `task.create`, `task.edit`, `task.delete`, `task.assign`, `task.log_time` |
| todo | `todo.view`, `todo.create`, `todo.edit`, `todo.delete`, `todo.share` |
| issue | `issue.view`, `issue.create`, `issue.edit`, `issue.delete`, `issue.assign`, `issue.close` |
| calendar | `calendar.view`, `calendar.create`, `calendar.edit`, `calendar.delete`, `calendar.manage_team` |
| attendance | `attendance.view`, `attendance.create`, `attendance.edit`, `attendance.approve`, `attendance.export` |
| leave | `leave.view`, `leave.apply`, `leave.approve`, `leave.manage_balance` |
| payroll | `payroll.view`, `payroll.generate`, `payroll.approve`, `payroll.manage_settings`, `payroll.export` |
| holiday | `holiday.view`, `holiday.create`, `holiday.edit`, `holiday.delete` |
| finance | `finance.invoice.view`, `finance.invoice.create`, `finance.invoice.edit`, `finance.invoice.send`, `finance.invoice.cancel`, `finance.payment.view`, `finance.payment.create`, `finance.payment.edit`, `finance.payment.export`, `finance.expense.view`, `finance.expense.create`, `finance.expense.edit`, `finance.expense.approve`, `finance.bank_account.view`, `finance.bank_account.create`, `finance.bank_account.edit`, `finance.bank_account.delete` |
| document | `document.view`, `document.upload`, `document.edit`, `document.delete`, `document.share` |
| report | `report.view`, `report.export`, `report.customize` |
| setting | `setting.view`, `setting.edit` |
| profile | `profile.view`, `profile.edit`, `profile.security` |

## Seeder reconciliation

The seeder arrays are internally complete: 76 platform permissions and 125 tenant permissions. Route middleware also references these names that are not currently seeded:

| Guard | Missing permission | Route usage |
| --- | --- | --- |
| platform | `document.view`, `document.upload`, `document.delete` | Shared files, attachments, and notes in `backend/routes/api-platform.php` |
| tenant | `audit_log.view` | Audit and activity-log routes in `backend/routes/api-tenant.php` |
| tenant | `report.edit` | Custom report creation in `backend/routes/api-tenant.php` |

Add these to the corresponding permission map seeder, or change the routes to use existing names. Note that tenant seeds `activity_log.*`, while platform seeds `audit_log.*`; this naming mismatch should be resolved deliberately.
