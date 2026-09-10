# Technofra CRM – Project Documentation

## 1. Overview

Technofra CRM is a Laravel-based CRM and internal operations platform. It combines a browser-based Blade application with a Sanctum-protected REST API. The system supports customer relationship management, lead capture and nurturing, service renewals, vendor management, project delivery, task tracking, client support, scheduling, notifications, and administrative configuration.

The application serves four primary user groups:

- **Administrators:** global configuration, users, roles, permissions, reporting, and all business modules.
- **Staff:** assigned leads, projects, tasks, support issues, calendar events, and operational work allowed by their permissions.
- **Clients/customers:** restricted access to their projects, tasks, renewals, and support issues.
- **Vendors:** managed as business records; the vendor workflow is operated by internal users rather than a vendor login portal.

## 2. Technology and Application Structure

- **Backend:** Laravel 9.x, PHP 8+, Eloquent ORM, Form Requests, service/action classes.
- **Authentication:** session authentication for the web application and Laravel Sanctum for API access.
- **Authorization:** Spatie Laravel Permission plus application middleware/policies for role and permission checks.
- **Frontend:** Laravel Blade views, Bootstrap/theme assets, JavaScript, Axios, Vite, DataTables, FullCalendar, ApexCharts/Highcharts.
- **Persistence:** relational database configured through Laravel; migrations are the source of schema history.
- **Files:** Laravel filesystem uploads for profile images, project files, task files, issue attachments, todo attachments, logos, team icons, and resumes.
- **External services:** Meta/Facebook Lead Ads, Google Ads lead capture, Google Calendar/Meet fields, email, WhatsApp Business API, Firebase Cloud Messaging.
- **API documentation:** L5-Swagger/OpenAPI assets are generated under `storage/api-docs` and exposed through the Swagger view.

Important application locations:

| Location | Responsibility |
|---|---|
| `app/Http/Controllers` | Web and API request handling |
| `app/Services` | Reusable business workflows and integrations |
| `app/Actions` | Focused write operations such as vendor and lead status changes |
| `app/Models` | Eloquent entities and relationships |
| `app/Http/Requests` | Input validation and authorization rules |
| `routes/web.php` | Authenticated browser routes and public webhooks/forms |
| `routes/api.php` | Versioned and non-versioned API endpoints |
| `database/migrations` | Database schema and schema evolution |
| `resources/views` | Blade UI and email templates |
| `app/Console/Commands` | Scheduled and maintenance commands |

## 3. Functional Modules

### 3.1 Authentication and User Profile

Users can register, sign in, sign out, request a password reset, set a new password, view their profile, update profile details, and manage profile images. The API additionally supports login, current-user details, FCM token registration, single-device logout, and logout-all.

The root route redirects authenticated users to the dashboard. Privacy policy and terms-and-conditions pages are publicly available.

### 3.2 Dashboard and Operations Summary

The dashboard aggregates operational information into one view. Depending on the user’s privileges and scope, it provides:

- client and vendor renewal counts and urgent renewal items;
- project and task summaries;
- lead and follow-up metrics;
- client issue metrics;
- digital-marketing and web-app lead metrics;
- calendar/appointment information;
- notification summaries and urgent notifications;
- downloadable operations summary data.

The API also exposes dashboard and quick-stat responses.

### 3.3 Roles, Permissions, Teams, and Departments

Administrators can create, edit, list, and delete roles and permissions. Permissions are grouped by module and are used by web middleware, API authorization checks, policies, and menu visibility.

Teams and departments are configurable in Settings. Staff can be associated with teams and departments through relationship tables. Some modules additionally apply staff/client ownership and assignment scope, so a user may have a permission but still only see records within their allowed scope.

### 3.4 Staff Management

Staff records support creation, editing, viewing, soft deletion, restoration, permanent deletion, bulk deletion, team assignment, department assignment, profile information, and user-account synchronization.

Staff detail and analytics screens include project/task activity, logged-time information, lead performance, lead charts, follow-up charts, KPIs, recent activity, overdue follow-ups, and date-range reporting. API endpoints provide staff lists, details, form options, CRUD operations, restore/force-delete operations, and staff projects/tasks.

### 3.5 Client and Customer Management

The application contains both the CRM client model and the customer/client portal identity model. Client workflows include:

- create, view, edit, update, delete, bulk delete, and status toggle;
- bulk import and downloadable import template;
- contact, address, profile, company/business details, and service relationships;
- optional invitation/welcome email and user-account linkage;
- source-lead conversion marking when a lead becomes a client;
- scoped access for staff and clients.

The API provides client CRUD and related form options. Customer-oriented API capabilities also cover customer projects, tasks, and client issues.

### 3.6 Vendor Management

Vendors can be listed, created, viewed, edited, deleted, bulk deleted, activated/deactivated, bulk imported, and exported through a template download. Vendor visibility is scoped according to role and permissions. The web application retains backward-compatible vendor routes in addition to resource routes.

The API provides vendor CRUD, pagination/filtering, and resource-formatted responses.

### 3.7 Client Services and Renewals

Client services represent subscriptions or services sold to clients. The module supports:

- service CRUD, filtering, status/plan handling, and bulk deletion;
- billing-date and renewal information;
- service remarks and client business-detail association;
- AMC/maintenance-package creation and synchronization;
- AMC visit details and visit-status updates;
- renewal tabs, active/expired/near-expiry filtering, and renewal reporting;
- email and scheduled reminders for upcoming renewals and AMC visits.

Client-renewal API endpoints expose form options, list/detail/create/update/delete operations, and AMC visit updates.

### 3.8 Vendor Services and Vendor Renewals

Vendor services track services or subscriptions purchased from vendors. The module supports CRUD, bulk deletion, plan type, billing date, remarks, soft deletion, vendor-scoped access, and renewal-oriented filters.

Vendor-renewal API endpoints provide form options and CRUD operations with the same access-scope rules used by the web application.

### 3.9 Lead Capture and Basic Lead CRUD

The basic lead module supports manual lead creation, list/detail/edit/update/delete, active/inactive status changes, bulk deletion, and export. Lead records can carry source information, contact details, status, assignment/pipeline fields, and enterprise-oriented metadata.

Lead API endpoints provide form options, dashboard data, CRUD, and normalized lead resources.

### 3.10 Unified Lead Management Pipeline

The lead-management module unifies leads from multiple sources, including internal leads, digital-marketing leads, web-app leads, Meta leads, and Google leads. It provides:

- source-aware list and detail views;
- individual and bulk staff assignment;
- status changes with transition validation;
- status history and activity timeline;
- follow-up creation and follow-up history;
- notes and reminders;
- escalation of overdue or problematic leads;
- conversion of a lead into a client/customer;
- staff performance statistics and analytics;
- source-aware deletion and access filtering;
- mobile/app notification scheduling for assignments and follow-ups.

The web and V1 API expose the same pipeline concepts. Access checks account for administrator level, staff assignment, client ownership, and Meta lead user scope.

### 3.11 Meta/Facebook Lead Ads

Meta Lead Ads integration supports Meta business connection/configuration, page/form discovery, credentials stored in settings, lead synchronization, user-scoped Meta lead access, and webhook verification/handling. Synced Meta leads can be viewed, deleted, and included in unified lead management.

The system can queue a welcome WhatsApp message for eligible Meta leads and records message delivery/tracking information.

### 3.12 Google Ads and Web-App Leads

Google Ads lead capture is accepted through a public webhook endpoint. Internal users can list, inspect, update status for, and delete Google leads. Statistics are available through the API.

Web-app lead records are also included in the lead-source workflow and can be handled through the digital-marketing/lead-management screens.

### 3.13 Project Management

Projects can be created, listed, viewed, edited, deleted, bulk deleted, and filtered by the user’s access. Staff have a “my projects” view; clients can see projects associated with their account.

Project functionality includes:

- project metadata, customer/client association, priority, technologies, workflow stage, and status;
- project members and staff visibility;
- milestones with due dates, progress percentages, and progress synchronization;
- project issues;
- project comments and activity feeds;
- project files with upload, download, and delete operations;
- project status logs and status history;
- change requests and change-request status transitions;
- dashboard charts, live insights, usage information, and milestone progress;
- task filtering and Kanban snapshots;
- project creation and lifecycle notifications.

The API provides project CRUD plus dedicated endpoints for milestones, issues, comments, files, change requests, details, usage, charts, activity feeds, milestone progress, filtered tasks, and Kanban movement.

### 3.14 Task Management

Tasks can be created, listed, viewed, edited, deleted, bulk deleted, filtered, and displayed in a Kanban board. Task access can be restricted to assigned staff or expanded for privileged users.

Task features include:

- project/customer association, assignee, priority, status, workflow stage, due dates, and progress;
- Kanban data and drag-and-drop movement;
- comments and comment attachments;
- file attachments;
- checklists;
- dependencies with self-dependency/circular-dependency protection;
- time tracking with start, stop, manual entries, and reports;
- QA review request, QA review, QA approval, and deployment completion;
- status history and project/task notifications.

The API exposes all of these operations, including comments, attachments, dependencies, checklists, time logs, QA workflow, and deployment.

### 3.15 Client Issues and Support Tasks

Internal staff and clients can use the client-issue module according to permission and ownership rules. It supports issue creation, list/detail, status changes, assignment to a team and optionally an individual, deletion, and bulk deletion.

Each issue can contain support tasks with their own create, view, update, status, delete, due-date, and attachment workflows. API form options and the same issue/task operations are available through `/api/client-issues`.

### 3.16 Todo Management

Todos provide personal or operational follow-up work. Users can create, list, view, update, delete, toggle completion/status, attach files, and receive CRUD/reminder notifications. API endpoints include options and status toggling.

### 3.17 Calendar, Appointments, Google Meet, and Reminders

Calendar events can be listed, created, viewed, updated, deleted, and status-toggled. Events support scheduling conflict checks, attendees/recipients, notification channels, reminders, agenda, and Google Meet-related fields.

The notification service can deliver event-created, event-updated, reminder, and event-time notifications through email, WhatsApp, in-app/database notifications, push notifications, and web notifications when enabled. Calendar API CRUD is available under `/api/calendar/events`.

### 3.18 Notifications and Push Messaging

Notifications are stored and exposed through web/API screens. Users can retrieve notifications, mark one notification as read, or mark all notifications as read. FCM device tokens support push delivery.

Notification sources include renewals, AMC visits, lead assignments/follow-ups, project lifecycle events, task assignments and QA events, todo operations/reminders, calendar events, and general application notifications. Delivery may use email, WhatsApp, database/in-app notifications, web notifications, or Firebase push depending on configuration.

### 3.19 Book-a-Call and Web Enquiries

Public website submissions are stored for internal review:

- **Book a Call:** appointment requests can be listed and removed; meeting agenda and Meet-related fields are supported.
- **Contact enquiries:** contact submissions can be listed and deleted, with status tracking.
- **Career enquiries:** applications can be listed, viewed, deleted, and served with a resume URL; applicant type and status are supported.

The API exposes book-call, contact, and career enquiry management endpoints.

### 3.20 Settings, Branding, and Legal Content

Settings screens/API sections cover:

- general application settings;
- company information and business details;
- email/mail configuration and test email sending;
- renewal notification timing and renewal behavior;
- Meta credentials and selected business assets;
- legal content updates;
- teams and departments;
- searchable tags;
- application logo and login logo uploads.

Privacy policy and terms-and-conditions content is served from the legal-content configuration/documents.

### 3.21 Tags

Tags can be listed, created, updated, deleted, searched, retrieved in bulk, and activated/deactivated. Tags are used in configurable/settings and lead/project-related workflows where applicable.

## 4. API Surface Summary

API routes are defined in `routes/api.php`. The main resource groups are:

| Group | Capabilities |
|---|---|
| Auth | Login, current user, FCM token, logout, password reset |
| Dashboard | Summary and quick stats |
| Staff | CRUD, restore/force-delete, teams/departments, projects/tasks |
| Clients | CRUD and client resource data |
| Vendors | CRUD |
| Vendor renewals | CRUD and form options |
| Client renewals | CRUD, form options, AMC visits |
| Todos | CRUD, options, status |
| Leads | CRUD, form options, dashboard |
| Lead management | Assignment, statuses, follow-ups, notes, reminders, conversion, escalation, performance |
| Meta leads | List/detail/sync/delete |
| Google leads | List/detail/stats |
| Projects | CRUD, milestones, issues, comments, files, change requests, dashboards/Kanban |
| Tasks | CRUD, comments, attachments, dependencies, checklists, time logs, QA/deployment |
| Client issues | CRUD, assignments, support tasks |
| Services | CRUD and form options |
| Calendar | Event CRUD |
| Settings | Configuration sections, logos, test email, tags |
| Enquiries | Book-a-call, careers, contacts |
| Notifications | List, read one, read all |

Most authenticated API endpoints are grouped under `auth:sanctum` and/or permission middleware. Request/response details should be taken from the corresponding controller, Form Request, API Resource, and generated OpenAPI document.

## 5. Scheduled and Background Functionality

The scheduler is configured in `app/Console/Kernel.php`. The host must run Laravel’s scheduler every minute, and queue workers must process queued jobs where configured.

| Schedule/command | Purpose |
|---|---|
| `calendar:send-notifications` every minute | Sends selected calendar reminders/event notifications |
| `todos:send-reminders` every minute | Sends todo reminders |
| `notifications:send-daily` daily at configured time | Sends daily operational/renewal notifications |
| `amc:send-visit-reminders` daily at 06:00 | Sends AMC visit reminders |
| `project-management:send-notifications` daily at 09:00 | Sends project milestone/task notifications |
| `project-management:sync-milestone-progress` hourly | Recalculates milestone/project progress |
| `meta:sync-leads` hourly | Synchronizes Meta leads |
| `leads:escalate-overdue` hourly | Escalates overdue leads |

Additional commands support permission-cache clearing, pipeline assignment backfill, calendar listing/testing, notification testing, WhatsApp testing, Meta welcome WhatsApp queuing, and email testing. Queue jobs handle calendar notifications, lead reminders, Meta welcome messages, dashboard reminders, and email/WhatsApp delivery.

## 6. Database Tables

The following inventory is based on the migration history. Later migrations modify earlier tables, so the final column set is the result of running all migrations in timestamp order.

### Framework, identity, and access control

| Table | Purpose |
|---|---|
| `users` | Authenticated users, profiles, login data, and user-level settings/scope |
| `password_resets` | Password reset tokens |
| `personal_access_tokens` | Sanctum API tokens |
| `failed_jobs` | Failed queued jobs |
| `roles` | Spatie roles |
| `permissions` | Spatie permissions |
| `model_has_roles` | Polymorphic user/model-to-role assignments |
| `model_has_permissions` | Direct model permission assignments |
| `role_has_permissions` | Role-to-permission assignments |
| `user_address` | User address details |
| `staff` | Staff-specific records and employment metadata |
| `customers` | Customer/client portal records |
| `staff_team` | Staff-to-team relationship |
| `staff_department` | Staff-to-department relationship |
| `teams` | Operational teams |
| `departments` | Operational departments |
| `settings` | Application, company, email, renewal, Meta, legal, branding, and scoped settings |
| `notifications` | Laravel database notifications |
| `notification_reads` | Read state for application notifications |
| `fcm_tokens` | Firebase Cloud Messaging device tokens |

### Clients, vendors, and services

| Table | Purpose |
|---|---|
| `clients` | CRM client/company/contact records |
| `client_business_details` | Extended business details for clients |
| `vendors` | Vendor records |
| `services` | Client services, billing, renewal, and AMC-related data |
| `vendor_services` | Vendor service/renewal records |
| `amc_services` | AMC package/header records |
| `amc_service_details` | Individual AMC visit schedules and reminder tracking |
| `tags` | Reusable tags |

### Leads and lead pipeline

| Table | Purpose |
|---|---|
| `leads` | Manually captured and normalized internal leads |
| `assigned_leads` | Legacy/source lead assignment support |
| `lead_assignments` | Enterprise lead-to-staff assignment history/current assignment |
| `lead_followups` | Scheduled and completed lead follow-ups |
| `lead_activities` | Lead activity timeline entries |
| `lead_notes` | Lead notes |
| `lead_reminders` | Lead reminder records |
| `lead_status_histories` | Lead status transitions |
| `lead_escalations` | Lead escalation records |
| `lead_conversions` | Lead-to-client conversion records |
| `lead_pipeline_stages` | Configurable/managed lead pipeline stages |
| `staff_lead_stats` | Aggregated staff lead-performance statistics |
| `digital_marketing_leads` | Digital marketing source leads |
| `webapp_leads` | Website/web-application source leads |
| `meta_leads` | Meta/Facebook Lead Ads records |
| `meta_lead_user` | User-to-Meta-lead scope/access relationship |
| `meta_lead_whatsapp_message_logs` | Meta lead WhatsApp welcome-message logs |
| `google_leads` | Google Ads lead records |

### Projects, tasks, and delivery operations

| Table | Purpose |
|---|---|
| `projects` | Project master records and lifecycle/workflow data |
| `project_status_logs` | Project status log entries |
| `project_status_histories` | Project status transition history |
| `project_activities` | Project activity feed |
| `project_files` | Uploaded project files |
| `project_milestones` | Project milestones, deadlines, and progress |
| `project_issues` | Project-level issues |
| `project_comments` | Project comments |
| `project_change_requests` | Customer/staff change requests and transitions |
| `project_deployments` | Project deployment records |
| `project_approvals` | Project approval records |
| `project_tags` | Project-to-tag relationship |
| `sprints` | Sprint planning records |
| `tasks` | Work tasks, assignments, status, workflow, and due dates |
| `task_attachments` | Task files |
| `task_comments` | Task comments |
| `task_comment_attachments` | Files attached to task comments |
| `task_checklists` | Task checklist items |
| `task_dependencies` | Task dependency graph |
| `task_followers` | Task follower/watch relationships |
| `task_status_histories` | Task status transition history |
| `task_tags` | Task-to-tag relationship |
| `task_labels` | Task labels |
| `task_time_logs` | Task timers and manual time entries |

### Support, scheduling, and external enquiries

| Table | Purpose |
|---|---|
| `client_issues` | Client support issues |
| `client_issue_tasks` | Tasks belonging to client issues |
| `client_issue_team_assignments` | Issue team and individual assignments |
| `calendar_events` | Appointments, meetings, attendees, reminders, and notification settings |
| `bookcall` | Public book-a-call submissions |
| `todos` | Todo records, attachments, reminders, and delivery tracking |
| `jobapplication` | Career/job application submissions |
| `contactform` | Contact-form submissions |
| `enquiry_forms` | General enquiry submissions and status |
| `jobs` | Queue job payloads |

## 7. Access and Data-Scope Rules

Authorization is enforced at several levels:

1. **Authentication:** web routes require a logged-in session; API routes generally require Sanctum.
2. **Role/permission middleware:** module visibility and write actions are permission-controlled.
3. **Policies and controller checks:** projects, leads, tasks, and issues verify ownership, assignment, client identity, or privileged staff status.
4. **Scoped queries:** staff, clients, vendors, vendor services, renewals, projects, tasks, and Meta leads may be filtered to the records visible to the current user.
5. **Workflow transitions:** lead, project, task, and change-request statuses are validated before being applied.

## 8. External Integrations and Delivery Channels

- **Meta Graph API:** business/page/form discovery and lead synchronization.
- **Google Ads:** inbound lead webhook and Google-lead reporting.
- **Google Calendar/Meet:** appointment fields, meeting details, and calendar notification workflows.
- **WhatsApp Business API:** templates, reminders, lead welcome messages, and event/todo notifications.
- **SMTP/mail provider:** password reset, invitations, renewals, project notifications, calendar reminders, todo reminders, and test messages.
- **Firebase FCM:** device push notifications.
- **File storage:** uploaded project/task/issue/todo files, resumes, logos, profile images, and team icons.

## 9. Operational Notes

- Run migrations in timestamp order before using any feature.
- Configure database, mail, queue, filesystem, Sanctum, Meta, Google, WhatsApp, and Firebase environment values in `.env`.
- Run `php artisan storage:link` when public uploaded files are required.
- Run `php artisan schedule:work` locally or configure the server cron entry for `php artisan schedule:run` every minute.
- Run a queue worker for queued notifications and mail, for example `php artisan queue:work`.
- Build frontend assets with `npm run build`; use `npm run dev` during development.
- Clear permission/configuration caches after changing roles, permissions, or relevant settings.

## 10. Source of Truth

This document summarizes behavior implemented in the current codebase. For endpoint-level contracts, use `routes/api.php`, the relevant API controller/Form Request/API Resource, and the generated OpenAPI document. For exact final column definitions and foreign keys, use the complete ordered migration set under `database/migrations`.
