<?php

use App\Http\Controllers\Platform\PlatformDashboardController;
use App\Http\Controllers\Platform\PlatformBillingController;
use App\Http\Controllers\Platform\PlatformCatalogController;
use App\Http\Controllers\Platform\PlatformCouponController;
use App\Http\Controllers\Platform\PlatformModuleController;
use App\Http\Controllers\Platform\PlatformSupportController;
use App\Http\Controllers\Platform\PlatformMonitoringController;
use App\Http\Controllers\Platform\PlatformReportsController;
use App\Http\Controllers\Platform\PlatformIntegrationController;
use App\Http\Controllers\Platform\PlatformSettingsAuditController;
use App\Http\Controllers\Platform\PlatformStaffController;
use App\Http\Controllers\Platform\PlatformSubscriptionController;
use App\Http\Controllers\Platform\PlatformTeamController;
use App\Http\Controllers\Platform\PlatformTenantController;
use App\Http\Controllers\Platform\PlatformApiTokenController;
use App\Http\Controllers\Platform\PlatformPermissionController;
use App\Http\Controllers\Platform\PlatformRoleController;
use App\Http\Controllers\Platform\PlatformAuthController;
use App\Http\Controllers\Platform\PlatformHealthController;
use App\Http\Controllers\Shared\SharedPrimitiveController;
use Illuminate\Support\Facades\Route;

Route::get('/health', PlatformHealthController::class)->name('health');

Route::prefix('auth')->name('auth.')->group(function (): void {
    Route::post('/forgot-password', [PlatformAuthController::class, 'forgotPassword'])->name('forgot-password');
    Route::post('/reset-password', [PlatformAuthController::class, 'resetPassword'])->name('reset-password');
});

Route::middleware(['auth:sanctum', 'platform.token'])->group(function (): void {
    Route::prefix('auth')->name('auth.')->group(function (): void {
        Route::post('/logout', [PlatformAuthController::class, 'logout'])->name('logout');
        Route::post('/refresh', [PlatformAuthController::class, 'refresh'])->name('refresh');
        Route::get('/me', [PlatformAuthController::class, 'me'])->name('me');
        Route::post('/verify-email/resend', [PlatformAuthController::class, 'resendVerification'])->name('verify-email.resend');
        Route::post('/2fa/enable', [PlatformAuthController::class, 'enable2fa'])->name('2fa.enable');
        Route::post('/2fa/confirm', [PlatformAuthController::class, 'confirm2fa'])->name('2fa.confirm');
        Route::post('/2fa/disable', [PlatformAuthController::class, 'disable2fa'])->name('2fa.disable');
    });

    Route::get('/profile', [PlatformAuthController::class, 'profile'])->name('profile.show');
    Route::match(['put', 'patch'], '/profile', [PlatformAuthController::class, 'updateProfile'])->name('profile.update');
    Route::put('/profile/password', [PlatformAuthController::class, 'changePassword'])->name('profile.password');
    Route::get('/settings/preferences', [PlatformAuthController::class, 'preferences'])->name('settings.preferences.show');
    Route::put('/settings/preferences', [PlatformAuthController::class, 'updatePreferences'])->name('settings.preferences.update');
    Route::get('/profile/sessions', [PlatformAuthController::class, 'sessions'])->name('profile.sessions.index');
    Route::delete('/profile/sessions/{session_id}', [PlatformAuthController::class, 'revokeSession'])->whereNumber('session_id')->name('profile.sessions.revoke');



    Route::prefix('dashboard')->name('dashboard.')->group(function (): void {
        Route::get('/summary', [PlatformDashboardController::class, 'summary'])->middleware('platform.permission:dashboard.view')->name('summary');
        Route::get('/charts', [PlatformDashboardController::class, 'charts'])->middleware('platform.permission:dashboard.view')->name('charts');
        Route::get('/recent', [PlatformDashboardController::class, 'recent'])->middleware('platform.permission:dashboard.view')->name('recent');
        Route::get('/alerts', [PlatformDashboardController::class, 'alerts'])->middleware('platform.permission:dashboard.view')->name('alerts');
        Route::post('/export', [PlatformDashboardController::class, 'export'])->middleware('platform.permission:dashboard.view')->name('export');
    });

    Route::post('/platform-users/export', [PlatformStaffController::class, 'export'])->middleware('platform.permission:platform_user.view')->name('platform-users.export');
    Route::post('/platform-users/invite', [PlatformStaffController::class, 'invite'])->middleware('platform.permission:platform_user.create')->name('platform-users.invite');
    Route::get('/platform-users', [PlatformStaffController::class, 'index'])->middleware('platform.permission:platform_user.view')->name('platform-users.index');
    Route::post('/platform-users', [PlatformStaffController::class, 'store'])->middleware('platform.permission:platform_user.create')->name('platform-users.store');
    Route::get('/platform-users/{platform_user_uuid}', [PlatformStaffController::class, 'show'])->middleware('platform.permission:platform_user.view')->name('platform-users.show');
    Route::match(['put', 'patch'], '/platform-users/{platform_user_uuid}', [PlatformStaffController::class, 'update'])->middleware('platform.permission:platform_user.edit')->name('platform-users.update');
    Route::delete('/platform-users/{platform_user_uuid}', [PlatformStaffController::class, 'destroy'])->middleware('platform.permission:platform_user.delete')->name('platform-users.destroy');
    Route::post('/platform-users/{platform_user_uuid}/restore', [PlatformStaffController::class, 'restore'])->middleware('platform.permission:platform_user.edit')->name('platform-users.restore');
    Route::post('/platform-users/{platform_user_uuid}/suspend', [PlatformStaffController::class, 'suspend'])->middleware('platform.permission:platform_user.suspend')->name('platform-users.suspend');
    Route::post('/platform-users/{platform_user_uuid}/activate', [PlatformStaffController::class, 'activate'])->middleware('platform.permission:platform_user.edit')->name('platform-users.activate');
    Route::post('/platform-users/{platform_user_uuid}/reset-password', [PlatformStaffController::class, 'resetPassword'])->middleware('platform.permission:platform_user.edit')->name('platform-users.reset-password');
    Route::post('/platform-users/{platform_user_uuid}/force-logout', [PlatformStaffController::class, 'forceLogout'])->middleware('platform.permission:platform_user.edit')->name('platform-users.force-logout');
    Route::post('/platform-users/{platform_user_uuid}/require-2fa', [PlatformStaffController::class, 'require2fa'])->middleware('platform.permission:platform_user.edit')->name('platform-users.require-2fa');
    Route::get('/platform-users/{platform_user_uuid}/roles', [PlatformStaffController::class, 'roles'])->middleware('platform.permission:platform_user.view')->name('platform-users.roles');
    Route::put('/platform-users/{platform_user_uuid}/roles', [PlatformStaffController::class, 'syncRoles'])->middleware('platform.permission:platform_user.edit')->name('platform-users.roles.sync');
    Route::get('/platform-users/{platform_user_uuid}/permissions', [PlatformStaffController::class, 'permissions'])->middleware('platform.permission:platform_user.view')->name('platform-users.permissions');
    Route::put('/platform-users/{platform_user_uuid}/permissions', [PlatformStaffController::class, 'syncPermissions'])->middleware('platform.permission:platform_user.edit')->name('platform-users.permissions.sync');
    Route::get('/platform-users/{platform_user_uuid}/activity', [PlatformStaffController::class, 'activity'])->middleware('platform.permission:audit_log.view')->name('platform-users.activity');

    Route::get('/platform-teams', [PlatformTeamController::class, 'index'])->middleware('platform.permission:platform_team.view')->name('platform-teams.index');
    Route::post('/platform-teams', [PlatformTeamController::class, 'store'])->middleware('platform.permission:platform_team.create')->name('platform-teams.store');
    Route::get('/platform-teams/{team_uuid}', [PlatformTeamController::class, 'show'])->middleware('platform.permission:platform_team.view')->name('platform-teams.show');
    Route::match(['put', 'patch'], '/platform-teams/{team_uuid}', [PlatformTeamController::class, 'update'])->middleware('platform.permission:platform_team.edit')->name('platform-teams.update');
    Route::delete('/platform-teams/{team_uuid}', [PlatformTeamController::class, 'destroy'])->middleware('platform.permission:platform_team.delete')->name('platform-teams.destroy');
    Route::get('/platform-teams/{team_uuid}/members', [PlatformTeamController::class, 'members'])->middleware('platform.permission:platform_team.view')->name('platform-teams.members');
    Route::post('/platform-teams/{team_uuid}/members', [PlatformTeamController::class, 'addMember'])->middleware('platform.permission:platform_team.assign')->name('platform-teams.members.store');
    Route::match(['put', 'patch'], '/platform-teams/{team_uuid}/members/{member_id}', [PlatformTeamController::class, 'updateMember'])->whereNumber('member_id')->middleware('platform.permission:platform_team.assign')->name('platform-teams.members.update');
    Route::delete('/platform-teams/{team_uuid}/members/{member_id}', [PlatformTeamController::class, 'removeMember'])->whereNumber('member_id')->middleware('platform.permission:platform_team.assign')->name('platform-teams.members.destroy');
    Route::get('/platform-teams/{team_uuid}/assignments', [PlatformTeamController::class, 'assignments'])->middleware('platform.permission:platform_team.view')->name('platform-teams.assignments');
    Route::post('/platform-teams/{team_uuid}/assignments', [PlatformTeamController::class, 'assign'])->middleware('platform.permission:platform_team.assign')->name('platform-teams.assignments.store');
    Route::delete('/platform-teams/{team_uuid}/assignments/{assignment_id}', [PlatformTeamController::class, 'releaseAssignment'])->whereNumber('assignment_id')->middleware('platform.permission:platform_team.assign')->name('platform-teams.assignments.destroy');
    Route::get('/platform-team-roles', [PlatformTeamController::class, 'teamRoles'])->middleware('platform.permission:platform_team.view')->name('platform-team-roles.index');
    Route::post('/platform-team-roles', [PlatformTeamController::class, 'createTeamRole'])->middleware('platform.permission:platform_team.create')->name('platform-team-roles.store');
    Route::match(['put', 'patch'], '/platform-team-roles/{role_uuid}', [PlatformTeamController::class, 'updateTeamRole'])->middleware('platform.permission:platform_team.edit')->name('platform-team-roles.update');

    Route::get('/tenants', [PlatformTenantController::class, 'index'])->middleware('platform.permission:tenant.view')->name('tenants.index');
    Route::post('/tenants', [PlatformTenantController::class, 'store'])->middleware('platform.permission:tenant.create')->name('tenants.store');
    Route::get('/tenants/{tenant_uuid}', [PlatformTenantController::class, 'show'])->middleware('platform.permission:tenant.view')->name('tenants.show');
    Route::match(['put', 'patch'], '/tenants/{tenant_uuid}', [PlatformTenantController::class, 'update'])->middleware('platform.permission:tenant.edit')->name('tenants.update');
    Route::delete('/tenants/{tenant_uuid}', [PlatformTenantController::class, 'destroy'])->middleware('platform.permission:tenant.delete')->name('tenants.destroy');
    Route::post('/tenants/{tenant_uuid}/restore', [PlatformTenantController::class, 'restore'])->middleware('platform.permission:tenant.edit')->name('tenants.restore');
    Route::post('/tenants/{tenant_uuid}/activate', [PlatformTenantController::class, 'activate'])->middleware('platform.permission:tenant.activate')->name('tenants.activate');
    Route::post('/tenants/{tenant_uuid}/suspend', [PlatformTenantController::class, 'suspend'])->middleware('platform.permission:tenant.suspend')->name('tenants.suspend');
    Route::post('/tenants/{tenant_uuid}/reactivate', [PlatformTenantController::class, 'reactivate'])->middleware('platform.permission:tenant.activate')->name('tenants.reactivate');
    Route::post('/tenants/{tenant_uuid}/archive', [PlatformTenantController::class, 'archive'])->middleware('platform.permission:tenant.delete')->name('tenants.archive');
    Route::post('/tenants/{tenant_uuid}/extend-trial', [PlatformTenantController::class, 'extendTrial'])->middleware('platform.permission:subscription.edit')->name('tenants.extend-trial');
    Route::post('/tenants/{tenant_uuid}/impersonate', [PlatformTenantController::class, 'remoteLogin'])->middleware('platform.permission:tenant.impersonate')->name('tenants.impersonate');
    Route::delete('/tenants/{tenant_uuid}/impersonate/{session_uuid}', [PlatformTenantController::class, 'endRemoteLogin'])->middleware('platform.permission:tenant.impersonate')->name('tenants.impersonate.end');
    Route::get('/tenants/{tenant_uuid}/{tab}', [PlatformTenantController::class, 'tab'])->whereIn('tab', ['users','offices','subscription','billing','usage','modules','settings','integrations','security','support','files','activity'])->middleware('platform.permission:tenant.view')->name('tenants.tab');
    Route::put('/tenants/{tenant_uuid}/modules', [PlatformTenantController::class, 'moduleOverrides'])->middleware('platform.permission:module.edit')->name('tenants.modules.update');
    Route::get('/subscriptions', [PlatformSubscriptionController::class, 'index'])->middleware('platform.permission:subscription.view')->name('subscriptions.index');
    Route::post('/subscriptions', [PlatformSubscriptionController::class, 'store'])->middleware('platform.permission:subscription.create')->name('subscriptions.store');
    Route::post('/subscriptions/export', [PlatformSubscriptionController::class, 'export'])->middleware('platform.permission:subscription.view')->name('subscriptions.export');
    Route::get('/subscriptions/{subscription_uuid}', [PlatformSubscriptionController::class, 'show'])->middleware('platform.permission:subscription.view')->name('subscriptions.show');
    Route::match(['put', 'patch'], '/subscriptions/{subscription_uuid}', [PlatformSubscriptionController::class, 'update'])->middleware('platform.permission:subscription.edit')->name('subscriptions.update');
    Route::post('/subscriptions/{subscription_uuid}/upgrade', [PlatformSubscriptionController::class, 'upgrade'])->middleware('platform.permission:subscription.upgrade')->name('subscriptions.upgrade');
    Route::post('/subscriptions/{subscription_uuid}/downgrade', [PlatformSubscriptionController::class, 'downgrade'])->middleware('platform.permission:subscription.downgrade')->name('subscriptions.downgrade');
    Route::post('/subscriptions/{subscription_uuid}/renew', [PlatformSubscriptionController::class, 'renew'])->middleware('platform.permission:subscription.renew')->name('subscriptions.renew');
    Route::post('/subscriptions/{subscription_uuid}/pause', [PlatformSubscriptionController::class, 'pause'])->middleware('platform.permission:subscription.edit')->name('subscriptions.pause');
    Route::post('/subscriptions/{subscription_uuid}/resume', [PlatformSubscriptionController::class, 'resume'])->middleware('platform.permission:subscription.edit')->name('subscriptions.resume');
    Route::post('/subscriptions/{subscription_uuid}/cancel', [PlatformSubscriptionController::class, 'cancel'])->middleware('platform.permission:subscription.cancel')->name('subscriptions.cancel');
    Route::post('/subscriptions/{subscription_uuid}/addons', [PlatformSubscriptionController::class, 'addAddon'])->middleware('platform.permission:subscription.edit')->name('subscriptions.addons.store');
    Route::match(['put', 'patch'], '/subscriptions/{subscription_uuid}/addons/{addon_id}', [PlatformSubscriptionController::class, 'updateAddon'])->whereNumber('addon_id')->middleware('platform.permission:subscription.edit')->name('subscriptions.addons.update');
    Route::delete('/subscriptions/{subscription_uuid}/addons/{addon_id}', [PlatformSubscriptionController::class, 'removeAddon'])->whereNumber('addon_id')->middleware('platform.permission:subscription.edit')->name('subscriptions.addons.destroy');
    Route::post('/subscriptions/{subscription_uuid}/apply-coupon', [PlatformSubscriptionController::class, 'applyCoupon'])->middleware('platform.permission:subscription.edit')->name('subscriptions.coupons.apply');
    Route::delete('/subscriptions/{subscription_uuid}/coupons/{coupon_uuid}', [PlatformSubscriptionController::class, 'removeCoupon'])->middleware('platform.permission:subscription.edit')->name('subscriptions.coupons.remove');
    Route::get('/subscriptions/{subscription_uuid}/usage', [PlatformSubscriptionController::class, 'usage'])->middleware('platform.permission:subscription.view')->name('subscriptions.usage');
    Route::post('/subscriptions/{subscription_uuid}/invoice', [PlatformSubscriptionController::class, 'createInvoice'])->middleware('platform.permission:billing.invoice.create')->name('subscriptions.invoice');
    Route::get('/subscriptions/{subscription_uuid}/history', [PlatformSubscriptionController::class, 'history'])->middleware('platform.permission:subscription.view')->name('subscriptions.history');

    Route::get('/plans', [PlatformCatalogController::class, 'plans'])->middleware('platform.permission:plan.view')->name('plans.index');
    Route::post('/plans', [PlatformCatalogController::class, 'storePlan'])->middleware('platform.permission:plan.create')->name('plans.store');
    Route::post('/plans/export', [PlatformCatalogController::class, 'exportPlans'])->middleware('platform.permission:plan.view')->name('plans.export');
    Route::get('/plans/{plan_uuid}', [PlatformCatalogController::class, 'showPlan'])->middleware('platform.permission:plan.view')->name('plans.show');
    Route::match(['put', 'patch'], '/plans/{plan_uuid}', [PlatformCatalogController::class, 'updatePlan'])->middleware('platform.permission:plan.edit')->name('plans.update');
    Route::delete('/plans/{plan_uuid}', [PlatformCatalogController::class, 'archivePlan'])->middleware('platform.permission:plan.delete')->name('plans.destroy');
    Route::post('/plans/{plan_uuid}/clone', [PlatformCatalogController::class, 'clonePlan'])->middleware('platform.permission:plan.create')->name('plans.clone');
    Route::get('/plans/{plan_uuid}/features', [PlatformCatalogController::class, 'planFeatures'])->middleware('platform.permission:plan.view')->name('plans.features');
    Route::put('/plans/{plan_uuid}/features', [PlatformCatalogController::class, 'replacePlanFeatures'])->middleware('platform.permission:plan.edit')->name('plans.features.update');
    Route::get('/plans/{plan_uuid}/subscriptions', [PlatformCatalogController::class, 'planSubscriptions'])->middleware('platform.permission:plan.view')->name('plans.subscriptions');
    Route::get('/features', [PlatformCatalogController::class, 'features'])->middleware('platform.permission:feature.view')->name('features.index');
    Route::post('/features', [PlatformCatalogController::class, 'storeFeature'])->middleware('platform.permission:feature.create')->name('features.store');
    Route::get('/features/{feature_uuid}', [PlatformCatalogController::class, 'showFeature'])->middleware('platform.permission:feature.view')->name('features.show');
    Route::match(['put', 'patch'], '/features/{feature_uuid}', [PlatformCatalogController::class, 'updateFeature'])->middleware('platform.permission:feature.edit')->name('features.update');
    Route::delete('/features/{feature_uuid}', [PlatformCatalogController::class, 'deleteFeature'])->middleware('platform.permission:feature.delete')->name('features.destroy');
    Route::get('/addons', [PlatformCatalogController::class, 'addons'])->middleware('platform.permission:plan.view')->name('addons.index');
    Route::post('/addons', [PlatformCatalogController::class, 'storeAddon'])->middleware('platform.permission:plan.create')->name('addons.store');
    Route::get('/addons/{addon_uuid}', [PlatformCatalogController::class, 'showAddon'])->middleware('platform.permission:plan.view')->name('addons.show');
    Route::match(['put', 'patch'], '/addons/{addon_uuid}', [PlatformCatalogController::class, 'updateAddon'])->middleware('platform.permission:plan.edit')->name('addons.update');
    Route::delete('/addons/{addon_uuid}', [PlatformCatalogController::class, 'archiveAddon'])->middleware('platform.permission:plan.delete')->name('addons.destroy');

    Route::get('/billing/invoices', [PlatformBillingController::class, 'invoices'])->middleware('platform.permission:billing.invoice.view')->name('billing.invoices.index');
    Route::post('/billing/invoices', [PlatformBillingController::class, 'storeInvoice'])->middleware('platform.permission:billing.invoice.create')->name('billing.invoices.store');
    Route::post('/billing/invoices/export', [PlatformBillingController::class, 'exportInvoices'])->middleware('platform.permission:billing.invoice.view')->name('billing.invoices.export');
    Route::get('/billing/invoices/{invoice_uuid}', [PlatformBillingController::class, 'showInvoice'])->middleware('platform.permission:billing.invoice.view')->name('billing.invoices.show');
    Route::match(['put', 'patch'], '/billing/invoices/{invoice_uuid}', [PlatformBillingController::class, 'updateInvoice'])->middleware('platform.permission:billing.invoice.edit')->name('billing.invoices.update');
    Route::delete('/billing/invoices/{invoice_uuid}', [PlatformBillingController::class, 'cancelInvoice'])->middleware('platform.permission:billing.invoice.cancel')->name('billing.invoices.cancel');
    Route::post('/billing/invoices/{invoice_uuid}/send', [PlatformBillingController::class, 'sendInvoice'])->middleware('platform.permission:billing.invoice.send')->name('billing.invoices.send');
    Route::get('/billing/invoices/{invoice_uuid}/pdf', [PlatformBillingController::class, 'invoicePdf'])->middleware('platform.permission:billing.invoice.view')->name('billing.invoices.pdf');
    Route::post('/billing/invoices/{invoice_uuid}/payments', [PlatformBillingController::class, 'recordInvoicePayment'])->middleware('platform.permission:billing.payment.create')->name('billing.invoices.payments');
    Route::get('/billing/payments', [PlatformBillingController::class, 'payments'])->middleware('platform.permission:billing.payment.view')->name('billing.payments.index');
    Route::post('/billing/payments', [PlatformBillingController::class, 'storePayment'])->middleware('platform.permission:billing.payment.create')->name('billing.payments.store');
    Route::post('/billing/payments/export', [PlatformBillingController::class, 'exportPayments'])->middleware('platform.permission:billing.payment.view')->name('billing.payments.export');
    Route::get('/billing/payments/{payment_uuid}', [PlatformBillingController::class, 'showPayment'])->middleware('platform.permission:billing.payment.view')->name('billing.payments.show');
    Route::post('/billing/payments/{payment_uuid}/retry', [PlatformBillingController::class, 'retryPayment'])->middleware('platform.permission:billing.payment.create')->name('billing.payments.retry');
    Route::post('/billing/payments/{payment_uuid}/reconcile', [PlatformBillingController::class, 'reconcilePayment'])->middleware('platform.permission:billing.payment.create')->name('billing.payments.reconcile');
    Route::post('/billing/payments/{payment_uuid}/refund', [PlatformBillingController::class, 'refundPayment'])->middleware('platform.permission:billing.payment.refund')->name('billing.payments.refund');
    Route::get('/billing/refunds', [PlatformBillingController::class, 'refunds'])->middleware('platform.permission:billing.payment.view')->name('billing.refunds.index');
    Route::post('/billing/refunds', [PlatformBillingController::class, 'storeRefund'])->middleware('platform.permission:billing.payment.refund')->name('billing.refunds.store');
    Route::post('/billing/refunds/export', [PlatformBillingController::class, 'exportRefunds'])->middleware('platform.permission:billing.payment.view')->name('billing.refunds.export');
    Route::get('/billing/refunds/{refund_uuid}', [PlatformBillingController::class, 'showRefund'])->middleware('platform.permission:billing.payment.view')->name('billing.refunds.show');
    Route::post('/billing/refunds/{refund_uuid}/retry', [PlatformBillingController::class, 'retryRefund'])->middleware('platform.permission:billing.payment.refund')->name('billing.refunds.retry');

    Route::get('/coupons', [PlatformCouponController::class, 'index'])->middleware('platform.permission:coupon.view')->name('coupons.index');
    Route::post('/coupons', [PlatformCouponController::class, 'store'])->middleware('platform.permission:coupon.create')->name('coupons.store');
    Route::post('/coupons/export', [PlatformCouponController::class, 'export'])->middleware('platform.permission:coupon.view')->name('coupons.export');
    Route::get('/coupons/{coupon_uuid}', [PlatformCouponController::class, 'show'])->middleware('platform.permission:coupon.view')->name('coupons.show');
    Route::match(['put', 'patch'], '/coupons/{coupon_uuid}', [PlatformCouponController::class, 'update'])->middleware('platform.permission:coupon.edit')->name('coupons.update');
    Route::delete('/coupons/{coupon_uuid}', [PlatformCouponController::class, 'destroy'])->middleware('platform.permission:coupon.delete')->name('coupons.destroy');
    Route::post('/coupons/{coupon_uuid}/activate', [PlatformCouponController::class, 'activate'])->middleware('platform.permission:coupon.edit')->name('coupons.activate');
    Route::post('/coupons/{coupon_uuid}/deactivate', [PlatformCouponController::class, 'deactivate'])->middleware('platform.permission:coupon.edit')->name('coupons.deactivate');
    Route::get('/coupons/{coupon_uuid}/redemptions', [PlatformCouponController::class, 'redemptions'])->middleware('platform.permission:coupon.view')->name('coupons.redemptions');
    Route::put('/coupons/{coupon_uuid}/plans', [PlatformCouponController::class, 'plans'])->middleware('platform.permission:coupon.edit')->name('coupons.plans');
    Route::put('/coupons/{coupon_uuid}/tenants', [PlatformCouponController::class, 'tenants'])->middleware('platform.permission:coupon.edit')->name('coupons.tenants');

    Route::get('/modules', [PlatformModuleController::class, 'index'])->middleware('platform.permission:module.view')->name('modules.index');
    Route::post('/modules', [PlatformModuleController::class, 'store'])->middleware('platform.permission:module.edit')->name('modules.store');
    Route::get('/modules/{module_uuid}', [PlatformModuleController::class, 'show'])->middleware('platform.permission:module.view')->name('modules.show');
    Route::match(['put', 'patch'], '/modules/{module_uuid}', [PlatformModuleController::class, 'update'])->middleware('platform.permission:module.edit')->name('modules.update');
    Route::post('/modules/{module_uuid}/enable', [PlatformModuleController::class, 'enable'])->middleware('platform.permission:module.edit')->name('modules.enable');
    Route::post('/modules/{module_uuid}/disable', [PlatformModuleController::class, 'disable'])->middleware('platform.permission:module.edit')->name('modules.disable');
    Route::get('/modules/{module_uuid}/features', [PlatformModuleController::class, 'features'])->middleware('platform.permission:module.view')->name('modules.features');
    Route::put('/modules/{module_uuid}/features', [PlatformModuleController::class, 'replaceFeatures'])->middleware('platform.permission:module.edit')->name('modules.features.update');
    Route::get('/modules/{module_uuid}/tenants', [PlatformModuleController::class, 'tenants'])->middleware('platform.permission:module.view')->name('modules.tenants');
    Route::get('/tenants/{tenant_uuid}/module-entitlements', [PlatformModuleController::class, 'tenantModules'])->middleware('platform.permission:module.view')->name('tenants.module-entitlements');
    Route::put('/tenants/{tenant_uuid}/modules/{module_code}', [PlatformModuleController::class, 'overrideTenantModule'])->middleware('platform.permission:module.edit')->name('tenants.modules.override');
    Route::prefix('access-control')->name('access-control.')->group(function (): void {
        Route::get('/roles', [PlatformRoleController::class, 'index'])->middleware('platform.permission:platform_role.view')->name('roles.index');
        Route::post('/roles', [PlatformRoleController::class, 'store'])->middleware('platform.permission:platform_role.create')->name('roles.store');
        Route::get('/roles/{role_uuid}', [PlatformRoleController::class, 'show'])->middleware('platform.permission:platform_role.view')->name('roles.show');
        Route::match(['put', 'patch'], '/roles/{role_uuid}', [PlatformRoleController::class, 'update'])->middleware('platform.permission:platform_role.edit')->name('roles.update');
        Route::delete('/roles/{role_uuid}', [PlatformRoleController::class, 'destroy'])->middleware('platform.permission:platform_role.delete')->name('roles.destroy');
        Route::post('/roles/{role_uuid}/clone', [PlatformRoleController::class, 'clone'])->middleware('platform.permission:platform_role.create')->name('roles.clone');
        Route::post('/roles/{role_uuid}/activate', [PlatformRoleController::class, 'activate'])->middleware('platform.permission:platform_role.edit')->name('roles.activate');
        Route::post('/roles/{role_uuid}/deactivate', [PlatformRoleController::class, 'deactivate'])->middleware('platform.permission:platform_role.edit')->name('roles.deactivate');
        Route::get('/roles/{role_uuid}/permissions', [PlatformRoleController::class, 'permissions'])->middleware('platform.permission:platform_role.view')->name('roles.permissions');
        Route::put('/roles/{role_uuid}/permissions', [PlatformRoleController::class, 'syncPermissions'])->middleware('platform.permission:platform_role.edit')->name('roles.permissions.sync');
        Route::get('/roles/{role_uuid}/users', [PlatformRoleController::class, 'users'])->middleware('platform.permission:platform_role.view')->name('roles.users');
        Route::post('/roles/{role_uuid}/users', [PlatformRoleController::class, 'assignUsers'])->middleware('platform.permission:platform_role.edit')->name('roles.users.assign');
        Route::delete('/roles/{role_uuid}/users/{platform_user_uuid}', [PlatformRoleController::class, 'removeUser'])->middleware('platform.permission:platform_role.edit')->name('roles.users.remove');

        Route::get('/permissions/grouped', [PlatformPermissionController::class, 'grouped'])->middleware('platform.permission:platform_permission.view')->name('permissions.grouped');
        Route::get('/permissions', [PlatformPermissionController::class, 'index'])->middleware('platform.permission:platform_permission.view')->name('permissions.index');
        Route::post('/permissions', [PlatformPermissionController::class, 'store'])->middleware('platform.permission:platform_permission.create')->name('permissions.store');
        Route::get('/permissions/{permission_uuid}', [PlatformPermissionController::class, 'show'])->middleware('platform.permission:platform_permission.view')->name('permissions.show');
        Route::match(['put', 'patch'], '/permissions/{permission_uuid}', [PlatformPermissionController::class, 'update'])->middleware('platform.permission:platform_permission.edit')->name('permissions.update');
        Route::delete('/permissions/{permission_uuid}', [PlatformPermissionController::class, 'destroy'])->middleware('platform.permission:platform_permission.delete')->name('permissions.destroy');
    });

    Route::get('/files', [SharedPrimitiveController::class, 'files'])->middleware('platform.permission:document.view')->name('files.index');
    Route::post('/files', [SharedPrimitiveController::class, 'upload'])->middleware('platform.permission:document.upload')->name('files.store');
    Route::get('/files/{file_uuid}', [SharedPrimitiveController::class, 'file'])->middleware('platform.permission:document.view')->name('files.show');
    Route::get('/files/{file_uuid}/download', [SharedPrimitiveController::class, 'download'])->middleware('platform.permission:document.view')->name('files.download');
    Route::delete('/files/{file_uuid}', [SharedPrimitiveController::class, 'deleteFile'])->middleware('platform.permission:document.delete')->name('files.destroy');
    Route::get('/attachments', [SharedPrimitiveController::class, 'attachments'])->middleware('platform.permission:document.view')->name('attachments.index');
    Route::post('/attachments', [SharedPrimitiveController::class, 'attach'])->middleware('platform.permission:document.upload')->name('attachments.store');
    Route::delete('/attachments/{attachment_id}', [SharedPrimitiveController::class, 'detach'])->whereNumber('attachment_id')->middleware('platform.permission:document.delete')->name('attachments.destroy');
    Route::get('/notes', [SharedPrimitiveController::class, 'notes'])->middleware('platform.permission:document.view')->name('notes.index');
    Route::post('/notes', [SharedPrimitiveController::class, 'createNote'])->middleware('platform.permission:document.upload')->name('notes.store');
    Route::match(['put', 'patch'], '/notes/{note_uuid}', [SharedPrimitiveController::class, 'updateNote'])->middleware('platform.permission:document.upload')->name('notes.update');
    Route::delete('/notes/{note_uuid}', [SharedPrimitiveController::class, 'deleteNote'])->middleware('platform.permission:document.delete')->name('notes.destroy');
    Route::get('/activity-logs', [SharedPrimitiveController::class, 'activityLogs'])->middleware('platform.permission:audit_log.view')->name('activity-logs.index');
    Route::get('/activity-logs/{activity_id}/compare', [SharedPrimitiveController::class, 'activityCompare'])->whereNumber('activity_id')->middleware('platform.permission:audit_log.view')->name('activity-logs.compare');

    Route::prefix('support')->name('support.')->group(function (): void {
        Route::get('/tickets', [PlatformSupportController::class, 'tickets'])->middleware('platform.permission:support.ticket.view')->name('tickets.index');
        Route::post('/tickets', [PlatformSupportController::class, 'storeTicket'])->middleware('platform.permission:support.ticket.reply')->name('tickets.store');
        Route::post('/tickets/export', [PlatformSupportController::class, 'exportTickets'])->middleware('platform.permission:support.ticket.view')->name('tickets.export');
        Route::get('/tickets/{ticket_uuid}', [PlatformSupportController::class, 'showTicket'])->middleware('platform.permission:support.ticket.view')->name('tickets.show');
        Route::match(['put', 'patch'], '/tickets/{ticket_uuid}', [PlatformSupportController::class, 'updateTicket'])->middleware('platform.permission:support.ticket.reply')->name('tickets.update');
        Route::post('/tickets/{ticket_uuid}/assign', [PlatformSupportController::class, 'assignTicket'])->middleware('platform.permission:support.ticket.assign')->name('tickets.assign');
        Route::post('/tickets/{ticket_uuid}/comments', [PlatformSupportController::class, 'comment'])->middleware('platform.permission:support.ticket.reply')->name('tickets.comments');
        Route::post('/tickets/{ticket_uuid}/attachments', [PlatformSupportController::class, 'attach'])->middleware('platform.permission:support.ticket.reply')->name('tickets.attachments');
        Route::post('/tickets/{ticket_uuid}/close', [PlatformSupportController::class, 'close'])->middleware('platform.permission:support.ticket.close')->name('tickets.close');
        Route::post('/tickets/{ticket_uuid}/reopen', [PlatformSupportController::class, 'reopen'])->middleware('platform.permission:support.ticket.close')->name('tickets.reopen');
        Route::get('/knowledge-base/categories', [PlatformSupportController::class, 'kbCategories'])->middleware('platform.permission:support.knowledge_base.view')->name('kb.categories');
        Route::post('/knowledge-base/categories', [PlatformSupportController::class, 'storeKbCategory'])->middleware('platform.permission:support.knowledge_base.create')->name('kb.categories.store');
        Route::match(['put', 'patch'], '/knowledge-base/categories/{category_uuid}', [PlatformSupportController::class, 'updateKbCategory'])->middleware('platform.permission:support.knowledge_base.edit')->name('kb.categories.update');
        Route::get('/knowledge-base/articles', [PlatformSupportController::class, 'articles'])->middleware('platform.permission:support.knowledge_base.view')->name('kb.articles');
        Route::post('/knowledge-base/articles', [PlatformSupportController::class, 'storeArticle'])->middleware('platform.permission:support.knowledge_base.create')->name('kb.articles.store');
        Route::get('/knowledge-base/articles/{article_uuid}', [PlatformSupportController::class, 'showArticle'])->middleware('platform.permission:support.knowledge_base.view')->name('kb.articles.show');
        Route::match(['put', 'patch'], '/knowledge-base/articles/{article_uuid}', [PlatformSupportController::class, 'updateArticle'])->middleware('platform.permission:support.knowledge_base.edit')->name('kb.articles.update');
        Route::post('/knowledge-base/articles/{article_uuid}/publish', [PlatformSupportController::class, 'publishArticle'])->middleware('platform.permission:support.knowledge_base.publish')->name('kb.articles.publish');
        Route::post('/knowledge-base/articles/{article_uuid}/unpublish', [PlatformSupportController::class, 'unpublishArticle'])->middleware('platform.permission:support.knowledge_base.publish')->name('kb.articles.unpublish');
        Route::post('/knowledge-base/articles/{article_uuid}/archive', [PlatformSupportController::class, 'archiveArticle'])->middleware('platform.permission:support.knowledge_base.edit')->name('kb.articles.archive');
        Route::get('/remote-login-sessions', [PlatformSupportController::class, 'remoteSessions'])->middleware('platform.permission:tenant.impersonate')->name('remote-login-sessions.index');
        Route::get('/remote-login-sessions/{session_uuid}', [PlatformSupportController::class, 'remoteSession'])->middleware('platform.permission:tenant.impersonate')->name('remote-login-sessions.show');
        Route::post('/remote-login-sessions/{session_uuid}/end', [PlatformSupportController::class, 'endRemoteSession'])->middleware('platform.permission:tenant.impersonate')->name('remote-login-sessions.end');
    });

    Route::get('/reports/export-jobs', [PlatformReportsController::class, 'exportJobs'])->middleware('platform.permission:report.view')->name('reports.export-jobs');
    Route::get('/reports/export-jobs/{job_uuid}', [PlatformReportsController::class, 'exportJob'])->middleware('platform.permission:report.view')->name('reports.export-jobs.show');
    Route::get('/reports/{report_code}', [PlatformReportsController::class, 'report'])->middleware('platform.permission:report.view')->name('reports.show');
    Route::post('/reports/{report_code}/export', [PlatformReportsController::class, 'export'])->middleware('platform.permission:report.export')->name('reports.export');

    Route::prefix('monitoring')->name('monitoring.')->group(function (): void {
        Route::get('/services', [PlatformMonitoringController::class, 'services'])->middleware('platform.permission:monitoring.view')->name('services');
        Route::get('/services/{service_code}/logs', [PlatformMonitoringController::class, 'serviceLogs'])->middleware('platform.permission:monitoring.view')->name('services.logs');
        Route::get('/api-request-logs', [PlatformMonitoringController::class, 'apiRequestLogs'])->middleware('platform.permission:monitoring.view')->name('api-request-logs');
        Route::get('/queue-jobs', [PlatformMonitoringController::class, 'queueJobs'])->middleware('platform.permission:monitoring.view')->name('queue-jobs');
        Route::post('/queue-jobs/{job_id}/retry', [PlatformMonitoringController::class, 'retryQueueJob'])->whereNumber('job_id')->middleware('platform.permission:monitoring.manage')->name('queue-jobs.retry');
        Route::delete('/queue-jobs/{job_id}', [PlatformMonitoringController::class, 'deleteQueueJob'])->whereNumber('job_id')->middleware('platform.permission:monitoring.manage')->name('queue-jobs.delete');
        Route::get('/scheduler-logs', [PlatformMonitoringController::class, 'schedulerLogs'])->middleware('platform.permission:monitoring.view')->name('scheduler-logs');
        Route::get('/alerts', [PlatformMonitoringController::class, 'alerts'])->middleware('platform.permission:monitoring.view')->name('alerts');
        Route::post('/alerts/{alert_id}/resolve', [PlatformMonitoringController::class, 'resolveAlert'])->middleware('platform.permission:monitoring.manage')->name('alerts.resolve');
        Route::get('/incidents', [PlatformMonitoringController::class, 'incidents'])->middleware('platform.permission:monitoring.view')->name('incidents');
        Route::post('/incidents', [PlatformMonitoringController::class, 'storeIncident'])->middleware('platform.permission:monitoring.manage')->name('incidents.store');
        Route::get('/incidents/{incident_id}', [PlatformMonitoringController::class, 'showIncident'])->whereNumber('incident_id')->middleware('platform.permission:monitoring.view')->name('incidents.show');
        Route::match(['put', 'patch'], '/incidents/{incident_id}', [PlatformMonitoringController::class, 'updateIncident'])->whereNumber('incident_id')->middleware('platform.permission:monitoring.manage')->name('incidents.update');
        Route::post('/incidents/{incident_id}/resolve', [PlatformMonitoringController::class, 'resolveIncident'])->whereNumber('incident_id')->middleware('platform.permission:monitoring.manage')->name('incidents.resolve');
        Route::get('/tenant-usage-snapshots', [PlatformMonitoringController::class, 'tenantUsageSnapshots'])->middleware('platform.permission:monitoring.view')->name('tenant-usage-snapshots');
    });

    Route::prefix('integrations')->name('integrations.')->group(function (): void {
        Route::get('/providers', [PlatformIntegrationController::class, 'providers'])->middleware('platform.permission:integration.view')->name('providers');
        Route::post('/providers', [PlatformIntegrationController::class, 'storeProvider'])->middleware('platform.permission:integration.create')->name('providers.store');
        Route::match(['put', 'patch'], '/providers/{provider_code}', [PlatformIntegrationController::class, 'updateProvider'])->middleware('platform.permission:integration.edit')->name('providers.update');
        Route::get('/tenant-integrations', [PlatformIntegrationController::class, 'tenantIntegrations'])->middleware('platform.permission:integration.view')->name('tenant-integrations');
        Route::post('/tenant-integrations', [PlatformIntegrationController::class, 'storeTenantIntegration'])->middleware('platform.permission:integration.create')->name('tenant-integrations.store');
        Route::get('/tenant-integrations/{integration_uuid}', [PlatformIntegrationController::class, 'showTenantIntegration'])->middleware('platform.permission:integration.view')->name('tenant-integrations.show');
        Route::match(['put', 'patch'], '/tenant-integrations/{integration_uuid}', [PlatformIntegrationController::class, 'updateTenantIntegration'])->middleware('platform.permission:integration.edit')->name('tenant-integrations.update');
        Route::post('/tenant-integrations/{integration_uuid}/credentials', [PlatformIntegrationController::class, 'rotateCredentials'])->middleware('platform.permission:integration.edit')->name('tenant-integrations.credentials');
        Route::post('/tenant-integrations/{integration_uuid}/test', [PlatformIntegrationController::class, 'testIntegration'])->middleware('platform.permission:integration.test')->name('tenant-integrations.test');
        Route::post('/tenant-integrations/{integration_uuid}/disconnect', [PlatformIntegrationController::class, 'disconnectIntegration'])->middleware('platform.permission:integration.edit')->name('tenant-integrations.disconnect');
        Route::get('/tenant-integrations/{integration_uuid}/mappings', [PlatformIntegrationController::class, 'mappings'])->middleware('platform.permission:integration.view')->name('mappings');
        Route::put('/tenant-integrations/{integration_uuid}/mappings', [PlatformIntegrationController::class, 'replaceMappings'])->middleware('platform.permission:integration.edit')->name('mappings.update');
        Route::get('/tenant-integrations/{integration_uuid}/rate-limits', [PlatformIntegrationController::class, 'rateLimits'])->middleware('platform.permission:integration.view')->name('rate-limits');
        Route::get('/webhooks', [PlatformIntegrationController::class, 'webhooks'])->middleware('platform.permission:integration.view')->name('webhooks');
        Route::post('/webhooks', [PlatformIntegrationController::class, 'storeWebhook'])->middleware('platform.permission:integration.create')->name('webhooks.store');
        Route::get('/webhooks/{webhook_id}', [PlatformIntegrationController::class, 'showWebhook'])->whereNumber('webhook_id')->middleware('platform.permission:integration.view')->name('webhooks.show');
        Route::match(['put', 'patch'], '/webhooks/{webhook_id}', [PlatformIntegrationController::class, 'updateWebhook'])->whereNumber('webhook_id')->middleware('platform.permission:integration.edit')->name('webhooks.update');
        Route::delete('/webhooks/{webhook_id}', [PlatformIntegrationController::class, 'deleteWebhook'])->whereNumber('webhook_id')->middleware('platform.permission:integration.delete')->name('webhooks.delete');
        Route::get('/webhooks/{webhook_id}/logs', [PlatformIntegrationController::class, 'webhookLogs'])->whereNumber('webhook_id')->middleware('platform.permission:integration.view')->name('webhooks.logs');
        Route::post('/webhook-logs/{log_id}/retry', [PlatformIntegrationController::class, 'retryWebhookLog'])->whereNumber('log_id')->middleware('platform.permission:integration.edit')->name('webhook-logs.retry');
        Route::get('/sync-jobs', [PlatformIntegrationController::class, 'syncJobs'])->middleware('platform.permission:integration.view')->name('sync-jobs');
        Route::post('/sync-jobs/{job_id}/retry', [PlatformIntegrationController::class, 'retrySyncJob'])->whereNumber('job_id')->middleware('platform.permission:integration.edit')->name('sync-jobs.retry');
    });

    Route::prefix('settings')->name('settings-admin.')->group(function (): void {
        Route::get('/platform', [PlatformSettingsAuditController::class, 'settings'])->middleware('platform.permission:setting.view')->name('platform');
        Route::put('/platform', [PlatformSettingsAuditController::class, 'updateSettings'])->middleware('platform.permission:setting.edit')->name('platform.update');
        Route::get('/notification-templates', [PlatformSettingsAuditController::class, 'templates'])->middleware('platform.permission:setting.view')->name('templates');
        Route::post('/notification-templates', [PlatformSettingsAuditController::class, 'storeTemplate'])->middleware('platform.permission:setting.edit')->name('templates.store');
        Route::match(['put', 'patch'], '/notification-templates/{template_uuid}', [PlatformSettingsAuditController::class, 'updateTemplate'])->middleware('platform.permission:setting.edit')->name('templates.update');
        Route::get('/backups', [PlatformSettingsAuditController::class, 'backupSettings'])->middleware('platform.permission:setting.view')->name('backups');
        Route::put('/backups', [PlatformSettingsAuditController::class, 'updateBackupSettings'])->middleware('platform.permission:setting.edit')->name('backups.update');
        Route::post('/backups/run', [PlatformSettingsAuditController::class, 'runBackup'])->middleware('platform.permission:setting.edit')->name('backups.run');
        Route::get('/backups/runs', [PlatformSettingsAuditController::class, 'backupRuns'])->middleware('platform.permission:setting.view')->name('backups.runs');
        Route::get('/backups/runs/{run_uuid}', [PlatformSettingsAuditController::class, 'backupRun'])->middleware('platform.permission:setting.view')->name('backups.runs.show');
        Route::get('/backups/runs/{run_uuid}/download', [PlatformSettingsAuditController::class, 'backupDownload'])->middleware('platform.permission:setting.view')->name('backups.runs.download');
    });

    Route::get('/audit/activity-logs', [PlatformSettingsAuditController::class, 'activityLogs'])->middleware('platform.permission:audit_log.view')->name('audit.activity-logs');
    Route::get('/audit/security-events', [PlatformSettingsAuditController::class, 'securityEvents'])->middleware('platform.permission:audit_log.view')->name('audit.security-events');
    Route::post('/audit/security-events/{event_id}/review', [PlatformSettingsAuditController::class, 'reviewSecurityEvent'])->whereNumber('event_id')->middleware('platform.permission:audit_log.view')->name('audit.security-events.review');
    Route::post('/audit/export', [PlatformSettingsAuditController::class, 'exportAudit'])->middleware('platform.permission:audit_log.export')->name('audit.export');

    Route::get('/onboarding/tenants', [PlatformSettingsAuditController::class, 'onboardingTenants'])->middleware('platform.permission:tenant.view')->name('onboarding.tenants');
    Route::get('/onboarding/tenants/{tenant_uuid}', [PlatformSettingsAuditController::class, 'onboardingTenant'])->middleware('platform.permission:tenant.view')->name('onboarding.tenants.show');
    Route::put('/onboarding/tenants/{tenant_uuid}/steps/{step_code}', [PlatformSettingsAuditController::class, 'updateOnboardingStep'])->middleware('platform.permission:tenant.edit')->name('onboarding.tenants.steps');
    Route::get('/trials', [PlatformSettingsAuditController::class, 'trials'])->middleware('platform.permission:tenant.view')->name('trials.index');
    Route::post('/trials/{tenant_uuid}/extend', [PlatformSettingsAuditController::class, 'extendTrial'])->middleware('platform.permission:subscription.edit')->name('trials.extend');
    Route::post('/trials/{tenant_uuid}/convert', [PlatformSettingsAuditController::class, 'convertTrial'])->middleware('platform.permission:subscription.edit')->name('trials.convert');

    Route::get('/legal/documents', [PlatformSettingsAuditController::class, 'legalDocuments'])->middleware('platform.permission:setting.view')->name('legal.documents');
    Route::post('/legal/documents', [PlatformSettingsAuditController::class, 'storeLegalDocument'])->middleware('platform.permission:setting.edit')->name('legal.documents.store');
    Route::get('/legal/documents/{document_uuid}', [PlatformSettingsAuditController::class, 'legalDocument'])->middleware('platform.permission:setting.view')->name('legal.documents.show');
    Route::match(['put', 'patch'], '/legal/documents/{document_uuid}', [PlatformSettingsAuditController::class, 'updateLegalDocument'])->middleware('platform.permission:setting.edit')->name('legal.documents.update');
    Route::post('/legal/documents/{document_uuid}/publish', [PlatformSettingsAuditController::class, 'publishLegalDocument'])->middleware('platform.permission:setting.edit')->name('legal.documents.publish');
    Route::get('/legal/documents/{document_uuid}/acceptances', [PlatformSettingsAuditController::class, 'legalAcceptances'])->middleware('platform.permission:setting.view')->name('legal.documents.acceptances');

    Route::get('/announcements', [PlatformSettingsAuditController::class, 'announcements'])->middleware('platform.permission:setting.view')->name('announcements.index');
    Route::post('/announcements', [PlatformSettingsAuditController::class, 'storeAnnouncement'])->middleware('platform.permission:setting.edit')->name('announcements.store');
    Route::get('/announcements/{announcement_uuid}', [PlatformSettingsAuditController::class, 'announcement'])->middleware('platform.permission:setting.view')->name('announcements.show');
    Route::match(['put', 'patch'], '/announcements/{announcement_uuid}', [PlatformSettingsAuditController::class, 'updateAnnouncement'])->middleware('platform.permission:setting.edit')->name('announcements.update');
    Route::post('/announcements/{announcement_uuid}/publish', [PlatformSettingsAuditController::class, 'publishAnnouncement'])->middleware('platform.permission:setting.edit')->name('announcements.publish');
    Route::post('/announcements/{announcement_uuid}/archive', [PlatformSettingsAuditController::class, 'archiveAnnouncement'])->middleware('platform.permission:setting.edit')->name('announcements.archive');
    Route::delete('/announcements/{announcement_uuid}', [PlatformSettingsAuditController::class, 'deleteAnnouncement'])->middleware('platform.permission:setting.edit')->name('announcements.delete');

    Route::get('/webhook-endpoints', [PlatformSettingsAuditController::class, 'webhookEndpoints'])->middleware('platform.permission:integration.view')->name('webhook-endpoints.index');
    Route::post('/webhook-endpoints', [PlatformSettingsAuditController::class, 'storeWebhookEndpoint'])->middleware('platform.permission:integration.create')->name('webhook-endpoints.store');
    Route::get('/webhook-endpoints/{endpoint_uuid}', [PlatformSettingsAuditController::class, 'webhookEndpoint'])->middleware('platform.permission:integration.view')->name('webhook-endpoints.show');
    Route::match(['put', 'patch'], '/webhook-endpoints/{endpoint_uuid}', [PlatformSettingsAuditController::class, 'updateWebhookEndpoint'])->middleware('platform.permission:integration.edit')->name('webhook-endpoints.update');
    Route::delete('/webhook-endpoints/{endpoint_uuid}', [PlatformSettingsAuditController::class, 'deleteWebhookEndpoint'])->middleware('platform.permission:integration.delete')->name('webhook-endpoints.delete');
    Route::get('/webhook-endpoints/{endpoint_uuid}/deliveries', [PlatformSettingsAuditController::class, 'webhookDeliveries'])->middleware('platform.permission:integration.view')->name('webhook-endpoints.deliveries');
    Route::get('/webhook-deliveries/{delivery_uuid}', [PlatformSettingsAuditController::class, 'webhookDelivery'])->middleware('platform.permission:integration.view')->name('webhook-deliveries.show');
    Route::post('/webhook-deliveries/{delivery_uuid}/retry', [PlatformSettingsAuditController::class, 'retryWebhookDelivery'])->middleware('platform.permission:integration.edit')->name('webhook-deliveries.retry');
    Route::get('/api-tokens', [PlatformApiTokenController::class, 'index'])->name('api-tokens.index');
    Route::post('/api-tokens', [PlatformApiTokenController::class, 'store'])->name('api-tokens.store');
    Route::get('/api-tokens/{token_uuid}', [PlatformApiTokenController::class, 'show'])->name('api-tokens.show');
    Route::post('/api-tokens/{token_uuid}/rotate', [PlatformApiTokenController::class, 'rotate'])->name('api-tokens.rotate');
    Route::post('/api-tokens/{token_uuid}/revoke', [PlatformApiTokenController::class, 'revoke'])->name('api-tokens.revoke');
});



