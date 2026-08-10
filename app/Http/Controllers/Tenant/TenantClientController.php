<?php

namespace App\Http\Controllers\Tenant;

use App\Services\Tenant\TenantCrmPartyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TenantClientController extends BaseTenantController
{
    public function __construct(private readonly TenantCrmPartyService $crm) {}

    public function index(Request $request): JsonResponse
    {
        $query = $this->query();
        if ($request->filled('search')) {
            $query->where(fn ($q) => $q->where('parties.display_name', 'like', '%'.$request->search.'%')->orWhere('client_profiles.client_code', 'like', '%'.$request->search.'%')->orWhere('parties.email', 'like', '%'.$request->search.'%'));
        }
        foreach (['client_type'] as $field) {
            if ($request->filled('filter.'.$field)) {
                $query->where('client_profiles.'.$field, $request->input('filter.'.$field));
            }
        }
        $page = $query->orderBy('parties.display_name')->paginate((int) $request->integer('per_page', 25));

        return $this->list($page->items(), $page);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->bundleData($request, false);
        $party = $this->crm->createPartyBundle($request, 'client_profiles', 'client_code', $data, 'client');

        return $this->success(['client' => $this->crm->bundle('client_profiles', $party->id)], 'Client created.', 201);
    }

    public function show(string $client_uuid): JsonResponse
    {
        $client = $this->crm->findProfile('client_profiles', $client_uuid);

        return $this->success(['client' => $this->crm->bundle('client_profiles', $client->party_id)]);
    }

    public function update(Request $request, string $client_uuid): JsonResponse
    {
        $client = $this->crm->findProfile('client_profiles', $client_uuid);
        $party = $this->crm->updatePartyBundle($request, $client->party_id, 'client_profiles', 'client_code', $this->bundleData($request, true), 'client');

        return $this->success(['client' => $this->crm->bundle('client_profiles', $party->id)], 'Client updated.');
    }

    public function destroy(Request $request, string $client_uuid): JsonResponse
    {
        $client = $this->crm->findProfile('client_profiles', $client_uuid);
        DB::table('parties')->where('id', $client->party_id)->update(['deleted_at' => now(), 'updated_by' => $request->user()?->id, 'updated_at' => now()]);
        $this->crm->audit($request, 'tenant_client_archived', 'party', $client->party_id, (array) $client, null);

        return $this->success(null, 'Client archived.');
    }

    public function restore(Request $request, string $client_uuid): JsonResponse
    {
        $party = DB::table('parties')->where('tenant_id', app(\App\Tenancy\TenantContext::class)->id())->where('uuid', $client_uuid)->first() ?: abort(404, 'Client not found.');
        DB::table('parties')->where('id', $party->id)->update(['deleted_at' => null, 'updated_at' => now()]);
        $this->crm->audit($request, 'tenant_client_restored', 'party', $party->id, (array) $party, ['deleted_at' => null]);

        return $this->success(['client' => $this->crm->bundle('client_profiles', $party->id)], 'Client restored.');
    }

    public function import(Request $request): JsonResponse { return $this->success(['job' => $this->crm->createJob($request, 'import', 'clients', $request->all())], 'Client import queued.', 202); }
    public function export(Request $request): JsonResponse { return $this->success(['job' => $this->crm->createJob($request, 'export', 'clients', $request->all())], 'Client export queued.', 202); }

    public function merge(Request $request): JsonResponse
    {
        $data = $request->validate(['primary_client_id' => ['required', 'string'], 'duplicate_client_ids' => ['required', 'array'], 'duplicate_client_ids.*' => ['required', 'string'], 'reason' => ['nullable', 'string']]);
        $this->crm->mergeParties($request, 'client_profiles', $data['primary_client_id'], $data['duplicate_client_ids'], ['projects' => 'client_party_id', 'tenant_invoices' => 'client_party_id', 'tenant_payments' => 'client_party_id', 'renewals' => 'party_id', 'client_issues' => 'client_party_id', 'communication_logs' => 'party_id'], 'tenant_client_merged');

        return $this->success(null, 'Clients merged.');
    }

    public function contacts(string $client_uuid): JsonResponse { $client = $this->crm->findProfile('client_profiles', $client_uuid); return $this->success(['contacts' => DB::table('party_contacts')->where('tenant_id', app(\App\Tenancy\TenantContext::class)->id())->where('party_id', $client->party_id)->whereNull('deleted_at')->get()]); }
    public function storeContact(Request $request, string $client_uuid): JsonResponse { $client = $this->crm->findProfile('client_profiles', $client_uuid); return $this->success(['contact' => $this->crm->saveContact($request, $client->party_id, $this->crm->contactData($request))], 'Contact created.', 201); }
    public function updateContact(Request $request, string $client_uuid, string $contact_uuid): JsonResponse { $client = $this->crm->findProfile('client_profiles', $client_uuid); $contact = $this->contact($client->party_id, $contact_uuid); return $this->success(['contact' => $this->crm->saveContact($request, $client->party_id, $this->crm->contactData($request), $contact->id)], 'Contact updated.'); }
    public function deleteContact(Request $request, string $client_uuid, string $contact_uuid): JsonResponse { $client = $this->crm->findProfile('client_profiles', $client_uuid); $contact = $this->contact($client->party_id, $contact_uuid); DB::table('party_contacts')->where('id', $contact->id)->update(['deleted_at' => now(), 'updated_at' => now()]); return $this->success(null, 'Contact deleted.'); }

    public function addresses(string $client_uuid): JsonResponse { $client = $this->crm->findProfile('client_profiles', $client_uuid); return $this->success(['addresses' => DB::table('party_addresses')->where('tenant_id', app(\App\Tenancy\TenantContext::class)->id())->where('party_id', $client->party_id)->get()]); }
    public function storeAddress(Request $request, string $client_uuid): JsonResponse { $client = $this->crm->findProfile('client_profiles', $client_uuid); return $this->success(['address' => $this->crm->saveAddress($client->party_id, $this->crm->addressData($request))], 'Address created.', 201); }
    public function updateAddress(Request $request, string $client_uuid, int $address_id): JsonResponse { $client = $this->crm->findProfile('client_profiles', $client_uuid); return $this->success(['address' => $this->crm->saveAddress($client->party_id, $this->crm->addressData($request), $address_id)], 'Address updated.'); }
    public function deleteAddress(string $client_uuid, int $address_id): JsonResponse { $client = $this->crm->findProfile('client_profiles', $client_uuid); DB::table('party_addresses')->where('tenant_id', app(\App\Tenancy\TenantContext::class)->id())->where('party_id', $client->party_id)->where('id', $address_id)->delete(); return $this->success(null, 'Address deleted.'); }

    public function related(string $client_uuid, string $resource): JsonResponse
    {
        $client = $this->crm->findProfile('client_profiles', $client_uuid);
        $map = ['projects' => ['projects', 'client_party_id'], 'invoices' => ['tenant_invoices', 'client_party_id'], 'payments' => ['tenant_payments', 'client_party_id'], 'renewals' => ['renewals', 'party_id'], 'issues' => ['client_issues', 'client_party_id']];
        abort_unless(isset($map[$resource]), 404, 'Related resource not found.');

        return $this->success([$resource => DB::table($map[$resource][0])->where('tenant_id', app(\App\Tenancy\TenantContext::class)->id())->where($map[$resource][1], $client->party_id)->orderByDesc('id')->limit(50)->get()]);
    }

    public function activity(string $client_uuid): JsonResponse
    {
        $client = $this->crm->findProfile('client_profiles', $client_uuid);

        return $this->success(['activity' => DB::table('activity_logs')->where('tenant_id', app(\App\Tenancy\TenantContext::class)->id())->where('subject_type', 'party')->where('subject_id', $client->party_id)->orderByDesc('created_at')->get()]);
    }

    private function query()
    {
        return DB::table('client_profiles')->join('parties', 'parties.id', '=', 'client_profiles.party_id')->where('client_profiles.tenant_id', app(\App\Tenancy\TenantContext::class)->id())->whereNull('parties.deleted_at')->select('parties.uuid', 'parties.display_name', 'parties.email', 'parties.phone', 'parties.status_id', 'client_profiles.*');
    }

    private function bundleData(Request $request, bool $partial): array
    {
        return $request->validate(['party' => [$partial ? 'sometimes' : 'required', 'array'], 'party.display_name' => [$partial ? 'sometimes' : 'required', 'string'], 'party.*' => ['nullable'], 'profile' => [$partial ? 'sometimes' : 'required', 'array'], 'profile.client_code' => [$partial ? 'sometimes' : 'required', 'string', 'max:80'], 'profile.*' => ['nullable'], 'contacts' => ['nullable', 'array'], 'addresses' => ['nullable', 'array'], 'tag_ids' => ['nullable', 'array'], 'custom_fields' => ['nullable', 'array']]);
    }

    private function contact(int $partyId, string $uuid): object
    {
        return DB::table('party_contacts')->where('tenant_id', app(\App\Tenancy\TenantContext::class)->id())->where('party_id', $partyId)->where('uuid', $uuid)->whereNull('deleted_at')->first() ?: abort(404, 'Contact not found.');
    }
}
