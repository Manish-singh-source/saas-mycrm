<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TenantRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_can_register_with_owner_account(): void
    {
        DB::table('permissions')->insert([
            'uuid' => '11111111-1111-4111-8111-111111111111',
            'module' => 'dashboard',
            'name' => 'dashboard.view',
            'display_name' => 'Dashboard View',
            'guard_name' => 'tenant',
            'description' => 'Dashboard View',
            'is_system' => true,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->postJson('/api/auth/v1/tenants/register', [
            'organization_name' => 'Acme Pvt Ltd',
            'display_name' => 'Acme',
            'slug' => 'acme',
            'organization_code' => 'ACME',
            'default_currency' => 'INR',
            'default_timezone' => 'Asia/Kolkata',
            'owner' => [
                'first_name' => 'Sahil',
                'last_name' => 'Owner',
                'email' => 'owner@example.com',
                'mobile' => '+919999999999',
                'password' => 'Password@123',
                'password_confirmation' => 'Password@123',
            ],
            'office' => [
                'office_name' => 'Head Office',
                'address_line_1' => 'Main Street',
            ],
        ]);

        $response->assertCreated()
            ->assertJsonPath('message', 'Tenant registered.')
            ->assertJsonPath('data.tenant.slug', 'acme')
            ->assertJsonPath('data.owner.email', 'owner@example.com')
            ->assertJsonPath('data.roles.0', 'owner')
            ->assertJsonStructure(['data' => ['access_token', 'tenant' => ['uuid'], 'owner' => ['uuid']]]);

        $tenantId = DB::table('tenants')->where('slug', 'acme')->value('id');
        $ownerId = DB::table('users')->where('tenant_id', $tenantId)->where('email', 'owner@example.com')->value('id');
        $roleId = DB::table('roles')->where('tenant_id', $tenantId)->where('name', 'owner')->value('id');

        $this->assertNotNull($tenantId);
        $this->assertNotNull($ownerId);
        $this->assertDatabaseHas('tenant_offices', ['tenant_id' => $tenantId, 'office_code' => 'HO', 'is_default' => true]);
        $this->assertDatabaseHas('model_has_roles', ['tenant_id' => $tenantId, 'role_id' => $roleId, 'model_id' => $ownerId]);
        $this->assertDatabaseHas('role_has_permissions', ['role_id' => $roleId, 'permission_id' => 1]);
        $this->assertDatabaseHas('activity_logs', ['tenant_id' => $tenantId, 'actor_user_id' => $ownerId, 'event' => 'tenant_registered']);
    }
}
