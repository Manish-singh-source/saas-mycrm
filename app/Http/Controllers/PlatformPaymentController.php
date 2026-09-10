<?php

namespace App\Http\Controllers;

use App\Models\PlatformInvoice;
use App\Models\PlatformPayment;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class PlatformPaymentController extends Controller
{
    public function index(Request $request): mixed
    {
        $query = PlatformPayment::query()->with(['tenant', 'invoice', 'subscription.plan', 'refunds'])
            ->when($request->filled('search'), function ($q) use ($request): void {
                $term = '%'.$request->string('search').'%';
                $q->where(function ($inner) use ($term): void {
                    $inner->where('payment_number', 'like', $term)->orWhere('gateway_payment_id', 'like', $term)
                        ->orWhereHas('tenant', fn ($tenant) => $tenant->where('organization_name', 'like', $term))
                        ->orWhereHas('invoice', fn ($invoice) => $invoice->where('invoice_number', 'like', $term));
                });
            })
            ->when($request->filled('payment_status'), fn ($q) => $q->where('payment_status', $request->string('payment_status')))
            ->when($request->filled('gateway'), fn ($q) => $q->where('gateway', $request->string('gateway')))
            ->when($request->filled('tenant_uuid'), fn ($q) => $q->whereHas('tenant', fn ($tenant) => $tenant->where('uuid', $request->string('tenant_uuid'))))
            ->when($request->filled('invoice_uuid'), fn ($q) => $q->whereHas('invoice', fn ($invoice) => $invoice->where('uuid', $request->string('invoice_uuid'))))
            ->when($request->filled('subscription_uuid'), fn ($q) => $q->whereHas('subscription', fn ($subscription) => $subscription->where('uuid', $request->string('subscription_uuid'))))
            ->latest('paid_at')->latest('id');

        $paginator = $query->paginate($request->integer('per_page', 10))->withQueryString();
        return ApiResponse::success($paginator->items(), 'Platform payments fetched.', 200, [
            'current_page' => $paginator->currentPage(), 'per_page' => $paginator->perPage(),
            'total' => $paginator->total(), 'last_page' => $paginator->lastPage(),
        ]);
    }

    public function store(Request $request): mixed
    {
$data = $request->validate([
            'tenant_uuid' => ['nullable', 'uuid', 'exists:tenants,uuid'], 'tenant_id' => ['nullable', 'uuid', 'exists:tenants,uuid'],
            'platform_invoice_uuid' => ['nullable', 'uuid', 'exists:platform_invoices,uuid'], 'platform_invoice_id' => ['nullable', 'uuid', 'exists:platform_invoices,uuid'],
            'invoice_uuid' => ['nullable', 'uuid', 'exists:platform_invoices,uuid'],
            'subscription_uuid' => ['nullable', 'uuid', 'exists:subscriptions,uuid'], 'subscription_id' => ['nullable', 'uuid', 'exists:subscriptions,uuid'],
            'gateway' => ['nullable', 'string', 'max:80'], 'gateway_payment_id' => ['nullable', 'string', 'max:255'],
            'payment_method' => ['nullable', 'string', 'max:80'], 'amount' => ['required', 'numeric', 'min:0.01'],
            'currency' => ['nullable', 'string', 'size:3'], 'payment_status' => ['required', 'string', 'max:50'],
            'paid_at' => ['nullable', 'date'], 'failure_reason' => ['nullable', 'string', 'max:255'],
            'raw_response' => ['nullable', 'array'],
        ]);

        return DB::transaction(function () use ($data): mixed {
            $invoiceUuid = $data['platform_invoice_uuid'] ?? $data['platform_invoice_id'] ?? $data['invoice_uuid'] ?? null;
            $invoice = $invoiceUuid ? PlatformInvoice::where('uuid', $invoiceUuid)->lockForUpdate()->firstOrFail() : null;
            if ($invoice && (float) $data['amount'] > (float) $invoice->balance_amount && $data['payment_status'] === 'success') {
                return ApiResponse::error('Payment exceeds the invoice balance.', 422, null, 'OVERPAYMENT');
            }
            if (! empty($data['gateway_payment_id']) && PlatformPayment::where('gateway_payment_id', $data['gateway_payment_id'])->exists()) {
                return ApiResponse::error('Payment reference has already been recorded.', 422, null, 'PAYMENT_DUPLICATE');
            }
            if (! $invoice && empty($data['tenant_uuid'] ?? ($data['tenant_id'] ?? null))) {
                return ApiResponse::error('A tenant or invoice is required.', 422, null, 'PAYMENT_OWNER_REQUIRED');
            }
            $payment = PlatformPayment::create([
                'uuid' => (string) Str::uuid(), 'payment_number' => 'PAY-'.strtoupper(Str::random(10)),
                'tenant_id' => $invoice?->tenant_id ?? $this->tenantId($data['tenant_uuid'] ?? ($data['tenant_id'] ?? null)),
                'platform_invoice_id' => $invoice?->id, 'subscription_id' => $this->subscriptionId($data['subscription_uuid'] ?? ($data['subscription_id'] ?? null)) ?? $invoice?->subscription_id,
                'gateway' => $data['gateway'] ?? null, 'gateway_payment_id' => $data['gateway_payment_id'] ?? null,
                'payment_method' => $data['payment_method'] ?? null, 'amount' => $data['amount'],
                'currency' => strtoupper($data['currency'] ?? $invoice?->currency ?? 'INR'), 'payment_status' => $data['payment_status'],
                'paid_at' => $data['paid_at'] ?? ($data['payment_status'] === 'success' ? now() : null),
                'failure_reason' => $data['failure_reason'] ?? null, 'raw_response' => $data['raw_response'] ?? null,
            ]);
            if ($invoice) $this->syncInvoice($invoice);
            return ApiResponse::success(['payment' => $this->present($payment->fresh()), 'invoice' => $invoice ? $this->presentInvoice($invoice->fresh()) : null], 'Platform payment created successfully.', 201);
        });
    }

    public function show(string $uuid): mixed
    {
        $payment = PlatformPayment::where('uuid', $uuid)->first();
        if (! $payment) return ApiResponse::error('Platform payment not found.', 404, null, 'PAYMENT_NOT_FOUND');
        return ApiResponse::success(['payment' => $this->present($payment)], 'Platform payment fetched.');
    }

    public function retry(Request $request, string $uuid): mixed
    {
        $payment = PlatformPayment::where('uuid', $uuid)->first();
        if (! $payment) return ApiResponse::error('Platform payment not found.', 404, null, 'PAYMENT_NOT_FOUND');
        if (! in_array($payment->payment_status, ['failed', 'declined', 'retryable'], true)) return ApiResponse::error('Payment is not eligible for retry.', 422, null, 'PAYMENT_NOT_RETRYABLE');
        $payment->update(['payment_status' => 'processing', 'failure_reason' => null]);
        return ApiResponse::success(['payment' => $this->present($payment->fresh()), 'processing' => true], 'Payment retry queued.');
    }

    public function reconcile(Request $request, string $uuid): mixed
    {
        $payment = PlatformPayment::where('uuid', $uuid)->first();
        if (! $payment) return ApiResponse::error('Platform payment not found.', 404, null, 'PAYMENT_NOT_FOUND');
        $data = $request->validate(['payment_status' => ['nullable', 'string', 'max:50'], 'failure_reason' => ['nullable', 'string', 'max:255'], 'raw_response' => ['nullable', 'array']]);
        return DB::transaction(function () use ($payment, $data): mixed {
            $payment->update(['payment_status' => $data['payment_status'] ?? 'success', 'failure_reason' => $data['failure_reason'] ?? null, 'raw_response' => $data['raw_response'] ?? $payment->raw_response, 'paid_at' => ($data['payment_status'] ?? 'success') === 'success' ? ($payment->paid_at ?? now()) : $payment->paid_at]);
            $invoice = $payment->invoice()->lockForUpdate()->first();
            if ($invoice) $this->syncInvoice($invoice);
            return ApiResponse::success(['payment' => $this->present($payment->fresh()), 'invoice' => $invoice ? $this->presentInvoice($invoice->fresh()) : null], 'Payment reconciled successfully.');
        });
    }

    public function refund(Request $request, string $uuid): mixed
    {
        $payment = PlatformPayment::where('uuid', $uuid)->first();
        if (! $payment) return ApiResponse::error('Platform payment not found.', 404, null, 'PAYMENT_NOT_FOUND');
        $data = $request->validate(['amount' => ['required', 'numeric', 'min:0.01'], 'currency' => ['nullable', 'string', 'size:3'], 'reason' => ['nullable', 'string', 'max:255'], 'status' => ['nullable', 'string', 'max:50'], 'raw_response' => ['nullable', 'array']]);
        return app(PlatformRefundController::class)->createForPayment($payment, $data);
    }

    public function export(): mixed { return ApiResponse::success(['export' => ['job_id' => (string) Str::uuid(), 'status' => 'queued', 'format' => 'csv']], 'Payment export queued.', 202); }

    private function present(PlatformPayment $payment): PlatformPayment { return $payment->load(['tenant', 'invoice.tenant', 'invoice.subscription.plan', 'subscription.plan', 'refunds']); }
    private function presentInvoice(PlatformInvoice $invoice): PlatformInvoice { return $invoice->load(['tenant', 'subscription.plan', 'items', 'payments.refunds']); }
    private function tenantId(?string $uuid): int { return (int) DB::table('tenants')->where('uuid', $uuid)->value('id'); }
    private function subscriptionId(?string $uuid): ?int { return $uuid ? (int) DB::table('subscriptions')->where('uuid', $uuid)->value('id') : null; }
    private function syncInvoice(PlatformInvoice $invoice): void
    {
        $paid = (float) $invoice->payments()->where('payment_status', 'success')->sum('amount') - (float) $invoice->payments()->where('payment_status', 'success')->withSum(['refunds as refunded_total' => fn ($q) => $q->where('status', 'success')], 'amount')->get()->sum('refunded_total');
        $total = (float) $invoice->total_amount;
        $invoice->update(['paid_amount' => max(0, $paid), 'balance_amount' => max(0, $total - $paid), 'status' => $paid >= $total ? 'paid' : ($paid > 0 ? 'partially_paid' : $invoice->status)]);
    }
}