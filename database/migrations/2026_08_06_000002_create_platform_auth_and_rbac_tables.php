<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_users', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('employee_code', 50)->nullable()->unique();
            $table->string('first_name', 100);
            $table->string('last_name', 100)->nullable();
            $table->string('display_name', 200);
            $table->string('email', 150)->unique();
            $table->string('mobile', 20)->nullable();
            $table->string('password');
            $table->unsignedBigInteger('profile_photo_file_id')->nullable()->index();
            $table->string('designation', 100)->nullable();
            $table->string('department', 100)->nullable();
            $table->string('timezone', 100)->default('UTC');
            $table->string('locale', 20)->default('en');
            $table->dateTime('email_verified_at')->nullable();
            $table->boolean('two_factor_enabled')->default(false);
            $table->dateTime('last_login_at')->nullable();
            $table->string('last_login_ip', 45)->nullable();
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active');
            $table->foreignId('created_by')->nullable()->constrained('platform_users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('platform_users')->nullOnDelete();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['status', 'department']);
        });

        Schema::create('platform_roles', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name', 150);
            $table->string('guard_name', 50)->default('platform');
            $table->string('description')->nullable();
            $table->boolean('is_system')->default(false);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
            $table->unique(['name', 'guard_name']);
        });

        Schema::create('platform_permissions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('module', 100);
            $table->string('name', 150);
            $table->string('guard_name', 50)->default('platform');
            $table->string('description')->nullable();
            $table->timestamps();
            $table->unique(['name', 'guard_name']);
            $table->index(['module', 'guard_name']);
        });

        Schema::create('platform_role_has_permissions', function (Blueprint $table) {
            $table->foreignId('role_id')->constrained('platform_roles')->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained('platform_permissions')->cascadeOnDelete();
            $table->primary(['role_id', 'permission_id']);
        });

        Schema::create('platform_model_has_roles', function (Blueprint $table) {
            $table->foreignId('role_id')->constrained('platform_roles')->cascadeOnDelete();
            $table->unsignedBigInteger('model_id');
            $table->string('model_type', 120);
            $table->primary(['role_id', 'model_id', 'model_type']);
            $table->index(['model_id', 'model_type']);
        });

        Schema::create('platform_model_has_permissions', function (Blueprint $table) {
            $table->foreignId('permission_id')->constrained('platform_permissions')->cascadeOnDelete();
            $table->unsignedBigInteger('model_id');
            $table->string('model_type', 120);
            $table->primary(['permission_id', 'model_id', 'model_type'], 'plat_model_perm_pk');
            $table->index(['model_id', 'model_type']);
        });

        Schema::create('platform_teams', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name', 150);
            $table->string('code', 80)->unique();
            $table->string('description')->nullable();
            $table->foreignId('lead_platform_user_id')->nullable()->constrained('platform_users')->nullOnDelete();
            $table->string('email', 150)->nullable();
            $table->string('color', 30)->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('platform_team_roles', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name', 150);
            $table->string('code', 80)->unique();
            $table->json('permissions')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });

        Schema::create('platform_team_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('platform_team_id')->constrained('platform_teams')->cascadeOnDelete();
            $table->foreignId('platform_user_id')->constrained('platform_users')->cascadeOnDelete();
            $table->foreignId('platform_team_role_id')->nullable()->constrained('platform_team_roles')->nullOnDelete();
            $table->date('joined_at')->nullable();
            $table->date('left_at')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
            $table->unique(['platform_team_id', 'platform_user_id'], 'plat_team_member_unique');
        });

        Schema::create('platform_team_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('platform_team_id')->constrained('platform_teams')->cascadeOnDelete();
            $table->string('assignable_type', 120);
            $table->unsignedBigInteger('assignable_id');
            $table->string('assignment_role', 80)->nullable();
            $table->foreignId('assigned_by')->nullable()->constrained('platform_users')->nullOnDelete();
            $table->dateTime('assigned_at')->nullable();
            $table->dateTime('released_at')->nullable();
            $table->enum('status', ['active', 'released'])->default('active');
            $table->timestamps();
            $table->index(['assignable_type', 'assignable_id'], 'plat_team_assignable_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_team_assignments');
        Schema::dropIfExists('platform_team_members');
        Schema::dropIfExists('platform_team_roles');
        Schema::dropIfExists('platform_teams');
        Schema::dropIfExists('platform_model_has_permissions');
        Schema::dropIfExists('platform_model_has_roles');
        Schema::dropIfExists('platform_role_has_permissions');
        Schema::dropIfExists('platform_permissions');
        Schema::dropIfExists('platform_roles');
        Schema::dropIfExists('platform_users');
    }
};