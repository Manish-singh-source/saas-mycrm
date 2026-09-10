<?php

namespace App\Support;

use App\Models\Tenant;
use Illuminate\Contracts\Support\Arrayable;

final class TenantPresenter
{
    public const RELATIONS = ['owner.defaultOffice', 'owner.roles', 'headOffice.country', 'headOffice.state', 'headOffice.city', 'subscription.plan', 'businessType', 'industry', 'logoFile', 'faviconFile'];

    public static function summary(Tenant $tenant): array
    {
        $owner = $tenant->owner;
        $subscription = $tenant->subscription;

        return [
            'id' => $tenant->id,
            'uuid' => $tenant->uuid,
            'organization_name' => $tenant->organization_name,
            'legal_name' => $tenant->legal_name,
            'display_name' => $tenant->display_name,
            'organization_code' => $tenant->organization_code,
            'owner_name' => $owner?->display_name,
            'owner_email' => $owner?->email,
            'users_count' => (int) ($tenant->users_count ?? $tenant->users()->count()),
            'slug' => $tenant->slug,
            'trial_ends_at' => $tenant->trial_ends_at,
            'status' => $tenant->status,
            'plan_name' => $subscription?->plan?->name,
            'subscription_status' => $subscription?->status,
            'created_at' => $tenant->created_at?->toISOString(),
        ];
    }
    public static function tenant(Tenant $tenant): array
    {
        $tenant->loadMissing(self::RELATIONS);
        if (! isset($tenant->users_count)) {
            $tenant->loadCount('users');
        }
        $data = self::safe($tenant);
        $plan = $data['subscription']['plan'] ?? null;
        $data['owner_name'] = $data['owner']['display_name'] ?? null;
        $data['owner_email'] = $data['owner']['email'] ?? null;
        $data['subscription_status'] = $data['subscription']['status'] ?? null;
        $data['current_plan'] = $plan;
        $data['plan_name'] = $plan['name'] ?? null;
        $data['plan_uuid'] = $plan['uuid'] ?? null;

        return $data;
    }

    public static function detail(Tenant $tenant): array
    {
        $data = self::tenant($tenant);

        return ['tenant' => $data, 'owner' => $data['owner'], 'office' => $data['head_office'], 'subscription' => $data['subscription']];
    }

    public static function safe(mixed $value): mixed
    {
        if ($value instanceof Arrayable) {
            $value = $value->toArray();
        } elseif (is_object($value)) {
            $value = (array) $value;
        }
        if (! is_array($value)) {
            return $value;
        }
        $sensitive = ['password', 'password_confirmation', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes', 'encrypted_value', 'token_hash', 'encrypted_token_preview', 'secret_hash', 'path', 'disk'];
        foreach ($value as $key => $item) {
            if (in_array($key, $sensitive, true)) {
                unset($value[$key]);

                continue;
            }
            $value[$key] = self::safe($item);
        }
        if (isset($value['plan']) && is_array($value['plan'])) {
            foreach (['uuid', 'name', 'code'] as $key) {
                $value['plan_'.$key] = $value['plan'][$key] ?? null;
            }
        }

        return $value;
    }
}
