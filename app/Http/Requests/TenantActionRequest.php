<?php

namespace App\Http\Requests;

use App\Models\Tenant;
use App\Support\ApiResponse;
use App\Support\PaymentLogger;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

final class TenantActionRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function failedValidation(Validator $validator): void
    {
        if ($this->route()->getActionMethod() === 'paymentOrder') {
            PaymentLogger::failure($this, 'request_validation_failed', ['tenant_uuid' => $this->route('tenant_uuid'), 'request' => $this->all(), 'errors' => $validator->errors()->toArray()]);
        }
        throw new HttpResponseException(ApiResponse::validation($validator->errors()->toArray()));
    }

    public function rules(): array
    {
        $tenantId = Tenant::where('uuid', $this->route('tenant_uuid'))->value('id');
        $reason = ['reason' => ['nullable', 'string', 'max:1000']];

        return match ($this->route()->getActionMethod()) {
            'index' => ['search' => ['nullable', 'string', 'max:200'], 'filter' => ['nullable', 'array:status'], 'filter.status' => ['nullable', Rule::in(['pending', 'trial', 'active', 'suspended', 'expired', 'cancelled', 'archived'])], 'per_page' => ['nullable', 'integer', 'between:1,100'], 'page' => ['nullable', 'integer', 'min:1']],
            'bulkDestroy' => $reason + ['tenant_uuids' => ['required', 'array', 'min:1', 'max:100'], 'tenant_uuids.*' => ['required', 'uuid', 'distinct']],
            'suspend' => ['reason' => ['required', 'string', 'max:1000'], 'notify_owner' => ['nullable', 'boolean'], 'suspended_until' => ['nullable', 'date']],
            'extendTrial' => $reason + ['trial_ends_at' => ['required', 'date']],
            'trialIndex' => ['per_page' => ['nullable', 'integer', 'between:1,100'], 'page' => ['nullable', 'integer', 'min:1']],
            'onboardingIndex' => ['per_page' => ['nullable', 'integer', 'between:1,100'], 'page' => ['nullable', 'integer', 'min:1']],
            'updateOnboardingStep' => ['status' => ['required', 'string', 'max:50'], 'metadata' => ['nullable', 'array']],

            'changePlan' => $reason + ['plan_uuid' => ['required', 'uuid', Rule::exists('plans', 'uuid')->whereNull('deleted_at')], 'billing_cycle' => ['nullable', Rule::in(['monthly', 'quarterly', 'half-yearly', 'yearly', 'lifetime', 'one_time'])], 'starts_at' => ['nullable', 'date'], 'expires_at' => ['nullable', 'date'], 'renewal_type' => ['nullable', Rule::in(['manual', 'auto'])], 'auto_renew' => ['nullable', 'boolean']],
            'resetOwnerPassword' => $reason + ['password' => ['nullable', 'string', 'min:8', 'max:255'], 'notify_owner' => ['nullable', 'boolean']],
            'paymentOrder' => ['amount' => ['required', 'numeric', 'min:1', 'max:9999999999.99', 'decimal:0,2'], 'currency' => ['nullable', 'string', 'size:3'], 'method' => ['required', Rule::in(['online', 'cash'])], 'subscription_uuid' => ['nullable', 'uuid', Rule::exists('subscriptions', 'uuid')->where('tenant_id', $tenantId)->whereNull('deleted_at')], 'notes' => ['nullable', 'array']],
            'impersonate' => ['reason' => ['required', 'string', 'min:5', 'max:1000'], 'duration_minutes' => ['required', 'integer', 'between:5,240'], 'target_user_uuid' => ['nullable', 'uuid', Rule::exists('users', 'uuid')->where('tenant_id', $tenantId)->whereNull('deleted_at')]],
            'modules' => ['modules' => ['present', 'array', 'max:100'], 'modules.*' => ['array:module_code,enabled,limits,metadata'], 'modules.*.module_code' => ['required', 'string', 'max:100', 'distinct', 'exists:modules,code'], 'modules.*.enabled' => ['required', 'boolean'], 'modules.*.limits' => ['nullable', 'array'], 'modules.*.metadata' => ['nullable', 'array']],
            default => $reason,
        };
    }
}
