<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        
        if (! Schema::hasTable('modules')) {
            Schema::create('modules', function (Blueprint $table): void {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->string('name');
                $table->string('code', 100)->unique();
                $table->text('description')->nullable();
                $table->string('icon', 80)->nullable();
                $table->string('category', 100)->nullable();
                $table->boolean('is_core')->default(false);
                $table->string('status', 50)->default('active');
                $table->integer('sort_order')->default(0);
                $table->timestamps(); 
                $table->index(['category', 'status']);
            });
        }

        if (! Schema::hasTable('platform_idempotency_keys')) {
            Schema::create('platform_idempotency_keys', function (Blueprint $table): void {
                $table->id();
                $table->string('key', 120);
                $table->string('operation', 120);
                $table->foreignId('platform_user_id')->nullable()->constrained('platform_users')->nullOnDelete();
                $table->string('request_hash', 128);
                $table->string('status', 30)->default('completed');
                $table->unsignedSmallInteger('response_status')->default(200);
                $table->json('response_body')->nullable();
                $table->timestamps();
                $table->unique(['key', 'operation', 'platform_user_id'], 'platform_idem_key_operation_user_uq');
            });
        }

        Schema::create('platform_user_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('platform_user_id')->constrained('platform_users')->cascadeOnDelete();
            $table->string('group', 100);
            $table->string('key', 150);
            $table->json('value')->nullable();
            $table->timestamps();
            $table->unique(['platform_user_id', 'group', 'key']);
        });

        Schema::create('platform_settings', function (Blueprint $table) {
            $table->id();
            $table->string('group', 100);
            $table->string('key', 150);
            $table->json('value')->nullable();
            $table->string('value_type', 50)->default('json');
            $table->boolean('is_encrypted')->default(false);
            $table->foreignId('updated_by')->nullable()->constrained('platform_users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['group', 'key']);
        });

        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('code', 80)->unique();
            $table->text('description')->nullable();
            $table->string('billing_cycle', 50);
            $table->decimal('base_price', 18, 2)->default(0);
            $table->char('currency', 3)->default('INR');
            $table->unsignedInteger('trial_days')->default(0);
            $table->boolean('is_custom')->default(false);
            $table->boolean('is_public')->default(true);
            $table->enum('status', ['active', 'inactive', 'archived'])->default('active');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('features', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('module', 100);
            $table->string('name');
            $table->string('code', 100)->unique();
            $table->string('data_type', 50);
            $table->string('unit', 50)->nullable();
            $table->text('description')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
            $table->index(['module', 'status']);
        });

        Schema::create('plan_features', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained('plans')->cascadeOnDelete();
            $table->foreignId('feature_id')->constrained('features')->restrictOnDelete();
            $table->string('value')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(['plan_id', 'feature_id']);
        });

        
        Schema::create('addon_plans', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('code', 80)->unique();
            $table->string('pricing_type', 50);
            $table->decimal('price', 18, 2)->default(0);
            $table->char('currency', 3)->default('INR');
            $table->boolean('is_public')->default(true);
            $table->enum('status', ['active', 'inactive', 'archived'])->default('active');
            $table->timestamps();
        });
        
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('code', 80)->unique();
            $table->string('name');
            $table->string('discount_type', 50);
            $table->decimal('discount_value', 18, 2);
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('expires_at')->nullable();
            $table->unsignedInteger('max_redemptions')->nullable();
            $table->string('status', 50)->default('active');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('coupon_plan_assignments', function (Blueprint $table) {
            $table->foreignId('coupon_id')->constrained('coupons')->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained('plans')->cascadeOnDelete();
            $table->primary(['coupon_id', 'plan_id']);
        });
        
        Schema::create('plan_addons', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('plan_id')->constrained('plans')->cascadeOnDelete();
            $table->foreignId('addon_plan_id')->constrained('addon_plans')->restrictOnDelete();
            $table->timestamps();
            $table->unique(['plan_id', 'addon_plan_id']);
        });

        Schema::create('monitoring_services', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 80)->unique();
            $table->string('service_type', 80);
            $table->string('status', 50)->default('active');
            $table->unsignedInteger('check_interval_seconds')->default(60);
            $table->timestamps();
        });

        Schema::create('monitoring_service_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained('monitoring_services')->cascadeOnDelete();
            $table->string('status', 50);
            $table->unsignedInteger('response_time_ms')->nullable();
            $table->text('message')->nullable();
            $table->dateTime('checked_at')->index();
        });

        Schema::create('queue_job_logs', function (Blueprint $table) {
            $table->id();
            $table->string('queue', 100);
            $table->string('job_name');
            $table->string('status', 50);
            $table->unsignedInteger('attempts')->default(0);
            $table->longText('exception')->nullable();
            $table->dateTime('started_at')->nullable();
            $table->dateTime('finished_at')->nullable();
        });

        Schema::create('scheduler_logs', function (Blueprint $table) {
            $table->id();
            $table->string('command');
            $table->string('status', 50);
            $table->longText('output')->nullable();
            $table->dateTime('started_at')->nullable();
            $table->dateTime('finished_at')->nullable();
        });
        
        Schema::create('system_incidents', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('severity', 50);
            $table->string('status', 50);
            $table->dateTime('started_at')->nullable();
            $table->dateTime('resolved_at')->nullable();
            $table->text('summary')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->unsignedBigInteger('resolved_by')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('monitoring_alerts', function (Blueprint $table) {
            $table->id();
            $table->string('alertable_type', 120);
            $table->unsignedBigInteger('alertable_id');
            $table->string('severity', 50);
            $table->text('message');
            $table->string('status', 50)->default('open');
            $table->dateTime('triggered_at')->nullable();
            $table->dateTime('resolved_at')->nullable();
            $table->index(['alertable_type', 'alertable_id']);
            $table->text('resolution_notes')->nullable();
            $table->unsignedBigInteger('resolved_by')->nullable()->index();
        });

        Schema::create('integration_providers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 80)->unique();
            $table->string('category', 80);
            $table->string('auth_type', 80);
            $table->string('status', 50)->default('active');
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
        
        Schema::create('knowledge_base_categories', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('parent_id')->nullable()->constrained('knowledge_base_categories')->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('audience', 50)->default('all');
            $table->string('status', 50)->default('active');
            $table->timestamps();
        });

        Schema::create('knowledge_base_articles', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('category_id')->nullable()->constrained('knowledge_base_categories')->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->longText('body');
            $table->string('audience', 50)->default('all');
            $table->string('status', 50)->default('draft');
            $table->foreignId('created_by')->nullable()->constrained('platform_users')->nullOnDelete();
            $table->dateTime('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });        
        
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
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_settings');
        Schema::dropIfExists('platform_user_preferences');
        Schema::dropIfExists('platform_idempotency_keys');
        Schema::dropIfExists('modules');
        Schema::dropIfExists('plan_features');
        Schema::dropIfExists('addon_plans');
        Schema::dropIfExists('coupons');
        Schema::dropIfExists('coupon_plan_assignments');
        Schema::dropIfExists('plan_addons');
    }
};
