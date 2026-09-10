<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('organization_name', 200);
            $table->string('legal_name', 200)->nullable();
            $table->string('display_name', 200);
            $table->string('organization_code', 50)->unique();
            $table->string('slug', 150)->unique();
            $table->foreignId('business_type_id')->nullable()->constrained('business_types')->nullOnDelete();
            $table->foreignId('industry_id')->nullable()->constrained('industries')->nullOnDelete();
            $table->enum('company_size', ['self', 'small', 'medium', 'large', 'enterprise'])->nullable();
            $table->string('gst_number', 30)->nullable();
            $table->string('pan_number', 30)->nullable();
            $table->string('registration_number', 80)->nullable();
            $table->string('website')->nullable();
            $table->unsignedBigInteger('logo_file_id')->nullable()->index();
            $table->unsignedBigInteger('favicon_file_id')->nullable()->index();
            $table->char('default_currency', 3)->default('INR');
            $table->string('default_timezone', 100)->default('Asia/Kolkata');
            $table->text('description')->nullable();
            $table->dateTime('onboarded_at')->nullable();
            $table->dateTime('trial_ends_at')->nullable();
            $table->enum('status', ['pending', 'trial', 'active', 'suspended', 'expired', 'cancelled', 'archived'])->default('pending');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['status', 'trial_ends_at']);
            $table->index(['business_type_id', 'industry_id']);
        });

        Schema::create('tenant_offices', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('office_name', 150);
            $table->string('office_code', 50);
            $table->enum('office_type', ['head_office', 'branch', 'regional', 'warehouse', 'factory', 'store', 'remote', 'franchise'])->default('branch');
            $table->boolean('is_head_office')->default(false);
            $table->boolean('is_default')->default(false);
            $table->string('address_line_1')->nullable();
            $table->string('address_line_2')->nullable();
            $table->string('landmark')->nullable();
            $table->foreignId('country_id')->nullable()->constrained('countries')->nullOnDelete();
            $table->foreignId('state_id')->nullable()->constrained('states')->nullOnDelete();
            $table->foreignId('city_id')->nullable()->constrained('cities')->nullOnDelete();
            $table->string('postal_code', 20)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('contact_person', 150)->nullable();
            $table->string('contact_email', 150)->nullable();
            $table->string('contact_phone', 20)->nullable();
            $table->string('timezone', 100)->nullable();
            $table->json('working_hours')->nullable();
            $table->string('gst_number', 30)->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->unsignedBigInteger('updated_by')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['tenant_id', 'office_code']);
            $table->index(['tenant_id', 'office_type']);
            $table->index(['tenant_id', 'status']);
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('module', 100);
            $table->string('name', 150);
            $table->string('display_name', 150)->nullable();
            $table->string('guard_name', 50)->default('tenant');
            $table->string('description')->nullable();
            $table->boolean('is_system')->default(false);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
            $table->unique(['name', 'guard_name']);
            $table->index(['module', 'guard_name']);
        });

        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('name', 150);
            $table->string('display_name', 150)->nullable();
            $table->string('guard_name', 50)->default('tenant');
            $table->string('description')->nullable();
            $table->boolean('is_system')->default(false);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
            $table->unique(['tenant_id', 'name', 'guard_name']);
        });

        
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->unsignedBigInteger('staff_id')->nullable()->index();
            $table->unsignedBigInteger('client_contact_id')->nullable()->index();
            $table->foreignId('default_office_id')->nullable()->constrained('tenant_offices')->nullOnDelete();
            $table->string('employee_code', 50)->nullable();
            $table->string('first_name', 100);
            $table->string('last_name', 100)->nullable();
            $table->string('display_name', 200);
            $table->string('email', 150);
            $table->string('mobile', 20)->nullable();
            $table->string('password');
            $table->unsignedBigInteger('profile_photo_file_id')->nullable()->index();
            $table->string('timezone', 100)->default('UTC');
            $table->string('locale', 20)->default('en');
            $table->dateTime('email_verified_at')->nullable();
            $table->dateTime('mobile_verified_at')->nullable();
            $table->boolean('two_factor_enabled')->default(false);
            $table->enum('account_type', ['owner', 'staff', 'client']);
            $table->dateTime('last_login_at')->nullable();
            $table->string('last_login_ip', 45)->nullable();
            $table->enum('status', ['invited', 'active', 'inactive', 'suspended'])->default('invited');
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->unsignedBigInteger('updated_by')->nullable()->index();
            $table->text('two_factor_secret')->nullable();
            $table->text('two_factor_recovery_codes')->nullable();
            $table->dateTime('two_factor_confirmed_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['tenant_id', 'email']);
            $table->unique(['tenant_id', 'employee_code']);
            $table->index(['tenant_id', 'account_type', 'status']);
        });

        Schema::create('role_has_permissions', function (Blueprint $table) {
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained('permissions')->cascadeOnDelete();
            $table->primary(['role_id', 'permission_id']);
        });

        Schema::create('model_has_roles', function (Blueprint $table) {
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->unsignedBigInteger('model_id');
            $table->string('model_type', 120);
            $table->primary(['tenant_id', 'role_id', 'model_id', 'model_type'], 'tenant_model_roles_pk');
            $table->index(['tenant_id', 'model_id', 'model_type']);
        });

        Schema::create('model_has_permissions', function (Blueprint $table) {
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained('permissions')->cascadeOnDelete();
            $table->unsignedBigInteger('model_id');
            $table->string('model_type', 120);
            $table->primary(['tenant_id', 'permission_id', 'model_id', 'model_type'], 'tenant_model_perms_pk');
            $table->index(['tenant_id', 'model_id', 'model_type']);
        });

        
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->string('name', 150);
            $table->string('code', 80);
            $table->unsignedBigInteger('manager_user_id')->nullable()->index();
            $table->string('status', 50)->default('active');
            $table->timestamps();
            $table->unique(['tenant_id', 'code']);
        });

        Schema::create('designations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->string('name', 150);
            $table->string('code', 80);
            $table->string('status', 50)->default('active');
            $table->timestamps();
            $table->unique(['tenant_id', 'code']);
        });

        
        Schema::create('tenant_api_tokens', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('name');
            $table->string('token_hash', 128)->unique();
            $table->text('encrypted_token_preview')->nullable();
            $table->json('abilities')->nullable();
            $table->dateTime('last_used_at')->nullable();
            $table->dateTime('expires_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['tenant_id', 'expires_at']);
        });
        
        if (! Schema::hasTable('tenant_module_overrides')) {
            Schema::create('tenant_module_overrides', function (Blueprint $table): void {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->string('module_code', 100);
                $table->boolean('enabled')->default(true);
                $table->json('limits')->nullable();
                $table->json('metadata')->nullable();
                $table->foreignId('updated_by')->nullable()->constrained('platform_users')->nullOnDelete();
                $table->timestamps();
                $table->unique(['tenant_id', 'module_code']);
            });
        } 

        
        Schema::create('tenant_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('group', 100);
            $table->string('key', 150);
            $table->json('value')->nullable();
            $table->string('value_type', 50)->default('json');
            $table->boolean('is_encrypted')->default(false);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['tenant_id', 'group', 'key']);
        });

        Schema::create('user_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('group', 100);
            $table->string('key', 150);
            $table->json('value')->nullable();
            $table->timestamps();
            $table->unique(['tenant_id', 'user_id', 'group', 'key']);
        });

        
        Schema::create('files', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('platform_uploaded_by')->nullable()->constrained('platform_users')->nullOnDelete();
            $table->string('disk', 80);
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type', 150)->nullable();
            $table->string('extension', 20)->nullable();
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->string('checksum', 128)->nullable();
            $table->enum('visibility', ['private', 'public', 'tenant'])->default('private');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['tenant_id', 'visibility']);
            $table->index(['tenant_id', 'mime_type']);
        });
        

        Schema::create('attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('file_id')->constrained('files')->cascadeOnDelete();
            $table->string('attachable_type', 120);
            $table->unsignedBigInteger('attachable_id');
            $table->string('label', 150)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('created_at')->nullable();
            $table->index(['tenant_id', 'attachable_type', 'attachable_id']);
        });

        
        Schema::create('document_folders', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('document_folders')->cascadeOnDelete();
            $table->string('name', 150);
            $table->string('slug', 180);
            $table->string('folder_type', 80)->default('general');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['tenant_id', 'parent_id', 'slug']);
        });

        Schema::create('document_folder_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('document_folder_id')->constrained('document_folders')->cascadeOnDelete();
            $table->foreignId('file_id')->constrained('files')->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['document_folder_id', 'file_id']);
        });

        Schema::create('tenant_lookups', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->cascadeOnDelete();
            $table->string('group', 100);
            $table->string('code', 100);
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->string('color', 30)->nullable();
            $table->string('icon', 80)->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_default')->default(false);
            $table->boolean('is_system')->default(false);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(['tenant_id', 'group', 'code']);
            $table->index(['tenant_id', 'group', 'status']);
        });

        
        Schema::create('custom_fields', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('entity_type', 100);
            $table->string('name', 150);
            $table->string('code', 100);
            $table->string('field_type', 50);
            $table->json('options')->nullable();
            $table->json('validation_rules')->nullable();
            $table->boolean('is_required')->default(false);
            $table->integer('sort_order')->default(0);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
            $table->unique(['tenant_id', 'entity_type', 'code']);
        });

        Schema::create('custom_field_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('custom_field_id')->constrained('custom_fields')->cascadeOnDelete();
            $table->string('entity_type', 100);
            $table->unsignedBigInteger('entity_id');
            $table->text('value_text')->nullable();
            $table->decimal('value_number', 18, 2)->nullable();
            $table->date('value_date')->nullable();
            $table->json('value_json')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'entity_type', 'entity_id']);
        });


        Schema::create('notes', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete()->nullable();
            $table->string('notable_type', 120);
            $table->unsignedBigInteger('notable_id');
            $table->text('note');
            $table->enum('visibility', ['private', 'team', 'tenant', 'client'])->default('tenant');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('platform_created_by')->nullable()->constrained('platform_users')->nullOnDelete();
            $table->foreignId('platform_updated_by')->nullable()->constrained('platform_users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['tenant_id', 'notable_type', 'notable_id']);
        });

        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('slug', 150);
            $table->string('color', 30)->nullable();
            $table->string('icon', 80)->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
            $table->unique(['tenant_id', 'slug']);
        });

        Schema::create('taggables', function (Blueprint $table) {
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained('tags')->cascadeOnDelete();
            $table->string('taggable_type', 120);
            $table->unsignedBigInteger('taggable_id');
            $table->dateTime('created_at')->nullable();
            $table->primary(['tenant_id', 'tag_id', 'taggable_type', 'taggable_id']);
            $table->index(['tenant_id', 'taggable_type', 'taggable_id']);
        });

        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('actor_platform_user_id')->nullable()->constrained('platform_users')->nullOnDelete();
            $table->string('subject_type', 120);
            $table->unsignedBigInteger('subject_id');
            $table->string('event', 120); 
            $table->text('description')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('request_id', 120)->nullable();
            $table->dateTime('created_at')->nullable();
            $table->index(['tenant_id', 'subject_type', 'subject_id']);
            $table->index(['tenant_id', 'event', 'created_at']);
        });

        
        
        // Files table needed
        Schema::create('backup_runs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('backup_type', 80);
            $table->string('status', 50);
            $table->foreignId('file_id')->nullable()->constrained('files')->nullOnDelete();
            $table->dateTime('started_at')->nullable();
            $table->dateTime('finished_at')->nullable();
            $table->longText('error_message')->nullable();
        });

        
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

    }


    public function down(): void
    {
        Schema::dropIfExists('tenant_offices');
        Schema::dropIfExists('tenants');
    }
};
