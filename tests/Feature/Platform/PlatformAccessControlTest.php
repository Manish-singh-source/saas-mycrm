<?php

namespace Tests\Feature\Platform;

use App\Models\PlatformPermission;
use App\Models\PlatformRole;
use App\Models\PlatformUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PlatformAccessControlTest extends TestCase
{
    use RefreshDatabase;

    public function test_roles_list_supports_search_filters_and_sorting(): void
    {
        Sanctum::actingAs($this->platformUser(), ['platform:*']);

        $alpha = $this->role(['name' => 'access_alpha', 'display_name' => 'Alpha Role', 'status' => 'active']);
        $this->role(['name' => 'access_beta', 'display_name' => 'Beta Role', 'status' => 'inactive', 'is_system' => true]);

        $response = $this->getJson('/api/platform/v1/access-control/roles?search=alpha&filter[status]=active&filter[type]=custom&filter[guard_name]=platform&sort=display_name&direction=desc');

        $response->assertOk()
            ->assertJsonPath('data.0.uuid', $alpha->uuid)
            ->assertJsonCount(1, 'data');
    }

    public function test_permissions_list_supports_search_filters_and_sorting(): void
    {
        Sanctum::actingAs($this->platformUser(), ['platform:*']);

        $permission = $this->permission(['module' => 'billing', 'name' => 'billing.invoice.view', 'display_name' => 'View Invoices']);
        $this->permission(['module' => 'support', 'name' => 'support.ticket.view', 'display_name' => 'View Tickets', 'status' => 'inactive']);

        $response = $this->getJson('/api/platform/v1/access-control/permissions?search=invoice&filter[module]=billing&filter[status]=active&filter[guard_name]=platform&sort=name&direction=asc');

        $response->assertOk()
            ->assertJsonPath('data.0.uuid', $permission->uuid)
            ->assertJsonCount(1, 'data');
    }

    public function test_role_exports_support_job_and_download_delivery(): void
    {
        Sanctum::actingAs($this->platformUser(), ['platform:*']);
        $role = $this->role(['name' => 'export_role', 'display_name' => 'Export Role']);

        $this->postJson('/api/platform/v1/access-control/roles/export', [
            'format' => 'csv',
            'delivery' => 'job',
            'scope' => 'filtered',
            'filters' => ['status' => 'active'],
            'sort' => 'name',
            'direction' => 'asc',
        ])->assertCreated()->assertJsonPath('data.export.report_code', 'platform-roles');

        $download = $this->postJson('/api/platform/v1/access-control/roles/export', [
            'format' => 'csv',
            'delivery' => 'download',
            'scope' => 'selected',
            'selected_ids' => [$role->uuid],
            'columns' => ['uuid', 'name', 'display_name', 'status'],
        ]);

        $download->assertCreated()->assertJsonPath('data.download.mime_type', 'text/csv');
        $this->assertStringContainsString('export_role', $download->json('data.download.content'));
    }

    public function test_role_create_allows_empty_permission_ids(): void
    {
        Sanctum::actingAs($this->platformUser(), ['platform:*']);

        $response = $this->postJson('/api/platform/v1/access-control/roles', [
            'name' => 'empty_permission_role',
            'display_name' => 'Empty Permission Role',
            'guard_name' => 'platform',
            'description' => 'Role can be created without initial permissions.',
            'is_system' => false,
            'status' => 'active',
            'permission_ids' => [],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.role.name', 'empty_permission_role');

        $role = PlatformRole::query()->where('name', 'empty_permission_role')->firstOrFail();
        $this->assertSame(0, $role->permissions()->count());
    }

    public function test_permission_exports_and_delete_guards_work(): void
    {
        Sanctum::actingAs($this->platformUser(), ['platform:*']);
        $permission = $this->permission(['module' => 'access', 'name' => 'access.export.test']);
        $role = $this->role(['name' => 'permission_guard_role']);
        $role->permissions()->attach($permission->id);

        $this->deleteJson('/api/platform/v1/access-control/permissions/'.$permission->uuid, ['audit_reason' => 'test guard'])
            ->assertStatus(409)
            ->assertJsonPath('errors.code', 'PERMISSION_IN_USE');

        $download = $this->postJson('/api/platform/v1/access-control/permissions/export', [
            'format' => 'csv',
            'delivery' => 'download',
            'scope' => 'selected',
            'selected_ids' => [$permission->uuid],
            'columns' => ['uuid', 'module', 'name', 'status'],
        ]);

        $download->assertCreated()->assertJsonPath('data.download.mime_type', 'text/csv');
        $this->assertStringContainsString('access.export.test', $download->json('data.download.content'));
    }

    public function test_audit_logs_filter_and_export_contracts_work(): void
    {
        $user = $this->platformUser();
        Sanctum::actingAs($user, ['platform:*']);

        DB::table('activity_logs')->insert([
            'tenant_id' => null,
            'actor_user_id' => null,
            'actor_platform_user_id' => $user->id,
            'subject_type' => 'platform_roles',
            'subject_id' => 123,
            'event' => 'platform_role_created',
            'description' => 'Created role for test',
            'old_values' => null,
            'new_values' => json_encode(['name' => 'test']),
            'ip_address' => '127.0.0.1',
            'request_id' => 'test-request',
            'created_at' => now(),
        ]);

        $this->getJson('/api/platform/v1/audit/activity-logs?filter[event]=platform_role_created&sort=created_at&direction=desc')
            ->assertOk()
            ->assertJsonPath('data.0.event', 'platform_role_created');

        $this->postJson('/api/platform/v1/audit/export', [
            'format' => 'csv',
            'delivery' => 'job',
            'filters' => ['event' => 'platform_role_created'],
        ])->assertCreated()->assertJsonPath('data.export.report_code', 'audit-logs');

        $download = $this->postJson('/api/platform/v1/audit/export', [
            'format' => 'csv',
            'delivery' => 'download',
            'filters' => ['event' => 'platform_role_created'],
            'columns' => ['event', 'subject_type', 'description'],
        ]);

        $download->assertCreated()->assertJsonPath('data.download.mime_type', 'text/csv');
        $this->assertStringContainsString('platform_role_created', $download->json('data.download.content'));
    }

    public function test_export_routes_require_platform_permissions(): void
    {
        Sanctum::actingAs($this->platformUser(), []);

        $this->postJson('/api/platform/v1/access-control/roles/export', ['format' => 'csv'])->assertForbidden();
        $this->postJson('/api/platform/v1/access-control/permissions/export', ['format' => 'csv'])->assertForbidden();
        $this->postJson('/api/platform/v1/audit/export', ['format' => 'csv'])->assertForbidden();
    }

    private function role(array $attributes = []): PlatformRole
    {
        return PlatformRole::query()->create(array_merge([
            'name' => 'role_'.str()->random(8),
            'display_name' => 'Role '.str()->random(8),
            'guard_name' => 'platform',
            'description' => 'Access-control test role',
            'is_system' => false,
            'status' => 'active',
        ], $attributes));
    }

    private function permission(array $attributes = []): PlatformPermission
    {
        return PlatformPermission::query()->create(array_merge([
            'module' => 'access',
            'name' => 'permission.'.str()->random(8),
            'display_name' => 'Permission '.str()->random(8),
            'guard_name' => 'platform',
            'description' => 'Access-control test permission',
            'is_system' => false,
            'status' => 'active',
        ], $attributes));
    }

    private function platformUser(array $attributes = []): PlatformUser
    {
        return PlatformUser::query()->create(array_merge([
            'uuid' => (string) str()->uuid(),
            'employee_code' => 'TEST-'.str()->upper(str()->random(6)),
            'first_name' => 'Access',
            'last_name' => 'Tester',
            'display_name' => 'Access Tester',
            'email' => str()->random(10).'@example.test',
            'password' => 'Password@123',
            'status' => 'active',
            'email_verified_at' => now(),
        ], $attributes));
    }
}
