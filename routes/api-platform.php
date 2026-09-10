<?php

use App\Http\Controllers\PlatformAnnouncementController;
use App\Http\Controllers\PlatformApiTokenController;
use App\Http\Controllers\PlatformAuditController;
use App\Http\Controllers\PlatformConfigurationController;
use App\Http\Controllers\PlatformController;
use App\Http\Controllers\PlatformCouponController;
use App\Http\Controllers\PlatformDepartmentController;
use App\Http\Controllers\PlatformDesignationController;
use App\Http\Controllers\PlatformDocumentController;
use App\Http\Controllers\PlatformFeatureController;
use App\Http\Controllers\PlatformIntegrationProviderController;
use App\Http\Controllers\PlatformKnowledgeBaseArticleController;
use App\Http\Controllers\PlatformKnowledgeBaseCategoryController;
use App\Http\Controllers\PlatformLegalDocumentController;
use App\Http\Controllers\PlatformModuleController;
use App\Http\Controllers\PlatformMonitoringController;
use App\Http\Controllers\PlatformPermissionController;
use App\Http\Controllers\PlatformPlanController;
use App\Http\Controllers\PlatformRoleController;
use App\Http\Controllers\PlatformSecurityController;
use App\Http\Controllers\PlatformTeamController;
use App\Http\Controllers\PlatformTeamRoleController;
use App\Http\Controllers\PlatformUserController;
use App\Http\Controllers\PlatformWebhookController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PlatformSubscriptionController;
use App\Http\Controllers\PlatformInvoiceController;
use App\Http\Controllers\PlatformAddonController;
use App\Http\Controllers\PlatformPaymentController;
use App\Http\Controllers\PlatformRefundController;
use App\Http\Controllers\PlatformSupportTicketController;
use App\Http\Controllers\PlatformDashboardController;

Route::get('health', [PlatformController::class, 'health'])->middleware('throttle:api-health');

Route::middleware('throttle:api-public-security')->group(function (): void {
    Route::post('verify-email/resend', [PlatformSecurityController::class, 'resendVerification']);
    Route::post('2fa/enable', [PlatformSecurityController::class, 'enableTwoFactor']);
    Route::post('2fa/confirm', [PlatformSecurityController::class, 'confirmTwoFactor']);
    Route::post('2fa/disable', [PlatformSecurityController::class, 'disableTwoFactor']);
});
Route::get('settings/preferences', [PlatformSecurityController::class, 'preferences'])->middleware('throttle:api-preferences-read');
Route::put('settings/preferences', [PlatformSecurityController::class, 'updatePreferences'])->middleware('throttle:api-preferences-write');

Route::middleware(['auth:sanctum', 'platform.token', 'throttle:api-authenticated'])->group(function (): void {
    Route::get('summary', [PlatformDashboardController::class, 'summary'])->middleware('abilities:dashboard.view');
    Route::get('charts', [PlatformDashboardController::class, 'charts'])->middleware('abilities:dashboard.view');
    Route::get('charts/{chart}', [PlatformDashboardController::class, 'chart'])->middleware('abilities:dashboard.view');
    Route::get('recent', [PlatformDashboardController::class, 'recent'])->middleware('abilities:dashboard.view');
    Route::get('recent-tenants', [PlatformDashboardController::class, 'recentTenantsEndpoint'])->middleware('abilities:dashboard.view');
    Route::get('recent-payments', [PlatformDashboardController::class, 'recentPaymentsEndpoint'])->middleware('abilities:dashboard.view');
    Route::get('overdue-invoices', [PlatformDashboardController::class, 'overdueInvoicesEndpoint'])->middleware('abilities:dashboard.view');
    Route::get('alerts', [PlatformDashboardController::class, 'alerts'])->middleware('abilities:dashboard.view');
    Route::get('active-alerts', [PlatformDashboardController::class, 'activeAlerts'])->middleware('abilities:dashboard.view');
    Route::get('security-events', [PlatformDashboardController::class, 'securityEvents'])->middleware('abilities:dashboard.view');
    Route::post('dashboard/export', [PlatformDashboardController::class, 'export'])->middleware('abilities:dashboard.view');    Route::get('profile', [PlatformController::class, 'profile']);
    Route::match(['put', 'patch'], 'profile', [PlatformController::class, 'updateProfile']);
    Route::put('profile/password', [PlatformController::class, 'changePassword']);
    Route::get('profile/sessions', [PlatformController::class, 'sessions']);
    Route::delete('profile/sessions/{sessionId}', [PlatformController::class, 'revokeSession']);

    Route::middleware('abilities:platform_permission.view')->group(function (): void {
        Route::get('permissions/grouped', [PlatformPermissionController::class, 'grouped']);
        Route::get('permissions', [PlatformPermissionController::class, 'index']);
        Route::get('permissions/{permission_uuid}', [PlatformPermissionController::class, 'show']);
        Route::post('permissions/export', [PlatformPermissionController::class, 'export']);
    });
    Route::post('permissions', [PlatformPermissionController::class, 'store'])->middleware('abilities:platform_permission.create');
    Route::match(['put', 'patch'], 'permissions/{permission_uuid}', [PlatformPermissionController::class, 'update'])->middleware('abilities:platform_permission.edit');
    Route::delete('permissions/{permission_uuid}', [PlatformPermissionController::class, 'destroy'])->middleware('abilities:platform_permission.delete');

    Route::middleware('abilities:platform_role.view')->group(function (): void {
        Route::get('roles', [PlatformRoleController::class, 'index']);
        Route::get('roles/{role_uuid}', [PlatformRoleController::class, 'show']);
        Route::get('roles/{role_uuid}/permissions', [PlatformRoleController::class, 'permissions']);
        Route::get('roles/{role_uuid}/users', [PlatformRoleController::class, 'users']);
        Route::post('roles/export', [PlatformRoleController::class, 'export']);
    });
    Route::post('roles', [PlatformRoleController::class, 'store'])->middleware('abilities:platform_role.create');
    Route::put('roles/{role_uuid}', [PlatformRoleController::class, 'update'])->middleware('abilities:platform_role.edit');
    Route::patch('roles/{role_uuid}', [PlatformRoleController::class, 'update'])->middleware('abilities:platform_role.edit');
    Route::delete('roles/{role_uuid}', [PlatformRoleController::class, 'destroy'])->middleware('abilities:platform_role.delete');
    Route::post('roles/{role_uuid}/clone', [PlatformRoleController::class, 'clone'])->middleware('abilities:platform_role.create');
    Route::post('roles/{role_uuid}/activate', [PlatformRoleController::class, 'activate'])->middleware('abilities:platform_role.edit');
    Route::post('roles/{role_uuid}/deactivate', [PlatformRoleController::class, 'deactivate'])->middleware('abilities:platform_role.edit');
    Route::put('roles/{role_uuid}/permissions', [PlatformRoleController::class, 'replacePermissions'])->middleware('abilities:platform_role.edit');
    Route::post('roles/{role_uuid}/users', [PlatformRoleController::class, 'assignUsers'])->middleware('abilities:platform_role.edit');
    Route::delete('roles/{role_uuid}/users/{platform_user_uuid}', [PlatformRoleController::class, 'removeUser'])->middleware('abilities:platform_role.edit');

    Route::middleware('abilities:platform_department.view')->group(function (): void {
        Route::get('platform-departments', [PlatformDepartmentController::class, 'index']);
        Route::get('platform-departments/{department_uuid}', [PlatformDepartmentController::class, 'show']);
    });
    Route::post('platform-departments', [PlatformDepartmentController::class, 'store'])->middleware('abilities:platform_department.create');
    Route::put('platform-departments/{department_uuid}', [PlatformDepartmentController::class, 'update'])->middleware('abilities:platform_department.edit');
    Route::patch('platform-departments/{department_uuid}', [PlatformDepartmentController::class, 'update'])->middleware('abilities:platform_department.edit');
    Route::delete('platform-departments/{department_uuid}', [PlatformDepartmentController::class, 'destroy'])->middleware('abilities:platform_department.delete');

    Route::middleware('abilities:platform_designation.view')->group(function (): void {
        Route::get('platform-designations', [PlatformDesignationController::class, 'index']);
        Route::get('platform-designations/{designation_uuid}', [PlatformDesignationController::class, 'show']);
    });
    Route::post('platform-designations', [PlatformDesignationController::class, 'store'])->middleware('abilities:platform_designation.create');
    Route::put('platform-designations/{designation_uuid}', [PlatformDesignationController::class, 'update'])->middleware('abilities:platform_designation.edit');
    Route::patch('platform-designations/{designation_uuid}', [PlatformDesignationController::class, 'update'])->middleware('abilities:platform_designation.edit');
    Route::delete('platform-designations/{designation_uuid}', [PlatformDesignationController::class, 'destroy'])->middleware('abilities:platform_designation.delete');

    Route::middleware('abilities:platform_user.view')->group(function (): void {
        Route::get('platform-users', [PlatformUserController::class, 'index']);
        Route::post('platform-users/export', [PlatformUserController::class, 'export']);
        Route::get('platform-users/{platform_user_uuid}', [PlatformUserController::class, 'show']);
        Route::get('platform-users/{platform_user_uuid}/roles', [PlatformUserController::class, 'roles']);
        Route::get('platform-users/{platform_user_uuid}/teams', [PlatformUserController::class, 'teams']);
        Route::get('platform-users/{platform_user_uuid}/permissions', [PlatformUserController::class, 'permissions']);
        Route::get('platform-users/{platform_user_uuid}/activity', [PlatformUserController::class, 'usersActivity']);
    });
    
    Route::post('platform-users', [PlatformUserController::class, 'store'])->middleware('abilities:platform_user.create');
    Route::post('platform-users/invite', [PlatformUserController::class, 'invite'])->middleware('abilities:platform_user.create');
    Route::match(['put', 'patch'], 'platform-users/{platform_user_uuid}', [PlatformUserController::class, 'update'])->middleware('abilities:platform_user.edit');
    Route::delete('platform-users/{platform_user_uuid}', [PlatformUserController::class, 'destroy'])->middleware('abilities:platform_user.delete');
    Route::post('platform-users/{platform_user_uuid}/restore', [PlatformUserController::class, 'restore'])->middleware('abilities:platform_user.edit');
    Route::post('platform-users/{platform_user_uuid}/suspend', [PlatformUserController::class, 'suspend'])->middleware('abilities:platform_user.suspend');
    Route::post('platform-users/{platform_user_uuid}/activate', [PlatformUserController::class, 'activate'])->middleware('abilities:platform_user.edit');
    Route::post('platform-users/{platform_user_uuid}/reset-password', [PlatformUserController::class, 'resetPassword'])->middleware('abilities:platform_user.edit');
    Route::post('platform-users/{platform_user_uuid}/force-logout', [PlatformUserController::class, 'forceLogout'])->middleware('abilities:platform_user.edit');
    Route::post('platform-users/{platform_user_uuid}/require-2fa', [PlatformUserController::class, 'requireTwoFactor'])->middleware('abilities:platform_user.edit');
    Route::put('platform-users/{platform_user_uuid}/roles', [PlatformUserController::class, 'syncRoles'])->middleware('abilities:platform_user.edit');
    Route::put('platform-users/{platform_user_uuid}/teams', [PlatformUserController::class, 'syncTeams'])->middleware('abilities:platform_user.edit');
    Route::put('platform-users/{platform_user_uuid}/permissions', [PlatformUserController::class, 'syncPermissions'])->middleware('abilities:platform_user.edit');

    Route::middleware('abilities:platform_team.view')->group(function (): void {
        Route::get('platform-teams', [PlatformTeamController::class, 'index']);

        Route::get('platform-teams/{team_uuid}', [PlatformTeamController::class, 'show']);
        Route::get('platform-teams/{team_uuid}/members', [PlatformTeamController::class, 'members']);
        Route::get('platform-teams/{team_uuid}/assignments', [PlatformTeamController::class, 'assignments']);
        Route::get('platform-team-roles', [PlatformTeamRoleController::class, 'index']);
        Route::get('platform-team-roles/{role_uuid}', [PlatformTeamRoleController::class, 'show']);
    });
    Route::post('platform-teams', [PlatformTeamController::class, 'store'])->middleware('abilities:platform_team.create');
    Route::put('platform-teams/{team_uuid}', [PlatformTeamController::class, 'update'])->middleware('abilities:platform_team.edit');
    Route::patch('platform-teams/{team_uuid}', [PlatformTeamController::class, 'update'])->middleware('abilities:platform_team.edit');
    Route::delete('platform-teams/{team_uuid}', [PlatformTeamController::class, 'destroy'])->middleware('abilities:platform_team.delete');
    Route::post('platform-teams/{team_uuid}/members', [PlatformTeamController::class, 'addMembers'])->middleware('abilities:platform_team.assign');
    Route::put('platform-teams/{team_uuid}/members/{member_id}', [PlatformTeamController::class, 'updateMember'])->middleware('abilities:platform_team.assign');
    Route::patch('platform-teams/{team_uuid}/members/{member_id}', [PlatformTeamController::class, 'updateMember'])->middleware('abilities:platform_team.assign');
    Route::delete('platform-teams/{team_uuid}/members/{member_id}', [PlatformTeamController::class, 'removeMember'])->middleware('abilities:platform_team.assign');
    Route::post('platform-teams/{team_uuid}/assignments', [PlatformTeamController::class, 'addAssignment'])->middleware('abilities:platform_team.assign');
    Route::delete('platform-teams/{team_uuid}/assignments/{assignment_id}', [PlatformTeamController::class, 'releaseAssignment'])->middleware('abilities:platform_team.assign');
    Route::post('platform-team-roles', [PlatformTeamRoleController::class, 'store'])->middleware('abilities:platform_team.create');
    Route::put('platform-team-roles/{role_uuid}', [PlatformTeamRoleController::class, 'update'])->middleware('abilities:platform_team.edit');
    Route::patch('platform-team-roles/{role_uuid}', [PlatformTeamRoleController::class, 'update'])->middleware('abilities:platform_team.edit');
    Route::delete('platform-team-roles/{role_uuid}', [PlatformTeamRoleController::class, 'destroy'])->middleware('abilities:platform_team.delete');

    Route::middleware('abilities:feature.view')->group(function (): void {
        Route::get('features', [PlatformFeatureController::class, 'index']);
        Route::get('features/options/modules', [PlatformFeatureController::class, 'moduleOptions']);
        Route::get('features/{feature_uuid}', [PlatformFeatureController::class, 'show']);
        Route::post('features/export', [PlatformFeatureController::class, 'export']);
    });
    Route::post('features', [PlatformFeatureController::class, 'store'])->middleware('abilities:feature.create');
    Route::post('features/import', [PlatformFeatureController::class, 'import'])->middleware('abilities:feature.create');
    Route::match(['put', 'patch'], 'features/{feature_uuid}', [PlatformFeatureController::class, 'update'])->middleware('abilities:feature.edit');
    Route::delete('features/bulk', [PlatformFeatureController::class, 'bulkDestroy'])->middleware('abilities:feature.delete');
    Route::delete('features/{feature_uuid}', [PlatformFeatureController::class, 'destroy'])->middleware('abilities:feature.delete');

    Route::middleware('abilities:module.view')->group(function (): void {
        Route::get('modules', [PlatformModuleController::class, 'index']);
        Route::get('modules/{module_uuid}', [PlatformModuleController::class, 'show']);
        Route::get('modules/{module_uuid}/features', [PlatformModuleController::class, 'features']);
        Route::get('modules/{module_uuid}/tenants', [PlatformModuleController::class, 'tenants']);
        Route::post('modules/export', [PlatformModuleController::class, 'export']);
    });
    Route::post('modules', [PlatformModuleController::class, 'store'])->middleware('abilities:module.edit');
    Route::post('modules/import', [PlatformModuleController::class, 'import'])->middleware('abilities:module.edit');
    Route::match(['put', 'patch'], 'modules/{module_uuid}', [PlatformModuleController::class, 'update'])->middleware('abilities:module.edit');
    Route::delete('modules/bulk', [PlatformModuleController::class, 'bulkDestroy'])->middleware('abilities:module.edit');
    Route::delete('modules/{module_uuid}', [PlatformModuleController::class, 'destroy'])->middleware('abilities:module.edit');
    Route::post('modules/{module_uuid}/enable', [PlatformModuleController::class, 'enable'])->middleware('abilities:module.edit');
    Route::post('modules/{module_uuid}/disable', [PlatformModuleController::class, 'disable'])->middleware('abilities:module.edit');
    Route::put('modules/{module_uuid}/features', [PlatformModuleController::class, 'replaceFeatures'])->middleware('abilities:module.edit');
   
    Route::middleware('abilities:coupon.view')->group(function (): void {
        Route::get('coupons', [PlatformCouponController::class, 'index']);
        Route::get('coupons/{coupon_uuid}', [PlatformCouponController::class, 'show']);
        Route::get('coupons/{coupon_uuid}/redemptions', [PlatformCouponController::class, 'redemptions']);
        Route::post('coupons/export', [PlatformCouponController::class, 'export']);
    });
    Route::post('coupons', [PlatformCouponController::class, 'store'])->middleware('abilities:coupon.create');
    Route::post('coupons/import', [PlatformCouponController::class, 'import'])->middleware('abilities:coupon.create');
    Route::match(['put', 'patch'], 'coupons/{coupon_uuid}', [PlatformCouponController::class, 'update'])->middleware('abilities:coupon.edit');
    Route::delete('coupons/bulk', [PlatformCouponController::class, 'bulkDestroy'])->middleware('abilities:coupon.delete');
    Route::delete('coupons/{coupon_uuid}', [PlatformCouponController::class, 'destroy'])->middleware('abilities:coupon.delete');
    Route::post('coupons/{coupon_uuid}/activate', [PlatformCouponController::class, 'activate'])->middleware('abilities:coupon.edit');
    Route::post('coupons/{coupon_uuid}/deactivate', [PlatformCouponController::class, 'deactivate'])->middleware('abilities:coupon.edit');
    
    Route::middleware('abilities:plan.view')->group(function (): void {
        Route::get('addons', [PlatformAddonController::class, 'index']);
        Route::get('addons/{addon_uuid}', [PlatformAddonController::class, 'show']);
        Route::post('addons/export', [PlatformAddonController::class, 'export']);
    });
    Route::post('addons', [PlatformAddonController::class, 'store'])->middleware('abilities:plan.create');
    Route::post('addons/import', [PlatformAddonController::class, 'import'])->middleware('abilities:plan.create');
    Route::match(['put', 'patch'], 'addons/{addon_uuid}', [PlatformAddonController::class, 'update'])->middleware('abilities:plan.edit');
    Route::delete('addons/bulk', [PlatformAddonController::class, 'bulkDestroy'])->middleware('abilities:plan.delete');
    Route::delete('addons/{addon_uuid}', [PlatformAddonController::class, 'destroy'])->middleware('abilities:plan.delete');
    Route::post('addons/{addon_uuid}/activate', [PlatformAddonController::class, 'activate'])->middleware('abilities:plan.edit');
    Route::post('addons/{addon_uuid}/deactivate', [PlatformAddonController::class, 'deactivate'])->middleware('abilities:plan.edit');
    
    Route::middleware('abilities:plan.view')->group(function (): void {
        Route::get('plans', [PlatformPlanController::class, 'index']);
        Route::get('plans/{plan_uuid}', [PlatformPlanController::class, 'show']);
        Route::get('plans/{plan_uuid}/features', [PlatformPlanController::class, 'features']);
        Route::get('plans/{plan_uuid}/addons', [PlatformPlanController::class, 'addons']);
        Route::get('plans/{plan_uuid}/subscriptions', [PlatformPlanController::class, 'subscriptions']);
        Route::post('plans/export', [PlatformPlanController::class, 'export']);
    });
    Route::post('plans', [PlatformPlanController::class, 'store'])->middleware('abilities:plan.create');
    Route::post('plans/import', [PlatformPlanController::class, 'import'])->middleware('abilities:plan.create');
    Route::match(['put', 'patch'], 'plans/{plan_uuid}', [PlatformPlanController::class, 'update'])->middleware('abilities:plan.edit');
    Route::delete('plans/bulk', [PlatformPlanController::class, 'bulkDestroy'])->middleware('abilities:plan.delete');
    Route::delete('plans/{plan_uuid}', [PlatformPlanController::class, 'destroy'])->middleware('abilities:plan.delete');
    Route::post('plans/{plan_uuid}/clone', [PlatformPlanController::class, 'clone'])->middleware('abilities:plan.create');
    Route::post('plans/{plan_uuid}/activate', [PlatformPlanController::class, 'activate'])->middleware('abilities:plan.edit');
    Route::post('plans/{plan_uuid}/deactivate', [PlatformPlanController::class, 'deactivate'])->middleware('abilities:plan.edit');
    Route::put('plans/{plan_uuid}/features', [PlatformPlanController::class, 'replaceFeatures'])->middleware('abilities:plan.edit');
    Route::put('plans/{plan_uuid}/addons', [PlatformPlanController::class, 'replaceAddons'])->middleware('abilities:plan.edit');

    Route::put('coupons/{coupon_uuid}/plans', [PlatformCouponController::class, 'replacePlans'])->middleware('abilities:coupon.edit');
    Route::put('coupons/{coupon_uuid}/tenants', [PlatformCouponController::class, 'replaceTenants'])->middleware('abilities:coupon.edit');

    Route::get('support/tickets', [PlatformSupportTicketController::class, 'index'])->middleware('abilities:support.ticket.view');
    Route::post('support/tickets', [PlatformSupportTicketController::class, 'store'])->middleware('abilities:support.ticket.reply');
    Route::post('support/tickets/export', [PlatformSupportTicketController::class, 'export'])->middleware('abilities:support.ticket.view');
    Route::get('support/tickets/{ticket_uuid}', [PlatformSupportTicketController::class, 'show'])->middleware('abilities:support.ticket.view');
    Route::match(['put','patch'], 'support/tickets/{ticket_uuid}', [PlatformSupportTicketController::class, 'update'])->middleware('abilities:support.ticket.reply');
    Route::post('support/tickets/{ticket_uuid}/assign', [PlatformSupportTicketController::class, 'assign'])->middleware('abilities:support.ticket.assign');
    Route::post('support/tickets/{ticket_uuid}/comments', [PlatformSupportTicketController::class, 'comment'])->middleware('abilities:support.ticket.reply');
    Route::post('support/tickets/{ticket_uuid}/attachments', [PlatformSupportTicketController::class, 'attach'])->middleware('abilities:support.ticket.reply');
    Route::post('support/tickets/{ticket_uuid}/close', [PlatformSupportTicketController::class, 'close'])->middleware('abilities:support.ticket.close');
    Route::post('support/tickets/{ticket_uuid}/reopen', [PlatformSupportTicketController::class, 'reopen'])->middleware('abilities:support.ticket.close');
    Route::middleware('abilities:support.knowledge_base.view')->group(function (): void {
        Route::get('support/knowledge-base/categories', [PlatformKnowledgeBaseCategoryController::class, 'index']);
        Route::get('support/knowledge-base/articles', [PlatformKnowledgeBaseArticleController::class, 'index']);
        Route::get('support/knowledge-base/articles/{article_uuid}', [PlatformKnowledgeBaseArticleController::class, 'show']);
    });
    Route::post('support/knowledge-base/categories', [PlatformKnowledgeBaseCategoryController::class, 'store'])->middleware('abilities:support.knowledge_base.create');
    Route::match(['put', 'patch'], 'support/knowledge-base/categories/{category_uuid}', [PlatformKnowledgeBaseCategoryController::class, 'update'])->middleware('abilities:support.knowledge_base.edit');
    Route::post('support/knowledge-base/articles', [PlatformKnowledgeBaseArticleController::class, 'store'])->middleware('abilities:support.knowledge_base.create');
    Route::match(['put', 'patch'], 'support/knowledge-base/articles/{article_uuid}', [PlatformKnowledgeBaseArticleController::class, 'update'])->middleware('abilities:support.knowledge_base.edit');
    Route::post('support/knowledge-base/articles/{article_uuid}/publish', [PlatformKnowledgeBaseArticleController::class, 'publish'])->middleware('abilities:support.knowledge_base.publish');
    Route::post('support/knowledge-base/articles/{article_uuid}/unpublish', [PlatformKnowledgeBaseArticleController::class, 'unpublish'])->middleware('abilities:support.knowledge_base.publish');
    Route::post('support/knowledge-base/articles/{article_uuid}/archive', [PlatformKnowledgeBaseArticleController::class, 'archive'])->middleware('abilities:support.knowledge_base.edit');

    Route::middleware('abilities:monitoring.view')->group(function (): void {
        Route::get('monitoring/services', [PlatformMonitoringController::class, 'services']);
        Route::get('monitoring/services/{service_code}/logs', [PlatformMonitoringController::class, 'serviceLogs']);
        Route::get('monitoring/queue-jobs', [PlatformMonitoringController::class, 'queueJobs']);
        Route::get('monitoring/queue-jobs/{job_id}', [PlatformMonitoringController::class, 'showQueueJob'])->whereNumber('job_id');
        Route::get('monitoring/scheduler-logs', [PlatformMonitoringController::class, 'schedulerLogs']);
        Route::get('monitoring/api-request-logs', [PlatformMonitoringController::class, 'apiRequestLogs']);
        Route::get('monitoring/alerts', [PlatformMonitoringController::class, 'alerts']);
        Route::get('monitoring/incidents', [PlatformMonitoringController::class, 'incidents']);
        Route::get('monitoring/incidents/{incident_id}', [PlatformMonitoringController::class, 'showIncident']);
        Route::get('monitoring/tenant-usage-snapshots', [PlatformMonitoringController::class, 'usageSnapshots']);
    });
    Route::post('monitoring/queue-jobs/{job_id}/retry', [PlatformMonitoringController::class, 'retryQueueJob'])->middleware('abilities:monitoring.manage');
    Route::delete('monitoring/queue-jobs/{job_id}', [PlatformMonitoringController::class, 'deleteQueueJob'])->middleware('abilities:monitoring.manage');
    Route::post('monitoring/alerts/{alert_id}/resolve', [PlatformMonitoringController::class, 'resolveAlert'])->middleware('abilities:monitoring.manage');
    Route::post('monitoring/incidents', [PlatformMonitoringController::class, 'storeIncident'])->middleware('abilities:monitoring.manage');
    Route::match(['put', 'patch'], 'monitoring/incidents/{incident_id}', [PlatformMonitoringController::class, 'updateIncident'])->middleware('abilities:monitoring.manage');
    Route::post('monitoring/incidents/{incident_id}/resolve', [PlatformMonitoringController::class, 'resolveIncident'])->middleware('abilities:monitoring.manage');

    Route::get('providers', [PlatformIntegrationProviderController::class, 'index'])->middleware('abilities:integration.view');
    Route::post('providers', [PlatformIntegrationProviderController::class, 'store'])->middleware('abilities:integration.create');
    Route::match(['put', 'patch'], 'providers/{provider_code}', [PlatformIntegrationProviderController::class, 'update'])->middleware('abilities:integration.edit');

    Route::middleware('abilities:setting.view')->group(function (): void {
        Route::get('platform', [PlatformConfigurationController::class, 'platform']);
        Route::get('notification-templates', [PlatformConfigurationController::class, 'notificationTemplates']);
        Route::get('backups', [PlatformConfigurationController::class, 'backups']);
        Route::get('backups/runs', [PlatformConfigurationController::class, 'backupRuns']);
        Route::get('backups/runs/{run_uuid}/download', [PlatformConfigurationController::class, 'downloadBackup'])->name('platform.backups.download');
        Route::get('backups/runs/{run_uuid}', [PlatformConfigurationController::class, 'backupRun']);
    });
    Route::put('platform', [PlatformConfigurationController::class, 'updatePlatform'])->middleware('abilities:setting.edit');
    Route::post('notification-templates', [PlatformConfigurationController::class, 'storeNotificationTemplate'])->middleware('abilities:setting.edit');
    Route::match(['put', 'patch'], 'notification-templates/{template_uuid}', [PlatformConfigurationController::class, 'updateNotificationTemplate'])->middleware('abilities:setting.edit');
    Route::put('backups', [PlatformConfigurationController::class, 'updateBackups'])->middleware('abilities:setting.edit');
    Route::post('backups/run', [PlatformConfigurationController::class, 'runBackup'])->middleware('abilities:setting.edit');

    Route::get('audit/activity-logs', [PlatformAuditController::class, 'activityLogs'])->middleware('abilities:audit_log.view');
    Route::get('audit/security-events', [PlatformAuditController::class, 'securityEvents'])->middleware('abilities:audit_log.view');
    Route::post('audit/security-events/{event_id}/review', [PlatformAuditController::class, 'reviewSecurityEvent'])->middleware('abilities:audit_log.view');
    Route::post('audit/export', [PlatformAuditController::class, 'export'])->middleware('abilities:audit_log.export');

    Route::get('legal/documents', [PlatformLegalDocumentController::class, 'index'])->middleware('abilities:legal_document.view');
    Route::post('legal/documents', [PlatformLegalDocumentController::class, 'store'])->middleware('abilities:legal_document.create');
    Route::get('legal/documents/{document_uuid}/acceptances', [PlatformLegalDocumentController::class, 'acceptances'])->middleware('abilities:legal_document.view');
    Route::get('legal/documents/{document_uuid}', [PlatformLegalDocumentController::class, 'show'])->middleware('abilities:legal_document.view');
    Route::match(['put', 'patch'], 'legal/documents/{document_uuid}', [PlatformLegalDocumentController::class, 'update'])->middleware('abilities:legal_document.edit');
    Route::post('legal/documents/{document_uuid}/publish', [PlatformLegalDocumentController::class, 'publish'])->middleware('abilities:legal_document.publish');
    
    
    Route::get('announcements', [PlatformAnnouncementController::class, 'index'])->middleware('abilities:announcement.view');
    Route::post('announcements', [PlatformAnnouncementController::class, 'store'])->middleware('abilities:announcement.create');
    Route::get('announcements/{announcement_uuid}', [PlatformAnnouncementController::class, 'show'])->middleware('abilities:announcement.view');
    Route::match(['put', 'patch'], 'announcements/{announcement_uuid}', [PlatformAnnouncementController::class, 'update'])->middleware('abilities:announcement.edit');
    Route::post('announcements/{announcement_uuid}/publish', [PlatformAnnouncementController::class, 'publish'])->middleware('abilities:announcement.publish');
    Route::post('announcements/{announcement_uuid}/archive', [PlatformAnnouncementController::class, 'archive'])->middleware('abilities:announcement.delete');
    Route::delete('announcements/{announcement_uuid}', [PlatformAnnouncementController::class, 'destroy'])->middleware('abilities:announcement.delete');

    Route::get('webhook-endpoints', [PlatformWebhookController::class, 'endpoints'])->middleware('abilities:integration.view');
    Route::post('webhook-endpoints', [PlatformWebhookController::class, 'store'])->middleware('abilities:integration.create');
    Route::get('webhook-endpoints/{endpoint_uuid}/deliveries', [PlatformWebhookController::class, 'deliveries'])->middleware('abilities:integration.view');
    Route::get('webhook-endpoints/{endpoint_uuid}', [PlatformWebhookController::class, 'show'])->middleware('abilities:integration.view');
    Route::match(['put', 'patch'], 'webhook-endpoints/{endpoint_uuid}', [PlatformWebhookController::class, 'update'])->middleware('abilities:integration.edit');
    Route::delete('webhook-endpoints/{endpoint_uuid}', [PlatformWebhookController::class, 'destroy'])->middleware('abilities:integration.delete');
    Route::get('webhook-deliveries/{delivery_uuid}', [PlatformWebhookController::class, 'delivery'])->middleware('abilities:integration.view');
    Route::post('webhook-deliveries/{delivery_uuid}/retry', [PlatformWebhookController::class, 'retry'])->middleware('abilities:integration.edit');

    Route::get('api-tokens', [PlatformApiTokenController::class, 'index'])->middleware('abilities:api_token.view');
    Route::post('api-tokens', [PlatformApiTokenController::class, 'store'])->middleware('abilities:api_token.create');
    Route::get('api-tokens/{token_uuid}', [PlatformApiTokenController::class, 'show'])->middleware('abilities:api_token.view');
    Route::post('api-tokens/{token_uuid}/rotate', [PlatformApiTokenController::class, 'rotate'])->middleware('abilities:api_token.rotate');
    Route::post('api-tokens/{token_uuid}/revoke', [PlatformApiTokenController::class, 'revoke'])->middleware('abilities:api_token.revoke');

    Route::middleware('abilities:document.view')->group(function (): void {
        Route::get('files', [PlatformDocumentController::class, 'files']);
        Route::get('files/{file_uuid}', [PlatformDocumentController::class, 'showFile']);
        Route::get('files/{file_uuid}/download', [PlatformDocumentController::class, 'downloadFile']);
        Route::get('attachments', [PlatformDocumentController::class, 'attachments']);
        Route::get('notes', [PlatformDocumentController::class, 'notes']);
    });
    Route::post('files', [PlatformDocumentController::class, 'storeFile'])->middleware('abilities:document.upload');
    Route::delete('files/{file_uuid}', [PlatformDocumentController::class, 'destroyFile'])->middleware('abilities:document.delete');
    Route::post('attachments', [PlatformDocumentController::class, 'storeAttachment'])->middleware('abilities:document.upload');
    Route::delete('attachments/{attachment_id}', [PlatformDocumentController::class, 'destroyAttachment'])->whereNumber('attachment_id')->middleware('abilities:document.delete');
    Route::post('notes', [PlatformDocumentController::class, 'storeNote'])->middleware('abilities:document.upload');
    Route::match(['put', 'patch'], 'notes/{note_uuid}', [PlatformDocumentController::class, 'updateNote'])->middleware('abilities:document.upload');
    Route::delete('notes/{note_uuid}', [PlatformDocumentController::class, 'destroyNote'])->middleware('abilities:document.delete');
    Route::get('activity-logs', [PlatformAuditController::class, 'platformActivityLogs'])->middleware('abilities:audit_log.view');
    Route::get('activity-logs/{activity_id}/compare', [PlatformAuditController::class, 'compareActivity'])->whereNumber('activity_id')->middleware('abilities:audit_log.view');

    Route::middleware('abilities:integration.view')->group(function (): void {
        Route::get('tenant-integrations', [App\Http\Controllers\PlatformIntegrationController::class, 'index']);
        Route::get('tenant-integrations/{integration_uuid}', [App\Http\Controllers\PlatformIntegrationController::class, 'show']);
        Route::get('tenant-integrations/{integration_uuid}/mappings', [App\Http\Controllers\PlatformIntegrationController::class, 'mappings']);
        Route::get('tenant-integrations/{integration_uuid}/rate-limits', [App\Http\Controllers\PlatformIntegrationController::class, 'rateLimits']);
        Route::get('webhooks', [App\Http\Controllers\PlatformIntegrationController::class, 'webhookIndex']);
        Route::get('webhooks/{webhook_id}', [App\Http\Controllers\PlatformIntegrationController::class, 'webhookShow'])->whereNumber('webhook_id');
        Route::get('webhooks/{webhook_id}/logs', [App\Http\Controllers\PlatformIntegrationController::class, 'logs'])->whereNumber('webhook_id');
        Route::get('sync-jobs', [App\Http\Controllers\PlatformIntegrationController::class, 'syncJobs']);
    });
    Route::post('tenant-integrations', [App\Http\Controllers\PlatformIntegrationController::class, 'store'])->middleware('abilities:integration.create');
    Route::match(['put', 'patch'], 'tenant-integrations/{integration_uuid}', [App\Http\Controllers\PlatformIntegrationController::class, 'update'])->middleware('abilities:integration.edit');
    Route::post('tenant-integrations/{integration_uuid}/credentials', [App\Http\Controllers\PlatformIntegrationController::class, 'credentials'])->middleware('abilities:integration.edit');
    Route::post('tenant-integrations/{integration_uuid}/test', [App\Http\Controllers\PlatformIntegrationController::class, 'test'])->middleware('abilities:integration.test');
    Route::post('tenant-integrations/{integration_uuid}/disconnect', [App\Http\Controllers\PlatformIntegrationController::class, 'disconnect'])->middleware('abilities:integration.edit');
    Route::put('tenant-integrations/{integration_uuid}/mappings', [App\Http\Controllers\PlatformIntegrationController::class, 'updateMappings'])->middleware('abilities:integration.edit');
    Route::post('webhooks', [App\Http\Controllers\PlatformIntegrationController::class, 'webhookStore'])->middleware('abilities:integration.create');
    Route::match(['put', 'patch'], 'webhooks/{webhook_id}', [App\Http\Controllers\PlatformIntegrationController::class, 'webhookUpdate'])->whereNumber('webhook_id')->middleware('abilities:integration.edit');
    Route::delete('webhooks/{webhook_id}', [App\Http\Controllers\PlatformIntegrationController::class, 'webhookDelete'])->whereNumber('webhook_id')->middleware('abilities:integration.delete');
    Route::post('webhook-logs/{log_id}/retry', [App\Http\Controllers\PlatformIntegrationController::class, 'retryLog'])->whereNumber('log_id')->middleware('abilities:integration.edit');
    Route::post('sync-jobs/{job_id}/retry', [App\Http\Controllers\PlatformIntegrationController::class, 'retryJob'])->whereNumber('job_id')->middleware('abilities:integration.edit');
    Route::middleware('abilities:tenant.impersonate')->group(function (): void {
        Route::get('remote-login-sessions', [App\Http\Controllers\PlatformSupportController::class, 'sessions']);
        Route::get('remote-login-sessions/{session_uuid}', [App\Http\Controllers\PlatformSupportController::class, 'show']);
        Route::post('remote-login-sessions/{session_uuid}/end', [App\Http\Controllers\PlatformSupportController::class, 'end']);
    });
    Route::middleware('abilities:report.view')->group(function (): void {
        Route::get('reports/export-jobs', [App\Http\Controllers\PlatformReportsController::class, 'jobs']);
        Route::get('reports/export-jobs/{job_uuid}', [App\Http\Controllers\PlatformReportsController::class, 'job']);
        Route::get('reports/{report_code}', [App\Http\Controllers\PlatformReportsController::class, 'index']);
    });
    Route::post('reports/{report_code}/export', [App\Http\Controllers\PlatformReportsController::class, 'export'])->middleware('abilities:report.export');
    Route::get('tenants', [App\Http\Controllers\PlatformTenantController::class, 'index'])->middleware('abilities:tenant.view');
    Route::post('tenants', [App\Http\Controllers\PlatformTenantController::class, 'store'])->middleware('abilities:tenant.create');
    Route::get('tenants/{tenant_uuid}', [App\Http\Controllers\PlatformTenantController::class, 'show'])->middleware('abilities:tenant.view');
    Route::match(['put', 'patch'], 'tenants/{tenant_uuid}', [App\Http\Controllers\PlatformTenantController::class, 'update'])->middleware('abilities:tenant.edit');
    Route::delete('tenants/bulk', [App\Http\Controllers\PlatformTenantController::class, 'bulkDestroy'])->middleware('abilities:tenant.delete');
    Route::delete('tenants/{tenant_uuid}', [App\Http\Controllers\PlatformTenantController::class, 'destroy'])->middleware('abilities:tenant.delete');
    Route::post('tenants/{tenant_uuid}/restore', [App\Http\Controllers\PlatformTenantController::class, 'restore'])->middleware('abilities:tenant.edit');
    Route::post('tenants/{tenant_uuid}/activate', [App\Http\Controllers\PlatformTenantController::class, 'activate'])->middleware('abilities:tenant.activate');
    Route::post('tenants/{tenant_uuid}/suspend', [App\Http\Controllers\PlatformTenantController::class, 'suspend'])->middleware('abilities:tenant.suspend');
    Route::post('tenants/{tenant_uuid}/reactivate', [App\Http\Controllers\PlatformTenantController::class, 'reactivate'])->middleware('abilities:tenant.activate');
    Route::post('tenants/{tenant_uuid}/archive', [App\Http\Controllers\PlatformTenantController::class, 'archive'])->middleware('abilities:tenant.delete');
    Route::post('tenants/{tenant_uuid}/extend-trial', [App\Http\Controllers\PlatformTenantController::class, 'extendTrial'])->middleware('abilities:subscription.edit');
    Route::get('onboarding/tenants', [App\Http\Controllers\PlatformTenantController::class, 'onboardingIndex'])->middleware('abilities:tenant.view');
    Route::get('onboarding/tenants/{tenant_uuid}', [App\Http\Controllers\PlatformTenantController::class, 'onboardingShow'])->middleware('abilities:tenant.view');
    Route::put('onboarding/tenants/{tenant_uuid}/steps/{step_code}', [App\Http\Controllers\PlatformTenantController::class, 'updateOnboardingStep'])->middleware('abilities:tenant.edit');
    Route::get('trials', [App\Http\Controllers\PlatformTenantController::class, 'trialIndex'])->middleware('abilities:tenant.view');
    Route::post('trials/{tenant_uuid}/extend', [App\Http\Controllers\PlatformTenantController::class, 'extendTrial'])->middleware('abilities:subscription.edit');
    Route::post('trials/{tenant_uuid}/convert', [App\Http\Controllers\PlatformTenantController::class, 'convertTrial'])->middleware('abilities:subscription.edit');

    Route::post('tenants/{tenant_uuid}/change-plan', [App\Http\Controllers\PlatformTenantController::class, 'changePlan'])->middleware('abilities:subscription.edit');
    Route::post('tenants/{tenant_uuid}/reset-owner-password', [App\Http\Controllers\PlatformTenantController::class, 'resetOwnerPassword'])->middleware('abilities:tenant.edit');
    Route::post('tenants/{tenant_uuid}/payment-order', [App\Http\Controllers\PlatformTenantController::class, 'paymentOrder'])->middleware('abilities:billing.payment.create');
    Route::post('tenants/{tenant_uuid}/impersonate', [App\Http\Controllers\PlatformTenantController::class, 'impersonate'])->middleware('abilities:tenant.impersonate');
    Route::delete('tenants/{tenant_uuid}/impersonate/{session_uuid}', [App\Http\Controllers\PlatformTenantController::class, 'endImpersonation'])->middleware('abilities:tenant.impersonate');
    Route::put('tenants/{tenant_uuid}/modules', [App\Http\Controllers\PlatformTenantController::class, 'modules'])->middleware('abilities:module.edit');
    Route::get('tenants/{tenant_uuid}/module-entitlements', [App\Http\Controllers\PlatformTenantController::class, 'moduleEntitlements'])->middleware('abilities:module.view');
    Route::put('tenants/{tenant_uuid}/modules/{module_code}', [App\Http\Controllers\PlatformTenantController::class, 'upsertModuleOverride'])->middleware('abilities:module.edit');
    Route::get('tenants/{tenant_uuid}/{tab}', [App\Http\Controllers\PlatformTenantController::class, 'tab'])->whereIn('tab', ['users', 'offices', 'subscription', 'billing', 'usage', 'modules', 'settings', 'integrations', 'security', 'support', 'files', 'activity'])->middleware('abilities:tenant.view');

    
    Route::middleware('abilities:subscription.view')->group(function (): void {
        Route::get('subscriptions', [PlatformSubscriptionController::class, 'index']);
        Route::get('subscriptions/{subscription_uuid}/usage', [PlatformSubscriptionController::class, 'usage']);
        Route::get('subscriptions/{subscription_uuid}/history', [PlatformSubscriptionController::class, 'history']);
    });
    Route::post('subscriptions', [PlatformSubscriptionController::class, 'store'])->middleware('abilities:subscription.create');
    Route::post('subscriptions/export', [PlatformSubscriptionController::class, 'export'])->middleware('abilities:subscription.view');
    Route::match(['put', 'patch'], 'subscriptions/{subscription_uuid}', [PlatformSubscriptionController::class, 'update'])->middleware('abilities:subscription.edit');
    Route::post('subscriptions/{subscription_uuid}/upgrade', [PlatformSubscriptionController::class, 'lifecycle'])->defaults('action', 'upgrade')->middleware('abilities:subscription.edit');
    Route::post('subscriptions/{subscription_uuid}/downgrade', [PlatformSubscriptionController::class, 'lifecycle'])->defaults('action', 'downgrade')->middleware('abilities:subscription.edit');
    Route::post('subscriptions/{subscription_uuid}/renew', [PlatformSubscriptionController::class, 'lifecycle'])->defaults('action', 'renew')->middleware('abilities:subscription.edit');
    Route::post('subscriptions/{subscription_uuid}/pause', [PlatformSubscriptionController::class, 'lifecycle'])->defaults('action', 'pause')->middleware('abilities:subscription.edit');
    Route::post('subscriptions/{subscription_uuid}/resume', [PlatformSubscriptionController::class, 'lifecycle'])->defaults('action', 'resume')->middleware('abilities:subscription.edit');
    Route::post('subscriptions/{subscription_uuid}/cancel', [PlatformSubscriptionController::class, 'lifecycle'])->defaults('action', 'cancel')->middleware('abilities:subscription.edit');
    Route::post('subscriptions/{subscription_uuid}/addons', [PlatformSubscriptionController::class, 'addAddon'])->middleware('abilities:subscription.edit');
    Route::match(['put', 'patch'], 'subscriptions/{subscription_uuid}/addons/{addon_id}', [PlatformSubscriptionController::class, 'updateAddon'])->middleware('abilities:subscription.edit');
    Route::delete('subscriptions/{subscription_uuid}/addons/{addon_id}', [PlatformSubscriptionController::class, 'removeAddon'])->middleware('abilities:subscription.edit');
    Route::post('subscriptions/{subscription_uuid}/apply-coupon', [PlatformSubscriptionController::class, 'applyCoupon'])->middleware('abilities:subscription.edit');
    Route::delete('subscriptions/{subscription_uuid}/coupons/{coupon_uuid}', [PlatformSubscriptionController::class, 'removeCoupon'])->middleware('abilities:subscription.edit');
    Route::post('subscriptions/{subscription_uuid}/invoice', [PlatformSubscriptionController::class, 'invoice'])->middleware('abilities:subscription.edit');
    Route::get('subscriptions/{subscription_uuid}', [PlatformSubscriptionController::class, 'show']);

    
    Route::middleware('abilities:billing.invoice.view')->group(function (): void {
        Route::get('billing/invoices', [PlatformInvoiceController::class, 'index']);
        Route::get('billing/invoices/{invoice_uuid}', [PlatformInvoiceController::class, 'show']);
        Route::get('billing/invoices/{invoice_uuid}/pdf', [PlatformInvoiceController::class, 'pdf']);
    });
    Route::post('billing/invoices', [PlatformInvoiceController::class, 'store'])->middleware('abilities:billing.invoice.create');
    Route::post('billing/invoices/export', [PlatformInvoiceController::class, 'export'])->middleware('abilities:billing.invoice.view');
    Route::match(['put', 'patch'], 'billing/invoices/{invoice_uuid}', [PlatformInvoiceController::class, 'update'])->middleware('abilities:billing.invoice.edit');
    Route::delete('billing/invoices/{invoice_uuid}', [PlatformInvoiceController::class, 'destroy'])->middleware('abilities:billing.invoice.cancel');
    Route::post('billing/invoices/{invoice_uuid}/send', [PlatformInvoiceController::class, 'send'])->middleware('abilities:billing.invoice.send');
    Route::post('billing/invoices/{invoice_uuid}/payments', [PlatformInvoiceController::class, 'payment'])->middleware('abilities:billing.payment.create');

    Route::middleware('abilities:billing.payment.view')->group(function (): void {
        Route::get('billing/payments', [PlatformPaymentController::class, 'index']);
        Route::get('billing/payments/{payment_uuid}', [PlatformPaymentController::class, 'show']);
        Route::get('billing/refunds', [PlatformRefundController::class, 'index']);
        Route::get('billing/refunds/{refund_uuid}', [PlatformRefundController::class, 'show']);
    });
    Route::post('billing/payments', [PlatformPaymentController::class, 'store'])->middleware('abilities:billing.payment.create');
    Route::post('billing/payments/export', [PlatformPaymentController::class, 'export'])->middleware('abilities:billing.payment.view');
    Route::post('billing/payments/{payment_uuid}/retry', [PlatformPaymentController::class, 'retry'])->middleware('abilities:billing.payment.create');
    Route::post('billing/payments/{payment_uuid}/reconcile', [PlatformPaymentController::class, 'reconcile'])->middleware('abilities:billing.payment.create');
    Route::post('billing/payments/{payment_uuid}/refund', [PlatformPaymentController::class, 'refund'])->middleware('abilities:billing.payment.refund');
    Route::post('billing/refunds', [PlatformRefundController::class, 'store'])->middleware('abilities:billing.payment.refund');
    Route::post('billing/refunds/export', [PlatformRefundController::class, 'export'])->middleware('abilities:billing.payment.view');
    Route::post('billing/refunds/{refund_uuid}/retry', [PlatformRefundController::class, 'retry'])->middleware('abilities:billing.payment.refund');


    
});
