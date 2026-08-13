<?php

namespace App\Http\Controllers\Platform;

use App\Services\Platform\PlatformBillingCatalogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PlatformSubscriptionController extends BasePlatformController
{
    public function __construct(private readonly PlatformBillingCatalogService $billing) {}

    public function index(Request $request)
    {
        $q = DB::table('subscriptions')->whereNull('deleted_at');
        foreach (['status', 'type', 'billing_cycle', 'renewal_type'] as $filter) {
            if ($request->filled($filter)) $q->where($filter, $request->input($filter));
        }
        if ($request->filled('tenant_uuid')) $q->where('tenant_id', $this->billing->tenantId((string) $request->input('tenant_uuid')));
        if ($request->filled('plan_uuid')) $q->where('plan_id', $this->billing->byUuid('plans', (string) $request->input('plan_uuid'))->id);
        $p = $q->latest('id')->paginate((int) $request->integer('per_page', 25));
        return $this->list($p->items(), $p);
    }

    public function store(Request $request)
    {
        if ($hit = $this->billing->idempotencyHit($request, 'subscription.store')) return $hit;
        $data = $this->subscriptionData($request);

        return $this->billing->storeIdempotency($request, 'subscription.store', DB::transaction(function () use ($request, $data) {
            $tenantId = $this->billing->tenantId($data['tenant_id']);
            $plan = $this->billing->byUuid('plans', $data['plan_id']);
            $id = DB::table('subscriptions')->insertGetId([
                'uuid' => (string) Str::uuid(),
                'subscription_number' => 'SUB-' . Str::upper(Str::random(10)),
                'tenant_id' => $tenantId,
                'plan_id' => $plan->id,
                'current_version' => 1,
                'type' => $data['type'] ?? 'paid',
                'billing_cycle' => $data['billing_cycle'] ?? $plan->billing_cycle,
                'status' => $data['status'] ?? 'active',
                'renewal_type' => $data['renewal_type'] ?? 'manual',
                'starts_at' => $data['starts_at'] ?? now(),
                'expires_at' => $data['expires_at'] ?? null,
                'next_billing_at' => $data['next_billing_at'] ?? null,
                'trial_starts_at' => $data['trial_starts_at'] ?? null,
                'trial_ends_at' => $data['trial_ends_at'] ?? null,
                'base_amount' => $data['base_amount'] ?? $plan->base_price,
                'addon_amount' => $data['addon_amount'] ?? 0,
                'discount_amount' => $data['discount_amount'] ?? 0,
                'taxable_amount' => $data['taxable_amount'] ?? ($data['base_amount'] ?? $plan->base_price),
                'tax_amount' => $data['tax_amount'] ?? 0,
                'payable_amount' => $data['payable_amount'] ?? ($data['base_amount'] ?? $plan->base_price),
                'currency' => $data['currency'] ?? $plan->currency,
                'auto_renew' => (bool) ($data['auto_renew'] ?? false),
                'notes' => $data['notes'] ?? null,
                'created_by' => $request->user()?->id,
                'updated_by' => $request->user()?->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->billing->writeSubscriptionVersion($request, $id, $plan->id, 1, 'created', $data['starts_at'] ?? null, $data['expires_at'] ?? null);
            $subscription = DB::table('subscriptions')->where('id', $id)->first();
            $this->billing->audit($request, 'subscription_created', 'subscriptions', $id, null, (array) $subscription);

            return $this->success(['subscription' => $subscription], 'Subscription created.', 201);
        }));
    }

    public function show(string $subscription_uuid)
    {
        $subscription = $this->billing->byUuid('subscriptions', $subscription_uuid);
        return $this->success(['subscription' => $subscription, ...$this->relations($subscription)]);
    }

    public function update(Request $request, string $subscription_uuid)
    {
        if ($hit = $this->billing->idempotencyHit($request, 'subscription.update')) return $hit;
        $subscription = $this->billing->byUuid('subscriptions', $subscription_uuid);
        $data = $request->validate([
            'billing_cycle' => ['sometimes', 'string', 'max:50'],
            'status' => ['sometimes', Rule::in(['trial', 'active', 'paused', 'expired', 'cancelled', 'suspended', 'pending_payment', 'grace_period'])],
            'renewal_type' => ['sometimes', 'string', 'max:50'],
            'starts_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date'],
            'next_billing_at' => ['nullable', 'date'],
            'auto_renew' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
        ]);

        return $this->billing->storeIdempotency($request, 'subscription.update', DB::transaction(function () use ($request, $subscription, $data) {
            DB::table('subscriptions')->where('id', $subscription->id)->update([...$data, 'updated_by' => $request->user()?->id, 'updated_at' => now()]);
            $fresh = DB::table('subscriptions')->where('id', $subscription->id)->first();
            $this->billing->audit($request, 'subscription_updated', 'subscriptions', $subscription->id, (array) $subscription, (array) $fresh);
            return $this->success(['subscription' => $fresh], 'Subscription updated.');
        }));
    }

    public function upgrade(Request $request, string $uuid)
    {
        return $this->changePlan($request, $uuid, 'upgrade', 'subscription.upgrade');
    }
    public function downgrade(Request $request, string $uuid)
    {
        return $this->changePlan($request, $uuid, 'downgrade', 'subscription.downgrade');
    }

    public function renew(Request $request, string $uuid)
    {
        if ($hit = $this->billing->idempotencyHit($request, 'subscription.renew')) return $hit;
        $subscription = $this->billing->byUuid('subscriptions', $uuid);
        $data = $request->validate(['renewal_starts_at' => ['nullable', 'date'], 'renewal_expires_at' => ['required', 'date'], 'amount' => ['nullable', 'numeric', 'min:0'], 'currency' => ['nullable', 'string', 'size:3'], 'create_invoice' => ['nullable', 'boolean'], 'notes' => ['nullable', 'string']]);

        return $this->billing->storeIdempotency($request, 'subscription.renew', DB::transaction(function () use ($request, $subscription, $data) {
            $oldExpires = $subscription->expires_at;
            DB::table('subscriptions')->where('id', $subscription->id)->update(['status' => 'active', 'starts_at' => $data['renewal_starts_at'] ?? $subscription->starts_at, 'expires_at' => $data['renewal_expires_at'], 'last_renewed_at' => now(), 'updated_by' => $request->user()?->id, 'updated_at' => now()]);
            DB::table('subscription_renewals')->insert(['subscription_id' => $subscription->id, 'old_expires_at' => $oldExpires, 'new_expires_at' => $data['renewal_expires_at'], 'amount' => $data['amount'] ?? $subscription->payable_amount, 'status' => 'completed', 'renewed_at' => now()]);
            $fresh = DB::table('subscriptions')->where('id', $subscription->id)->first();
            $this->billing->audit($request, 'subscription_renewed', 'subscriptions', $subscription->id, (array) $subscription, (array) $fresh, $data['notes'] ?? null);
            return $this->success(['subscription' => $fresh], 'Subscription renewed.');
        }));
    }

    public function pause(Request $request, string $uuid)
    {
        return $this->status($request, $uuid, 'paused', 'subscription.paused', ['paused_at' => now()]);
    }
    public function resume(Request $request, string $uuid)
    {
        return $this->status($request, $uuid, 'active', 'subscription.resumed', ['resumed_at' => now()]);
    }

    public function cancel(Request $request, string $uuid)
    {
        $request->validate(['reason' => ['required', 'string']]);
        return $this->status($request, $uuid, 'cancelled', 'subscription.cancelled', ['cancelled_at' => now(), 'cancellation_reason' => $request->input('reason')]);
    }

    public function addAddon(Request $request, string $uuid)
    {
        if ($hit = $this->billing->idempotencyHit($request, 'subscription.addon.add')) return $hit;
        $subscription = $this->billing->byUuid('subscriptions', $uuid);
        $data = $request->validate(['addon_plan_id' => ['required', 'uuid'], 'quantity' => ['nullable', 'integer', 'min:1'], 'unit_price' => ['nullable', 'numeric', 'min:0'], 'starts_at' => ['nullable', 'date'], 'ends_at' => ['nullable', 'date'], 'status' => ['nullable', 'string']]);

        return $this->billing->storeIdempotency($request, 'subscription.addon.add', DB::transaction(function () use ($request, $subscription, $data) {
            $addon = $this->billing->byUuid('addon_plans', $data['addon_plan_id']);
            $id = DB::table('subscription_addons')->insertGetId(['subscription_id' => $subscription->id, 'addon_plan_id' => $addon->id, 'quantity' => $data['quantity'] ?? 1, 'unit_price' => $data['unit_price'] ?? $addon->price, 'starts_at' => $data['starts_at'] ?? now(), 'ends_at' => $data['ends_at'] ?? null, 'status' => $data['status'] ?? 'active', 'created_at' => now(), 'updated_at' => now()]);
            $sum = DB::table('subscription_addons')->where('subscription_id', $subscription->id)->where('status', 'active')->selectRaw('COALESCE(SUM(quantity * unit_price),0) as total')->value('total');
            DB::table('subscriptions')->where('id', $subscription->id)->update(['addon_amount' => $sum, 'payable_amount' => (float) $subscription->base_amount + (float) $sum - (float) $subscription->discount_amount + (float) $subscription->tax_amount, 'updated_at' => now()]);
            $record = DB::table('subscription_addons')->where('id', $id)->first();
            $this->billing->audit($request, 'subscription_addon_added', 'subscription_addons', $id, null, (array) $record);
            return $this->success(['addon' => $record], 'Subscription add-on added.', 201);
        }));
    }

    public function updateAddon(Request $request, string $uuid, string $addon_uuid)
    {
        if ($hit = $this->billing->idempotencyHit($request, 'subscription.addon.update')) return $hit;
        $subscription = $this->billing->byUuid('subscriptions', $uuid);
        $addon = DB::table('subscription_addons')->where('subscription_id', $subscription->id)->where('id', $addon_uuid)->first();
        abort_if(! $addon, 404);
        $data = $request->validate(['quantity' => ['nullable', 'integer', 'min:1'], 'unit_price' => ['nullable', 'numeric', 'min:0'], 'ends_at' => ['nullable', 'date'], 'status' => ['nullable', 'string']]);

        return $this->billing->storeIdempotency($request, 'subscription.addon.update', DB::transaction(function () use ($request, $addon, $data) {
            DB::table('subscription_addons')->where('id', $addon->id)->update([...$data, 'updated_at' => now()]);
            $fresh = DB::table('subscription_addons')->where('id', $addon->id)->first();
            $this->billing->audit($request, 'subscription_addon_updated', 'subscription_addons', $addon->id, (array) $addon, (array) $fresh);
            return $this->success(['addon' => $fresh], 'Subscription add-on updated.');
        }));
    }

    public function removeAddon(Request $request, string $uuid, string $addon_uuid)
    {
        if ($hit = $this->billing->idempotencyHit($request, 'subscription.addon.remove')) return $hit;
        $subscription = $this->billing->byUuid('subscriptions', $uuid);
        $addon = DB::table('subscription_addons')->where('subscription_id', $subscription->id)->where('id', $addon_uuid)->first();
        abort_if(! $addon, 404);
        return $this->billing->storeIdempotency($request, 'subscription.addon.remove', DB::transaction(function () use ($request, $addon) {
            DB::table('subscription_addons')->where('id', $addon->id)->update(['status' => 'cancelled', 'ends_at' => now(), 'updated_at' => now()]);
            $this->billing->audit($request, 'subscription_addon_cancelled', 'subscription_addons', $addon->id, (array) $addon, ['status' => 'cancelled']);
            return $this->success(null, 'Subscription add-on cancelled.');
        }));
    }

    public function applyCoupon(Request $request, string $uuid)
    {
        if ($hit = $this->billing->idempotencyHit($request, 'subscription.coupon.apply')) return $hit;
        $subscription = $this->billing->byUuid('subscriptions', $uuid);
        $data = $request->validate(['coupon_code' => ['required_without:coupon_uuid', 'string'], 'coupon_uuid' => ['required_without:coupon_code', 'uuid']]);

        return $this->billing->storeIdempotency($request, 'subscription.coupon.apply', DB::transaction(function () use ($request, $subscription, $data) {
            $coupon = DB::table('coupons')->whereNull('deleted_at')->when($data['coupon_uuid'] ?? null, fn($q, $v) => $q->where('uuid', $v))->when($data['coupon_code'] ?? null, fn($q, $v) => $q->where('code', Str::upper($v)))->first();
            abort_if(! $coupon || $coupon->status !== 'active', 422, 'Coupon is not active.');
            $discount = $coupon->discount_type === 'percent' ? ((float) $subscription->payable_amount * (float) $coupon->discount_value / 100) : (float) $coupon->discount_value;
            $id = DB::table('coupon_redemptions')->insertGetId(['coupon_id' => $coupon->id, 'tenant_id' => $subscription->tenant_id, 'subscription_id' => $subscription->id, 'discount_amount' => $discount, 'redeemed_at' => now()]);
            DB::table('subscriptions')->where('id', $subscription->id)->update(['discount_amount' => (float) $subscription->discount_amount + $discount, 'payable_amount' => max(0, (float) $subscription->payable_amount - $discount), 'updated_at' => now()]);
            $redemption = DB::table('coupon_redemptions')->where('id', $id)->first();
            $this->billing->audit($request, 'subscription_coupon_applied', 'subscriptions', $subscription->id, (array) $subscription, (array) $redemption);
            return $this->success(['redemption' => $redemption, 'subscription' => DB::table('subscriptions')->where('id', $subscription->id)->first()], 'Coupon applied.');
        }));
    }

    public function removeCoupon(Request $request, string $uuid, string $coupon_uuid)
    {
        if ($hit = $this->billing->idempotencyHit($request, 'subscription.coupon.remove')) return $hit;
        $subscription = $this->billing->byUuid('subscriptions', $uuid);
        $coupon = $this->billing->byUuid('coupons', $coupon_uuid);
        return $this->billing->storeIdempotency($request, 'subscription.coupon.remove', DB::transaction(function () use ($request, $subscription, $coupon) {
            DB::table('coupon_redemptions')->where('subscription_id', $subscription->id)->where('coupon_id', $coupon->id)->delete();
            $this->billing->audit($request, 'subscription_coupon_removed', 'subscriptions', $subscription->id, null, ['coupon_id' => $coupon->id]);
            return $this->success(null, 'Coupon removed.');
        }));
    }

    public function usage(string $uuid)
    {
        $subscription = $this->billing->byUuid('subscriptions', $uuid);
        return $this->success(['usage' => DB::table('subscription_usage')->join('features', 'features.id', '=', 'subscription_usage.feature_id')->where('subscription_id', $subscription->id)->select('subscription_usage.*', 'features.uuid as feature_uuid', 'features.code as feature_code', 'features.module')->get()]);
    }

    public function history(string $uuid)
    {
        $subscription = $this->billing->byUuid('subscriptions', $uuid);
        return $this->success(['versions' => DB::table('subscription_versions')->where('subscription_id', $subscription->id)->orderByDesc('version')->get(), 'renewals' => DB::table('subscription_renewals')->where('subscription_id', $subscription->id)->latest('id')->get()]);
    }

    public function createInvoice(Request $request, string $uuid)
    {
        return app(PlatformBillingController::class)->createInvoiceFromSubscription($request, $uuid);
    }

    public function export()
    {
        return $this->success(['export' => ['status' => 'queued', 'format' => 'csv']], 'Subscription export queued.');
    }

    private function changePlan(Request $request, string $uuid, string $event, string $operation)
    {
        if ($hit = $this->billing->idempotencyHit($request, $operation)) return $hit;
        $subscription = $this->billing->byUuid('subscriptions', $uuid);
        $data = $request->validate(['new_plan_id' => ['required', 'uuid'], 'effective_at' => ['nullable', 'date'], 'proration' => ['nullable', 'string'], 'billing_cycle' => ['nullable', 'string'], 'reason' => ['nullable', 'string']]);

        return $this->billing->storeIdempotency($request, $operation, DB::transaction(function () use ($request, $subscription, $data, $event) {
            $plan = $this->billing->byUuid('plans', $data['new_plan_id']);
            $version = ((int) $subscription->current_version) + 1;
            DB::table('subscriptions')->where('id', $subscription->id)->update(['plan_id' => $plan->id, 'current_version' => $version, 'billing_cycle' => $data['billing_cycle'] ?? $plan->billing_cycle, 'base_amount' => $plan->base_price, 'taxable_amount' => $plan->base_price, 'payable_amount' => max(0, (float) $plan->base_price + (float) $subscription->addon_amount - (float) $subscription->discount_amount + (float) $subscription->tax_amount), 'updated_by' => $request->user()?->id, 'updated_at' => now()]);
            $this->billing->writeSubscriptionVersion($request, $subscription->id, $plan->id, $version, $event . ': ' . ($data['reason'] ?? 'plan change'), $data['effective_at'] ?? null);
            $fresh = DB::table('subscriptions')->where('id', $subscription->id)->first();
            $this->billing->audit($request, 'subscription_' . $event, 'subscriptions', $subscription->id, (array) $subscription, (array) $fresh, $data['reason'] ?? null);
            return $this->success(['subscription' => $fresh], 'Subscription ' . $event . ' completed.');
        }));
    }

    private function status(Request $request, string $uuid, string $status, string $operation, array $extra)
    {
        if ($hit = $this->billing->idempotencyHit($request, $operation)) return $hit;
        $subscription = $this->billing->byUuid('subscriptions', $uuid);
        return $this->billing->storeIdempotency($request, $operation, DB::transaction(function () use ($request, $subscription, $status, $operation, $extra) {
            DB::table('subscriptions')->where('id', $subscription->id)->update([...$extra, 'status' => $status, 'updated_by' => $request->user()?->id, 'updated_at' => now()]);
            $fresh = DB::table('subscriptions')->where('id', $subscription->id)->first();
            $this->billing->audit($request, str_replace('.', '_', $operation), 'subscriptions', $subscription->id, (array) $subscription, (array) $fresh, $request->input('reason'));
            return $this->success(['subscription' => $fresh], 'Subscription status updated.');
        }));
    }

    private function relations(object $subscription): array
    {
        return [
            'addons' => DB::table('subscription_addons')->where('subscription_id', $subscription->id)->get(),
            'invoices' => DB::table('platform_invoices')->where('subscription_id', $subscription->id)->latest('id')->get(),
            'payments' => DB::table('platform_payments')->where('subscription_id', $subscription->id)->latest('id')->get()->map(fn($p) => tap($p, fn($x) => $x->raw_response = $this->billing->maskRaw($x->raw_response))),
            'redemptions' => DB::table('coupon_redemptions')->where('subscription_id', $subscription->id)->latest('id')->get(),
        ];
    }

    private function subscriptionData(Request $request): array
    {
        return $request->validate([
            'tenant_id' => ['required', 'uuid'],
            'plan_id' => ['required', 'uuid'],
            'type' => ['nullable', 'string', 'max:50'],
            'billing_cycle' => ['nullable', 'string', 'max:50'],
            'status' => ['nullable', Rule::in(['trial', 'active', 'paused', 'expired', 'cancelled', 'suspended', 'pending_payment', 'grace_period'])],
            'renewal_type' => ['nullable', 'string', 'max:50'],
            'starts_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date'],
            'next_billing_at' => ['nullable', 'date'],
            'trial_starts_at' => ['nullable', 'date'],
            'trial_ends_at' => ['nullable', 'date'],
            'base_amount' => ['nullable', 'numeric', 'min:0'],
            'addon_amount' => ['nullable', 'numeric', 'min:0'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'taxable_amount' => ['nullable', 'numeric', 'min:0'],
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
            'payable_amount' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'auto_renew' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
        ]);
    }
}
