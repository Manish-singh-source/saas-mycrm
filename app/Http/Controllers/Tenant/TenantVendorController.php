<?php

namespace App\Http\Controllers\Tenant;

use App\Services\Tenant\TenantCrmPartyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class TenantVendorController extends BaseTenantController
{
    public function __construct(private readonly TenantCrmPartyService $crm) {}

    public function index(Request $request): JsonResponse
    {
        $query = DB::table('vendor_profiles')->join('parties', 'parties.id', '=', 'vendor_profiles.party_id')->where('vendor_profiles.tenant_id', app(\App\Tenancy\TenantContext::class)->id())->whereNull('parties.deleted_at')->select('parties.uuid', 'parties.display_name', 'parties.email', 'parties.phone', 'vendor_profiles.*');
        if ($request->filled('search')) {
            $query->where(fn ($q) => $q->where('parties.display_name', 'like', '%'.$request->search.'%')->orWhere('vendor_profiles.vendor_code', 'like', '%'.$request->search.'%'));
        }
        $page = $query->orderBy('parties.display_name')->paginate((int) $request->integer('per_page', 25));

        return $this->list($page->items(), $page);
    }

    public function store(Request $request): JsonResponse
    {
        $party = $this->crm->createPartyBundle($request, 'vendor_profiles', 'vendor_code', $this->bundleData($request, false), 'vendor');

        return $this->success(['vendor' => $this->crm->bundle('vendor_profiles', $party->id)], 'Vendor created.', 201);
    }

    public function show(string $vendor_uuid): JsonResponse
    {
        $vendor = $this->crm->findProfile('vendor_profiles', $vendor_uuid);
        $bundle = $this->crm->bundle('vendor_profiles', $vendor->party_id);
        $bundle['bank_accounts'] = $this->bankRows($vendor->party_id);

        return $this->success(['vendor' => $bundle]);
    }

    public function update(Request $request, string $vendor_uuid): JsonResponse
    {
        $vendor = $this->crm->findProfile('vendor_profiles', $vendor_uuid);
        $party = $this->crm->updatePartyBundle($request, $vendor->party_id, 'vendor_profiles', 'vendor_code', $this->bundleData($request, true), 'vendor');

        return $this->success(['vendor' => $this->crm->bundle('vendor_profiles', $party->id)], 'Vendor updated.');
    }

    public function destroy(Request $request, string $vendor_uuid): JsonResponse
    {
        $vendor = $this->crm->findProfile('vendor_profiles', $vendor_uuid);
        DB::table('parties')->where('id', $vendor->party_id)->update(['deleted_at' => now(), 'updated_by' => $request->user()?->id, 'updated_at' => now()]);
        $this->crm->audit($request, 'tenant_vendor_archived', 'party', $vendor->party_id, (array) $vendor, null);

        return $this->success(null, 'Vendor archived.');
    }

    public function import(Request $request): JsonResponse { return $this->success(['job' => $this->crm->createJob($request, 'import', 'vendors', $request->all())], 'Vendor import queued.', 202); }
    public function export(Request $request): JsonResponse { return $this->success(['job' => $this->crm->createJob($request, 'export', 'vendors', $request->all())], 'Vendor export queued.', 202); }

    public function contacts(string $vendor_uuid): JsonResponse { $vendor = $this->crm->findProfile('vendor_profiles', $vendor_uuid); return $this->success(['contacts' => DB::table('party_contacts')->where('tenant_id', app(\App\Tenancy\TenantContext::class)->id())->where('party_id', $vendor->party_id)->whereNull('deleted_at')->get()]); }
    public function storeContact(Request $request, string $vendor_uuid): JsonResponse { $vendor = $this->crm->findProfile('vendor_profiles', $vendor_uuid); return $this->success(['contact' => $this->crm->saveContact($request, $vendor->party_id, $this->crm->contactData($request))], 'Contact created.', 201); }
    public function updateContact(Request $request, string $vendor_uuid, string $contact_uuid): JsonResponse { $vendor = $this->crm->findProfile('vendor_profiles', $vendor_uuid); $contact = $this->contact($vendor->party_id, $contact_uuid); return $this->success(['contact' => $this->crm->saveContact($request, $vendor->party_id, $this->crm->contactData($request), $contact->id)], 'Contact updated.'); }
    public function deleteContact(string $vendor_uuid, string $contact_uuid): JsonResponse { $vendor = $this->crm->findProfile('vendor_profiles', $vendor_uuid); $contact = $this->contact($vendor->party_id, $contact_uuid); DB::table('party_contacts')->where('id', $contact->id)->update(['deleted_at' => now(), 'updated_at' => now()]); return $this->success(null, 'Contact deleted.'); }

    public function addresses(string $vendor_uuid): JsonResponse { $vendor = $this->crm->findProfile('vendor_profiles', $vendor_uuid); return $this->success(['addresses' => DB::table('party_addresses')->where('tenant_id', app(\App\Tenancy\TenantContext::class)->id())->where('party_id', $vendor->party_id)->get()]); }
    public function storeAddress(Request $request, string $vendor_uuid): JsonResponse { $vendor = $this->crm->findProfile('vendor_profiles', $vendor_uuid); return $this->success(['address' => $this->crm->saveAddress($vendor->party_id, $this->crm->addressData($request))], 'Address created.', 201); }
    public function updateAddress(Request $request, string $vendor_uuid, int $address_id): JsonResponse { $vendor = $this->crm->findProfile('vendor_profiles', $vendor_uuid); return $this->success(['address' => $this->crm->saveAddress($vendor->party_id, $this->crm->addressData($request), $address_id)], 'Address updated.'); }
    public function deleteAddress(string $vendor_uuid, int $address_id): JsonResponse { $vendor = $this->crm->findProfile('vendor_profiles', $vendor_uuid); DB::table('party_addresses')->where('tenant_id', app(\App\Tenancy\TenantContext::class)->id())->where('party_id', $vendor->party_id)->where('id', $address_id)->delete(); return $this->success(null, 'Address deleted.'); }

    public function bankAccounts(string $vendor_uuid): JsonResponse
    {
        $vendor = $this->crm->findProfile('vendor_profiles', $vendor_uuid);

        return $this->success(['bank_accounts' => $this->bankRows($vendor->party_id)]);
    }

    public function storeBankAccount(Request $request, string $vendor_uuid): JsonResponse
    {
        $vendor = $this->crm->findProfile('vendor_profiles', $vendor_uuid);
        $data = $request->validate(['bank_name' => ['required', 'string'], 'account_number' => ['required', 'string'], 'routing_number' => ['nullable', 'string'], 'ifsc_code' => ['nullable', 'string', 'max:30'], 'is_primary' => ['nullable', 'boolean']]);
        $id = DB::table('bank_accounts')->insertGetId(['tenant_id' => app(\App\Tenancy\TenantContext::class)->id(), 'owner_type' => 'vendor_party', 'owner_id' => $vendor->party_id, 'bank_name' => $data['bank_name'], 'account_number_encrypted' => Crypt::encryptString($data['account_number']), 'routing_number_encrypted' => isset($data['routing_number']) ? Crypt::encryptString($data['routing_number']) : null, 'ifsc_code' => $data['ifsc_code'] ?? null, 'is_primary' => $data['is_primary'] ?? false, 'created_at' => now(), 'updated_at' => now()]);
        $row = DB::table('bank_accounts')->where('id', $id)->first();
        $this->crm->audit($request, 'tenant_vendor_bank_account_created', 'bank_account', $id, null, ['masked' => true]);

        return $this->success(['bank_account' => $this->crm->bankPayload($row)], 'Bank account created.', 201);
    }

    public function updateBankAccount(Request $request, string $vendor_uuid, int $account_id): JsonResponse
    {
        $vendor = $this->crm->findProfile('vendor_profiles', $vendor_uuid);
        $row = $this->bankAccount($vendor->party_id, $account_id);
        $data = $request->validate(['bank_name' => ['sometimes', 'string'], 'account_number' => ['nullable', 'string'], 'routing_number' => ['nullable', 'string'], 'ifsc_code' => ['nullable', 'string', 'max:30'], 'is_primary' => ['nullable', 'boolean']]);
        $payload = array_filter([
            'bank_name' => $data['bank_name'] ?? null,
            'account_number_encrypted' => array_key_exists('account_number', $data) && $data['account_number'] !== null ? Crypt::encryptString($data['account_number']) : null,
            'routing_number_encrypted' => array_key_exists('routing_number', $data) && $data['routing_number'] !== null ? Crypt::encryptString($data['routing_number']) : null,
            'ifsc_code' => array_key_exists('ifsc_code', $data) ? $data['ifsc_code'] : null,
            'is_primary' => array_key_exists('is_primary', $data) ? (bool) $data['is_primary'] : null,
        ], fn ($value) => $value !== null);
        $payload['updated_at'] = now();
        DB::table('bank_accounts')->where('id', $row->id)->update($payload);
        $fresh = DB::table('bank_accounts')->where('id', $row->id)->first();
        $this->crm->audit($request, 'tenant_vendor_bank_account_updated', 'bank_account', $row->id, ['masked' => true], ['masked' => true]);

        return $this->success(['bank_account' => $this->crm->bankPayload($fresh)], 'Bank account updated.');
    }

    public function deleteBankAccount(Request $request, string $vendor_uuid, int $account_id): JsonResponse
    {
        $vendor = $this->crm->findProfile('vendor_profiles', $vendor_uuid);
        $row = $this->bankAccount($vendor->party_id, $account_id);
        DB::table('bank_accounts')->where('id', $row->id)->delete();
        $this->crm->audit($request, 'tenant_vendor_bank_account_deleted', 'bank_account', $row->id, ['masked' => true], null);

        return $this->success(null, 'Bank account deleted.');
    }

    public function related(string $vendor_uuid, string $resource): JsonResponse
    {
        $vendor = $this->crm->findProfile('vendor_profiles', $vendor_uuid);
        $map = ['expenses' => ['tenant_expenses', 'vendor_party_id'], 'renewals' => ['renewals', 'party_id']];
        abort_unless(isset($map[$resource]), 404, 'Related resource not found.');

        return $this->success([$resource => DB::table($map[$resource][0])->where('tenant_id', app(\App\Tenancy\TenantContext::class)->id())->where($map[$resource][1], $vendor->party_id)->orderByDesc('id')->limit(50)->get()]);
    }

    public function activity(string $vendor_uuid): JsonResponse
    {
        $vendor = $this->crm->findProfile('vendor_profiles', $vendor_uuid);

        return $this->success(['activity' => DB::table('activity_logs')->where('tenant_id', app(\App\Tenancy\TenantContext::class)->id())->where('subject_type', 'party')->where('subject_id', $vendor->party_id)->orderByDesc('created_at')->get()]);
    }

    private function bundleData(Request $request, bool $partial): array
    {
        return $request->validate(['party' => [$partial ? 'sometimes' : 'required', 'array'], 'party.display_name' => [$partial ? 'sometimes' : 'required', 'string'], 'party.*' => ['nullable'], 'profile' => [$partial ? 'sometimes' : 'required', 'array'], 'profile.vendor_code' => [$partial ? 'sometimes' : 'required', 'string', 'max:80'], 'profile.*' => ['nullable'], 'contacts' => ['nullable', 'array'], 'addresses' => ['nullable', 'array'], 'tag_ids' => ['nullable', 'array']]);
    }

    private function contact(int $partyId, string $uuid): object
    {
        return DB::table('party_contacts')->where('tenant_id', app(\App\Tenancy\TenantContext::class)->id())->where('party_id', $partyId)->where('uuid', $uuid)->whereNull('deleted_at')->first() ?: abort(404, 'Contact not found.');
    }

    private function bankRows(int $partyId): array
    {
        return DB::table('bank_accounts')->where('tenant_id', app(\App\Tenancy\TenantContext::class)->id())->where('owner_type', 'vendor_party')->where('owner_id', $partyId)->get()->map(fn ($row) => $this->crm->bankPayload($row))->all();
    }

    private function bankAccount(int $partyId, int $accountId): object
    {
        return DB::table('bank_accounts')->where('tenant_id', app(\App\Tenancy\TenantContext::class)->id())->where('owner_type', 'vendor_party')->where('owner_id', $partyId)->where('id', $accountId)->first() ?: abort(404, 'Bank account not found.');
    }
}
