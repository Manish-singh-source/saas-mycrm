# Rebuild Steps From Scratch

Use this document if you want to rebuild this CRM project cleanly from zero. The current project is split into:

- `backend`: Laravel 12 API backend with Sanctum, queues, database sessions/cache, service classes, policies, resources, seeders, factories, and tests.
- `frontend`: React 18 + TypeScript + Vite frontend with React Router, React Query, React Hook Form, Zod, Recharts, Vitest, Testing Library, and MSW.
- `Project-docs`: module requirements and phase documents.

## 1. Preparation

1. Read the project sequence first:
   - `project-sequence.md`
   - `Project-docs/api-response-standards.md`
   - `Project-docs/design.md`
   - Every phase document inside `Project-docs/phase 0` through `Project-docs/phase 12`

2. Build the product map before coding:
   - User flow
   - Database diagram with tables, fields, and relationships
   - Role and permission matrix
   - Platform admin modules
   - Tenant modules
   - Shared auth flow
   - API response format

3. Confirm local requirements:
   - PHP 8.2 or newer
   - Composer
   - Node.js LTS
   - npm
   - SQLite or MySQL
   - XAMPP/Apache if you want to serve through XAMPP

4. Create a fresh root folder:

   ```bash
   mkdir saas-mycrm
   cd saas-mycrm
   mkdir backend frontend Project-docs
   ```

5. Copy or recreate the documentation into `Project-docs` before implementation. Treat docs as the build contract.

## 2. Backend Setup

1. Create the Laravel app:

   ```bash
   composer create-project laravel/laravel backend
   cd backend
   ```

2. Install required backend packages:

   ```bash
   composer require laravel/sanctum twilio/sdk
   composer require --dev laravel/pint laravel/pail laravel/sail
   ```

3. Copy environment file and generate app key:

   ```bash
   copy .env.example .env
   php artisan key:generate
   ```

4. Configure `.env`:

   ```env
   APP_NAME="Enterprise CRM"
   APP_ENV=local
   APP_DEBUG=true
   APP_URL=http://localhost:8000

   DB_CONNECTION=sqlite
   SESSION_DRIVER=database
   CACHE_STORE=database
   QUEUE_CONNECTION=database
   MAIL_MAILER=log
   ```

   If using MySQL, replace database values with:

   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=saas_mycrm
   DB_USERNAME=root
   DB_PASSWORD=
   ```

5. Create SQLite database if using SQLite:

   ```bash
   type nul > database/database.sqlite
   ```

6. Publish/install Sanctum and Laravel support tables:

   ```bash
   php artisan install:api
   php artisan session:table
   php artisan cache:table
   php artisan queue:table
   php artisan notifications:table
   ```

7. Create backend structure:

   ```text
   app/Actions
   app/Enums
   app/Exceptions
   app/Http/Controllers
   app/Http/Middleware
   app/Http/Requests
   app/Http/Resources
   app/Jobs
   app/Mail
   app/Models
   app/Policies
   app/Services
   app/Support
   app/Tenancy
   routes/api.php
   routes/api-platform.php
   routes/api-tenant.php
   ```

8. Register API route files:
   - Keep shared/auth/common routes in `routes/api.php`.
   - Keep platform admin routes in `routes/api-platform.php`.
   - Keep tenant workspace routes in `routes/api-tenant.php`.
   - Load these route files from Laravel bootstrap/provider configuration.

## 3. Backend Build Order

Follow this order for every backend module.

1. Define enums and DTOs first:
   - Status enums
   - Type enums
   - Payment/refund/subscription states
   - User/account states
   - Permission/module constants

2. Create migrations, models, factories, and seeders:

   ```bash
   php artisan make:model ModelName -mfs
   ```

   Build parent tables before child tables.

3. Update each model:
   - `$table` only when table name is not conventional
   - `$fillable` or `$guarded`
   - `$casts`
   - UUID handling where needed
   - Relationships
   - Scopes for common filters

4. Add seed data:
   - Platform permissions
   - Platform roles
   - Modules
   - Features
   - Plans
   - Add-ons
   - Demo tenant
   - Demo tenant staff/users
   - Demo financial and CRM records

5. Build middleware:
   - `auth:sanctum`
   - Platform permission middleware
   - Tenant context middleware
   - Tenant token middleware
   - Tenant permission middleware

6. Build FormRequest classes:
   - Validation rules
   - Authorization checks
   - Normalization of request data

7. Build service classes:
   - Use services for business logic.
   - Use services for external integrations.
   - Use queued jobs for slow external calls like email, SMS, WhatsApp, webhooks, reports, exports, and payment callbacks.

8. Build controllers:
   - Keep controllers thin.
   - Validate using FormRequests.
   - Delegate logic to services/actions.
   - Return API Resources or standardized API responses.

9. Build API Resources:
   - List resources
   - Detail resources
   - Nested relationship resources
   - Pagination metadata

10. Add policies and gates:
   - Platform admin permissions
   - Tenant role permissions
   - Ownership checks
   - Team/staff access checks

11. Add feature tests for critical paths:

   ```bash
   php artisan make:test ModuleNameTest
   php artisan test
   ```

## 4. Backend Module Sequence

Build modules in this order because later modules depend on earlier ones.

1. Phase 0: Dashboard basics and global API standards.
2. Phase 1: Platform permissions, roles, team roles, teams.
3. Phase 2: Platform staff, add-ons, modules, features, coupons, plans.
4. Phase 3: Tenants, subscriptions, invoices, payments, refunds.
5. Phase 4: Tenant dashboard, tenant permissions, roles, teams, staff.
6. Phase 5: Tenant profile, settings, integrations, help center, recent activity, notifications.
7. Phase 6: Tenant tasks, calendar, invoices, payments, expenses, bank accounts.
8. Phase 7: Tenant clients, projects, tasks, client issues.
9. Phase 8: Tenant vendors, client renewals, vendor renewals.
10. Phase 9: Tenant leads, web forms, tenant reports.
11. Phase 10: Tickets, knowledge base, remote login.
12. Phase 11: Platform settings, integrations, monitoring, reports, audit logs.
13. Phase 12: Onboarding, trials, legal, announcements, API tokens, webhooks.

## 5. Backend Verification

1. Run migrations and seeders:

   ```bash
   php artisan migrate:fresh --seed
   ```

2. Run formatting:

   ```bash
   ./vendor/bin/pint
   ```

3. Run tests:

   ```bash
   php artisan test
   ```

4. Start backend services:

   ```bash
   php artisan serve
   php artisan queue:listen --tries=1 --timeout=0
   php artisan pail --timeout=0
   ```

5. Verify API groups:
   - `/api/auth/v1`
   - `/api/common/v1`
   - `/api/platform/v1`
   - `/api/tenant/v1`

## 6. Frontend Setup

1. Create the React app:

   ```bash
   cd ..
   npm create vite@latest frontend -- --template react-ts
   cd frontend
   ```

2. Install dependencies:

   ```bash
   npm install
   npm install @tanstack/react-query @tanstack/react-query-devtools react-router-dom react-hook-form @hookform/resolvers zod lucide-react recharts clsx
   npm install -D vitest @testing-library/react @testing-library/jest-dom @testing-library/user-event jsdom msw eslint-config-prettier prettier
   ```

3. Configure `.env`:

   ```env
   VITE_APP_NAME="Enterprise CRM"
   VITE_APP_ENV=local
   VITE_AUTH_API_BASE_URL=/api/auth/v1
   VITE_PLATFORM_API_BASE_URL=/api/platform/v1
   VITE_TENANT_API_BASE_URL=/api/tenant/v1
   VITE_CLIENT_VERSION=web-admin/0.1.0
   VITE_ENABLE_API_LOGS=true
   VITE_ENABLE_QUERY_DEVTOOLS=true
   ```

4. Configure path aliases in `tsconfig` and `vite.config.ts`, especially `@` pointing to `src`.

5. Create frontend structure:

   ```text
   src/app
   src/app/providers
   src/app/router
   src/config
   src/features
   src/features/auth
   src/features/platform
   src/features/tenant
   src/layouts
   src/lib
   src/pages
   src/shared
   src/styles
   src/test
   ```

6. Add app providers:
   - React Query client
   - Router provider
   - Auth/session provider if needed
   - Query devtools in local mode

7. Add API client layer:
   - Base HTTP client
   - Auth API client
   - Platform API client
   - Tenant API client
   - Shared error handling
   - Token/session handling
   - Standard API response parsing

## 7. Frontend Build Order

1. Build shared UI first:
   - Buttons
   - Inputs
   - Selects
   - Tabs
   - Tables
   - Modals
   - Drawers
   - Empty states
   - Loading states
   - Error states
   - Pagination
   - Toast/notification UI

2. Build auth:
   - Account discovery
   - Login
   - Two-factor verification
   - Forgot password
   - Reset password
   - Logout
   - Protected route guards
   - Public route guards

3. Build layouts:
   - Platform layout
   - Tenant layout
   - Sidebar navigation
   - Header
   - Profile menu
   - Permission-aware navigation

4. Build feature routes:
   - `features/platform/routes`
   - `features/tenant/routes`
   - Route constants before page implementation

5. Build feature API hooks:
   - React Query list hooks
   - Detail hooks
   - Create/update/delete mutations
   - Export/import mutations
   - Cache invalidation per module

6. Build forms:
   - Use React Hook Form.
   - Use Zod schemas.
   - Match backend FormRequest validation.

7. Build pages in the same phase order as backend modules.

8. Add charts and dashboards after core APIs are stable.

9. Add MSW handlers for testable API mocks.

10. Add frontend tests:

   ```bash
   npm run test
   npm run typecheck
   npm run lint
   ```

## 8. Frontend Verification

1. Run frontend locally:

   ```bash
   npm run dev
   ```

2. Build production assets:

   ```bash
   npm run build
   ```

3. Preview production build:

   ```bash
   npm run preview
   ```

4. Check the main flows:
   - Login
   - Platform dashboard
   - Tenant registration
   - Tenant dashboard
   - Platform CRUD modules
   - Tenant CRUD modules
   - Permissions and navigation visibility
   - API error states
   - Empty states
   - Loading states

## 9. Full Local Run

Run backend and frontend in separate terminals.

Backend:

```bash
cd backend
php artisan serve
php artisan queue:listen --tries=1 --timeout=0
```

Frontend:

```bash
cd frontend
npm run dev
```

Recommended local URLs:

- Backend: `http://127.0.0.1:8000`
- Frontend: `http://127.0.0.1:5173`

If using Vite proxy, route `/api/*` from frontend to the Laravel backend.

## 10. Deployment Checklist

1. Backend production setup:
   - Set production `.env`.
   - Use MySQL/PostgreSQL.
   - Configure mail.
   - Configure queue worker.
   - Configure scheduler.
   - Configure storage symlink.
   - Configure payment/SMS/webhook credentials.

2. Backend deployment commands:

   ```bash
   composer install --no-dev --optimize-autoloader
   php artisan key:generate --force
   php artisan migrate --force
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   php artisan storage:link
   ```

3. Frontend production setup:

   ```bash
   npm ci
   npm run build
   ```

4. Configure hosting:
   - Serve Laravel `public` as backend document root.
   - Serve frontend `dist` as static app.
   - Proxy API calls to backend.
   - Add HTTPS.
   - Add queue worker process.
   - Add scheduler cron:

     ```bash
     * * * * * php /path/to/backend/artisan schedule:run >> /dev/null 2>&1
     ```

5. Final production checks:
   - Login works.
   - Tenant registration works.
   - Permissions are enforced.
   - Queue jobs run.
   - Email/log notifications work.
   - Reports/exports work.
   - Webhooks are signed and logged.
   - Backups are configured.
   - Error logging is enabled.

## 11. Recommended Daily Rebuild Workflow

1. Pick one phase document.
2. Write or update backend migrations/models first.
3. Add seed data.
4. Build backend routes, requests, services, controllers, and resources.
5. Add backend tests.
6. Build frontend API types and hooks.
7. Build frontend page/forms/tables.
8. Add frontend tests or MSW handlers.
9. Run backend and frontend together.
10. Fix contract mismatches before starting the next module.

## 12. Important Rules To Follow

1. Do not build frontend pages before the backend API contract is clear.
2. Do not put business logic directly in controllers.
3. Do not skip FormRequests.
4. Do not skip API Resources for user-facing responses.
5. Do not hardcode permissions in multiple places; seed and centralize them.
6. Do not build tenant modules without tenant context middleware.
7. Do not call external services directly from controllers.
8. Do not skip tests for auth, permissions, payments, subscriptions, tenant context, and destructive actions.
9. Keep platform and tenant APIs separated.
10. Keep API response format consistent across all modules.
