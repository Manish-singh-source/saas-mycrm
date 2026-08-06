<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
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
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['tenant_id', 'email']);
            $table->unique(['tenant_id', 'employee_code']);
            $table->index(['tenant_id', 'account_type', 'status']);
        });

        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('name', 150);
            $table->string('guard_name', 50)->default('tenant');
            $table->string('description')->nullable();
            $table->boolean('is_system')->default(false);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
            $table->unique(['tenant_id', 'name', 'guard_name']);
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('module', 100);
            $table->string('name', 150);
            $table->string('guard_name', 50)->default('tenant');
            $table->string('description')->nullable();
            $table->timestamps();
            $table->unique(['name', 'guard_name']);
            $table->index(['module', 'guard_name']);
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
    }

    public function down(): void
    {
        Schema::dropIfExists('model_has_permissions');
        Schema::dropIfExists('model_has_roles');
        Schema::dropIfExists('role_has_permissions');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('users');
    }
};