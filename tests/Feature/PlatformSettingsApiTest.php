<?php

namespace Tests\Feature;

use App\Models\PlatformUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class PlatformSettingsApiTest extends TestCase
{
    use RefreshDatabase;

    private function platformUser(): PlatformUser
    {
        $user = PlatformUser::forceCreate([
            'uuid' => (string) Str::uuid(),
            'employee_code' => Str::random(10),
            'first_name' => 'Admin',
            'display_name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('old-password'),
            'status' => 'active',
        ]);
        Sanctum::actingAs($user, ['*']);

        return $user;
    }

    public function test_profile_update_accepts_documented_fields(): void
    {
        $user = $this->platformUser();

        $this->putJson('/api/platform/v1/profile', [
            'first_name' => 'Updated',
            'display_name' => 'Updated Admin',
            'timezone' => 'Asia/Kolkata',
        ])->assertOk()->assertJsonPath('data.user.display_name', 'Updated Admin');

        $this->assertDatabaseHas('platform_users', ['id' => $user->id, 'first_name' => 'Updated', 'timezone' => 'Asia/Kolkata']);
    }

    public function test_password_update_requires_confirmation_and_changes_password(): void
    {
        $user = $this->platformUser();

        $this->putJson('/api/platform/v1/profile/password', [
            'current_password' => 'old-password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertOk()->assertJsonPath('success', true);

        $this->assertTrue(Hash::check('new-password', $user->fresh()->password));
    }

    public function test_platform_configuration_accepts_documented_settings_array(): void
    {
        $this->platformUser();

        $this->putJson('/api/platform/v1/platform', [
            'settings' => [[
                'group' => 'general',
                'key' => 'test.handover',
                'value' => 'enabled',
                'value_type' => 'string',
                'is_encrypted' => false,
            ]],
        ])->assertOk();

        $this->assertDatabaseHas('platform_settings', ['group' => 'general', 'key' => 'test.handover', 'value_type' => 'string']);
    }

    public function test_preferences_update_accepts_documented_credentials_and_values(): void
    {
        $user = $this->platformUser();

        $this->putJson('/api/platform/v1/settings/preferences', [
            'email' => $user->email,
            'password' => 'old-password',
            'preferences' => ['display' => ['density' => 'compact']],
        ])->assertOk()->assertJsonPath('success', true);

        $this->assertDatabaseHas('platform_user_preferences', [
            'platform_user_id' => $user->id,
            'group' => 'display',
            'key' => 'density',
        ]);
    }
}

