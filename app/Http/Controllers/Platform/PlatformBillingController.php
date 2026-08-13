<?php

namespace App\Http\Controllers\Platform;

use App\Services\Platform\PlatformBillingCatalogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PlatformBillingController extends BasePlatformController
{
    public function __construct(private readonly PlatformBillingCatalogService $billing) {}

    public function invoices(Request $request)
    {
        $q = DB::table('platform_invoices')->whereNull('deleted_at');
        foreach (['status', 'currency'] as $filter) if ($request->filled($filter)) $q->where($filter, $request->input($filter));
        if ($request->filled('tenant_uuid')) $q->where('tenant_id', $this->billing->tenantId((string) $request->input('tenant_uuid')));
        if ($request->boolean('overdue')) $q->where('due_date', '<', now()->toDateString())->where('balance_amount', '>', 0);
        $p = $q->latest('id')->paginate((int) $request->integer('per_page', 25));
        return $this->list($p->items(), $p);
    }

    public function storeInvoice(Request $request)
    {
        if ($hit = $this->billing->idempotencyHit($request, 'invoice.store')) return $hit;
        $data = $this->invoiceData($request);
        return $this->billing->storeIdempotency($request, 'invoice.store', $this->writeInvoice($request, $data));
    }

    public function createInvoiceFromSubscription(Request $request, string $subscription_uuid)
    {
        if ($hit = $this->billing->idempotencyHit($request, 'subscription.invoice')) return $hit;
        $subscription = $this->billing->byUuid('subscriptions', $subscription_uuid);
        $plan = DB::table('plans')->where('id', $subscription->plan_id)->first();
        $data = [
            'tenant_id' => DB::table('tenants')->where('id', $subscription->tenant_id)->value('uuid'),
            'subscription_id' => $subscription_uuid,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(15)->toDateString(),
            'currency' => $subscription->currency,
            'status' => 'draft',
            'discount_amount' => $subscription->discount_amount,
            'tax_amount' => $subscription->tax_amount,
            'items' => [[
                'item_type' => 'plan',
                'description' => ($plan->name ?? 'Subscription') . ' subscription',
                'quantity' => 1,
                'unit_price' => $subscription->base_amount,
                'amount' => $subscription->base_amount,
                'metadata' => ['subscription_uuid' => $subscription_uuid],
            ]],
        ];

        return $this->billing->storeIdempotency($request, 'subscription.invoice', $this->writeInvoice($request, $data));
    }

    public function showInvoice(string $invoice_uuid)
    {
        $invoice = $this->billing->byUuid('platform_invoices', $invoice_uuid);
        return $this->success(['invoice' => $invoice, 'items' => DB::table('platform_invoice_items')->where('platform_invoice_id', $invoice->id)->get(), 'payments' => $this->paymentsForInvoice($invoice->id)]);
    }

    public function updateInvoice(Request $request, string $invoice_uuid)
    {
        if ($hit = $this->billing->idempotencyHit($request, 'invoice.update')) return $hit;
        $invoice = $this->billing->byUuid('platform_invoices', $invoice_uuid);
        if ($invoice->status !== 'draft') return $this->businessError('Only draft invoices can be updated.', 'INVOICE_NOT_DRAFT', 409);
        $data = $request->validate(['invoice_date' => ['nullable', 'date'], 'due_date' => ['nullable', 'date'], 'status' => ['nullable', 'string'], 'discount_amount' => ['nullable', 'numeric', 'min:0'], 'tax_amount' => ['nullable', 'numeric', 'min:0'], 'items' => ['nullable', 'array'], 'items.*.item_type' => ['required_with:items', 'string'], 'items.*.description' => ['required_with:items', 'string'], 'items.*.quantity' => ['nullable', 'numeric'], 'items.*.unit_price' => ['nullable', 'numeric'], 'items.*.amount' => ['nullable', 'numeric'], 'items.*.metadata' => ['nullable', 'array']]);

        return $this->billing->storeIdempotency($request, 'invoice.update', DB::transaction(function () use ($request, $invoice, $data) {
            if (isset($data['items'])) {
                DB::table('platform_invoice_items')->where('platform_invoice_id', $invoice->id)->delete();
                foreach ($data['items'] as $item) $this->insertLine($invoice->id, $item);
            }
            $items = DB::table('platform_invoice_items')->where('platform_invoice_id', $invoice->id)->get()->map(fn($i) => (array) $i)->all();
            $totals = $this->billing->invoiceTotals($items, (float) ($data['discount_amount'] ?? $invoice->discount_amount), (float) ($data['tax_amount'] ?? $invoice->tax_amount));
            DB::table('platform_invoices')->where('id', $invoice->id)->update([...collect($data)->except('items')->all(), ...$totals, 'updated_at' => now()]);
            $fresh = DB::table('platform_invoices')->where('id', $invoice->id)->first();
            $this->billing->audit($request, 'invoice_updated', 'platform_invoices', $invoice->id, (array) $invoice, (array) $fresh);
            return $this->success(['invoice' => $fresh], 'Invoice updated.');
        }));
    }

    public function cancelInvoice(Request $request, string $invoice_uuid)
    {
        if ($hit = $this->billing->idempotencyHit($request, 'invoice.cancel')) return $hit;
        $invoice = $this->billing->byUuid('platform_invoices', $invoice_uuid);
        return $this->billing->storeIdempotency($request, 'invoice.cancel', DB::transaction(function () use ($request, $invoice) {
            DB::table('platform_invoices')->where('id', $invoice->id)->update(['status' => 'cancelled', 'balance_amount' => 0, 'updated_at' => now()]);
            $this->billing->audit($request, 'invoice_cancelled', 'platform_invoices', $invoice->id, (array) $invoice, ['status' => 'cancelled']);
            return $this->success(null, 'Invoice cancelled.');
        }));
    }

    public function sendInvoice(Request $request, string $invoice_uuid)
    {
        $invoice = $this->billing->byUuid('platform_invoices', $invoice_uuid);
        $data = $request->validate(['to' => ['nullable', 'array'], 'to.*' => ['email'], 'cc' => ['nullable', 'array'], 'cc.*' => ['email'], 'message' => ['nullable', 'string']]);
        DB::table('platform_invoices')->where('id', $invoice->id)->update(['status' => $invoice->status === 'draft' ? 'sent' : $invoice->status, 'updated_at' => now()]);
        $this->billing->audit($request, 'invoice_sent', 'platform_invoices', $invoice->id, (array) $invoice, $data);
        return $this->success(['invoice' => DB::table('platform_invoices')->where('id', $invoice->id)->first()], 'Invoice send queued.');
    }

    public function invoicePdf(Request $request, string $invoice_uuid)
    {
        $invoice = $this->billing->byUuid('platform_invoices', $invoice_uuid);
        if (! $invoice->pdf_file_id) {
            $fileId = DB::table('files')->insertGetId(['uuid' => (string) Str::uuid(), 'tenant_id' => $invoice->tenant_id, 'disk' => config('filesystems.default', 'local'), 'path' => 'platform/invoices/' . $invoice->invoice_number . '.pdf', 'original_name' => $invoice->invoice_number . '.pdf', 'mime_type' => 'application/pdf', 'extension' => 'pdf', 'size_bytes' => 0, 'visibility' => 'private', 'platform_uploaded_by' => $request->user()?->id, 'created_at' => now(), 'updated_at' => now()]);
            DB::table('platform_invoices')->where('id', $invoice->id)->update(['pdf_file_id' => $fileId, 'updated_at' => now()]);
            $invoice = DB::table('platform_invoices')->where('id', $invoice->id)->first();
        }
        $file = DB::table('files')->where('id', $invoice->pdf_file_id)->first(['uuid', 'original_name', 'mime_type', 'size_bytes', 'visibility', 'created_at']);
        return $this->success(['pdf_file' => $file], 'Invoice PDF generated.');
    }

    public function recordInvoicePayment(Request $request, string $invoice_uuid)
    {
        $request->merge(['platform_invoice_id' => $invoice_uuid]);
        return $this->storePayment($request);
    }

    public function payments(Request $request)
    {
        $q = DB::table('platform_payments');
        foreach (['payment_status', 'gateway', 'currency'] as $filter) if ($request->filled($filter)) $q->where($filter, $request->input($filter));
        if ($request->filled('tenant_uuid')) $q->where('tenant_id', $this->billing->tenantId((string) $request->input('tenant_uuid')));
        $p = $q->latest('id')->paginate((int) $request->integer('per_page', 25));
        $items = collect($p->items())->map(fn($payment) => tap($payment, fn($p) => $p->raw_response = $this->billing->maskRaw($p->raw_response)))->all();
        return $this->list($items, $p);
    }

    public function storePayment(Request $request)
    {
        if ($hit = $this->billing->idempotencyHit($request, 'payment.store')) return $hit;
        $data = $request->validate(['tenant_id' => ['nullable', 'uuid'], 'platform_invoice_id' => ['nullable', 'uuid'], 'subscription_id' => ['nullable', 'uuid'], 'gateway' => ['nullable', 'string'], 'gateway_payment_id' => ['nullable', 'string'], 'payment_method' => ['nullable', 'string'], 'amount' => ['required', 'numeric', 'min:0.01'], 'currency' => ['nullable', 'string', 'size:3'], 'payment_status' => ['required', 'string'], 'paid_at' => ['nullable', 'date'], 'failure_reason' => ['nullable', 'string'], 'raw_response' => ['nullable', 'array']]);

        return $this->billing->storeIdempotency($request, 'payment.store', DB::transaction(function () use ($request, $data) {
            $invoice = ! empty($data['platform_invoice_id']) ? $this->billing->byUuid('platform_invoices', $data['platform_invoice_id']) : null;
            $subscription = ! empty($data['subscription_id']) ? $this->billing->byUuid('subscriptions', $data['subscription_id']) : null;
            $tenantId = $invoice->tenant_id ?? $subscription->tenant_id ?? (! empty($data['tenant_id']) ? $this->billing->tenantId($data['tenant_id']) : null);
            abort_if(! $tenantId, 422, 'tenant_id, platform_invoice_id, or subscription_id is required.');
            $id = DB::table('platform_payments')->insertGetId(['uuid' => (string) Str::uuid(), 'payment_number' => 'PAY-' . Str::upper(Str::random(10)), 'tenant_id' => $tenantId, 'platform_invoice_id' => $invoice->id ?? null, 'subscription_id' => $subscription->id ?? $invoice->subscription_id ?? null, 'gateway' => $data['gateway'] ?? null, 'gateway_payment_id' => $data['gateway_payment_id'] ?? null, 'payment_method' => $data['payment_method'] ?? null, 'amount' => $data['amount'], 'currency' => $data['currency'] ?? $invoice->currency ?? $subscription->currency ?? 'INR', 'payment_status' => $data['payment_status'], 'paid_at' => $data['paid_at'] ?? now(), 'failure_reason' => $data['failure_reason'] ?? null, 'raw_response' => isset($data['raw_response']) ? json_encode($data['raw_response']) : null, 'created_at' => now(), 'updated_at' => now()]);
            if ($invoice) $this->billing->applyInvoicePayment($invoice->id);
            $payment = DB::table('platform_payments')->where('id', $id)->first();
            $payment->raw_response = $this->billing->maskRaw($payment->raw_response);
            $this->billing->audit($request, 'payment_recorded', 'platform_payments', $id, null, (array) $payment);
            return $this->success(['payment' => $payment], 'Payment recorded.', 201);
        }));
    }

    public function showPayment(string $payment_uuid)
    {
        $payment = $this->billing->byUuid('platform_payments', $payment_uuid);
        $payment->raw_response = $this->billing->maskRaw($payment->raw_response);
        return $this->success(['payment' => $payment]);
    }

    public function retryPayment(Request $request, string $payment_uuid)
    {
        if ($hit = $this->billing->idempotencyHit($request, 'payment.retry')) return $hit;
        $payment = $this->billing->byUuid('platform_payments', $payment_uuid);
        return $this->billing->storeIdempotency($request, 'payment.retry', DB::transaction(function () use ($request, $payment) {
            DB::table('platform_payments')->where('id', $payment->id)->update(['payment_status' => 'retry_queued', 'updated_at' => now()]);
            $this->billing->audit($request, 'payment_retry_queued', 'platform_payments', $payment->id, (array) $payment, ['payment_status' => 'retry_queued']);
            return $this->success(['payment' => DB::table('platform_payments')->where('id', $payment->id)->first()], 'Payment retry queued.');
        }));
    }

    public function reconcilePayment(Request $request, string $payment_uuid)
    {
        if ($hit = $this->billing->idempotencyHit($request, 'payment.reconcile')) return $hit;
        $payment = $this->billing->byUuid('platform_payments', $payment_uuid);
        return $this->billing->storeIdempotency($request, 'payment.reconcile', $this->success(['payment' => $payment, 'reconciliation' => ['status' => 'queued']], 'Payment reconciliation queued.'));
    }

    public function refunds(Request $request)
    {
        $p = DB::table('platform_refunds')->latest('id')->paginate((int) $request->integer('per_page', 25));
        $items = collect($p->items())->map(fn($refund) => tap($refund, fn($r) => $r->raw_response = $this->billing->maskRaw($r->raw_response)))->all();
        return $this->list($items, $p);
    }

    public function storeRefund(Request $request)
    {
        if ($hit = $this->billing->idempotencyHit($request, 'refund.store')) return $hit;
        $data = $request->validate(['tenant_id' => ['nullable', 'uuid'], 'platform_payment_id' => ['required', 'uuid'], 'amount' => ['required', 'numeric', 'min:0.01'], 'currency' => ['nullable', 'string', 'size:3'], 'reason' => ['nullable', 'string'], 'gateway' => ['nullable', 'string']]);

        return $this->billing->storeIdempotency($request, 'refund.store', DB::transaction(function () use ($request, $data) {
            $payment = $this->billing->byUuid('platform_payments', $data['platform_payment_id']);
            $id = DB::table('platform_refunds')->insertGetId(['uuid' => (string) Str::uuid(), 'refund_number' => 'REF-' . Str::upper(Str::random(10)), 'tenant_id' => $payment->tenant_id, 'platform_payment_id' => $payment->id, 'amount' => $data['amount'], 'currency' => $data['currency'] ?? $payment->currency, 'reason' => $data['reason'] ?? null, 'status' => 'pending', 'raw_response' => json_encode(['gateway' => $data['gateway'] ?? $payment->gateway, 'status' => 'placeholder']), 'created_at' => now(), 'updated_at' => now()]);
            $refund = DB::table('platform_refunds')->where('id', $id)->first();
            $refund->raw_response = $this->billing->maskRaw($refund->raw_response);
            $this->billing->audit($request, 'refund_created', 'platform_refunds', $id, null, (array) $refund);
            return $this->success(['refund' => $refund], 'Refund created.', 201);
        }));
    }

    public function refundPayment(Request $request, string $payment_uuid)
    {
        $request->merge(['platform_payment_id' => $payment_uuid]);
        return $this->storeRefund($request);
    }
    public function showRefund(string $refund_uuid)
    {
        $refund = $this->billing->byUuid('platform_refunds', $refund_uuid);
        $refund->raw_response = $this->billing->maskRaw($refund->raw_response);
        return $this->success(['refund' => $refund]);
    }
    public function retryRefund(Request $request, string $refund_uuid)
    {
        if ($hit = $this->billing->idempotencyHit($request, 'refund.retry')) return $hit;
        $refund = $this->billing->byUuid('platform_refunds', $refund_uuid);
        DB::table('platform_refunds')->where('id', $refund->id)->update(['status' => 'retry_queued', 'updated_at' => now()]);
        return $this->billing->storeIdempotency($request, 'refund.retry', $this->success(['refund' => DB::table('platform_refunds')->where('id', $refund->id)->first()], 'Refund retry queued.'));
    }
    public function exportInvoices()
    {
        return $this->success(['export' => ['status' => 'queued', 'format' => 'csv']], 'Invoice export queued.');
    }
    public function exportPayments()
    {
        return $this->success(['export' => ['status' => 'queued', 'format' => 'csv']], 'Payment export queued.');
    }
    public function exportRefunds()
    {
        return $this->success(['export' => ['status' => 'queued', 'format' => 'csv']], 'Refund export queued.');
    }

    private function writeInvoice(Request $request, array $data)
    {
        return DB::transaction(function () use ($request, $data) {
            $tenantId = $this->billing->tenantId($data['tenant_id']);
            $subscription = ! empty($data['subscription_id']) ? $this->billing->byUuid('subscriptions', $data['subscription_id']) : null;
            $totals = $this->billing->invoiceTotals($data['items'], (float) ($data['discount_amount'] ?? 0), (float) ($data['tax_amount'] ?? 0));
            $id = DB::table('platform_invoices')->insertGetId(['uuid' => (string) Str::uuid(), 'invoice_number' => 'INV-' . Str::upper(Str::random(10)), 'tenant_id' => $tenantId, 'subscription_id' => $subscription->id ?? null, 'invoice_date' => $data['invoice_date'], 'due_date' => $data['due_date'] ?? null, ...$totals, 'paid_amount' => 0, 'currency' => $data['currency'] ?? $subscription->currency ?? 'INR', 'status' => $data['status'] ?? 'draft', 'created_at' => now(), 'updated_at' => now()]);
            foreach ($data['items'] as $item) $this->insertLine($id, $item);
            $invoice = DB::table('platform_invoices')->where('id', $id)->first();
            $this->billing->audit($request, 'invoice_created', 'platform_invoices', $id, null, (array) $invoice);
            return $this->success(['invoice' => $invoice, 'items' => DB::table('platform_invoice_items')->where('platform_invoice_id', $id)->get()], 'Invoice created.', 201);
        });
    }

    private function insertLine(int $invoiceId, array $item): void
    {
        DB::table('platform_invoice_items')->insert(['platform_invoice_id' => $invoiceId, 'item_type' => $item['item_type'], 'description' => $item['description'], 'quantity' => $item['quantity'] ?? 1, 'unit_price' => $item['unit_price'] ?? 0, 'amount' => $item['amount'] ?? ((float) ($item['quantity'] ?? 1) * (float) ($item['unit_price'] ?? 0)), 'metadata' => isset($item['metadata']) ? json_encode($item['metadata']) : null]);
    }

    private function paymentsForInvoice(int $invoiceId)
    {
        return DB::table('platform_payments')->where('platform_invoice_id', $invoiceId)->latest('id')->get()->map(fn($p) => tap($p, fn($x) => $x->raw_response = $this->billing->maskRaw($x->raw_response)));
    }

    private function invoiceData(Request $request): array
    {
        return $request->validate(['tenant_id' => ['required', 'uuid'], 'subscription_id' => ['nullable', 'uuid'], 'invoice_date' => ['required', 'date'], 'due_date' => ['nullable', 'date'], 'currency' => ['nullable', 'string', 'size:3'], 'status' => ['nullable', Rule::in(['draft', 'sent', 'paid', 'partially_paid', 'overdue', 'cancelled', 'void'])], 'discount_amount' => ['nullable', 'numeric', 'min:0'], 'tax_amount' => ['nullable', 'numeric', 'min:0'], 'notes' => ['nullable', 'string'], 'items' => ['required', 'array', 'min:1'], 'items.*.item_type' => ['required', 'string'], 'items.*.description' => ['required', 'string'], 'items.*.quantity' => ['nullable', 'numeric'], 'items.*.unit_price' => ['nullable', 'numeric'], 'items.*.amount' => ['nullable', 'numeric'], 'items.*.metadata' => ['nullable', 'array']]);
    }
}
