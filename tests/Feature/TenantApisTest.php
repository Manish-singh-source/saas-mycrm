<?php

namespace Tests\Feature;

use App\Mail\TenantAccountNotification;
use App\Models\Plan;
use App\Models\PlatformUser;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class TenantApisTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        Http::preventStrayRequests();
        config(['services.razorpay.key' => null, 'services.razorpay.secret' => null]);
    }

    private function plan(float $price = 0, array $extra = []): Plan
    {
        return Plan::create(array_replace(['uuid' => (string) Str::uuid(), 'name' => 'Test plan', 'code' => Str::random(12), 'billing_cycle' => 'monthly', 'base_price' => $price, 'currency' => 'INR', 'trial_days' => 15, 'is_public' => true, 'status' => 'active'], $extra));
    }

    private function body(Plan $plan): array
    {
        return ['organization_name' => 'Example Company', 'owner' => ['first_name' => 'Jane', 'email' => 'JANE@example.com', 'password' => 'secret123', 'password_confirmation' => 'secret123'], 'office' => [], 'plan_uuid' => $plan->uuid];
    }

    private function admin(array $abilities = ['*']): PlatformUser
    {
        $user = PlatformUser::forceCreate(['uuid' => (string) Str::uuid(), 'employee_code' => Str::random(10), 'first_name' => 'Admin', 'display_name' => 'Admin', 'email' => Str::random(10).'@example.com', 'password' => Hash::make('secret123'), 'status' => 'active']);
        Sanctum::actingAs($user, $abilities);

        return $user;
    }

    private function createTenant(): string
    {
        return $this->postJson('/api/platform/v1/tenants', $this->body($this->plan()))->assertCreated()->json('data.tenant.uuid');
    }

    public function test_tenant_team_create_generates_codes_and_rejects_duplicate_names(): void
    {
        $this->admin();
        $tenantUuid = $this->createTenant();
        $tenant = Tenant::where('uuid', $tenantUuid)->firstOrFail();
        $this->postJson('/api/platform/v1/tenants/'.$tenantUuid.'/activate')->assertOk();

        $permissionId = DB::table('permissions')->insertGetId(['uuid' => (string) Str::uuid(), 'module' => 'team', 'name' => 'team.create', 'guard_name' => 'tenant', 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
        $roleId = DB::table('roles')->where('tenant_id', $tenant->id)->first()->id;
        DB::table('role_has_permissions')->insert(['role_id' => $roleId, 'permission_id' => $permissionId]);
        Sanctum::actingAs(User::where('tenant_id', $tenant->id)->firstOrFail(), ['*']);
        $this->withHeader('X-Tenant', $tenantUuid);
        $url = '/api/tenant/v1/teams';

        $this->postJson($url, ['name' => 'Operations', 'description' => 'Core operations', 'visibility' => 'tenant', 'status' => 'active'])
            ->assertCreated()->assertJsonPath('data.team.code', 'TEAM-000001');
        $this->postJson($url, ['name' => 'Finance'])
            ->assertCreated()->assertJsonPath('data.team.code', 'TEAM-000002');
        $this->postJson($url, ['name' => 'Operations'])->assertUnprocessable();
        $this->postJson($url, ['name' => 'Engineering', 'code' => 'CUSTOM'])->assertUnprocessable();
        $this->postJson($url, [])->assertUnprocessable();
        $this->assertDatabaseCount('teams', 2);
    }
    public function test_tenant_user_invite_accepts_available_account_types(): void
    {
        $this->admin();
        $tenantUuid = $this->createTenant();
        $tenant = Tenant::where('uuid', $tenantUuid)->firstOrFail();
        $this->postJson('/api/platform/v1/tenants/'.$tenantUuid.'/activate')->assertOk();

        $permissionId = DB::table('permissions')->insertGetId(['uuid' => (string) Str::uuid(), 'module' => 'staff', 'name' => 'staff.create', 'guard_name' => 'tenant', 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
        $roleId = DB::table('roles')->where('tenant_id', $tenant->id)->first()->id;
        DB::table('role_has_permissions')->insert(['role_id' => $roleId, 'permission_id' => $permissionId]);
        Sanctum::actingAs(User::where('tenant_id', $tenant->id)->firstOrFail(), ['*']);
        $this->withHeader('X-Tenant', $tenantUuid);

        foreach (['staff', 'client', 'owner'] as $accountType) {
            $this->postJson('/api/tenant/v1/users/invite', [
                'first_name' => 'Test',
                'email' => $accountType.'@example.com',
                'account_type' => $accountType,
                'status' => 'invited',
                'role_ids' => [],
            ])->assertCreated()->assertJsonPath('data.user.account_type', $accountType);
        }
    }
    public function test_tenant_staff_create_generates_code_and_validates_linked_login(): void
    {
        $this->admin();
        $tenantUuid = $this->createTenant();
        $tenant = Tenant::where('uuid', $tenantUuid)->firstOrFail();
        $this->postJson('/api/platform/v1/tenants/'.$tenantUuid.'/activate')->assertOk();
        $roleId = DB::table('roles')->where('tenant_id', $tenant->id)->first()->id;
        foreach (['staff.create', 'staff.view'] as $permission) {
            $permissionId = DB::table('permissions')->insertGetId(['uuid' => (string) Str::uuid(), 'module' => 'staff', 'name' => $permission, 'guard_name' => 'tenant', 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
            DB::table('role_has_permissions')->insert(['role_id' => $roleId, 'permission_id' => $permissionId]);
        }
        Sanctum::actingAs(User::where('tenant_id', $tenant->id)->firstOrFail(), ['*']);
        $this->withHeader('X-Tenant', $tenantUuid);

        $this->getJson('/api/tenant/v1/staff/form-options')->assertOk()->assertJsonStructure(['data' => ['departments', 'designations', 'tenant_offices', 'teams', 'users']]);
        $this->postJson('/api/tenant/v1/staff', ['first_name' => 'Alice'])->assertCreated()->assertJsonPath('data.staff.employee_code', 'EMP-000001');
        $this->postJson('/api/tenant/v1/staff', ['first_name' => 'Bob', 'work_email' => 'bob@example.com', 'create_user' => true])
            ->assertCreated()->assertJsonPath('data.staff.employee_code', 'EMP-000002');
        $this->postJson('/api/tenant/v1/staff', ['first_name' => 'Carol', 'create_user' => true])->assertUnprocessable();
        $this->postJson('/api/tenant/v1/staff', ['first_name' => 'Dan', 'employee_code' => 'CUSTOM'])->assertUnprocessable();
        $this->assertDatabaseCount('staff', 2);
    }
    public function test_tenant_client_create_generates_code_and_saves_profile(): void
    {
        $this->admin();
        $tenantUuid = $this->createTenant();
        $tenant = Tenant::where('uuid', $tenantUuid)->firstOrFail();
        $this->postJson('/api/platform/v1/tenants/'.$tenantUuid.'/activate')->assertOk();
        $permissionId = DB::table('permissions')->insertGetId(['uuid' => (string) Str::uuid(), 'module' => 'client', 'name' => 'client.create', 'guard_name' => 'tenant', 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
        $roleId = DB::table('roles')->where('tenant_id', $tenant->id)->first()->id;
        DB::table('role_has_permissions')->insert(['role_id' => $roleId, 'permission_id' => $permissionId]);
        $user = User::where('tenant_id', $tenant->id)->firstOrFail();
        Sanctum::actingAs($user, ['*']);
        $this->withHeader('X-Tenant', $tenantUuid);

        $this->postJson('/api/tenant/v1/clients', ['party' => ['display_name' => 'Acme', 'email' => 'acme@example.com'], 'profile' => ['client_type' => 'business', 'credit_limit' => 1200, 'payment_terms_days' => 30, 'onboarding_date' => '2026-09-22', 'account_manager_id' => $user->uuid]])
            ->assertCreated()->assertJsonPath('data.client.client_code', 'CL-000001')->assertJsonPath('data.client.profile.payment_terms_days', 30);
        $this->postJson('/api/tenant/v1/clients', ['party' => ['display_name' => 'Beta']])
            ->assertCreated()->assertJsonPath('data.client.client_code', 'CL-000002');
        $this->postJson('/api/tenant/v1/clients', ['party' => ['display_name' => 'Invalid'], 'profile' => ['client_code' => 'CUSTOM']])->assertUnprocessable();
        $this->postJson('/api/tenant/v1/clients', ['party' => []])->assertUnprocessable();
        $this->assertDatabaseCount('client_profiles', 2);
    }
    public function test_public_plans_exclude_unavailable_plans_and_registration_creates_complete_graph(): void
    {
        $plan = $this->plan();
        $this->plan(10, ['is_public' => false]);
        $this->plan(20, ['status' => 'inactive']);
        $this->plan(30)->delete();
        $this->getJson('/api/auth/v1/tenants/plans')->assertOk()->assertJsonCount(1, 'data.plans');
        $response = $this->postJson('/api/auth/v1/tenants/register', $this->body($plan))->assertCreated()->assertJsonPath('success', true)->assertJsonPath('data.owner.email', 'jane@example.com')->assertJsonPath('data.subscription.plan.uuid', $plan->uuid)->assertJsonPath('data.payment.payment_status', 'paid');
        $this->assertArrayNotHasKey('password', $response->json('data.owner'));
        $this->assertArrayNotHasKey('two_factor_secret', $response->json('data.tenant.owner'));
        $this->assertTrue(Hash::check('secret123', User::first()->password));
        $this->assertDatabaseCount('tenants', 1);
        $this->assertDatabaseCount('tenant_offices', 1);
        $this->assertDatabaseCount('roles', 1);
        $this->assertDatabaseCount('model_has_roles', 1);
        $this->assertDatabaseCount('subscription_versions', 1);
        $this->assertDatabaseCount('platform_invoice_items', 1);
        $this->assertDatabaseCount('personal_access_tokens', 1);
        $this->assertDatabaseCount('tenant_settings', 3);
        $this->assertDatabaseHas('platform_invoices', ['paid_amount' => '0.00', 'balance_amount' => '0.00', 'status' => 'paid']);
        Mail::assertQueued(TenantAccountNotification::class);
    }

    public function test_registration_validation_and_gateway_failure_roll_back_everything(): void
    {
        $plan = $this->plan(499);
        $this->postJson('/api/auth/v1/tenants/register', [])->assertUnprocessable()->assertJsonPath('message', 'Validation failed.');
        $body = $this->body($plan);
        $body['payment'] = ['method' => 'online'];
        $this->postJson('/api/auth/v1/tenants/register', $body)->assertStatus(503);
        $this->assertDatabaseCount('tenants', 0);
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('subscriptions', 0);
        $this->assertDatabaseCount('platform_invoices', 0);
        config(['services.razorpay.key' => 'key', 'services.razorpay.secret' => 'secret']);
        Http::fake(['api.razorpay.com/*' => Http::response([], 500)]);
        $this->postJson('/api/auth/v1/tenants/register', $body)->assertStatus(502);
        $this->assertDatabaseCount('tenants', 0);
        $body['payment']['method'] = 'free';
        $this->postJson('/api/auth/v1/tenants/register', $body)->assertUnprocessable();
        $body['payment']['method'] = 'cash';
        $this->postJson('/api/auth/v1/tenants/register', $body)->assertCreated()->assertJsonPath('data.payment.payment_status', 'pending');
    }

    public function test_platform_permissions_and_partial_updates(): void
    {
        $this->getJson('/api/platform/v1/tenants')->assertUnauthorized();
        $this->admin(['tenant.view']);
        $this->postJson('/api/platform/v1/tenants', [])->assertForbidden();
        $this->admin();
        $uuid = $this->createTenant();
        $tenant = Tenant::first();
        $this->patchJson('/api/platform/v1/tenants/'.$uuid, ['description' => 'Keep me', 'owner' => ['last_name' => 'Doe'], 'office' => ['address_line_1' => 'Main Street']])->assertOk();
        $this->patchJson('/api/platform/v1/tenants/'.$uuid, ['organization_name' => 'Renamed'])->assertOk()->assertJsonPath('data.tenant.description', 'Keep me')->assertJsonPath('data.owner.last_name', 'Doe')->assertJsonPath('data.office.address_line_1', 'Main Street')->assertJsonPath('data.tenant.slug', $tenant->slug);
        $this->getJson('/api/platform/v1/tenants/'.$uuid)->assertOk()->assertJsonStructure(['data' => ['id', 'uuid', 'organization_name', 'legal_name', 'display_name', 'organization_code', 'owner_name', 'owner_email', 'users_count', 'slug', 'trial_ends_at', 'status', 'plan_name', 'subscription_status', 'created_at']])->assertJsonMissingPath('data.tenant');
        $this->getJson('/api/platform/v1/tenants?per_page=1&search=Renamed')->assertOk()->assertJsonPath('meta.total', 1)->assertJsonPath('meta.stats.total', 1);
        $this->getJson('/api/platform/v1/tenants?per_page=0')->assertUnprocessable();
        $this->getJson('/api/platform/v1/tenants/'.Str::uuid())->assertNotFound()->assertJsonPath('data', null);
    }

    public function test_lifecycle_trial_plan_password_reset_and_all_tabs(): void
    {
        $this->admin();
        $uuid = $this->createTenant();
        $base = '/api/platform/v1/tenants/'.$uuid;
        $this->postJson($base.'/activate')->assertOk()->assertJsonPath('data.tenant.status', 'active');
        $this->postJson($base.'/suspend', [])->assertUnprocessable();
        $this->postJson($base.'/suspend', ['reason' => 'Overdue', 'notify_owner' => true])->assertOk()->assertJsonPath('data.tenant.status', 'suspended');
        $this->postJson($base.'/reactivate')->assertOk()->assertJsonPath('data.tenant.status', 'active');
        $this->postJson($base.'/extend-trial', ['trial_ends_at' => '2026-10-15 23:59:59'])->assertOk()->assertJsonPath('data.subscription.trial_ends_at', '2026-10-15 23:59:59');
        $plan = $this->plan(499);
        $this->postJson($base.'/change-plan', ['plan_uuid' => $plan->uuid])->assertOk()->assertJsonPath('data.subscription.plan.uuid', $plan->uuid);
        $this->assertDatabaseCount('subscription_versions', 2);
        $this->postJson($base.'/reset-owner-password', ['password' => 'new-secret123'])->assertOk()->assertJsonMissingPath('data.temporary_password')->assertJsonMissingPath('data.owner.password');
        $this->assertTrue(Hash::check('new-secret123', User::first()->password));
        foreach (['users', 'offices', 'subscription', 'billing', 'usage', 'modules', 'settings', 'integrations', 'security', 'support', 'files', 'activity'] as $tab) {
            $this->getJson($base.'/'.$tab)->assertOk()->assertJsonStructure(['data' => [$tab]]);
        }
        $this->getJson($base.'/unsupported')->assertNotFound();
        $this->deleteJson($base)->assertOk()->assertJsonPath('data', null);
        $this->getJson($base)->assertNotFound();
        $this->postJson($base.'/restore')->assertOk()->assertJsonPath('data.tenant.status', 'archived');
        $this->postJson($base.'/reactivate')->assertOk();
        $this->postJson($base.'/archive')->assertOk();
        $this->postJson($base.'/restore')->assertOk();
        $this->deleteJson('/api/platform/v1/tenants/bulk', ['tenant_uuids' => [$uuid]])->assertOk()->assertJsonPath('data.archived', 1);
    }

    public function test_registration_rejects_private_and_deleted_plans_and_online_success_stays_pending(): void
    {
        $private = $this->plan(499, ['is_public' => false]);
        $this->postJson('/api/auth/v1/tenants/register', $this->body($private))->assertUnprocessable()->assertJsonValidationErrors('plan_uuid');
        $deleted = $this->plan();
        $deleted->delete();
        $this->postJson('/api/auth/v1/tenants/register', $this->body($deleted))->assertUnprocessable();
        $this->assertDatabaseCount('tenants', 0);
        $plan = $this->plan(499);
        config(['services.razorpay.key' => 'key', 'services.razorpay.secret' => 'secret']);
        Http::fake(['api.razorpay.com/*' => Http::response(['id' => 'order_registration'], 200)]);
        DB::table('permissions')->insert(['uuid' => (string) Str::uuid(), 'module' => 'crm', 'name' => 'crm.view', 'guard_name' => 'tenant', 'status' => 'active']);
        $body = $this->body($plan);
        $body['trial_days'] = 7;
        $response = $this->postJson('/api/auth/v1/tenants/register', $body)->assertCreated()->assertJsonPath('data.payment.payment_status', 'pending')->assertJsonPath('data.payment_order.id', 'order_registration')->assertJsonPath('data.permissions.0', 'crm.view');
        $this->assertDatabaseCount('role_has_permissions', 1);
        $this->assertSame($response->json('data.tenant.trial_ends_at'), $response->json('data.subscription.trial_ends_at'));
        $token = DB::table('personal_access_tokens')->first();
        $this->assertEqualsWithDelta(12, now()->diffInHours(Carbon::parse($token->expires_at)), 0.01);
        Http::assertSent(fn ($request) => $request['amount'] === 49900 && $request['currency'] === 'INR');
    }

    public function test_nullable_updates_sensitive_data_and_deleted_subscription_selection(): void
    {
        $this->admin();
        $uuid = $this->createTenant();
        $base = '/api/platform/v1/tenants/'.$uuid;
        $tenant = Tenant::first();
        $subscription = $tenant->subscription;
        $copy = $subscription->replicate();
        $copy->uuid = (string) Str::uuid();
        $copy->subscription_number = 'SUB-DELETED';
        $copy->save();
        $copy->delete();
        $tenant->owner->forceFill(['two_factor_secret' => 'never-return-this', 'two_factor_recovery_codes' => 'never-return-codes'])->save();
        $this->patchJson($base, ['owner' => ['display_name' => null, 'status' => null], 'subscription' => ['auto_renew' => null, 'renewal_type' => null]])->assertOk()->assertJsonPath('data.owner.display_name', 'Jane')->assertJsonPath('data.owner.status', 'active')->assertJsonPath('data.subscription.uuid', $subscription->uuid)->assertJsonMissingPath('data.owner.two_factor_secret');
        DB::table('tenant_settings')->where('tenant_id', $tenant->id)->where('key', 'locale')->update(['is_encrypted' => true, 'value' => json_encode('encrypted-secret')]);
        $this->getJson($base.'/settings')->assertOk()->assertJsonPath('data.settings.0.value', null);
        $this->getJson($base.'/users')->assertOk()->assertJsonMissingPath('data.users.0.two_factor_secret');
        $this->postJson($base.'/change-plan', ['plan_uuid' => $subscription->plan->uuid, 'starts_at' => '2026-10-01', 'expires_at' => '2026-09-01'])->assertUnprocessable();
        $this->assertDatabaseCount('subscription_versions', 2);
        foreach (DB::table('activity_logs')->where('tenant_id', $tenant->id)->pluck('new_values') as $value) {
            $this->assertStringNotContainsString('secret123', $value ?? '');
        }
    }

    public function test_payments_impersonation_and_modules_are_tenant_scoped(): void
    {
        $this->admin();
        $uuid = $this->createTenant();
        $other = $this->createTenant();
        $base = '/api/platform/v1/tenants/'.$uuid;
        $foreign = Tenant::where('uuid', $other)->first();
        $this->postJson($base.'/payment-order', ['amount' => 499, 'method' => 'cash', 'subscription_uuid' => $foreign->subscription->uuid])->assertUnprocessable();
        $this->postJson($base.'/payment-order', ['amount' => 499, 'method' => 'cash'])->assertCreated()->assertJsonPath('data.payment.payment_status', 'paid')->assertJsonStructure(['data' => ['payment' => ['subscription' => ['plan']]]]);
        config(['services.razorpay.key' => 'key', 'services.razorpay.secret' => 'secret']);
        Http::fake(['api.razorpay.com/*' => Http::response(['id' => 'order_test'], 200)]);
        $this->postJson($base.'/payment-order', ['amount' => 499, 'method' => 'online'])->assertCreated()->assertJsonPath('data.payment.payment_status', 'pending')->assertJsonPath('data.order.id', 'order_test');
        $this->postJson($base.'/impersonate', ['reason' => 'Support request', 'duration_minutes' => 30, 'target_user_uuid' => $foreign->owner->uuid])->assertUnprocessable();
        $session = $this->postJson($base.'/impersonate', ['reason' => 'Support request', 'duration_minutes' => 30])->assertCreated()->assertJsonMissingPath('data.session.target_user.password')->json('data.session.uuid');
        $this->deleteJson('/api/platform/v1/tenants/'.$other.'/impersonate/'.$session)->assertNotFound();
        $this->deleteJson($base.'/impersonate/'.$session)->assertOk();
        DB::table('modules')->insert(['uuid' => (string) Str::uuid(), 'name' => 'CRM', 'code' => 'crm', 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
        $module = $this->putJson($base.'/modules', ['modules' => [['module_code' => 'crm', 'enabled' => true, 'limits' => ['users' => 25]]]])->assertOk()->assertJsonPath('data.modules.0.module.code', 'crm')->json('data.modules.0.uuid');
        $this->putJson($base.'/modules', ['modules' => [['module_code' => 'crm', 'enabled' => false]]])->assertOk()->assertJsonPath('data.modules.0.uuid', $module)->assertJsonPath('data.modules.0.limits.users', 25);
        $this->putJson($base.'/modules', ['modules' => []])->assertOk()->assertJsonCount(1, 'data.modules');
    }
    public function test_project_child_actions_create_update_complete_and_delete_records(): void
    {
        $this->admin();
        $tenantUuid = $this->createTenant();
        $tenant = Tenant::where('uuid', $tenantUuid)->firstOrFail();
        $this->postJson('/api/platform/v1/tenants/'.$tenantUuid.'/activate')->assertOk();

        $roleId = DB::table('roles')->where('tenant_id', $tenant->id)->first()->id;
        foreach (['project.create', 'project.view', 'project.edit'] as $permission) {
            $permissionId = DB::table('permissions')->insertGetId([
                'uuid' => (string) Str::uuid(),
                'module' => 'project',
                'name' => $permission,
                'guard_name' => 'tenant',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('role_has_permissions')->insert(['role_id' => $roleId, 'permission_id' => $permissionId]);
        }

        $user = User::where('tenant_id', $tenant->id)->firstOrFail();
        Sanctum::actingAs($user, ['*']);
        $this->withHeader('X-Tenant', $tenantUuid);

        $this->getJson('/api/tenant/v1/navigation/sidebar')
            ->assertOk()
            ->assertJsonStructure(['data' => ['navigation' => ['badges' => ['overdue_tasks', 'open_issues', 'pending_leave', 'renewals_due_soon']]]]);

        $projectUuid = $this->postJson('/api/tenant/v1/projects', [
            'project_number' => 'PRJ-000001',
            'name' => 'Project child actions',
        ])->assertCreated()->json('data.project.uuid');
        $base = '/api/tenant/v1/projects/'.$projectUuid;

        $phaseId = $this->postJson($base.'/phases', [
            'name' => 'Discovery',
            'start_date' => '2026-09-23',
        ])->assertCreated()->json('data.phases.id');
        $this->patchJson($base.'/phases/'.$phaseId, ['name' => 'Discovery updated'])
            ->assertOk()
            ->assertJsonPath('data.phases.name', 'Discovery updated');

        $milestoneId = $this->postJson($base.'/milestones', [
            'name' => 'Kickoff',
            'start_date' => '2026-09-23',
            'due_date' => '2026-09-30',
        ])->assertCreated()->json('data.milestones.id');
        $this->postJson($base.'/milestones/'.$milestoneId.'/complete')
            ->assertOk()
            ->assertJsonPath('data.milestone.id', $milestoneId);
        $this->assertNotNull(DB::table('project_milestones')->where('id', $milestoneId)->value('completed_at'));

        $memberId = $this->postJson($base.'/members', [
            'user_id' => $user->uuid,
            'allocation_percent' => 75,
        ])->assertCreated()
            ->assertJsonPath('data.members.user_id', $user->id)
            ->json('data.members.id');

        $this->getJson($base.'/members')->assertOk()->assertJsonCount(1, 'data.members');
        $this->getJson('/api/tenant/v1/projects/'.$projectUuid)
            ->assertOk()
            ->assertJsonCount(1, 'data.project.members')
            ->assertJsonCount(1, 'data.project.phases')
            ->assertJsonCount(1, 'data.project.milestones');
        $this->deleteJson($base.'/members/'.$memberId)->assertOk();
        $this->assertDatabaseMissing('project_members', ['id' => $memberId]);
    }

}
