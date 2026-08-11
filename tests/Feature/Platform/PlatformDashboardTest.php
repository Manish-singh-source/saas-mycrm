<?php

namespace Tests\Feature\Platform;

use App\Models\PlatformUser;
use Database\Seeders\PlatformDashboardDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PlatformDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_granular_endpoints_return_widget_payload_shapes(): void
    {
        Sanctum::actingAs($this->platformUser(), ['platform:*']);
        $this->seed(PlatformDashboardDemoSeeder::class);

        $this->getJson('/api/platform/v1/dashboard/charts/tenant-growth')
            ->assertOk()
            ->assertJsonStructure(['data' => [['date', 'count']]]);

        $this->getJson('/api/platform/v1/dashboard/charts/plan-distribution')
            ->assertOk()
            ->assertJsonStructure(['data' => [['name', 'count', 'revenue']]]);

        $this->getJson('/api/platform/v1/dashboard/charts/subscription-status')
            ->assertOk()
            ->assertJsonStructure(['data' => [['status', 'count']]]);

        $this->getJson('/api/platform/v1/dashboard/charts/usage')
            ->assertOk()
            ->assertJsonStructure(['data' => [['date', 'api', 'storage', 'users', 'projects', 'invoices']]]);

        $this->getJson('/api/platform/v1/dashboard/recent-tenants')
            ->assertOk()
            ->assertJsonStructure(['data' => [['uuid', 'organization_name', 'slug', 'owner_name', 'owner_email', 'plan_name', 'subscription_status', 'status', 'created_at']]]);

        $this->getJson('/api/platform/v1/dashboard/recent-payments')
            ->assertOk()
            ->assertJsonStructure(['data' => [['uuid', 'payment_number', 'organization_name', 'tenant_name', 'amount', 'currency', 'gateway', 'payment_status', 'paid_at']]]);

        $this->getJson('/api/platform/v1/dashboard/overdue-invoices')
            ->assertOk()
            ->assertJsonStructure(['data' => [['uuid', 'invoice_number', 'organization_name', 'tenant_name', 'balance_amount', 'currency', 'due_date', 'status']]]);

        $this->getJson('/api/platform/v1/dashboard/active-alerts')
            ->assertOk()
            ->assertJsonStructure(['data' => [['id', 'severity', 'message', 'status', 'triggered_at']]]);

        $this->getJson('/api/platform/v1/dashboard/security-events')
            ->assertOk()
            ->assertJsonStructure(['data' => [['id', 'event', 'severity', 'ip_address', 'created_at']]]);
    }

    public function test_dashboard_requires_dashboard_permission(): void
    {
        Sanctum::actingAs($this->platformUser(), []);

        $this->getJson('/api/platform/v1/dashboard/summary')
            ->assertForbidden()
            ->assertJsonPath('errors.code', 'PLATFORM_PERMISSION_DENIED');
    }

    public function test_monitoring_status_filters_accept_plain_and_filter_query_shapes(): void
    {
        Sanctum::actingAs($this->platformUser(), ['platform:*']);
        $this->seed(PlatformDashboardDemoSeeder::class);

        $plainJobs = $this->getJson('/api/platform/v1/monitoring/queue-jobs?status=failed')
            ->assertOk()
            ->json('data');
        $this->assertNotEmpty($plainJobs);
        $this->assertSame(['failed'], array_values(array_unique(array_column($plainJobs, 'status'))));

        $filteredJobs = $this->getJson('/api/platform/v1/monitoring/queue-jobs?filter[status]=failed')
            ->assertOk()
            ->json('data');
        $this->assertNotEmpty($filteredJobs);
        $this->assertSame(['failed'], array_values(array_unique(array_column($filteredJobs, 'status'))));

        $plainIncidents = $this->getJson('/api/platform/v1/monitoring/incidents?status=investigating,resolved')
            ->assertOk()
            ->json('data');
        $this->assertNotEmpty($plainIncidents);
        $this->assertEqualsCanonicalizing(['investigating', 'resolved'], array_values(array_unique(array_column($plainIncidents, 'status'))));

        $filteredIncidents = $this->getJson('/api/platform/v1/monitoring/incidents?filter[status]=investigating')
            ->assertOk()
            ->json('data');
        $this->assertNotEmpty($filteredIncidents);
        $this->assertSame(['investigating'], array_values(array_unique(array_column($filteredIncidents, 'status'))));
    }

    private function platformUser(): PlatformUser
    {
        return PlatformUser::query()->create([
            'uuid' => (string) Str::uuid(),
            'employee_code' => 'PLAT-TEST-'.Str::random(6),
            'first_name' => 'Platform',
            'last_name' => 'Tester',
            'display_name' => 'Platform Tester',
            'email' => 'platform.tester.'.Str::random(6).'@example.test',
            'password' => Hash::make('Password@123'),
            'timezone' => 'UTC',
            'locale' => 'en',
            'email_verified_at' => now(),
            'two_factor_enabled' => false,
            'status' => 'active',
        ]);
    }
}
