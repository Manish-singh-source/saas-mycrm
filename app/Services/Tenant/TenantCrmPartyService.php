<?php

namespace App\Services\Tenant;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class TenantCrmPartyService extends TenantWorkspaceService
{
    public function createPartyBundle(Request $request, string $profileTable, string $codeColumn, array $data, string $partyKind): object
    {
        return DB::transaction(function () use ($request, $profileTable, $codeColumn, $data, $partyKind): object {
            $party = $this->partyPayload($request, $data['party'] ?? [], $partyKind);
            $partyId = DB::table('parties')->insertGetId($party);
            $profile = $this->profilePayload($data['profile'] ?? [], $profileTable);
            $this->assertUnique($profileTable, $codeColumn, (string) ($profile[$codeColumn] ?? ''));
            DB::table($profileTable)->insert([...$profile, 'tenant_id' => $this->tenantId(), 'party_id' => $partyId, 'created_at' => now(), 'updated_at' => now()]);
            $this->replaceContacts($request, $partyId, $data['contacts'] ?? []);
            $this->replaceAddresses($partyId, $data['addresses'] ?? []);
            $this->syncTags($partyId, $data['tag_ids'] ?? []);
            $this->audit($request, 'tenant_'.$partyKind.'_created', 'party', $partyId, null, ['profile' => $profile]);

            return DB::table('parties')->where('id', $partyId)->first();
        });
    }

    public function updatePartyBundle(Request $request, int $partyId, string $profileTable, string $codeColumn, array $data, string $partyKind): object
    {
        return DB::transaction(function () use ($request, $partyId, $profileTable, $codeColumn, $data, $partyKind): object {
            $old = $this->bundle($profileTable, $partyId);
            if (isset($data['party'])) {
                DB::table('parties')->where('id', $partyId)->update($this->partyPayload($request, $data['party'], $partyKind, true));
            }
            if (isset($data['profile'])) {
                $profile = $this->profilePayload($data['profile'], $profileTable, true);
                if (isset($profile[$codeColumn])) {
                    $this->assertUnique($profileTable, $codeColumn, (string) $profile[$codeColumn], $partyId);
                }
                DB::table($profileTable)->where('tenant_id', $this->tenantId())->where('party_id', $partyId)->update([...$profile, 'updated_at' => now()]);
            }
            if (array_key_exists('contacts', $data)) {
                $this->replaceContacts($request, $partyId, $data['contacts'] ?? []);
            }
            if (array_key_exists('addresses', $data)) {
                $this->replaceAddresses($partyId, $data['addresses'] ?? []);
            }
            if (array_key_exists('tag_ids', $data)) {
                $this->syncTags($partyId, $data['tag_ids'] ?? []);
            }
            $this->audit($request, 'tenant_'.$partyKind.'_updated', 'party', $partyId, $old, $this->bundle($profileTable, $partyId));

            return DB::table('parties')->where('id', $partyId)->first();
        });
    }

    public function bundle(string $profileTable, int $partyId): array
    {
        return [
            'party' => DB::table('parties')->where('tenant_id', $this->tenantId())->where('id', $partyId)->first(),
            'profile' => DB::table($profileTable)->where('tenant_id', $this->tenantId())->where('party_id', $partyId)->first(),
            'contacts' => DB::table('party_contacts')->where('tenant_id', $this->tenantId())->where('party_id', $partyId)->whereNull('deleted_at')->get()->all(),
            'addresses' => DB::table('party_addresses')->where('tenant_id', $this->tenantId())->where('party_id', $partyId)->get()->all(),
        ];
    }

    public function findProfile(string $profileTable, string $uuid): object
    {
        return DB::table($profileTable)
            ->join('parties', 'parties.id', '=', $profileTable.'.party_id')
            ->where($profileTable.'.tenant_id', $this->tenantId())
            ->where('parties.uuid', $uuid)
            ->whereNull('parties.deleted_at')
            ->first([$profileTable.'.*', 'parties.uuid as party_uuid', 'parties.display_name', 'parties.email', 'parties.phone'])
            ?: abort(404, 'Resource not found.');
    }

    public function contactData(Request $request): array
    {
        return $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'display_name' => ['nullable', 'string', 'max:200'],
            'email' => ['nullable', 'email', 'max:150'],
            'mobile' => ['nullable', 'string', 'max:20'],
            'phone' => ['nullable', 'string', 'max:30'],
            'designation' => ['nullable', 'string', 'max:100'],
            'department' => ['nullable', 'string', 'max:100'],
            'is_primary' => ['nullable', 'boolean'],
            'portal_enabled' => ['nullable', 'boolean'],
            'create_portal_user' => ['nullable', 'boolean'],
            'status' => ['nullable', 'string', 'max:50'],
        ]);
    }

    public function addressData(Request $request): array
    {
        $data = $request->validate([
            'address_type' => ['required', 'string', 'max:50'],
            'address_line_1' => ['required', 'string'],
            'address_line_2' => ['nullable', 'string'],
            'country_id' => ['nullable'],
            'state_id' => ['nullable'],
            'city_id' => ['nullable'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'is_default' => ['nullable', 'boolean'],
        ]);
        foreach (['country_id' => 'countries', 'state_id' => 'states', 'city_id' => 'cities'] as $key => $table) {
            if (array_key_exists($key, $data)) {
                $data[$key] = $this->publicUuidToId($table, $data[$key]);
            }
        }

        return $data;
    }

    public function saveContact(Request $request, int $partyId, array $data, ?int $contactId = null): object
    {
        $payload = $this->normalizeContact($data);
        if ($contactId) {
            DB::table('party_contacts')->where('tenant_id', $this->tenantId())->where('party_id', $partyId)->where('id', $contactId)->update([...$payload, 'updated_at' => now()]);
            $id = $contactId;
        } else {
            $id = DB::table('party_contacts')->insertGetId([...$payload, 'uuid' => (string) Str::uuid(), 'tenant_id' => $this->tenantId(), 'party_id' => $partyId, 'created_at' => now(), 'updated_at' => now()]);
        }
        $contact = DB::table('party_contacts')->where('id', $id)->first();
        if (($data['create_portal_user'] ?? false) || ($data['portal_enabled'] ?? false)) {
            $this->upsertPortalUser($request, $partyId, $contact);
        }

        return $contact;
    }

    public function saveAddress(int $partyId, array $data, ?int $addressId = null): object
    {
        if ($addressId) {
            DB::table('party_addresses')->where('tenant_id', $this->tenantId())->where('party_id', $partyId)->where('id', $addressId)->update([...$data, 'updated_at' => now()]);
            $id = $addressId;
        } else {
            $id = DB::table('party_addresses')->insertGetId([...$data, 'tenant_id' => $this->tenantId(), 'party_id' => $partyId, 'created_at' => now(), 'updated_at' => now()]);
        }

        return DB::table('party_addresses')->where('id', $id)->first();
    }

    public function mergeParties(Request $request, string $profileTable, string $primaryUuid, array $duplicateUuids, array $relations, string $event): void
    {
        DB::transaction(function () use ($request, $profileTable, $primaryUuid, $duplicateUuids, $relations, $event): void {
            $primary = $this->findProfile($profileTable, $primaryUuid);
            foreach ($duplicateUuids as $uuid) {
                $duplicate = $this->findProfile($profileTable, $uuid);
                foreach ($relations as $table => $column) {
                    if (Schema::hasTable($table) && Schema::hasColumn($table, $column)) {
                        DB::table($table)->where('tenant_id', $this->tenantId())->where($column, $duplicate->party_id)->update([$column => $primary->party_id]);
                    }
                }
                DB::table('party_contacts')->where('tenant_id', $this->tenantId())->where('party_id', $duplicate->party_id)->update(['party_id' => $primary->party_id]);
                DB::table('party_addresses')->where('tenant_id', $this->tenantId())->where('party_id', $duplicate->party_id)->update(['party_id' => $primary->party_id]);
                DB::table('parties')->where('id', $duplicate->party_id)->update(['deleted_at' => now(), 'updated_at' => now()]);
                $this->audit($request, $event, 'party', $duplicate->party_id, ['merged_into' => $primary->party_id], null, $request->input('reason'));
            }
        });
    }

    public function bankPayload(object $row): array
    {
        $account = null;
        try {
            $account = Crypt::decryptString((string) $row->account_number_encrypted);
        } catch (\Throwable) {
        }
        $data = (array) $row;
        unset($data['account_number_encrypted'], $data['routing_number_encrypted']);
        $data['account_number_masked'] = $account ? str_repeat('*', max(strlen($account) - 4, 0)).substr($account, -4) : null;

        return $data;
    }

    public function publicUuidToId(string $table, mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_numeric($value)) {
            return (int) $value;
        }
        $query = DB::table($table)->where('uuid', $value);
        if (Schema::hasColumn($table, 'tenant_id')) {
            $query->where(fn ($q) => $q->whereNull('tenant_id')->orWhere('tenant_id', $this->tenantId()));
        }

        return ($query->first()?->id) ?: abort(404, 'Referenced resource not found.');
    }

    private function partyPayload(Request $request, array $data, string $partyKind, bool $partial = false): array
    {
        foreach (['industry_id' => 'industries', 'source_id' => 'tenant_lookups', 'status_id' => 'tenant_lookups', 'owner_user_id' => 'users'] as $key => $table) {
            if (array_key_exists($key, $data)) {
                $data[$key] = $this->publicUuidToId($table, $data[$key]);
            }
        }
        if (array_key_exists('metadata', $data)) {
            $data['metadata'] = json_encode($data['metadata'] ?? []);
        }
        $data['party_type'] = $data['party_type'] ?? 'company';

        return $partial
            ? [...$data, 'updated_by' => $request->user()?->id, 'updated_at' => now()]
            : [...$data, 'uuid' => (string) Str::uuid(), 'tenant_id' => $this->tenantId(), 'created_by' => $request->user()?->id, 'created_at' => now(), 'updated_at' => now()];
    }

    private function profilePayload(array $data, string $profileTable, bool $partial = false): array
    {
        $allowed = match ($profileTable) {
            'client_profiles' => ['client_code', 'client_type', 'credit_limit', 'payment_terms_days', 'onboarding_date', 'account_manager_id'],
            'vendor_profiles' => ['vendor_code', 'vendor_category_id', 'payment_terms_days', 'rating', 'account_manager_id'],
            'lead_profiles' => ['lead_number', 'stage_id', 'priority_id', 'expected_value', 'probability', 'expected_close_date', 'converted_client_party_id', 'converted_at', 'lost_reason'],
            default => array_keys($data),
        };
        $data = array_intersect_key($data, array_flip($allowed));
        foreach (['account_manager_id' => 'users', 'vendor_category_id' => 'tenant_lookups', 'stage_id' => 'tenant_lookups', 'priority_id' => 'tenant_lookups'] as $key => $table) {
            if (array_key_exists($key, $data)) {
                $data[$key] = $this->publicUuidToId($table, $data[$key]);
            }
        }

        return $data;
    }

    private function replaceContacts(Request $request, int $partyId, array $contacts): void
    {
        foreach ($contacts as $contact) {
            $this->saveContact($request, $partyId, $contact);
        }
    }

    private function replaceAddresses(int $partyId, array $addresses): void
    {
        foreach ($addresses as $address) {
            $data = array_intersect_key($address, array_flip(['address_type', 'address_line_1', 'address_line_2', 'country_id', 'state_id', 'city_id', 'postal_code', 'is_default']));
            foreach (['country_id' => 'countries', 'state_id' => 'states', 'city_id' => 'cities'] as $key => $table) {
                if (array_key_exists($key, $data)) {
                    $data[$key] = $this->publicUuidToId($table, $data[$key]);
                }
            }
            $this->saveAddress($partyId, $data);
        }
    }

    private function normalizeContact(array $data): array
    {
        $data = array_intersect_key($data, array_flip(['first_name', 'last_name', 'display_name', 'email', 'mobile', 'phone', 'designation', 'department', 'is_primary', 'portal_enabled', 'status', 'create_portal_user']));
        unset($data['create_portal_user']);
        $data['display_name'] = $data['display_name'] ?? trim(($data['first_name'] ?? '').' '.($data['last_name'] ?? ''));

        return $data;
    }

    private function upsertPortalUser(Request $request, int $partyId, object $contact): void
    {
        if (! $contact->email) {
            return;
        }
        $password = Str::password(16);
        $user = User::query()->firstOrNew(['tenant_id' => $this->tenantId(), 'email' => $contact->email]);
        if (! $user->exists) {
            $user->uuid = (string) Str::uuid();
            $user->password = Hash::make($password);
            $user->account_type = 'client';
            $user->status = 'invited';
            $user->created_by = $request->user()?->id;
        }
        $user->fill(['client_contact_id' => $contact->id, 'first_name' => $contact->first_name, 'last_name' => $contact->last_name, 'display_name' => $contact->display_name, 'mobile' => $contact->mobile, 'updated_by' => $request->user()?->id])->save();
        DB::table('party_contacts')->where('id', $contact->id)->update(['portal_enabled' => true]);
        $this->audit($request, 'tenant_contact_portal_user_upserted', 'party', $partyId, null, ['contact_id' => $contact->id]);
    }

    private function syncTags(int $partyId, array $tagUuids): void
    {
        if ($tagUuids === []) {
            return;
        }
        $tagIds = DB::table('tags')->where('tenant_id', $this->tenantId())->whereIn('uuid', $tagUuids)->pluck('id')->all();
        foreach ($tagIds as $tagId) {
            DB::table('taggables')->updateOrInsert(['tenant_id' => $this->tenantId(), 'tag_id' => $tagId, 'taggable_type' => 'party', 'taggable_id' => $partyId], ['created_at' => now()]);
        }
    }

    private function assertUnique(string $table, string $column, string $value, ?int $ignorePartyId = null): void
    {
        if ($value === '') {
            abort(422, "{$column} is required.");
        }
        $exists = DB::table($table)->where('tenant_id', $this->tenantId())->where($column, $value)->when($ignorePartyId, fn ($q) => $q->where('party_id', '<>', $ignorePartyId))->exists();
        abort_if($exists, 409, "{$column} already exists for this tenant.");
    }
}


