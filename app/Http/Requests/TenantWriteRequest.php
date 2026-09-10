<?php

namespace App\Http\Requests;

use App\Models\Tenant;
use Illuminate\Validation\Rule;

class TenantWriteRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (is_array($this->input('owner')) && is_string($this->input('owner.email'))) {
            $this->merge(['owner' => array_replace($this->input('owner'), ['email' => strtolower(trim($this->input('owner.email')))])]);
        }
    }

    public function rules(): array
    {
        $public = $this instanceof RegisterTenantRequest;
        $partial = $this->isMethod('put') || $this->isMethod('patch');
        $tenant = $partial ? Tenant::where('uuid', $this->route('tenant_uuid'))->first() : null;
        $required = $partial ? 'sometimes' : 'required';
        $plan = Rule::exists('plans', 'uuid')->whereNull('deleted_at');
        if ($public) {
            $plan->where('status', 'active')->where('is_public', true);
        }
        $rules = [
            'organization_name' => [$required, 'string', 'max:200'],
            'organization_code' => ['nullable', 'string', 'max:50', 'alpha_dash', Rule::unique('tenants', 'organization_code')->ignore($tenant?->id)],
            'slug' => ['nullable', 'string', 'max:150', 'alpha_dash', Rule::unique('tenants', 'slug')->ignore($tenant?->id)],
            'business_type_id' => ['nullable', 'integer', 'exists:business_types,id'],
            'industry_id' => ['nullable', 'integer', 'exists:industries,id'],
            'company_size' => ['nullable', Rule::in(['self', 'small', 'medium', 'large', 'enterprise'])],
            'default_currency' => ['nullable', 'string', 'size:3', Rule::exists('currencies', 'code')->where('status', 'active')],
            'default_timezone' => ['nullable', 'string', 'max:100', Rule::exists('timezones', 'identifier')->where('status', 'active')],
            'plan_uuid' => ['nullable', 'uuid', $plan],
            'trial_days' => ['nullable', 'integer', 'between:0,365'],
            'owner' => [$required, 'array:first_name,last_name,display_name,email,mobile,password,password_confirmation,status,send_invite'],
            'owner.first_name' => [$required, 'string', 'max:100'],
            'owner.email' => [$required, 'email', 'max:150'],
            'owner.password' => [$public ? 'required' : 'nullable', 'string', 'min:8', 'max:255', ...($public ? ['confirmed'] : [])],
            'owner.password_confirmation' => ['nullable', 'string'],
            'owner.status' => [$public ? 'prohibited' : 'nullable', Rule::in(['active', 'inactive', 'invited', 'suspended'])],
            'owner.send_invite' => ['nullable', 'boolean'],
            'office' => [$public || $partial ? 'sometimes' : 'present', 'array:office_name,office_code,office_type,address_line_1,address_line_2,landmark,country_id,state_id,city_id,postal_code,contact_person,contact_email,contact_phone,gst_number,status,working_hours'],
            'office.office_type' => ['nullable', Rule::in(['head_office', 'branch', 'regional', 'warehouse', 'factory', 'store', 'remote', 'franchise'])],
            'office.status' => ['nullable', Rule::in(['active', 'inactive'])],
            'office.working_hours' => ['nullable', 'array'],
            'office.contact_email' => ['nullable', 'email', 'max:150'],
            'subscription' => ['nullable', 'array:type,billing_cycle,starts_at,expires_at,trial_starts_at,trial_ends_at,renewal_type,auto_renew'],
            'subscription.type' => ['nullable', Rule::in(['trial', 'paid', 'free', 'standard'])],
            'subscription.billing_cycle' => ['nullable', Rule::in(['monthly', 'quarterly', 'half-yearly', 'yearly', 'lifetime', 'one_time'])],
            'subscription.renewal_type' => ['nullable', Rule::in(['manual', 'auto'])],
            'subscription.auto_renew' => ['nullable', 'boolean'],
            'payment' => [$public ? 'nullable' : 'prohibited', 'array:method'],
            'payment.method' => ['nullable', Rule::in(['online', 'cash', 'free'])],
            'status' => [$public ? 'prohibited' : 'nullable', Rule::in(['pending', 'trial', 'active', 'suspended', 'expired', 'cancelled', 'archived', 'inactive'])],
        ];
        foreach (['legal_name' => 200, 'display_name' => 200, 'gst_number' => 30, 'pan_number' => 30, 'registration_number' => 80, 'website' => 255, 'description' => 10000, 'owner.last_name' => 100, 'owner.display_name' => 200, 'owner.mobile' => 20, 'office.office_name' => 150, 'office.office_code' => 50, 'office.address_line_1' => 255, 'office.address_line_2' => 255, 'office.landmark' => 255, 'office.postal_code' => 20, 'office.contact_person' => 150, 'office.contact_phone' => 20, 'office.gst_number' => 30] as $key => $max) {
            $rules[$key] = ['nullable', 'string', 'max:'.$max];
        }
        foreach (['country' => 'countries', 'state' => 'states', 'city' => 'cities'] as $key => $table) {
            $rules['office.'.$key.'_id'] = ['nullable', 'integer', 'exists:'.$table.',id'];
        }
        foreach (['starts_at', 'expires_at', 'trial_starts_at', 'trial_ends_at'] as $key) {
            $rules['subscription.'.$key] = [$public ? 'prohibited' : 'nullable', 'date'];
        }
        foreach (['logo_file_id', 'favicon_file_id'] as $key) {
            $rules[$key] = [$public ? 'prohibited' : 'nullable', 'integer', Rule::exists('files', 'id')->whereNull('deleted_at')->where(fn ($q) => $q->whereNull('tenant_id')->when($tenant, fn ($q) => $q->orWhere('tenant_id', $tenant->id)))];
        }
        if ($tenant) {
            $rules['owner.email'][] = Rule::unique('users', 'email')->where('tenant_id', $tenant->id)->ignore($tenant->owner?->id);
        }
        if ($tenant) {
            $rules['office.office_code'][] = Rule::unique('tenant_offices', 'office_code')->where('tenant_id', $tenant->id)->ignore($tenant->headOffice?->id);
        }

        return $rules;
    }
}
