<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('remote_login_sessions', function (Blueprint $table): void {
            if (! Schema::hasColumn('remote_login_sessions', 'target_user_id')) $table->unsignedBigInteger('target_user_id')->nullable()->after('platform_user_id');
            if (! Schema::hasColumn('remote_login_sessions', 'duration_minutes')) $table->unsignedInteger('duration_minutes')->nullable()->after('reason');
            if (! Schema::hasColumn('remote_login_sessions', 'expires_at')) $table->dateTime('expires_at')->nullable()->after('duration_minutes');
            if (! Schema::hasColumn('remote_login_sessions', 'user_agent')) $table->text('user_agent')->nullable()->after('ip_address');
        });

        Schema::table('monitoring_alerts', function (Blueprint $table): void {
            if (! Schema::hasColumn('monitoring_alerts', 'resolution_notes')) $table->text('resolution_notes')->nullable();
            if (! Schema::hasColumn('monitoring_alerts', 'resolved_by')) $table->unsignedBigInteger('resolved_by')->nullable()->index();
        });

        Schema::table('system_incidents', function (Blueprint $table): void {
            if (! Schema::hasColumn('system_incidents', 'resolution_notes')) $table->text('resolution_notes')->nullable();
            if (! Schema::hasColumn('system_incidents', 'resolved_by')) $table->unsignedBigInteger('resolved_by')->nullable()->index();
        });

        Schema::table('integration_webhook_logs', function (Blueprint $table): void {
            if (! Schema::hasColumn('integration_webhook_logs', 'retry_count')) $table->unsignedInteger('retry_count')->default(0);
            if (! Schema::hasColumn('integration_webhook_logs', 'queued_at')) $table->dateTime('queued_at')->nullable();
            if (! Schema::hasColumn('integration_webhook_logs', 'last_retry_idempotency_key')) $table->string('last_retry_idempotency_key', 120)->nullable();
        });

        Schema::table('platform_tickets', function (Blueprint $table): void {
            if (! Schema::hasColumn('platform_tickets', 'category')) $table->string('category', 100)->nullable();
            if (! Schema::hasColumn('platform_tickets', 'source')) $table->string('source', 80)->nullable();
            if (! Schema::hasColumn('platform_tickets', 'opened_at')) $table->dateTime('opened_at')->nullable();
            if (! Schema::hasColumn('platform_tickets', 'closed_at')) $table->dateTime('closed_at')->nullable();
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

        if (! Schema::hasTable('report_export_jobs')) {
            Schema::create('report_export_jobs', function (Blueprint $table): void {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->string('report_code', 120);
                $table->string('format', 20)->default('csv');
                $table->json('filters')->nullable();
                $table->string('status', 50)->default('queued');
                $table->foreignId('file_id')->nullable()->constrained('files')->nullOnDelete();
                $table->foreignId('created_by')->nullable()->constrained('platform_users')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('backup_settings')) {
            Schema::create('backup_settings', function (Blueprint $table): void {
                $table->id();
                $table->string('key', 150)->unique();
                $table->json('value')->nullable();
                $table->foreignId('updated_by')->nullable()->constrained('platform_users')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('onboarding_checklists')) {
            Schema::create('onboarding_checklists', function (Blueprint $table): void {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->string('step_code', 120)->unique();
                $table->string('title');
                $table->text('description')->nullable();
                $table->integer('sort_order')->default(0);
                $table->string('status', 50)->default('active');
                $table->timestamps();
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

        if (! Schema::hasTable('legal_documents')) {
            Schema::create('legal_documents', function (Blueprint $table): void {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->string('document_type', 80);
                $table->string('title');
                $table->string('version', 40);
                $table->longText('content');
                $table->string('status', 50)->default('draft');
                $table->dateTime('published_at')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('platform_users')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();
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

        if (! Schema::hasTable('platform_announcements')) {
            Schema::create('platform_announcements', function (Blueprint $table): void {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->string('title');
                $table->longText('body');
                $table->string('audience', 80)->default('all');
                $table->string('status', 50)->default('draft');
                $table->dateTime('published_at')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('platform_users')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();
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

    public function down(): void
    {
        foreach (['platform_webhook_deliveries', 'platform_webhook_endpoints', 'platform_announcements', 'tenant_legal_acceptances', 'legal_documents', 'tenant_onboarding_steps', 'onboarding_checklists', 'backup_settings', 'report_export_jobs', 'platform_ticket_attachments'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
