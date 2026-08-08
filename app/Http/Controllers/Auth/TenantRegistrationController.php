<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Shared\AuthAuditService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class TenantRegistrationController extends Controller
{
    public function __construct(private readonly AuthAuditService $audit) {}

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'organization_name' => ['required', 'string', 'max:200'],
            'legal_name' => ['nullable', 'string', 'max:200'],
            'display_name' => ['nullable', 'string', 'max:200'],
            'organization_code' => ['nullable', 'string', 'max:50', 'alpha_dash', Rule::unique('tenants', 'organization_code')],
            'slug' => ['nullable', 'string', 'max:150', 'alpha_dash', Rule::unique('tenants', 'slug')],
            'business_type_id' => ['nullable', 'integer', 'exists:business_types,id'],
            'industry_id' => ['nullable', 'integer', 'exists:industries,id'],
            'company_size' => ['nullable', Rule::in(['self', 'small', 'medium', 'large', 'enterprise'])],
            'gst_number' => ['nullable', 'string', 'max:30'],
            'pan_number' => ['nullable', 'string', 'max:30'],
            'registration_number' => ['nullable', 'string', 'max:80'],
            'website' => ['nullable', 'string', 'max:255'],
            'default_currency' => ['nullable', 'string', 'size:3'],
            'default_timezone' => ['nullable', 'string', 'max:100'],
            'owner.first_name' => ['required', 'string', 'max:100'],
            'owner.last_name' => ['nullable', 'string', 'max:100'],
            'owner.display_name' => ['nullable', 'string', 'max:200'],
            'owner.email' => ['required', 'email', 'max:150'],
            'owner.mobile' => ['nullable', 'string', 'max:20'],
            'owner.password' => ['required', 'confirmed', 'min:8'],
            'office.office_name' => ['nullable', 'string', 'max:150'],
            'office.address_line_1' => ['nullable', 'string', 'max:255'],
            'office.address_line_2' => ['nullable', 'string', 'max:255'],
            'office.landmark' => ['nullable', 'string', 'max:255'],
            'office.country_id' => ['nullable', 'integer', 'exists:countries,id'],
            'office.state_id' => ['nullable', 'integer', 'exists:states,id'],
            'office.city_id' => ['nullable', 'integer', 'exists:cities,id'],
            'office.postal_code' => ['nullable', 'string', 'max:20'],
            'office.contact_phone' => ['nullable', 'string', 'max:20'],
        ]);

        $email = Str::lower($data['owner']['email']);

        return DB::transaction(function () use ($request, $data, $email): JsonResponse {
            $tenant = Tenant::query()->create([
                'uuid' => (string) Str::uuid(),
                'organization_name' => $data['organization_name'],
                'legal_name' => $data['legal_name'] ?? null,
                'display_name' => $data['display_name'] ?? $data['organization_name'],
                'organization_code' => $data['organization_code'] ?? $this->uniqueCode($data['organization_name']),
                'slug' => $data['slug'] ?? $this->uniqueSlug($data['organization_name']),
                'business_type_id' => $data['business_type_id'] ?? null,
                'industry_id' => $data['industry_id'] ?? null,
                'company_size' => $data['company_size'] ?? null,
                'gst_number' => $data['gst_number'] ?? null,
                'pan_number' => $data['pan_number'] ?? null,
                'registration_number' => $data['registration_number'] ?? null,
                'website' => $data['website'] ?? null,
                'default_currency' => strtoupper($data['default_currency'] ?? 'INR'),
                'default_timezone' => $data['default_timezone'] ?? 'Asia/Kolkata',
                'onboarded_at' => now(),
                'trial_ends_at' => now()->addDays(14),
                'status' => 'trial',
            ]);

            $officeId = $this->createHeadOffice($tenant, $data['office'] ?? [], $data['owner']);
            $owner = User::query()->create([
                'uuid' => (string) Str::uuid(),
                'tenant_id' => $tenant->id,
                'default_office_id' => $officeId,
                'first_name' => $data['owner']['first_name'],
                'last_name' => $data['owner']['last_name'] ?? null,
                'display_name' => $data['owner']['display_name'] ?? trim($data['owner']['first_name'].' '.($data['owner']['last_name'] ?? '')),
                'email' => $email,
                'mobile' => $data['owner']['mobile'] ?? null,
                'password' => Hash::make($data['owner']['password']),
                'timezone' => $tenant->default_timezone,
                'locale' => 'en',
                'account_type' => 'owner',
                'email_verified_at' => now(),
                'status' => 'active',
            ]);

            $this->assignOwnerRole($tenant, $owner);
            $token = $owner->createToken('tenant-registration', ['tenant:'.$tenant->uuid], now()->addHours(12));
            $this->audit->log($request, 'tenant_registered', tenantUser: $owner, metadata: ['tenant_uuid' => $tenant->uuid, 'tenant_slug' => $tenant->slug]);

            return ApiResponse::success([
                'access_token' => $token->plainTextToken,
                'token_type' => 'Bearer',
                'expires_at' => optional($token->accessToken->expires_at)->toISOString(),
                'tenant' => $tenant->only(['uuid', 'organization_name', 'display_name', 'organization_code', 'slug', 'default_currency', 'default_timezone', 'status', 'trial_ends_at']),
                'owner' => $owner->only(['uuid', 'display_name', 'email', 'mobile', 'account_type', 'status']),
                'roles' => ['owner'],
                'permissions' => DB::table('permissions')->where('guard_name', 'tenant')->pluck('name')->all(),
            ], 'Tenant registered.', Response::HTTP_CREATED);
        });
    }

    private function createHeadOffice(Tenant $tenant, array $office, array $owner): int
    {
        return (int) DB::table('tenant_offices')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'tenant_id' => $tenant->id,
            'office_name' => $office['office_name'] ?? 'Head Office',
            'office_code' => 'HO',
            'office_type' => 'head_office',
            'is_head_office' => true,
            'is_default' => true,
            'address_line_1' => $office['address_line_1'] ?? null,
            'address_line_2' => $office['address_line_2'] ?? null,
            'landmark' => $office['landmark'] ?? null,
            'country_id' => $office['country_id'] ?? null,
            'state_id' => $office['state_id'] ?? null,
            'city_id' => $office['city_id'] ?? null,
            'postal_code' => $office['postal_code'] ?? null,
            'contact_person' => $owner['display_name'] ?? trim($owner['first_name'].' '.($owner['last_name'] ?? '')),
            'contact_email' => Str::lower($owner['email']),
            'contact_phone' => $office['contact_phone'] ?? $owner['mobile'] ?? null,
            'timezone' => $tenant->default_timezone,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function assignOwnerRole(Tenant $tenant, User $owner): void
    {
        $roleId = (int) DB::table('roles')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'tenant_id' => $tenant->id,
            'name' => 'owner',
            'display_name' => 'Owner',
            'guard_name' => 'tenant',
            'description' => 'Full tenant ownership access.',
            'is_system' => true,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach (DB::table('permissions')->where('guard_name', 'tenant')->pluck('id') as $permissionId) {
            DB::table('role_has_permissions')->insert(['role_id' => $roleId, 'permission_id' => $permissionId]);
        }

        DB::table('model_has_roles')->insert([
            'tenant_id' => $tenant->id,
            'role_id' => $roleId,
            'model_id' => $owner->id,
            'model_type' => User::class,
        ]);
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'tenant';
        $slug = $base;
        $i = 2;
        while (Tenant::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }
        return $slug;
    }

    private function uniqueCode(string $name): string
    {
        $base = Str::upper(Str::substr(preg_replace('/[^A-Za-z0-9]/', '', $name) ?: 'TENANT', 0, 12));
        $code = $base;
        $i = 2;
        while (Tenant::query()->where('organization_code', $code)->exists()) {
            $code = Str::substr($base, 0, 10).$i++;
        }
        return $code;
    }
}
