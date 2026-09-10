<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {

        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('subscription_number', 80)->unique();
            $table->foreignId('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->foreignId('plan_id')->constrained('plans')->restrictOnDelete();
            $table->unsignedInteger('current_version')->default(1);
            $table->string('type', 50)->default('standard');
            $table->string('billing_cycle', 50);
            $table->enum('status', ['trial', 'active', 'paused', 'expired', 'cancelled', 'suspended', 'pending_payment', 'grace_period'])->default('trial');
            $table->string('renewal_type', 50)->default('manual');
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('expires_at')->nullable();
            $table->dateTime('next_billing_at')->nullable();
            $table->dateTime('trial_starts_at')->nullable();
            $table->dateTime('trial_ends_at')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->string('cancellation_reason')->nullable();
            $table->dateTime('paused_at')->nullable();
            $table->dateTime('resumed_at')->nullable();
            foreach (['base_amount', 'addon_amount', 'discount_amount', 'taxable_amount', 'tax_amount', 'payable_amount'] as $column) {
                $table->decimal($column, 18, 2)->default(0);
            }
            $table->char('currency', 3)->default('INR');
            $table->boolean('auto_renew')->default(false);
            $table->dateTime('last_renewed_at')->nullable();
            $table->unsignedBigInteger('last_platform_invoice_id')->nullable()->index();
            $table->unsignedBigInteger('last_platform_payment_id')->nullable()->index();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('platform_users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('platform_users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['tenant_id', 'status', 'expires_at']);
        });
        Schema::create('subscription_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained('subscriptions')->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->foreignId('plan_id')->constrained('plans')->restrictOnDelete();
            $table->string('billing_cycle', 50);
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('ends_at')->nullable();
            $table->json('pricing_snapshot')->nullable();
            $table->json('feature_snapshot')->nullable();
            $table->string('change_reason')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('platform_users')->nullOnDelete();
            $table->dateTime('created_at')->nullable();
            $table->unique(['subscription_id', 'version']);
        });


        Schema::create('subscription_addons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained('subscriptions')->cascadeOnDelete();
            $table->foreignId('addon_plan_id')->constrained('addon_plans')->restrictOnDelete();
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('unit_price', 18, 2)->default(0);
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('ends_at')->nullable();
            $table->string('status', 50)->default('active');
            $table->timestamps();
        });
        Schema::create('subscription_usage', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('subscription_id')->constrained('subscriptions')->cascadeOnDelete();
            $table->foreignId('feature_id')->constrained('features')->restrictOnDelete();
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('used_value', 18, 2)->default(0);
            $table->decimal('limit_value', 18, 2)->nullable();
            $table->unique(['tenant_id', 'feature_id', 'period_start', 'period_end'], 'sub_usage_tenant_feature_period_uq');
        });

        Schema::create('subscription_renewals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained('subscriptions')->restrictOnDelete();
            $table->dateTime('old_expires_at')->nullable();
            $table->dateTime('new_expires_at')->nullable();
            $table->decimal('amount', 18, 2)->default(0);
            $table->string('status', 50);
            $table->dateTime('renewed_at')->nullable();
        });


        Schema::create('platform_invoices', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('invoice_number', 80)->unique();
            $table->foreignId('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->foreignId('subscription_id')->nullable()->constrained('subscriptions')->restrictOnDelete();
            $table->date('invoice_date');
            $table->date('due_date')->nullable();
            foreach (['subtotal', 'discount_amount', 'taxable_amount', 'tax_amount', 'total_amount', 'paid_amount', 'balance_amount'] as $column) {
                $table->decimal($column, 18, 2)->default(0);
            }
            $table->char('currency', 3)->default('INR');
            $table->string('status', 50)->default('draft');
            $table->foreignId('pdf_file_id')->nullable()->constrained('files')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['tenant_id', 'status', 'invoice_date']);
        });
        Schema::create('platform_invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('platform_invoice_id')->constrained('platform_invoices')->restrictOnDelete();
            $table->string('item_type', 80);
            $table->string('description');
            $table->decimal('quantity', 18, 2)->default(1);
            $table->decimal('unit_price', 18, 2)->default(0);
            $table->decimal('amount', 18, 2)->default(0);
            $table->json('metadata')->nullable();
        });
        Schema::create('platform_payments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('payment_number', 80)->unique();
            $table->foreignId('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->foreignId('platform_invoice_id')->nullable()->constrained('platform_invoices')->restrictOnDelete();
            $table->foreignId('subscription_id')->nullable()->constrained('subscriptions')->restrictOnDelete();
            $table->string('gateway', 80)->nullable();
            $table->string('gateway_payment_id')->nullable();
            $table->string('payment_method', 80)->nullable();
            $table->decimal('amount', 18, 2);
            $table->char('currency', 3)->default('INR');
            $table->string('payment_status', 50);
            $table->dateTime('paid_at')->nullable();
            $table->string('failure_reason')->nullable();
            $table->json('raw_response')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'payment_status', 'paid_at']);
        });
        Schema::create('platform_refunds', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('refund_number', 80)->unique();
            $table->foreignId('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->foreignId('platform_payment_id')->constrained('platform_payments')->restrictOnDelete();
            $table->decimal('amount', 18, 2);
            $table->char('currency', 3)->default('INR');
            $table->string('reason')->nullable();
            $table->string('status', 50)->default('pending');
            $table->dateTime('refunded_at')->nullable();
            $table->json('raw_response')->nullable();
            $table->timestamps();
        });


        Schema::create('coupon_tenant_assignments', function (Blueprint $table) {
            $table->foreignId('coupon_id')->constrained('coupons')->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->primary(['coupon_id', 'tenant_id']);
        });
        Schema::create('coupon_redemptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coupon_id')->constrained('coupons')->restrictOnDelete();
            $table->foreignId('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->foreignId('subscription_id')->nullable()->constrained('subscriptions')->restrictOnDelete();
            $table->foreignId('platform_invoice_id')->nullable()->constrained('platform_invoices')->restrictOnDelete();
            $table->decimal('discount_amount', 18, 2)->default(0);
            $table->dateTime('redeemed_at')->nullable();
        });

        Schema::create('tenant_usage_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->date('period_start');
            $table->date('period_end');
            $table->unsignedInteger('users_count')->default(0);
            $table->unsignedBigInteger('storage_bytes')->default(0);
            $table->unsignedBigInteger('api_requests')->default(0);
            $table->unsignedInteger('projects_count')->default(0);
            $table->unsignedInteger('invoices_count')->default(0);
            $table->unique(['tenant_id', 'period_start', 'period_end'], 'tenant_usage_period_uq');
        });

        Schema::create('api_request_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('method', 10);
            $table->string('path');
            $table->unsignedSmallInteger('status_code')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->dateTime('created_at')->nullable();
            $table->index(['tenant_id', 'path', 'created_at']);
        });

        Schema::create('security_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event', 120);
            $table->string('severity', 50);
            $table->string('ip_address', 45)->nullable();
            $table->json('metadata')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->index(['tenant_id', 'event', 'created_at']);
        });

        Schema::create('tenant_integrations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('provider_id')->constrained('integration_providers')->restrictOnDelete();
            $table->string('name');
            $table->string('status', 50)->default('active');
            $table->foreignId('connected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('connected_at')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'provider_id', 'status']);
        });
        Schema::create('integration_credentials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_integration_id')->constrained('tenant_integrations')->cascadeOnDelete();
            $table->string('key', 120);
            $table->text('encrypted_value');
            $table->dateTime('expires_at')->nullable();
            $table->unique(['tenant_integration_id', 'key']);
        });
        Schema::create('integration_webhooks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_integration_id')->constrained('tenant_integrations')->cascadeOnDelete();
            $table->string('event', 120);
            $table->string('secret_hash')->nullable();
            $table->string('status', 50)->default('active');
            $table->unique(['tenant_integration_id', 'event']);
        });
        Schema::create('integration_webhook_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('webhook_id')->constrained('integration_webhooks')->cascadeOnDelete();
            $table->string('event', 120);
            $table->json('payload')->nullable();
            $table->string('status', 50);
            $table->unsignedSmallInteger('response_code')->nullable();
            $table->unsignedInteger('retry_count')->default(0);
            $table->dateTime('queued_at')->nullable();
            $table->string('last_retry_idempotency_key', 120)->nullable();
            $table->dateTime('received_at')->nullable();
       
        });
        Schema::create('integration_sync_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_integration_id')->constrained('tenant_integrations')->cascadeOnDelete();
            $table->string('sync_type', 80);
            $table->string('direction', 50);
            $table->string('status', 50);
            $table->dateTime('started_at')->nullable();
            $table->dateTime('finished_at')->nullable();
        });
        Schema::create('integration_field_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_integration_id')->constrained('tenant_integrations')->cascadeOnDelete();
            $table->string('entity_type', 100);
            $table->string('local_field');
            $table->string('external_field');
            $table->json('transform_rule')->nullable();
        });
        Schema::create('integration_rate_limits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_integration_id')->constrained('tenant_integrations')->cascadeOnDelete();
            $table->dateTime('window_start');
            $table->dateTime('window_end');
            $table->unsignedInteger('limit_count');
            $table->unsignedInteger('used_count')->default(0);
        });

        Schema::create('notification_templates', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->cascadeOnDelete();
            $table->string('code', 120);
            $table->string('channel', 50);
            $table->string('subject')->nullable();
            $table->longText('body');
            $table->json('variables')->nullable();
            $table->string('status', 50)->default('active');
            $table->timestamps();
            $table->unique(['tenant_id', 'code', 'channel']);
        });

        Schema::create('tenant_backup_runs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('backup_type', 80);
            $table->string('status', 50);
            $table->foreignId('file_id')->nullable()->constrained('files')->nullOnDelete();
            $table->dateTime('started_at')->nullable();
            $table->dateTime('finished_at')->nullable();
            $table->longText('error_message')->nullable();
        });
        Schema::create('tenant_restore_requests', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->foreignId('tenant_backup_run_id')->nullable()->constrained('tenant_backup_runs')->restrictOnDelete();
            $table->string('status', 50)->default('pending');
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('platform_users')->nullOnDelete();
            $table->dateTime('requested_at')->nullable();
            $table->dateTime('approved_at')->nullable();
            $table->dateTime('restored_at')->nullable();
            $table->text('remarks')->nullable();
        });

        Schema::create('platform_tickets', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('ticket_number', 80)->unique();
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->nullOnDelete();
            $table->string('subject');
            $table->text('description')->nullable();
            $table->string('priority', 50)->default('medium');
            $table->string('status', 50)->default('open');
            $table->foreignId('assigned_to')->nullable()->constrained('platform_users')->nullOnDelete();
            $table->string('category', 100)->nullable();
            $table->string('source', 80)->nullable();
            $table->dateTime('opened_at')->nullable();
            $table->dateTime('closed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['tenant_id', 'status', 'priority']);

        
        });
        Schema::create('platform_ticket_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('platform_ticket_id')->constrained('platform_tickets')->cascadeOnDelete();
            $table->foreignId('platform_user_id')->nullable()->constrained('platform_users')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->longText('comment');
            $table->boolean('is_internal')->default(false);
            $table->timestamps();
        });

        Schema::create('remote_login_sessions', function (Blueprint $table): void {
            $table->id(); 
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('platform_user_id')->constrained('platform_users')->cascadeOnDelete();
            $table->foreignId('target_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reason');
            $table->unsignedInteger('duration_minutes')->nullable();
            $table->dateTime('expires_at')->nullable();
            $table->dateTime('ended_at')->nullable();
            $table->string('status', 50)->default('active');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();        
            $table->index(['tenant_id', 'status', 'expires_at']);
        });

        if (! Schema::hasTable('platform_ticket_attachments')) {
            Schema::create('platform_ticket_attachments', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('platform_ticket_id')->constrained('platform_tickets')->cascadeOnDelete();
                $table->foreignId('file_id')->constrained('files')->cascadeOnDelete();
                $table->foreignId('created_by')->nullable()->constrained('platform_users')->nullOnDelete();
                $table->timestamps();
                $table->unique(['platform_ticket_id', 'file_id']);
            });
        }

        if (! Schema::hasTable('tenant_import_export_jobs')) {
            Schema::create('tenant_import_export_jobs', function (Blueprint $table): void {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('type', 30);
                $table->string('module', 80);
                $table->string('status', 50)->default('queued');
                $table->json('payload')->nullable();
                $table->json('result')->nullable();
                $table->text('error_message')->nullable();
                $table->dateTime('started_at')->nullable();
                $table->dateTime('finished_at')->nullable();
                $table->timestamps();
                $table->index(['tenant_id', 'module', 'type', 'status']);
            });
        }

        if (! Schema::hasTable('tenant_onboarding_steps')) {
            Schema::create('tenant_onboarding_steps', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->string('step_code', 120);
                $table->string('status', 50)->default('pending');
                $table->json('metadata')->nullable();
                $table->foreignId('updated_by')->nullable()->constrained('platform_users')->nullOnDelete();
                $table->timestamps();
                $table->unique(['tenant_id', 'step_code']);
            });
        }


        if (! Schema::hasTable('tenant_legal_acceptances')) {
            Schema::create('tenant_legal_acceptances', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->foreignId('legal_document_id')->constrained('legal_documents')->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->dateTime('accepted_at')->nullable();
                $table->string('ip_address', 45)->nullable();
            });
        }

        if (! Schema::hasTable('platform_webhook_endpoints')) {
            Schema::create('platform_webhook_endpoints', function (Blueprint $table): void {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('tenant_id')->nullable()->constrained('tenants')->cascadeOnDelete();
                $table->string('name');
                $table->string('url');
                $table->json('events')->nullable();
                $table->string('secret_hash')->nullable();
                $table->string('status', 50)->default('active');
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('platform_webhook_deliveries')) {
            Schema::create('platform_webhook_deliveries', function (Blueprint $table): void {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('platform_webhook_endpoint_id')->constrained('platform_webhook_endpoints')->cascadeOnDelete();
                $table->string('event', 120);
                $table->json('payload')->nullable();
                $table->string('status', 50)->default('pending');
                $table->unsignedSmallInteger('response_code')->nullable();
                $table->unsignedInteger('retry_count')->default(0);
                $table->dateTime('queued_at')->nullable();
                $table->timestamps();
            });
        }
    }


    public function down(): void {}
};
