<?php

namespace App\Services;

use App\Models\PlatformInvoice;
use App\Models\PlatformPayment;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Support\ApiResponse;
use App\Support\PaymentLogger;
use App\Support\TenantAudit;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

final class TenantBillingService
{
    public function registration(Request $request, Tenant $tenant, Subscription $subscription, array $data): array
    {
        $amount = (float) $subscription->payable_amount;
        $method = $data['payment']['method'] ?? ($amount > 0 ? 'online' : 'free');
        PaymentLogger::request($request, 'registration_started', ['tenant_uuid' => $tenant->uuid, 'subscription_uuid' => $subscription->uuid, 'amount' => $amount, 'currency' => $subscription->currency, 'method' => $method]);
        if ($method === 'free' && $amount > 0) {
            PaymentLogger::failure($request, 'registration_rejected', ['tenant_uuid' => $tenant->uuid, 'subscription_uuid' => $subscription->uuid, 'amount' => $amount, 'method' => $method, 'reason' => 'free_method_for_non_zero_amount']);
            throw new HttpResponseException(ApiResponse::validation(['payment.method' => ['A non-zero plan requires cash or online payment.']]));
        }
        $invoice = PlatformInvoice::create([
            'uuid' => (string) Str::uuid(), 'invoice_number' => 'INV-'.Str::upper(Str::random(12)),
            'tenant_id' => $tenant->id, 'subscription_id' => $subscription->id, 'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays($amount > 0 ? 7 : 0)->toDateString(), 'subtotal' => $subscription->base_amount,
            'taxable_amount' => $subscription->taxable_amount, 'total_amount' => $amount,
            'paid_amount' => $amount <= 0 ? $amount : 0, 'balance_amount' => $amount <= 0 ? 0 : $amount,
            'currency' => $subscription->currency, 'status' => $amount > 0 ? 'sent' : 'paid',
        ]);
        PaymentLogger::response($request, 'invoice_created', ['tenant_uuid' => $tenant->uuid, 'invoice_uuid' => $invoice->uuid, 'invoice_number' => $invoice->invoice_number, 'amount' => $amount, 'status' => $invoice->status, 'balance_amount' => $invoice->balance_amount]);
        DB::table('platform_invoice_items')->insert(['platform_invoice_id' => $invoice->id, 'item_type' => 'subscription', 'description' => 'Initial subscription charge for '.$subscription->subscription_number, 'quantity' => 1, 'unit_price' => $subscription->base_amount, 'amount' => $subscription->base_amount, 'metadata' => json_encode(['subscription_uuid' => $subscription->uuid])]);
        $result = $this->payment($request, $tenant, $subscription, $amount, $subscription->currency, $method, [], $invoice, true);
        $subscription->update(['last_platform_invoice_id' => $invoice->id, 'last_platform_payment_id' => $result['payment']->id]);
        PaymentLogger::response($request, 'registration_completed', ['tenant_uuid' => $tenant->uuid, 'subscription_uuid' => $subscription->uuid, 'invoice_uuid' => $invoice->uuid, 'payment_uuid' => $result['payment']->uuid, 'payment_status' => $result['payment']->payment_status, 'order_id' => $result['order']['id'] ?? null]);

        return ['invoice' => $invoice->load(['items', 'subscription.plan']), 'invoice_items' => $invoice->items, 'payment_order' => $result['order'] ?? null, 'payment' => $result['payment'], 'razorpay_key' => $result['razorpay_key'] ?? null];
    }

    public function confirmRegistrationPayment(Request $request, string $tenantUuid, string $orderId, string $paymentId, string $signature): PlatformPayment
    {
        $tenant = Tenant::where('uuid', $tenantUuid)->first();
        if (! $tenant) {
            throw ValidationException::withMessages(['tenant_uuid' => ['Tenant was not found.']]);
        }

        $secret = config('services.razorpay.secret');
        $expected = hash_hmac('sha256', $orderId.'|'.$paymentId, (string) $secret);
        if (! $secret || ! hash_equals($expected, $signature)) {
            PaymentLogger::failure($request, 'gateway_payment_verification_failed', ['tenant_uuid' => $tenant->uuid, 'order_id' => $orderId, 'payment_id' => $paymentId]);
            throw ValidationException::withMessages(['razorpay_signature' => ['Razorpay payment verification failed.']]);
        }

        $payment = PlatformPayment::where('tenant_id', $tenant->id)
            ->where('gateway', 'razorpay')
            ->where('gateway_payment_id', $orderId)
            ->lockForUpdate()
            ->first();
        if (! $payment) {
            throw ValidationException::withMessages(['razorpay_order_id' => ['Razorpay order was not found.']]);
        }

        if ($payment->payment_status !== 'paid') {
            $payment->forceFill([
                'payment_status' => 'paid',
                'paid_at' => now(),
                'raw_response' => array_replace($payment->raw_response ?? [], [
                    'razorpay_payment_id' => $paymentId,
                    'razorpay_order_id' => $orderId,
                    'razorpay_signature' => $signature,
                ]),
            ])->save();

            if ($payment->invoice) {
                $payment->invoice->forceFill([
                    'status' => 'paid',
                    'paid_amount' => $payment->amount,
                    'balance_amount' => 0,
                ])->save();
            }

            if ($payment->subscription && $payment->subscription->status === 'pending_payment') {
                $payment->subscription->forceFill(['status' => 'active'])->save();
            }
        }

        PaymentLogger::response($request, 'gateway_payment_verified', ['tenant_uuid' => $tenant->uuid, 'payment_uuid' => $payment->uuid, 'order_id' => $orderId, 'payment_id' => $paymentId, 'payment_status' => $payment->payment_status]);

        return $payment->fresh(['invoice.items', 'subscription.plan']);
    }
    public function payment(Request $request, Tenant $tenant, ?Subscription $subscription, float $amount, string $currency, string $method, array $notes = [], ?PlatformInvoice $invoice = null, bool $registration = false): array
    {
        $order = null;
        $key = null;
        $currency = strtoupper($currency);
        PaymentLogger::request($request, 'payment_started', ['tenant_uuid' => $tenant->uuid, 'subscription_uuid' => $subscription?->uuid, 'invoice_uuid' => $invoice?->uuid, 'amount' => $amount, 'currency' => $currency, 'method' => $method, 'notes' => $notes, 'registration' => $registration]);
        if ($method === 'online' && $amount > 0) {
            $key = config('services.razorpay.key');
            $secret = config('services.razorpay.secret');
            if (! $key || ! $secret) {
                PaymentLogger::failure($request, 'gateway_not_configured', ['tenant_uuid' => $tenant->uuid, 'subscription_uuid' => $subscription?->uuid, 'amount' => $amount, 'currency' => $currency, 'method' => $method]);
                throw new HttpResponseException(ApiResponse::error('Razorpay credentials are not configured.', 503));
            }
            try {
                $gatewayPayload = [
                    'amount' => (int) round($amount * 100), 'currency' => $currency,
                    'receipt' => 'tenant-'.$tenant->id.'-'.Str::lower(Str::random(8)),
                    'notes' => array_replace($notes, ['tenant_uuid' => $tenant->uuid, 'subscription_uuid' => $subscription?->uuid, 'invoice_uuid' => $invoice?->uuid]),
                ];
                PaymentLogger::request($request, 'gateway_request', ['tenant_uuid' => $tenant->uuid, 'gateway' => 'razorpay', 'endpoint' => 'https://api.razorpay.com/v1/orders', 'payload' => $gatewayPayload]);
                $response = Http::withBasicAuth($key, $secret)->connectTimeout(10)->timeout(30)->post('https://api.razorpay.com/v1/orders', [
                    ...$gatewayPayload,
                ]);
            } catch (ConnectionException) {
                PaymentLogger::failure($request, 'gateway_connection_failed', ['tenant_uuid' => $tenant->uuid, 'amount' => $amount, 'currency' => $currency, 'method' => $method]);
                throw new HttpResponseException(ApiResponse::error('Unable to create Razorpay order.', 502));
            }
            $order = $response->json();
            PaymentLogger::response($request, 'gateway_response', ['tenant_uuid' => $tenant->uuid, 'http_status' => $response->status(), 'successful' => $response->successful(), 'order_id' => is_array($order) ? ($order['id'] ?? null) : null, 'body' => is_array($order) ? $order : ['body' => $response->body()]]);
            if (! $response->successful() || ! is_array($order) || empty($order['id'])) {
                PaymentLogger::failure($request, 'gateway_order_failed', ['tenant_uuid' => $tenant->uuid, 'http_status' => $response->status(), 'body' => is_array($order) ? $order : ['body' => $response->body()]]);
                throw new HttpResponseException(ApiResponse::error('Unable to create Razorpay order.', 502));
            }
        }
        $paid = $amount <= 0 || ($method === 'cash' && ! $registration);
        $payment = PlatformPayment::create([
            'uuid' => (string) Str::uuid(), 'payment_number' => 'PAY-'.Str::upper(Str::random(12)),
            'tenant_id' => $tenant->id, 'subscription_id' => $subscription?->id, 'platform_invoice_id' => $invoice?->id,
            'gateway' => $order ? 'razorpay' : ($method === 'cash' ? 'cash' : null), 'gateway_payment_id' => $order['id'] ?? null,
            'payment_method' => $method, 'amount' => $amount, 'currency' => $currency,
            'payment_status' => $paid ? 'paid' : 'pending', 'paid_at' => $paid ? now() : null,
            'raw_response' => $order ?? ['method' => $method, 'notes' => $notes],
        ]);
        TenantAudit::record($request, $tenant, 'tenant_payment_created', ['payment_uuid' => $payment->uuid, 'amount' => $amount, 'status' => $payment->payment_status]);
        PaymentLogger::response($request, 'payment_recorded', ['tenant_uuid' => $tenant->uuid, 'subscription_uuid' => $subscription?->uuid, 'invoice_uuid' => $invoice?->uuid, 'payment_uuid' => $payment->uuid, 'payment_status' => $payment->payment_status, 'gateway' => $payment->gateway, 'gateway_payment_id' => $payment->gateway_payment_id]);

        return ['payment' => $payment->load(['tenant', 'invoice.items', 'subscription.plan']), 'order' => $order, 'razorpay_key' => $key];
    }
}
