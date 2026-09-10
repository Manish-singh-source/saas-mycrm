<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {

        Schema::create('parties', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('party_type', 50);
            $table->string('display_name', 200);
            $table->string('legal_name', 200)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('website')->nullable();
            $table->string('gst_number', 30)->nullable();
            $table->string('pan_number', 30)->nullable();
            $table->foreignId('industry_id')->nullable()->constrained('industries')->nullOnDelete();
            $table->foreignId('source_id')->nullable()->constrained('tenant_lookups')->nullOnDelete();
            $table->foreignId('status_id')->nullable()->constrained('tenant_lookups')->nullOnDelete();
            $table->foreignId('owner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['tenant_id', 'party_type', 'status_id']);
            $table->index(['tenant_id', 'owner_user_id']);
        });
        Schema::create('party_contacts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('party_id')->constrained('parties')->cascadeOnDelete();
            $table->string('first_name', 100);
            $table->string('last_name', 100)->nullable();
            $table->string('display_name', 200);
            $table->string('email', 150)->nullable();
            $table->string('mobile', 20)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('designation', 100)->nullable();
            $table->string('department', 100)->nullable();
            $table->boolean('is_primary')->default(false);
            $table->boolean('portal_enabled')->default(false);
            $table->string('status', 50)->default('active');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['tenant_id', 'party_id', 'status']);
        });
        Schema::create('party_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('party_id')->constrained('parties')->cascadeOnDelete();
            $table->string('address_type', 50);
            $table->string('address_line_1');
            $table->string('address_line_2')->nullable();
            $table->foreignId('country_id')->nullable()->constrained('countries')->nullOnDelete();
            $table->foreignId('state_id')->nullable()->constrained('states')->nullOnDelete();
            $table->foreignId('city_id')->nullable()->constrained('cities')->nullOnDelete();
            $table->string('postal_code', 20)->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
            $table->index(['tenant_id', 'party_id', 'address_type']);
        });
        Schema::create('client_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('party_id')->constrained('parties')->cascadeOnDelete();
            $table->string('client_code', 80);
            $table->string('client_type', 80)->nullable();
            $table->decimal('credit_limit', 18, 2)->default(0);
            $table->unsignedInteger('payment_terms_days')->default(0);
            $table->date('onboarding_date')->nullable();
            $table->foreignId('account_manager_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['tenant_id', 'party_id']);
            $table->unique(['tenant_id', 'client_code']);
        });
        Schema::create('vendor_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('party_id')->constrained('parties')->cascadeOnDelete();
            $table->string('vendor_code', 80);
            $table->foreignId('vendor_category_id')->nullable()->constrained('tenant_lookups')->nullOnDelete();
            $table->unsignedInteger('payment_terms_days')->default(0);
            $table->decimal('rating', 3, 2)->nullable();
            $table->foreignId('account_manager_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['tenant_id', 'party_id']);
            $table->unique(['tenant_id', 'vendor_code']);
        });
        Schema::create('lead_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('party_id')->constrained('parties')->cascadeOnDelete();
            $table->string('lead_number', 80);
            $table->foreignId('stage_id')->nullable()->constrained('tenant_lookups')->nullOnDelete();
            $table->foreignId('priority_id')->nullable()->constrained('tenant_lookups')->nullOnDelete();
            $table->decimal('expected_value', 18, 2)->default(0);
            $table->unsignedTinyInteger('probability')->default(0);
            $table->date('expected_close_date')->nullable();
            $table->unsignedBigInteger('converted_client_party_id')->nullable()->index();
            $table->dateTime('converted_at')->nullable();
            $table->string('lost_reason')->nullable();
            $table->timestamps();
            $table->unique(['tenant_id', 'party_id']);
            $table->unique(['tenant_id', 'lead_number']);
        });
        Schema::create('lead_activities', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('lead_profile_id')->constrained('lead_profiles')->cascadeOnDelete();
            $table->string('activity_type', 80);
            $table->string('subject');
            $table->text('description')->nullable();
            $table->dateTime('scheduled_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->string('outcome')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['tenant_id', 'assigned_to', 'scheduled_at']);
        });

        if (! Schema::hasTable('lead_conversion_history')) {
            Schema::create('lead_conversion_history', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->foreignId('lead_profile_id')->constrained('lead_profiles')->cascadeOnDelete();
                $table->foreignId('client_party_id')->constrained('parties')->cascadeOnDelete();
                $table->foreignId('converted_by')->nullable()->constrained('users')->nullOnDelete();
                $table->text('conversion_note')->nullable();
                $table->json('metadata')->nullable();
                $table->dateTime('converted_at');
                $table->index(['tenant_id', 'lead_profile_id']);
            });
        }


        Schema::create('renewals', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('renewal_number', 80);
            $table->foreignId('party_id')->nullable()->constrained('parties')->restrictOnDelete();
            $table->string('renewal_type', 80);
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->date('renewal_date');
            $table->decimal('amount', 18, 2)->default(0);
            $table->char('currency', 3)->default('INR');
            $table->unsignedInteger('reminder_days_before')->default(0);
            $table->boolean('auto_renew')->default(false);
            $table->foreignId('status_id')->nullable()->constrained('tenant_lookups')->nullOnDelete();
            $table->foreignId('owner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['tenant_id', 'renewal_number']);
        });
        Schema::create('renewal_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('renewal_id')->constrained('renewals')->cascadeOnDelete();
            $table->string('name');
            $table->decimal('quantity', 18, 2)->default(1);
            $table->decimal('unit_price', 18, 2)->default(0);
            $table->decimal('amount', 18, 2)->default(0);
        });
        Schema::create('renewal_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('renewal_id')->constrained('renewals')->cascadeOnDelete();
            $table->date('old_end_date')->nullable();
            $table->date('new_end_date')->nullable();
            $table->foreignId('status_id')->nullable()->constrained('tenant_lookups')->nullOnDelete();
            $table->string('remarks')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('created_at')->nullable();
        });
        Schema::create('renewal_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('renewal_id')->constrained('renewals')->cascadeOnDelete();
            $table->dateTime('remind_at');
            $table->string('channel', 50);
            $table->dateTime('sent_at')->nullable();
            $table->string('status', 50)->default('pending');
        });

        
        Schema::create('communication_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('party_id')->nullable()->constrained('parties')->nullOnDelete();
            $table->string('channel', 50);
            $table->string('direction', 50);
            $table->string('subject')->nullable();
            $table->longText('body')->nullable();
            $table->string('provider', 80)->nullable();
            $table->string('provider_message_id')->nullable();
            $table->string('status', 50);
            $table->dateTime('sent_at')->nullable();
            $table->dateTime('delivered_at')->nullable();
            $table->text('failed_reason')->nullable();
            $table->json('metadata')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->index(['tenant_id', 'channel', 'status', 'created_at']);
        });
    }


    public function down(): void {}
};
