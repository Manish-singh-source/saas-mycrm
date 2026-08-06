<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id(); $table->uuid('uuid')->unique(); $table->string('name'); $table->string('code', 80)->unique();
            $table->text('description')->nullable(); $table->string('billing_cycle', 50); $table->decimal('base_price', 18, 2)->default(0); $table->char('currency', 3)->default('INR');
            $table->unsignedInteger('trial_days')->default(0); $table->boolean('is_custom')->default(false); $table->boolean('is_public')->default(true); $table->enum('status', ['active','inactive','archived'])->default('active');
            $table->timestamps(); $table->softDeletes();
        });
        Schema::create('features', function (Blueprint $table) {
            $table->id(); $table->uuid('uuid')->unique(); $table->string('module', 100); $table->string('name'); $table->string('code', 100)->unique(); $table->string('data_type', 50); $table->string('unit', 50)->nullable(); $table->text('description')->nullable(); $table->enum('status', ['active','inactive'])->default('active'); $table->timestamps(); $table->index(['module','status']);
        });
        Schema::create('plan_features', function (Blueprint $table) {
            $table->id(); $table->foreignId('plan_id')->constrained('plans')->cascadeOnDelete(); $table->foreignId('feature_id')->constrained('features')->restrictOnDelete(); $table->string('value')->nullable(); $table->json('metadata')->nullable(); $table->timestamps(); $table->unique(['plan_id','feature_id']);
        });
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id(); $table->uuid('uuid')->unique(); $table->string('subscription_number', 80)->unique(); $table->foreignId('tenant_id')->constrained('tenants')->restrictOnDelete(); $table->foreignId('plan_id')->constrained('plans')->restrictOnDelete();
            $table->unsignedInteger('current_version')->default(1); $table->string('type', 50)->default('standard'); $table->string('billing_cycle', 50); $table->enum('status', ['trial','active','paused','expired','cancelled','suspended','pending_payment','grace_period'])->default('trial'); $table->string('renewal_type', 50)->default('manual');
            $table->dateTime('starts_at')->nullable(); $table->dateTime('expires_at')->nullable(); $table->dateTime('next_billing_at')->nullable(); $table->dateTime('trial_starts_at')->nullable(); $table->dateTime('trial_ends_at')->nullable(); $table->dateTime('cancelled_at')->nullable(); $table->string('cancellation_reason')->nullable(); $table->dateTime('paused_at')->nullable(); $table->dateTime('resumed_at')->nullable();
            foreach (['base_amount','addon_amount','discount_amount','taxable_amount','tax_amount','payable_amount'] as $column) { $table->decimal($column, 18, 2)->default(0); }
            $table->char('currency', 3)->default('INR'); $table->boolean('auto_renew')->default(false); $table->dateTime('last_renewed_at')->nullable(); $table->unsignedBigInteger('last_platform_invoice_id')->nullable()->index(); $table->unsignedBigInteger('last_platform_payment_id')->nullable()->index(); $table->text('notes')->nullable(); $table->foreignId('created_by')->nullable()->constrained('platform_users')->nullOnDelete(); $table->foreignId('updated_by')->nullable()->constrained('platform_users')->nullOnDelete(); $table->timestamps(); $table->softDeletes(); $table->index(['tenant_id','status','expires_at']);
        });
        Schema::create('subscription_versions', function (Blueprint $table) {
            $table->id(); $table->foreignId('subscription_id')->constrained('subscriptions')->cascadeOnDelete(); $table->unsignedInteger('version'); $table->foreignId('plan_id')->constrained('plans')->restrictOnDelete(); $table->string('billing_cycle', 50); $table->dateTime('starts_at')->nullable(); $table->dateTime('ends_at')->nullable(); $table->json('pricing_snapshot')->nullable(); $table->json('feature_snapshot')->nullable(); $table->string('change_reason')->nullable(); $table->foreignId('created_by')->nullable()->constrained('platform_users')->nullOnDelete(); $table->dateTime('created_at')->nullable(); $table->unique(['subscription_id','version']);
        });
        Schema::create('platform_invoices', function (Blueprint $table) {
            $table->id(); $table->uuid('uuid')->unique(); $table->string('invoice_number', 80)->unique(); $table->foreignId('tenant_id')->constrained('tenants')->restrictOnDelete(); $table->foreignId('subscription_id')->nullable()->constrained('subscriptions')->restrictOnDelete(); $table->date('invoice_date'); $table->date('due_date')->nullable(); foreach (['subtotal','discount_amount','taxable_amount','tax_amount','total_amount','paid_amount','balance_amount'] as $column) { $table->decimal($column, 18, 2)->default(0); } $table->char('currency', 3)->default('INR'); $table->string('status', 50)->default('draft'); $table->foreignId('pdf_file_id')->nullable()->constrained('files')->nullOnDelete(); $table->timestamps(); $table->softDeletes(); $table->index(['tenant_id','status','invoice_date']);
        });
        Schema::create('platform_invoice_items', function (Blueprint $table) {
            $table->id(); $table->foreignId('platform_invoice_id')->constrained('platform_invoices')->restrictOnDelete(); $table->string('item_type', 80); $table->string('description'); $table->decimal('quantity', 18, 2)->default(1); $table->decimal('unit_price', 18, 2)->default(0); $table->decimal('amount', 18, 2)->default(0); $table->json('metadata')->nullable();
        });
        Schema::create('platform_payments', function (Blueprint $table) {
            $table->id(); $table->uuid('uuid')->unique(); $table->string('payment_number', 80)->unique(); $table->foreignId('tenant_id')->constrained('tenants')->restrictOnDelete(); $table->foreignId('platform_invoice_id')->nullable()->constrained('platform_invoices')->restrictOnDelete(); $table->foreignId('subscription_id')->nullable()->constrained('subscriptions')->restrictOnDelete(); $table->string('gateway', 80)->nullable(); $table->string('gateway_payment_id')->nullable(); $table->string('payment_method', 80)->nullable(); $table->decimal('amount', 18, 2); $table->char('currency', 3)->default('INR'); $table->string('payment_status', 50); $table->dateTime('paid_at')->nullable(); $table->string('failure_reason')->nullable(); $table->json('raw_response')->nullable(); $table->timestamps(); $table->index(['tenant_id','payment_status','paid_at']);
        });
        Schema::create('platform_refunds', function (Blueprint $table) {
            $table->id(); $table->uuid('uuid')->unique(); $table->string('refund_number', 80)->unique(); $table->foreignId('tenant_id')->constrained('tenants')->restrictOnDelete(); $table->foreignId('platform_payment_id')->constrained('platform_payments')->restrictOnDelete(); $table->decimal('amount', 18, 2); $table->char('currency', 3)->default('INR'); $table->string('reason')->nullable(); $table->string('status', 50)->default('pending'); $table->dateTime('refunded_at')->nullable(); $table->json('raw_response')->nullable(); $table->timestamps();
        });
        Schema::create('addon_plans', function (Blueprint $table) { $table->id(); $table->uuid('uuid')->unique(); $table->string('name'); $table->string('code', 80)->unique(); $table->string('pricing_type', 50); $table->decimal('price',18,2)->default(0); $table->char('currency',3)->default('INR'); $table->enum('status',['active','inactive'])->default('active'); $table->timestamps(); });
        Schema::create('subscription_addons', function (Blueprint $table) { $table->id(); $table->foreignId('subscription_id')->constrained('subscriptions')->cascadeOnDelete(); $table->foreignId('addon_plan_id')->constrained('addon_plans')->restrictOnDelete(); $table->unsignedInteger('quantity')->default(1); $table->decimal('unit_price',18,2)->default(0); $table->dateTime('starts_at')->nullable(); $table->dateTime('ends_at')->nullable(); $table->string('status',50)->default('active'); $table->timestamps(); });
        Schema::create('subscription_usage', function (Blueprint $table) { $table->id(); $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete(); $table->foreignId('subscription_id')->constrained('subscriptions')->cascadeOnDelete(); $table->foreignId('feature_id')->constrained('features')->restrictOnDelete(); $table->date('period_start'); $table->date('period_end'); $table->decimal('used_value',18,2)->default(0); $table->decimal('limit_value',18,2)->nullable(); $table->unique(['tenant_id','feature_id','period_start','period_end'], 'sub_usage_tenant_feature_period_uq'); });
        Schema::create('coupons', function (Blueprint $table) { $table->id(); $table->uuid('uuid')->unique(); $table->string('code',80)->unique(); $table->string('name'); $table->string('discount_type',50); $table->decimal('discount_value',18,2); $table->dateTime('starts_at')->nullable(); $table->dateTime('expires_at')->nullable(); $table->unsignedInteger('max_redemptions')->nullable(); $table->string('status',50)->default('active'); $table->timestamps(); $table->softDeletes(); });
        Schema::create('coupon_plan_assignments', function (Blueprint $table) { $table->foreignId('coupon_id')->constrained('coupons')->cascadeOnDelete(); $table->foreignId('plan_id')->constrained('plans')->cascadeOnDelete(); $table->primary(['coupon_id','plan_id']); });
        Schema::create('coupon_tenant_assignments', function (Blueprint $table) { $table->foreignId('coupon_id')->constrained('coupons')->cascadeOnDelete(); $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete(); $table->primary(['coupon_id','tenant_id']); });
        Schema::create('coupon_redemptions', function (Blueprint $table) { $table->id(); $table->foreignId('coupon_id')->constrained('coupons')->restrictOnDelete(); $table->foreignId('tenant_id')->constrained('tenants')->restrictOnDelete(); $table->foreignId('subscription_id')->nullable()->constrained('subscriptions')->restrictOnDelete(); $table->foreignId('platform_invoice_id')->nullable()->constrained('platform_invoices')->restrictOnDelete(); $table->decimal('discount_amount',18,2)->default(0); $table->dateTime('redeemed_at')->nullable(); });
        Schema::create('subscription_renewals', function (Blueprint $table) { $table->id(); $table->foreignId('subscription_id')->constrained('subscriptions')->restrictOnDelete(); $table->dateTime('old_expires_at')->nullable(); $table->dateTime('new_expires_at')->nullable(); $table->decimal('amount',18,2)->default(0); $table->string('status',50); $table->dateTime('renewed_at')->nullable(); });
    }

    public function down(): void
    {
        foreach (['subscription_renewals','coupon_redemptions','coupon_tenant_assignments','coupon_plan_assignments','coupons','subscription_usage','subscription_addons','addon_plans','platform_refunds','platform_payments','platform_invoice_items','platform_invoices','subscription_versions','subscriptions','plan_features','features','plans'] as $table) { Schema::dropIfExists($table); }
    }
};