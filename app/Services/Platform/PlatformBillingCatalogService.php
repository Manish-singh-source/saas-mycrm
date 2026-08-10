<?php

namespace App\Services\Platform;

use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PlatformBillingCatalogService
{
    public function __construct(private readonly PlatformAdminService $admin) {}

    public function byUuid(string $table, string $uuid, bool $includeDeleted = false): object
    {
        $query = DB::table($table)->where('uuid', $uuid);
        if (! $includeDeleted && $this->hasDeletedAt($table)) {
            $query->whereNull('deleted_at');
        }

        $record = $query->first();
        abort_if(! $record, 404);

        return $record;
    }

    public function tenantId(string $uuid): int
    {
        $tenantId = DB::table('tenants')->where('uuid', $uuid)->value('id');
        abort_if(! $tenantId, 404);

        return (int) $tenantId;
    }

    public function planSnapshot(int $planId): array
    {
        $plan = DB::table('plans')->where('id', $planId)->first();
        $features = DB::table('plan_features')
            ->join('features', 'features.id', '=', 'plan_features.feature_id')
            ->where('plan_features.plan_id', $planId)
            ->select('features.uuid', 'features.module', 'features.code', 'features.name', 'features.data_type', 'features.unit', 'plan_features.value', 'plan_features.metadata')
            ->orderBy('features.module')
            ->orderBy('features.name')
            ->get();

        return ['plan' => $plan ? (array) $plan : null, 'features' => $features->map(fn ($row) => (array) $row)->all()];
    }

    public function writeSubscriptionVersion(Request $request, int $subscriptionId, int $planId, int $version, string $reason, ?string $startsAt = null, ?string $endsAt = null): void
    {
        $snapshot = $this->planSnapshot($planId);

        DB::table('subscription_versions')->insert([
            'subscription_id' => $subscriptionId,
            'version' => $version,
            'plan_id' => $planId,
            'billing_cycle' => $snapshot['plan']->billing_cycle ?? 'monthly',
            'starts_at' => $startsAt ?? now(),
            'ends_at' => $endsAt,
            'pricing_snapshot' => json_encode($snapshot['plan']),
            'feature_snapshot' => json_encode($snapshot['features']),
            'change_reason' => $reason,
            'created_by' => $request->user()?->id,
            'created_at' => now(),
        ]);
    }

    public function invoiceTotals(array $items, float $discount = 0.0, float $tax = 0.0): array
    {
        $subtotal = array_reduce($items, fn (float $sum, array $item): float => $sum + (float) ($item['amount'] ?? ((float) ($item['quantity'] ?? 1) * (float) ($item['unit_price'] ?? 0))), 0.0);
        $taxable = max(0, $subtotal - $discount);
        $total = max(0, $taxable + $tax);

        return [
            'subtotal' => number_format($subtotal, 2, '.', ''),
            'discount_amount' => number_format($discount, 2, '.', ''),
            'taxable_amount' => number_format($taxable, 2, '.', ''),
            'tax_amount' => number_format($tax, 2, '.', ''),
            'total_amount' => number_format($total, 2, '.', ''),
            'balance_amount' => number_format($total, 2, '.', ''),
        ];
    }

    public function applyInvoicePayment(int $invoiceId): void
    {
        $paid = (float) DB::table('platform_payments')
            ->where('platform_invoice_id', $invoiceId)
            ->whereIn('payment_status', ['success', 'paid', 'completed'])
            ->sum('amount');
        $invoice = DB::table('platform_invoices')->where('id', $invoiceId)->first();
        if (! $invoice) {
            return;
        }

        $balance = max(0, (float) $invoice->total_amount - $paid);
        $status = $balance <= 0 ? 'paid' : ($paid > 0 ? 'partially_paid' : $invoice->status);

        DB::table('platform_invoices')->where('id', $invoiceId)->update([
            'paid_amount' => number_format($paid, 2, '.', ''),
            'balance_amount' => number_format($balance, 2, '.', ''),
            'status' => $status,
            'updated_at' => now(),
        ]);
    }

    public function maskRaw(mixed $value): mixed
    {
        $sensitive = ['card', 'cvv', 'token', 'secret', 'key', 'password', 'authorization', 'signature'];
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            return json_last_error() === JSON_ERROR_NONE ? $this->maskRaw($decoded) : '[masked]';
        }
        if (! is_array($value)) {
            return $value;
        }

        foreach ($value as $key => $child) {
            $lower = Str::lower((string) $key);
            $value[$key] = Str::contains($lower, $sensitive) ? '[masked]' : $this->maskRaw($child);
        }

        return $value;
    }

    public function idempotencyHit(Request $request, string $operation): ?JsonResponse
    {
        $key = $request->header('Idempotency-Key');
        if (! $key) {
            return ApiResponse::businessError('Idempotency-Key header is required for this financial mutation.', 'IDEMPOTENCY_KEY_REQUIRED', 409);
        }

        $hash = $this->requestHash($request);
        $existing = DB::table('platform_idempotency_keys')
            ->where('key', $key)
            ->where('operation', $operation)
            ->where('platform_user_id', $request->user()?->id)
            ->first();

        if (! $existing) {
            return null;
        }

        if (! hash_equals($existing->request_hash, $hash)) {
            return ApiResponse::businessError('Idempotency-Key was already used with different request data.', 'IDEMPOTENCY_KEY_CONFLICT', 409);
        }

        return response()->json(json_decode((string) $existing->response_body, true), (int) $existing->response_status);
    }

    public function storeIdempotency(Request $request, string $operation, JsonResponse $response): JsonResponse
    {
        $key = $request->header('Idempotency-Key');
        if (! $key) {
            return $response;
        }

        DB::table('platform_idempotency_keys')->updateOrInsert(
            ['key' => $key, 'operation' => $operation, 'platform_user_id' => $request->user()?->id],
            [
                'request_hash' => $this->requestHash($request),
                'status' => 'completed',
                'response_status' => $response->getStatusCode(),
                'response_body' => $response->getContent(),
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        return $response;
    }

    public function audit(Request $request, string $event, string $subjectType, int $subjectId, ?array $old, ?array $new, ?string $description = null): void
    {
        $this->admin->audit($request, $event, $subjectType, $subjectId, $old, $new, $description);
    }

    private function requestHash(Request $request): string
    {
        return hash('sha256', $request->method().'|'.$request->path().'|'.json_encode($request->all()));
    }

    private function hasDeletedAt(string $table): bool
    {
        return in_array($table, ['plans', 'subscriptions', 'platform_invoices', 'coupons'], true);
    }
}
