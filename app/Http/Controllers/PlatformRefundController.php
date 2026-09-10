<?php

namespace App\Http\Controllers;

use App\Models\PlatformPayment;
use App\Models\PlatformRefund;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class PlatformRefundController extends Controller
{
    public function index(Request $request): mixed
    {
        $query = PlatformRefund::query()->with(['tenant', 'payment.invoice', 'payment.subscription.plan'])
            ->when($request->filled('search'), fn ($q) => $q->where('refund_number', 'like', '%'.$request->string('search').'%')->orWhereHas('payment', fn ($payment) => $payment->where('payment_number', 'like', '%'.$request->string('search').'%')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('payment_uuid'), fn ($q) => $q->whereHas('payment', fn ($payment) => $payment->where('uuid', $request->string('payment_uuid'))))
            ->when($request->filled('tenant_uuid'), fn ($q) => $q->whereHas('tenant', fn ($tenant) => $tenant->where('uuid', $request->string('tenant_uuid'))))
            ->latest('refunded_at')->latest('id');
        $paginator = $query->paginate($request->integer('per_page', 10))->withQueryString();
        return ApiResponse::success($paginator->items(), 'Platform refunds fetched.', 200, ['current_page' => $paginator->currentPage(), 'per_page' => $paginator->perPage(), 'total' => $paginator->total(), 'last_page' => $paginator->lastPage()]);
    }

    public function store(Request $request): mixed
    {
        $data = $request->validate(['platform_payment_uuid' => ['nullable', 'uuid'], 'platform_payment_id' => ['nullable', 'uuid'], 'payment_id' => ['nullable', 'uuid'], 'payment_uuid' => ['nullable', 'uuid'], 'amount' => ['required', 'numeric', 'min:0.01'], 'currency' => ['nullable', 'string', 'size:3'], 'reason' => ['nullable', 'string', 'max:255'], 'status' => ['nullable', 'string', 'max:50'], 'raw_response' => ['nullable', 'array']]);
        $paymentUuid = $data['platform_payment_uuid'] ?? $data['platform_payment_id'] ?? $data['payment_uuid'] ?? $data['payment_id'] ?? null;
        if (! $paymentUuid) return ApiResponse::error('A payment UUID is required.', 422, null, 'PAYMENT_REQUIRED');
        $payment = PlatformPayment::where('uuid', $paymentUuid)->first();
        if (! $payment) return ApiResponse::error('Platform payment not found.', 404, null, 'PAYMENT_NOT_FOUND');
        return $this->createForPayment($payment, $data);
    }

    public function createForPayment(PlatformPayment $payment, array $data): mixed
    {
        return DB::transaction(function () use ($payment, $data): mixed {
            $captured = (float) $payment->amount;
            $refunded = (float) $payment->refunds()->whereIn('status', ['success', 'refunded'])->sum('amount');
            if ((float) $data['amount'] > max(0, $captured - $refunded)) return ApiResponse::error('Refund exceeds the remaining refundable amount.', 422, null, 'REFUND_EXCEEDS_REMAINING');
            $refund = PlatformRefund::create(['uuid' => (string) Str::uuid(), 'refund_number' => 'REF-'.strtoupper(Str::random(10)), 'tenant_id' => $payment->tenant_id, 'platform_payment_id' => $payment->id, 'amount' => $data['amount'], 'currency' => strtoupper($data['currency'] ?? $payment->currency), 'reason' => $data['reason'] ?? null, 'status' => $data['status'] ?? 'success', 'refunded_at' => ($data['status'] ?? 'success') === 'success' ? now() : null, 'raw_response' => $data['raw_response'] ?? null]);
            if ($refund->status === 'success') $payment->update(['payment_status' => $refunded + (float) $refund->amount >= $captured ? 'refunded' : $payment->payment_status]);
            return ApiResponse::success(['refund' => $this->present($refund->fresh()), 'payment' => $this->presentPayment($payment->fresh())], 'Platform refund created successfully.', 201);
        });
    }

    public function show(string $uuid): mixed
    {
        $refund = PlatformRefund::where('uuid', $uuid)->first();
        if (! $refund) return ApiResponse::error('Platform refund not found.', 404, null, 'REFUND_NOT_FOUND');
        return ApiResponse::success(['refund' => $this->present($refund)], 'Platform refund fetched.');
    }

    public function retry(Request $request, string $uuid): mixed
    {
        $refund = PlatformRefund::where('uuid', $uuid)->first();
        if (! $refund) return ApiResponse::error('Platform refund not found.', 404, null, 'REFUND_NOT_FOUND');
        if (! in_array($refund->status, ['failed', 'declined', 'retryable'], true)) return ApiResponse::error('Refund is not eligible for retry.', 422, null, 'REFUND_NOT_RETRYABLE');
        $refund->update(['status' => 'processing']);
        return ApiResponse::success(['refund' => $this->present($refund->fresh()), 'processing' => true], 'Refund retry queued.');
    }

    public function export(): mixed { return ApiResponse::success(['export' => ['job_id' => (string) Str::uuid(), 'status' => 'queued', 'format' => 'csv']], 'Refund export queued.', 202); }
    private function present(PlatformRefund $refund): PlatformRefund { return $refund->load(['tenant', 'payment.invoice.tenant', 'payment.subscription.plan', 'payment.refunds']); }
    private function presentPayment(PlatformPayment $payment): PlatformPayment { return $payment->load(['tenant', 'invoice', 'subscription.plan', 'refunds']); }
}