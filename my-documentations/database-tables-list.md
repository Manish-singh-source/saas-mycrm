Seeders:

Super Admin:
1. permissions list
2. roles (super admin)
3. modules list
4. features list
5. integrations list (predefined like email, whatsapp, etc.)

Tenant:
1. permissions list
2. role (owner/tenant)

Packages:
1. laravolt avatar
2. 

Database Tables List:


================== Phase 1 ========================

System Generated:
- personal_access_tokens - done
- sessions- done
- password_reset_tokens- done
- cache- done
- cache_locks- done
- jobs- done
- job_batches- done
- failed_jobs- done
- notifications- done

Global Master Data:
- countries- done
- states- done
- cities- done

- business_types
- industries
- currencies
- languages
- timezones
- date_formats
- time_formats

Platform Access And RBAC:
- platform_permissions- done
- platform_roles- done
- platform_departments- done
- platform_designations- done

- platform_role_has_permissions- done
- platform_model_has_roles- done
- platform_model_has_permissions- done

- platform_users- done
- platform_teams- done
- platform_team_roles- done
- platform_team_members- done
- platform_team_assignments- done
- platform_api_tokens- done










================== Phase 2 ========================


Before Tenants:  

Platform Catalog And Module Setup:
- modules
- platform_idempotency_keys

Platform Settings And Preferences:
- platform_user_preferences
- platform_settings 


Platform Billing And Subscriptions:
- features
- plans
- plan_features

- addon_plans
- plan_addons

- coupons
- coupon_plan_assignments


Monitoring And Audit:
- monitoring_services
- monitoring_service_logs
- queue_job_logs
- scheduler_logs
- system_incidents
- monitoring_alerts

Integrations And Communication:
- integration_providers


Backup And Restore:
- backup_settings
- backup_runs

Support And Knowledge Base:
- knowledge_base_categories
- knowledge_base_articles

Reports And Import Export:
- report_export_jobs


Onboarding, Legal, Announcements, And Webhooks:
- onboarding_checklists
- legal_documents
- platform_announcements













================== Phase 3 ========================


Tenant Foundation:
- tenants
- tenant_offices

- permissions
- roles

- role_has_permissions
- model_has_roles
- model_has_permissions

- designations
- departments

- users
- tenant_api_tokens
- tenant_module_overrides

Tenant Settings And Preferences:
- tenant_settings
- user_preferences


Tenant Shared Primitives:
- files
- attachments
- document_folders
- document_folder_files
- tenant_lookups
- custom_fields
- custom_field_values
- notes
- tags
- taggables
- activity_logs
















================== Phase 4 ========================

- subscriptions
- subscription_versions
- subscription_addons
- subscription_usage
- subscription_renewals

- platform_invoices
- platform_invoice_items
- platform_payments
- platform_refunds

- coupon_tenant_assignments
- coupon_redemptions


Monitoring And Audit:
- tenant_usage_snapshots
- api_request_logs
- security_events

Integrations And Communication:
- tenant_integrations
- integration_credentials
- integration_webhooks
- integration_webhook_logs
- integration_sync_jobs
- integration_field_mappings
- integration_rate_limits
- communication_logs
- notification_templates

Backup And Restore:
- tenant_backup_runs
- tenant_restore_requests

Support And Knowledge Base:
- platform_tickets
- platform_ticket_comments
- platform_ticket_attachments
- remote_login_sessions

Reports And Import Export:
- tenant_import_export_jobs

Onboarding, Legal, Announcements, And Webhooks:
- tenant_onboarding_steps
- tenant_legal_acceptances
- platform_webhook_endpoints
- platform_webhook_deliveries











================== Phase 5 ========================

Tenant Organization And Staff:
- teams
- team_roles

- staff
- team_members
- team_permissions
- team_settings
- team_assignments
- staff_employment_history
- staff_bank_accounts
- staff_salary_structures
- staff_documents
- staff_emergency_contacts
- staff_assets
- staff_certifications
- staff_appraisals
- staff_training

Attendance And Leave:
- shifts
- staff_shift_assignments
- attendance_records
- attendance_requests
- leave_types
- leave_requests
- leave_balances


















================== Phase 6 ========================

Clients and Renewals:
- parties
- party_contacts
- party_addresses
- client_profiles
- vendor_profiles
- lead_profiles
- lead_activities
- lead_conversion_history

Renewals:
- renewals
- renewal_items
- renewal_history
- renewal_reminders








================== Phase 6 ========================

Projects And Tasks:
- projects
- project_members
- project_phases
- project_milestones
- tasks
- project_time_logs
- project_expenses
- task_checklists
- task_checklist_items
- task_comments
- task_dependencies
- task_watchers
- task_assignments
- task_time_logs
- todo_lists

Client Issues:
- client_issues













================== Phase 7 ========================

Calendar And Meetings:
- calendars
- calendar_events
- calendar_event_attendees
- calendar_event_reminders
- meeting_rooms
- meeting_room_bookings
- video_meetings
- calendar_sync_logs
- reminders

Tenant Finance:
- tenant_invoices
- tenant_invoice_items
- tenant_payments
- tenant_expenses
- tenant_expense_items
- bank_accounts













================== Phase 8 ========================


Holidays:
- holiday_calendars
- holidays
- holiday_applicabilities
- holiday_groups
- holiday_group_members


Payroll:
- payroll_cycles
- payrolls
- payroll_component_types
- payroll_components
- payroll_component_assignments
- payroll_items
- payroll_overtime
- payroll_loans
- payroll_loan_installments
- payroll_reimbursements
- payroll_tax_slabs
- payroll_tax_deductions
- payroll_pf_settings
- payroll_esi_settings
- payroll_bank_transfers
- payroll_payslips
- payroll_approvals
