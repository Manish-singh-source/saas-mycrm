<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BackfillRegistrationBilling extends Command
{
    protected $signature = 'billing:backfill-registration-ledgers {--dry-run : Show what would be created without writing rows}';

    protected $description = 'Create missing initial invoices and payment ledger rows for registered tenant subscriptions.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $subscriptions = DB::table('subscriptions')
            ->join('tenants', 'tenants.id', '=', 'subscriptions.tenant_id')
            ->whereNull('subscriptions.deleted_at')
            ->select('subscriptions.*', 'tenants.default_currency')
            ->orderBy('subscriptions.id')
            ->get();

        $createdInvoices = 0;
        $createdPayments = 0;

        foreach ($subscriptions as $subscription) {
            DB::transaction(function () use ($subscription, $dryRun, &$createdInvoices, &$createdPayments): void {
                $invoice = DB::table('platform_invoices')
                    ->where('subscription_id', $subscription->id)
                    ->whereNull('deleted_at')
                    ->oldest('id')
                    ->first();

                if (! $invoice) {
                    if ($dryRun) {
                        $createdInvoices++;
                    } else {
                        $invoice = $this->createInvoice($subscription);
                        $createdInvoices++;
                    }
                }

                $payment = DB::table('platform_payments')
                    ->where('subscription_id', $subscription->id)
                    ->oldest('id')
                    ->first();

                if (! $payment) {
                    if ($dryRun) {
                        $createdPayments++;
                    } else {
                        $payment = $this->createPayment($subscription, $invoice);
                        $createdPayments++;
                    }
                }

                if (! $dryRun && $invoice && $payment) {
                    if (! $payment->platform_invoice_id) {
                        DB::table('platform_payments')->where('id', $payment->id)->update([
                            'platform_invoice_id' => $invoice->id,
                            'updated_at' => now(),
                        ]);
                    }

                    DB::table('subscriptions')->where('id', $subscription->id)->update([
                        'last_platform_invoice_id' => $invoice->id,
                        'last_platform_payment_id' => $payment->id,
                        'updated_at' => now(),
                    ]);
                }
            });
        }

        $prefix = $dryRun ? 'Would create' : 'Created';
        $this->info("{$prefix} {$createdInvoices} invoices and {$createdPayments} payments.");

        return self::SUCCESS;
    }

    private function createInvoice(object $subscription): object
    {
        $amount = (float) $subscription->payable_amount;
        $invoiceId = DB::table('platform_invoices')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'invoice_number' => 'INV-' . Str::upper(Str::random(10)),
            'tenant_id' => $subscription->tenant_id,
            'subscription_id' => $subscription->id,
            'invoice_date' => now()->toDateString(),
            'due_date' => $amount > 0 ? now()->addDays(7)->toDateString() : now()->toDateString(),
            'subtotal' => $subscription->base_amount,
            'discount_amount' => $subscription->discount_amount ?? 0,
            'taxable_amount' => $subscription->taxable_amount,
            'tax_amount' => $subscription->tax_amount ?? 0,
            'total_amount' => $amount,
            'paid_amount' => 0,
            'balance_amount' => $amount,
            'currency' => $subscription->currency ?? $subscription->default_currency ?? 'INR',
            'status' => $amount <= 0 ? 'paid' : 'sent',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('platform_invoice_items')->insert([
            'platform_invoice_id' => $invoiceId,
            'item_type' => 'subscription',
            'description' => 'Initial subscription charge for ' . $subscription->subscription_number,
            'quantity' => 1,
            'unit_price' => $subscription->base_amount,
            'amount' => $subscription->base_amount,
            'metadata' => json_encode([
                'subscription_uuid' => $subscription->uuid,
                'billing_cycle' => $subscription->billing_cycle,
                'backfilled' => true,
            ]),
        ]);

        return DB::table('platform_invoices')->where('id', $invoiceId)->first();
    }

    private function createPayment(object $subscription, object $invoice): object
    {
        $amount = (float) $subscription->payable_amount;
        $paymentId = DB::table('platform_payments')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'payment_number' => 'PAY-' . Str::upper(Str::random(10)),
            'tenant_id' => $subscription->tenant_id,
            'platform_invoice_id' => $invoice->id,
            'subscription_id' => $subscription->id,
            'gateway' => null,
            'gateway_payment_id' => null,
            'payment_method' => $amount <= 0 ? 'free' : 'pending',
            'amount' => $amount,
            'currency' => $subscription->currency ?? $subscription->default_currency ?? 'INR',
            'payment_status' => $amount <= 0 ? 'paid' : 'pending',
            'paid_at' => $amount <= 0 ? now() : null,
            'raw_response' => json_encode(['source' => 'billing_backfill']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($amount <= 0) {
            DB::table('platform_invoices')->where('id', $invoice->id)->update([
                'paid_amount' => 0,
                'balance_amount' => 0,
                'status' => 'paid',
                'updated_at' => now(),
            ]);
        }

        return DB::table('platform_payments')->where('id', $paymentId)->first();
    }
}
