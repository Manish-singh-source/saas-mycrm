<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterTenantRequest;
use App\Models\Plan;
use App\Services\TenantBillingService;
use App\Services\TenantProvisioningService;
use App\Support\ApiResponse;
use App\Support\TenantPresenter;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

final class TenantRegistrationController extends Controller
{
    public function plans(): mixed
    {
        return ApiResponse::success(['plans' => Plan::where('status', 'active')->where('is_public', true)->orderBy('base_price')->get(['uuid', 'name', 'code', 'billing_cycle', 'base_price', 'currency', 'trial_days', 'description'])], 'Public plans fetched successfully.');
    }

    public function confirmPayment(Request $request, TenantBillingService $billing): mixed
    {
        $data = $request->validate([
            'tenant_uuid' => ['required', 'uuid'],
            'razorpay_order_id' => ['required', 'string', 'max:100'],
            'razorpay_payment_id' => ['required', 'string', 'max:100'],
            'razorpay_signature' => ['required', 'string', 'size:64'],
        ]);

        return DB::transaction(function () use ($request, $billing, $data) {
            $payment = $billing->confirmRegistrationPayment($request, $data['tenant_uuid'], $data['razorpay_order_id'], $data['razorpay_payment_id'], $data['razorpay_signature']);

            return ApiResponse::success(['payment' => TenantPresenter::safe($payment)], 'Payment verified successfully.');
        });
    }
    public function store(RegisterTenantRequest $request, TenantProvisioningService $provisioning, TenantBillingService $billing): mixed
    {
        return DB::transaction(function () use ($request, $provisioning, $billing) {
            [$tenant, $owner, $subscription] = $provisioning->create($request, $request->validated(), true);
            $payment = $billing->registration($request, $tenant, $subscription, $request->validated());
            $permissions = DB::table('permissions')->where('guard_name', 'tenant')->where('status', 'active')->pluck('name')->all();
            $token = $owner->createToken('tenant-registration', ['tenant:'.$tenant->uuid, ...$permissions], now()->addHours(12));

            return ApiResponse::success(array_merge(TenantPresenter::detail($tenant->fresh()), TenantPresenter::safe($payment), [
                'access_token' => $token->plainTextToken, 'token_type' => 'Bearer', 'expires_at' => $token->accessToken->expires_at->toISOString(), 'roles' => ['owner'], 'permissions' => $permissions,
            ]), 'Tenant registered successfully.', 201);
        });
    }
}
