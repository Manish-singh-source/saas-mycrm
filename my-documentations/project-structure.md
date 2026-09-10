# Backend Project Structure

The backend is a Laravel application located in `backend/`. The structure below reflects the current source tree; generated/runtime contents under `storage/` and `bootstrap/cache/` are excluded except for their tracked `.gitignore` files.

```text
backend/
├── app/
│   ├── Actions/
│   │   ├── Platform/
│   │   ├── Shared/
│   │   └── Tenant/
│   ├── Console/Commands/
│   ├── Enums/
│   ├── Exceptions/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Auth/
│   │   │   ├── Common/
│   │   │   ├── Controller.php
│   │   │   ├── Platform/
│   │   │   ├── Shared/
│   │   │   └── Tenant/
│   │   ├── Middleware/
│   │   ├── Requests/{Platform,Shared,Tenant}/
│   │   └── Resources/{Platform,Shared,Tenant}/
│   ├── Jobs/{Platform,Shared,Tenant}/
│   ├── Mail/
│   ├── Models/
│   ├── Policies/
│   │   ├── Platform/
│   │   ├── Shared/
│   │   └── Tenant/
│   ├── Providers/
│   ├── Services/
│   │   ├── Platform/
│   │   ├── Rbac/
│   │   ├── Shared/
│   │   └── Tenant/
│   ├── Support/
│   └── Tenancy/
├── bootstrap/
├── config/
├── database/
│   ├── factories/
│   ├── migrations/
│   └── seeders/
├── docs/
├── public/
├── resources/
├── routes/
├── tests/
├── artisan
├── composer.json / composer.lock
├── package.json
├── phpunit.xml
├── vite.config.js
└── README.md
```

## Backend boundaries

- `Platform` contains SaaS-level administration, billing, catalog, tenant management, platform users, and platform RBAC.
- `Tenant` contains tenant-scoped CRM, HRMS, finance, projects, operations, users, and tenant RBAC.
- `Shared` contains reusable controllers, actions, jobs, services, policies, resources, and primitives.
- `ResolveTenantContext` and the tenant base classes enforce tenant context; platform and tenant token/permission middleware protect their respective route groups.
- Database migrations establish the platform, tenant, shared, billing, CRM, HR, finance, payroll, operations, and integration schemas. Seeders populate master data, permission maps, roles, catalog data, and demo data.
