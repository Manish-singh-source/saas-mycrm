<?php

namespace Tests\Feature\Security;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Tenant;
use App\Models\User;
use App\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('test_tenant_records', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();
        });
    }

    public function test_tenant_scoped_queries_cannot_read_records_from_another_tenant(): void
    {
        [$tenantA, $tenantB] = $this->tenants();

        TenantScopedTestRecord::withoutGlobalScope('tenant')->create(['uuid' => '11111111-1111-4111-8111-111111111111', 'tenant_id' => $tenantA->id, 'name' => 'Tenant A Record']);
        TenantScopedTestRecord::withoutGlobalScope('tenant')->create(['uuid' => '22222222-2222-4222-8222-222222222222', 'tenant_id' => $tenantB->id, 'name' => 'Tenant B Record']);
        app(TenantContext::class)->set($tenantA);

        $this->assertSame(['Tenant A Record'], TenantScopedTestRecord::query()->pluck('name')->all());
        $this->assertNull(TenantScopedTestRecord::query()->where('uuid', '22222222-2222-4222-8222-222222222222')->first());
    }

    public function test_tenant_scoped_queries_cannot_update_records_from_another_tenant_by_uuid(): void
    {
        [$tenantA, $tenantB] = $this->tenants();

        TenantScopedTestRecord::withoutGlobalScope('tenant')->create(['uuid' => '11111111-1111-4111-8111-111111111111', 'tenant_id' => $tenantA->id, 'name' => 'Tenant A Record']);
        TenantScopedTestRecord::withoutGlobalScope('tenant')->create(['uuid' => '22222222-2222-4222-8222-222222222222', 'tenant_id' => $tenantB->id, 'name' => 'Tenant B Record']);
        app(TenantContext::class)->set($tenantA);

        $updated = TenantScopedTestRecord::query()
            ->where('uuid', '22222222-2222-4222-8222-222222222222')
            ->update(['name' => 'Updated By Wrong Tenant']);

        $this->assertSame(0, $updated);
        $this->assertSame('Tenant B Record', TenantScopedTestRecord::withoutGlobalScope('tenant')->where('tenant_id', $tenantB->id)->value('name'));
    }

    public function test_tenant_scoped_queries_cannot_delete_records_from_another_tenant_by_uuid(): void
    {
        [$tenantA, $tenantB] = $this->tenants();

        TenantScopedTestRecord::withoutGlobalScope('tenant')->create(['uuid' => '11111111-1111-4111-8111-111111111111', 'tenant_id' => $tenantA->id, 'name' => 'Tenant A Record']);
        TenantScopedTestRecord::withoutGlobalScope('tenant')->create(['uuid' => '22222222-2222-4222-8222-222222222222', 'tenant_id' => $tenantB->id, 'name' => 'Tenant B Record']);
        app(TenantContext::class)->set($tenantA);

        $deleted = TenantScopedTestRecord::query()
            ->where('uuid', '22222222-2222-4222-8222-222222222222')
            ->delete();

        $this->assertSame(0, $deleted);
        $this->assertTrue(TenantScopedTestRecord::withoutGlobalScope('tenant')->where('tenant_id', $tenantB->id)->exists());
    }

    public function test_tenant_middleware_rejects_authenticated_user_from_another_tenant(): void
    {
        [$tenantA, $tenantB] = $this->tenants();
        $user = $this->userForTenant($tenantA);

        Route::middleware(['tenant.context', 'auth:sanctum', 'tenant.token'])
            ->get('/api/testing/tenant-context', fn () => response()->json(['tenant_id' => app(TenantContext::class)->id()]));

        Sanctum::actingAs($user, ['tenant:'.$tenantA->uuid]);

        $this->getJson('/api/testing/tenant-context', ['X-Tenant' => $tenantB->slug])
            ->assertForbidden()
            ->assertJsonPath('errors.code', 'TENANT_TOKEN_MISMATCH');
    }

    public function test_tenant_context_autofills_tenant_id_when_creating_records(): void
    {
        [$tenantA] = $this->tenants();
        app(TenantContext::class)->set($tenantA);

        $record = TenantScopedTestRecord::createForCurrentTenant(['uuid' => '33333333-3333-4333-8333-333333333333', 'name' => 'Auto Tenant Record']);

        $this->assertSame($tenantA->id, $record->tenant_id);
    }

    private function tenants(): array
    {
        return [
            Tenant::query()->create(['uuid' => 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', 'organization_name' => 'Tenant A Pvt Ltd', 'display_name' => 'Tenant A', 'organization_code' => 'TENANTA', 'slug' => 'tenant-a', 'default_currency' => 'INR', 'default_timezone' => 'Asia/Kolkata', 'status' => 'active']),
            Tenant::query()->create(['uuid' => 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb', 'organization_name' => 'Tenant B Pvt Ltd', 'display_name' => 'Tenant B', 'organization_code' => 'TENANTB', 'slug' => 'tenant-b', 'default_currency' => 'INR', 'default_timezone' => 'Asia/Kolkata', 'status' => 'active']),
        ];
    }

    private function userForTenant(Tenant $tenant): User
    {
        return User::query()->create([
            'uuid' => 'cccccccc-cccc-4ccc-8ccc-cccccccccccc',
            'tenant_id' => $tenant->id,
            'first_name' => 'Tenant',
            'last_name' => 'User',
            'display_name' => 'Tenant User',
            'email' => 'tenant.user@example.com',
            'password' => Hash::make('Password@123'),
            'timezone' => 'Asia/Kolkata',
            'locale' => 'en',
            'account_type' => 'owner',
            'status' => 'active',
        ]);
    }
}

class TenantScopedTestRecord extends Model
{
    use BelongsToTenant;

    protected $table = 'test_tenant_records';

    protected $fillable = ['uuid', 'tenant_id', 'name'];
}