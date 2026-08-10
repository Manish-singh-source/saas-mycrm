<?php

namespace App\Http\Controllers\Tenant;

use App\Services\Tenant\TenantCrmPartyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TenantLeadController extends BaseTenantController
{
    public function __construct(private readonly TenantCrmPartyService $crm) {}

    public function dashboard(): JsonResponse
    {
        $tenantId = app(\App\Tenancy\TenantContext::class)->id();
        $leads = DB::table('lead_profiles')->where('tenant_id', $tenantId);

        return $this->success(['dashboard' => [
            'cards' => [
                'new_leads' => (clone $leads)->whereDate('created_at', '>=', now()->startOfMonth())->count(),
                'open_leads' => (clone $leads)->whereNull('converted_at')->whereNull('lost_reason')->count(),
                'won_leads' => (clone $leads)->whereNotNull('converted_at')->count(),
                'lost_leads' => (clone $leads)->whereNotNull('lost_reason')->count(),
                'expected_value' => (float) (clone $leads)->sum('expected_value'),
                'weighted_pipeline_value' => (float) DB::table('lead_profiles')->where('tenant_id', $tenantId)->selectRaw('sum(expected_value * probability / 100) as total')->value('total'),
                'overdue_follow_ups' => DB::table('lead_activities')->where('tenant_id', $tenantId)->whereNull('completed_at')->where('scheduled_at', '<', now())->count(),
            ],
            'by_stage' => $this->grouped('stage_id'),
            'by_priority' => $this->grouped('priority_id'),
            'upcoming_follow_ups' => DB::table('lead_activities')->where('tenant_id', $tenantId)->whereNull('completed_at')->where('scheduled_at', '>=', now())->orderBy('scheduled_at')->limit(10)->get(),
        ]]);
    }

    public function index(Request $request): JsonResponse
    {
        $query = $this->query();
        if ($request->filled('search')) {
            $query->where(fn ($q) => $q->where('parties.display_name', 'like', '%'.$request->search.'%')->orWhere('lead_profiles.lead_number', 'like', '%'.$request->search.'%'));
        }
        foreach (['stage_id', 'priority_id'] as $field) {
            if ($request->filled('filter.'.$field)) {
                $query->where('lead_profiles.'.$field, $this->crm->publicUuidToId('tenant_lookups', $request->input('filter.'.$field)));
            }
        }
        $page = $query->orderByDesc('lead_profiles.id')->paginate((int) $request->integer('per_page', 25));

        return $this->list($page->items(), $page);
    }

    public function kanban(): JsonResponse
    {
        $items = $this->query()->orderBy('lead_profiles.stage_id')->orderByDesc('lead_profiles.expected_value')->get();

        return $this->success(['kanban' => $items->groupBy('stage_id')->map(fn ($rows) => ['total' => $rows->count(), 'value' => $rows->sum('expected_value'), 'leads' => $rows->values()->all()])->all()]);
    }

    public function store(Request $request): JsonResponse
    {
        $party = $this->crm->createPartyBundle($request, 'lead_profiles', 'lead_number', $this->bundleData($request, false), 'lead');

        return $this->success(['lead' => $this->crm->bundle('lead_profiles', $party->id)], 'Lead created.', 201);
    }

    public function show(string $lead_uuid): JsonResponse
    {
        $lead = $this->crm->findProfile('lead_profiles', $lead_uuid);
        $bundle = $this->crm->bundle('lead_profiles', $lead->party_id);
        $bundle['activities'] = $this->activityRows($lead->id);
        $bundle['conversion_history'] = DB::table('lead_conversion_history')->where('tenant_id', app(\App\Tenancy\TenantContext::class)->id())->where('lead_profile_id', $lead->id)->orderByDesc('id')->get();

        return $this->success(['lead' => $bundle]);
    }

    public function update(Request $request, string $lead_uuid): JsonResponse
    {
        $lead = $this->crm->findProfile('lead_profiles', $lead_uuid);
        $party = $this->crm->updatePartyBundle($request, $lead->party_id, 'lead_profiles', 'lead_number', $this->bundleData($request, true), 'lead');

        return $this->success(['lead' => $this->crm->bundle('lead_profiles', $party->id)], 'Lead updated.');
    }

    public function destroy(Request $request, string $lead_uuid): JsonResponse
    {
        $lead = $this->crm->findProfile('lead_profiles', $lead_uuid);
        DB::table('parties')->where('id', $lead->party_id)->update(['deleted_at' => now(), 'updated_by' => $request->user()?->id, 'updated_at' => now()]);
        $this->crm->audit($request, 'tenant_lead_archived', 'party', $lead->party_id, (array) $lead, null);

        return $this->success(null, 'Lead archived.');
    }

    public function import(Request $request): JsonResponse { return $this->success(['job' => $this->crm->createJob($request, 'import', 'leads', $request->all())], 'Lead import queued.', 202); }
    public function export(Request $request): JsonResponse { return $this->success(['job' => $this->crm->createJob($request, 'export', 'leads', $request->all())], 'Lead export queued.', 202); }

    public function duplicate(Request $request, string $lead_uuid): JsonResponse
    {
        $lead = $this->crm->findProfile('lead_profiles', $lead_uuid);
        $bundle = $this->crm->bundle('lead_profiles', $lead->party_id);
        $party = (array) $bundle['party'];
        unset($party['id'], $party['uuid'], $party['tenant_id'], $party['created_at'], $party['updated_at'], $party['deleted_at'], $party['created_by'], $party['updated_by']);
        $party['display_name'] .= ' Copy';
        $profile = (array) $bundle['profile'];
        unset($profile['id'], $profile['tenant_id'], $profile['party_id'], $profile['created_at'], $profile['updated_at']);
        $profile['lead_number'] = $request->input('lead_number', $profile['lead_number'].'-COPY-'.Str::upper(Str::random(4)));
        $new = $this->crm->createPartyBundle($request, 'lead_profiles', 'lead_number', ['party' => $party, 'profile' => $profile, 'contacts' => array_map(fn ($c) => (array) $c, $bundle['contacts']), 'addresses' => array_map(fn ($a) => (array) $a, $bundle['addresses'])], 'lead');

        return $this->success(['lead' => $this->crm->bundle('lead_profiles', $new->id)], 'Lead duplicated.', 201);
    }

    public function convert(Request $request, string $lead_uuid): JsonResponse
    {
        $lead = $this->crm->findProfile('lead_profiles', $lead_uuid);
        $data = $request->validate(['client_id' => ['nullable', 'string'], 'client_code' => ['required_without:client_id', 'string', 'max:80'], 'client_type' => ['nullable', 'string', 'max:80'], 'account_manager_id' => ['nullable', 'string'], 'conversion_note' => ['nullable', 'string'], 'move_open_tasks' => ['nullable', 'boolean'], 'create_project' => ['nullable', 'boolean']]);

        return DB::transaction(function () use ($request, $lead, $data): JsonResponse {
            if (! empty($data['client_id'])) {
                $clientPartyId = $this->crm->findProfile('client_profiles', $data['client_id'])->party_id;
            } else {
                $party = DB::table('parties')->where('id', $lead->party_id)->first();
                $copy = (array) $party;
                unset($copy['id'], $copy['uuid'], $copy['created_at'], $copy['updated_at'], $copy['deleted_at']);
                $client = $this->crm->createPartyBundle($request, 'client_profiles', 'client_code', ['party' => $copy, 'profile' => ['client_code' => $data['client_code'], 'client_type' => $data['client_type'] ?? null, 'account_manager_id' => $data['account_manager_id'] ?? null], 'contacts' => DB::table('party_contacts')->where('party_id', $lead->party_id)->get()->map(fn ($r) => (array) $r)->all(), 'addresses' => DB::table('party_addresses')->where('party_id', $lead->party_id)->get()->map(fn ($r) => (array) $r)->all()], 'client');
                $clientPartyId = $client->id;
            }
            DB::table('lead_profiles')->where('id', $lead->id)->update(['converted_client_party_id' => $clientPartyId, 'converted_at' => now(), 'updated_at' => now()]);
            if ($data['move_open_tasks'] ?? false) {
                DB::table('tasks')->where('tenant_id', app(\App\Tenancy\TenantContext::class)->id())->where('related_type', 'lead')->where('related_id', $lead->id)->update(['related_type' => 'client', 'related_id' => $clientPartyId, 'updated_at' => now()]);
            }
            DB::table('lead_conversion_history')->insert(['tenant_id' => app(\App\Tenancy\TenantContext::class)->id(), 'lead_profile_id' => $lead->id, 'client_party_id' => $clientPartyId, 'converted_by' => $request->user()?->id, 'conversion_note' => $data['conversion_note'] ?? null, 'metadata' => json_encode(['move_open_tasks' => $data['move_open_tasks'] ?? false, 'create_project' => $data['create_project'] ?? false]), 'converted_at' => now()]);
            $this->crm->audit($request, 'tenant_lead_converted', 'lead_profile', $lead->id, (array) $lead, ['client_party_id' => $clientPartyId], $data['conversion_note'] ?? null);

            return $this->success(['client_party_id' => $clientPartyId], 'Lead converted.');
        });
    }

    public function markLost(Request $request, string $lead_uuid): JsonResponse
    {
        $lead = $this->crm->findProfile('lead_profiles', $lead_uuid);
        $data = $request->validate(['lost_reason' => ['required', 'string', 'max:255']]);
        DB::table('lead_profiles')->where('id', $lead->id)->update(['lost_reason' => $data['lost_reason'], 'updated_at' => now()]);
        $this->crm->audit($request, 'tenant_lead_lost', 'lead_profile', $lead->id, (array) $lead, $data);

        return $this->success(['lead' => DB::table('lead_profiles')->where('id', $lead->id)->first()], 'Lead marked lost.');
    }

    public function merge(Request $request): JsonResponse
    {
        $data = $request->validate(['primary_lead_id' => ['required', 'string'], 'duplicate_lead_ids' => ['required', 'array'], 'duplicate_lead_ids.*' => ['required', 'string'], 'reason' => ['nullable', 'string']]);
        $primary = $this->crm->findProfile('lead_profiles', $data['primary_lead_id']);
        DB::transaction(function () use ($request, $data, $primary): void {
            foreach ($data['duplicate_lead_ids'] as $uuid) {
                $duplicate = $this->crm->findProfile('lead_profiles', $uuid);
                DB::table('lead_activities')->where('tenant_id', app(\App\Tenancy\TenantContext::class)->id())->where('lead_profile_id', $duplicate->id)->update(['lead_profile_id' => $primary->id]);
                DB::table('tasks')->where('tenant_id', app(\App\Tenancy\TenantContext::class)->id())->where('related_type', 'lead')->where('related_id', $duplicate->id)->update(['related_id' => $primary->id]);
                DB::table('calendar_events')->where('tenant_id', app(\App\Tenancy\TenantContext::class)->id())->where('related_type', 'lead')->where('related_id', $duplicate->id)->update(['related_id' => $primary->id]);
                DB::table('parties')->where('id', $duplicate->party_id)->update(['deleted_at' => now(), 'updated_at' => now()]);
                $this->crm->audit($request, 'tenant_lead_merged', 'lead_profile', $duplicate->id, ['merged_into' => $primary->id], null, $data['reason'] ?? null);
            }
        });

        return $this->success(null, 'Leads merged.');
    }

    public function activities(string $lead_uuid): JsonResponse { $lead = $this->crm->findProfile('lead_profiles', $lead_uuid); return $this->success(['activities' => $this->activityRows($lead->id)]); }
    public function storeActivity(Request $request, string $lead_uuid): JsonResponse { $lead = $this->crm->findProfile('lead_profiles', $lead_uuid); $id = DB::table('lead_activities')->insertGetId([...$this->activityData($request), 'uuid' => (string) Str::uuid(), 'tenant_id' => app(\App\Tenancy\TenantContext::class)->id(), 'lead_profile_id' => $lead->id, 'created_by' => $request->user()?->id, 'created_at' => now(), 'updated_at' => now()]); return $this->success(['activity' => DB::table('lead_activities')->where('id', $id)->first()], 'Lead activity created.', 201); }
    public function updateActivity(Request $request, string $lead_uuid, string $activity_uuid): JsonResponse { $lead = $this->crm->findProfile('lead_profiles', $lead_uuid); $activity = DB::table('lead_activities')->where('tenant_id', app(\App\Tenancy\TenantContext::class)->id())->where('lead_profile_id', $lead->id)->where('uuid', $activity_uuid)->first() ?: abort(404, 'Activity not found.'); DB::table('lead_activities')->where('id', $activity->id)->update([...$this->activityData($request, true), 'updated_at' => now()]); return $this->success(['activity' => DB::table('lead_activities')->where('id', $activity->id)->first()], 'Lead activity updated.'); }
    public function activity(string $lead_uuid): JsonResponse { $lead = $this->crm->findProfile('lead_profiles', $lead_uuid); return $this->success(['activity' => DB::table('activity_logs')->where('tenant_id', app(\App\Tenancy\TenantContext::class)->id())->whereIn('subject_type', ['party', 'lead_profile'])->whereIn('subject_id', [$lead->party_id, $lead->id])->orderByDesc('created_at')->get()]); }

    private function query()
    {
        return DB::table('lead_profiles')->join('parties', 'parties.id', '=', 'lead_profiles.party_id')->where('lead_profiles.tenant_id', app(\App\Tenancy\TenantContext::class)->id())->whereNull('parties.deleted_at')->select('parties.uuid', 'parties.display_name', 'parties.email', 'parties.phone', 'parties.owner_user_id', 'parties.source_id', 'lead_profiles.*');
    }

    private function bundleData(Request $request, bool $partial): array
    {
        return $request->validate(['party' => [$partial ? 'sometimes' : 'required', 'array'], 'party.display_name' => [$partial ? 'sometimes' : 'required', 'string'], 'party.*' => ['nullable'], 'profile' => [$partial ? 'sometimes' : 'required', 'array'], 'profile.lead_number' => [$partial ? 'sometimes' : 'required', 'string', 'max:80'], 'profile.*' => ['nullable'], 'contacts' => ['nullable', 'array'], 'addresses' => ['nullable', 'array'], 'tag_ids' => ['nullable', 'array']]);
    }

    private function activityData(Request $request, bool $partial = false): array
    {
        $data = $request->validate(['activity_type' => [$partial ? 'sometimes' : 'required', 'string', 'max:80'], 'subject' => [$partial ? 'sometimes' : 'required', 'string'], 'description' => ['nullable', 'string'], 'scheduled_at' => ['nullable', 'date'], 'completed_at' => ['nullable', 'date'], 'outcome' => ['nullable', 'string'], 'assigned_to' => ['nullable', 'string']]);
        if (array_key_exists('assigned_to', $data)) {
            $data['assigned_to'] = $this->crm->publicUuidToId('users', $data['assigned_to']);
        }

        return $data;
    }

    private function activityRows(int $leadId): array
    {
        return DB::table('lead_activities')->where('tenant_id', app(\App\Tenancy\TenantContext::class)->id())->where('lead_profile_id', $leadId)->orderByDesc('scheduled_at')->get()->all();
    }

    private function grouped(string $column): array
    {
        return DB::table('lead_profiles')->where('tenant_id', app(\App\Tenancy\TenantContext::class)->id())->selectRaw($column.' as label, count(*) as total, sum(expected_value) as value')->groupBy($column)->get()->all();
    }
}
