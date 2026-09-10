<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
        |--------------------------------------------------------------------------
        | PLATFORM PERMISSIONS
        |--------------------------------------------------------------------------
        |
        | Defines individual actions that can be performed in the platform.
        |
        | Examples:
        | users.view
        | users.create
        | users.update
        | users.delete
        | tenants.view
        | tenants.create
        |
        */

        Schema::create('platform_permissions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->string('module', 100);
            $table->string('name', 150);
            $table->string('display_name', 150)->nullable();
            $table->string('guard_name', 50)->default('platform');

            $table->text('description')->nullable();
            $table->boolean('is_system')->default(false);
            $table->enum('status', ['active', 'inactive'])->default('active');

            $table->timestamps();

            $table->unique(['name', 'guard_name']);
            $table->index(['module', 'guard_name']);
        });


        /*
        |--------------------------------------------------------------------------
        | PLATFORM ROLES
        |--------------------------------------------------------------------------
        |
        | A role is a collection of permissions.
        |
        | Examples:
        | Super Admin
        | Platform Admin
        | Support Manager
        | Finance Manager
        |
        */

        Schema::create('platform_roles', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->string('name', 150);
            $table->string('display_name', 150)->nullable();
            $table->string('guard_name', 50)->default('platform');

            $table->text('description')->nullable();

            $table->boolean('is_system')->default(false);

            $table->enum('status', [
                'active',
                'inactive',
            ])->default('active');

            $table->timestamps();

            $table->unique(['name', 'guard_name']);
            $table->index(['status', 'is_system']);
        });


        /*
        |--------------------------------------------------------------------------
        | PLATFORM DEPARTMENTS
        |--------------------------------------------------------------------------
        |
        | Organizational departments.
        |
        | Supports hierarchical departments using parent_id.
        |
        | Example:
        |
        | Company
        | ├── Finance
        | │   ├── Accounts
        | │   └── Billing
        | └── Sales
        |
        */

        Schema::create('platform_departments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('platform_departments')
                ->nullOnDelete();

            $table->string('name', 150);
            $table->string('code', 80)->unique();

            /*
             * Added as a column first.
             * The FK is added after platform_users exists.
             */
            $table->unsignedBigInteger('platform_manager_user_id')
                ->nullable()
                ->index();

            $table->enum('status', [
                'active',
                'inactive',
            ])->default('active');

            $table->timestamps();

            $table->index(['parent_id', 'status']);
        });


        /*
        |--------------------------------------------------------------------------
        | PLATFORM DESIGNATIONS
        |--------------------------------------------------------------------------
        |
        | Job titles / positions.
        |
        | Designations are intentionally independent of departments.
        |
        | Example:
        | Accountant
        | Senior Accountant
        | Manager
        | Director
        |
        */

        Schema::create('platform_designations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->string('name', 150);
            $table->string('code', 80)->unique();

            $table->text('description')->nullable();

            $table->unsignedInteger('level')->nullable();

            $table->enum('status', [
                'active',
                'inactive',
            ])->default('active');

            $table->timestamps();

            $table->index(['status', 'level']);
        });


        /*
        |--------------------------------------------------------------------------
        | PLATFORM USERS
        |--------------------------------------------------------------------------
        |
        | Platform-level users.
        |
        | Organizational relationships:
        |
        | User
        | ├── Department
        | ├── Designation
        | └── Manager
        |
        | Authorization relationships are handled through:
        |
        | platform_model_has_roles
        | platform_model_has_permissions
        |
        */

        Schema::create('platform_users', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->string('employee_code', 50)->unique();

            $table->string('first_name', 100);
            $table->string('last_name', 100)->nullable();
            $table->string('display_name', 200);

            $table->string('email', 150)->unique();
            $table->string('mobile', 20)->nullable();

            $table->string('password');

            $table->text('profile_photo')->nullable();

            /*
             * Organizational information
             */

            $table->foreignId('designation_id')
                ->nullable()
                ->constrained('platform_designations')
                ->nullOnDelete();

            $table->foreignId('department_id')
                ->nullable()
                ->constrained('platform_departments')
                ->nullOnDelete();

            /*
             * Reporting hierarchy.
             *
             * Example:
             *
             * Director
             *    ↓
             * Manager
             *    ↓
             * Employee
             */

            $table->foreignId('manager_id')
                ->nullable()
                ->constrained('platform_users')
                ->nullOnDelete();

            /*
             * Localization
             */

            $table->string('timezone', 100)->default('UTC');
            $table->string('locale', 20)->default('en');

            /*
             * Authentication
             */

            $table->dateTime('email_verified_at')->nullable();

            $table->boolean('two_factor_enabled')->default(false);
            $table->boolean('two_factor_required')->default(false);

            $table->text('two_factor_secret')->nullable();
            $table->text('two_factor_recovery_codes')->nullable();
            $table->dateTime('two_factor_confirmed_at')->nullable();
 
            $table->dateTime('last_login_at')->nullable();
            $table->string('last_login_ip', 45)->nullable();

            /*
             * Status
             */

            $table->enum('status', [
                'active',
                'inactive',
                'suspended',
            ])->default('active');

            /*
             * Audit
             */

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('platform_users')
                ->nullOnDelete();

            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('platform_users')
                ->nullOnDelete();

            $table->rememberToken();

            $table->timestamps();
            $table->softDeletes();

            /*
             * Indexes
             */

            $table->index([
                'status',
                'department_id',
                'designation_id',
            ]);

            $table->index([
                'manager_id',
                'status',
            ]);
        });


        /*
        |--------------------------------------------------------------------------
        | DEPARTMENT MANAGER FOREIGN KEY
        |--------------------------------------------------------------------------
        |
        | platform_departments was created before platform_users.
        | Therefore this FK is added after platform_users exists.
        |
        */

        Schema::table('platform_departments', function (Blueprint $table) {
            $table->foreign('platform_manager_user_id')
                ->references('id')
                ->on('platform_users')
                ->nullOnDelete();
        });


        /*
        |--------------------------------------------------------------------------
        | ROLE HAS PERMISSIONS
        |--------------------------------------------------------------------------
        |
        | Role M:N Permission
        |
        | Example:
        |
        | Accountant
        |    ├── invoices.view
        |    ├── invoices.create
        |    └── invoices.update
        |
        */

        Schema::create('platform_role_has_permissions', function (Blueprint $table) {
            $table->foreignId('role_id')
                ->constrained('platform_roles')
                ->cascadeOnDelete();

            $table->foreignId('permission_id')
                ->constrained('platform_permissions')
                ->cascadeOnDelete();

            $table->primary([
                'role_id',
                'permission_id',
            ]);
        });


        /*
        |--------------------------------------------------------------------------
        | MODEL HAS ROLES
        |--------------------------------------------------------------------------
        |
        | Polymorphic relationship.
        |
        | Normally platform_users receive roles, but keeping this
        | polymorphic allows future models to receive platform roles.
        |
        */

        Schema::create('platform_model_has_roles', function (Blueprint $table) {
            $table->foreignId('role_id')
                ->constrained('platform_roles')
                ->cascadeOnDelete();

            $table->unsignedBigInteger('model_id');
            $table->string('model_type', 120);

            $table->primary([
                'role_id',
                'model_id',
                'model_type',
            ]);

            $table->index([
                'model_id',
                'model_type',
            ]);
        });


        /*
        |--------------------------------------------------------------------------
        | MODEL HAS DIRECT PERMISSIONS
        |--------------------------------------------------------------------------
        |
        | Allows a user/model to receive a permission directly without
        | putting that permission inside a role.
        |
        | Effective permissions:
        |
        | Role permissions
        | +
        | Direct permissions
        |
        */

        Schema::create('platform_model_has_permissions', function (Blueprint $table) {
            $table->foreignId('permission_id')
                ->constrained('platform_permissions')
                ->cascadeOnDelete();

            $table->unsignedBigInteger('model_id');
            $table->string('model_type', 120);

            $table->primary(
                [
                    'permission_id',
                    'model_id',
                    'model_type',
                ],
                'plat_model_perm_pk'
            );

            $table->index([
                'model_id',
                'model_type',
            ]);
        });


        /*
        |--------------------------------------------------------------------------
        | PLATFORM TEAMS
        |--------------------------------------------------------------------------
        |
        | Teams are collaborative groups.
        |
        | A team belongs to a department.
        |
        | A team can have a lead user.
        |
        */

        Schema::create('platform_teams', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
 
            $table->foreignId('platform_department_id')
                ->nullable()
                ->constrained('platform_departments')
                ->nullOnDelete();

            $table->string('name', 150);
            $table->string('code', 80)->unique();

            $table->text('description')->nullable();

            /*
             * Added as a column first.
             * FK is added after the table exists.
             */

            $table->unsignedBigInteger('lead_platform_user_id')
                ->nullable()
                ->index();
            $table->foreignId('assistant_lead_platform_user_id')->nullable()->constrained('platform_users')->nullOnDelete();

            $table->string('email', 150)->nullable();
            $table->string('phone', 40)->nullable();
            $table->string('color', 30)->nullable();
            $table->string('icon', 80)->nullable();
            $table->string('visibility', 40)->default('internal');

            $table->enum('status', [
                'active',
                'inactive',
            ])->default('active');


            $table->timestamps();
            $table->softDeletes();

            $table->index([
                'platform_department_id',
                'status',
            ]);
        });


        /*
        |--------------------------------------------------------------------------
        | TEAM LEAD FOREIGN KEY
        |--------------------------------------------------------------------------
        */

        Schema::table('platform_teams', function (Blueprint $table) {
            $table->foreign('lead_platform_user_id')
                ->references('id')
                ->on('platform_users')
                ->nullOnDelete();
        });


        /*
        |--------------------------------------------------------------------------
        | PLATFORM TEAM ROLES
        |--------------------------------------------------------------------------
        |
        | IMPORTANT:
        |
        | These are NOT application authorization roles.
        |
        | They describe a user's position inside a team.
        |
        | Examples:
        |
        | Team Lead
        | Member
        | Reviewer
        | Coordinator
        |
        | Application permissions continue to come from:
        |
        | platform_roles
        | platform_permissions
        |
        */

        Schema::create('platform_team_roles', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->string('name', 150);
            $table->string('code', 80)->unique();

            $table->text('description')->nullable();

            $table->integer('sort_order')->default(0);
            $table->boolean('is_system')->default(false);

            $table->enum('status', [
                'active',
                'inactive',
            ])->default('active');

            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
        });


        /*
        |--------------------------------------------------------------------------
        | PLATFORM TEAM MEMBERS
        |--------------------------------------------------------------------------
        |
        | User M:N Team
        |
        | Example:
        |
        | User A
        |   ├── Sales Team
        |   └── CRM Team
        |
        | The team_role_id describes the user's position within that
        | particular team.
        |
        */

        Schema::create('platform_team_members', function (Blueprint $table) {
            $table->id();

            $table->foreignId('platform_team_id')
                ->constrained('platform_teams')
                ->cascadeOnDelete();

            $table->foreignId('platform_user_id')
                ->constrained('platform_users')
                ->cascadeOnDelete();

            $table->foreignId('platform_team_role_id')
                ->nullable()
                ->constrained('platform_team_roles')
                ->nullOnDelete();

            $table->date('joined_at')->nullable();
            $table->date('left_at')->nullable();

            $table->enum('status', [
                'active',
                'inactive',
            ])->default('active');

            $table->timestamps();

            $table->unique(
                [
                    'platform_team_id',
                    'platform_user_id',
                ],
                'plat_team_member_unique'
            );

            $table->index([
                'platform_user_id',
                'status',
            ]);

            $table->index([
                'platform_team_id',
                'status',
            ]);
        });


        /*
        |--------------------------------------------------------------------------
        | PLATFORM TEAM ASSIGNMENTS
        |--------------------------------------------------------------------------
        |
        | Allows a team to be assigned to different business entities.
        |
        | Examples:
        |
        | Team → Project
        | Team → Customer
        | Team → Lead
        | Team → Ticket
        | Team → Campaign
        |
        | assignable_type + assignable_id is polymorphic.
        |
        */

        Schema::create('platform_team_assignments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('platform_team_id')
                ->constrained('platform_teams')
                ->cascadeOnDelete();

            $table->string('assignable_type', 120);
            $table->unsignedBigInteger('assignable_id');

            $table->string('assignment_role', 80)->nullable();

            $table->foreignId('assigned_by')
                ->nullable()
                ->constrained('platform_users')
                ->nullOnDelete();

            $table->dateTime('assigned_at')->nullable();
            $table->dateTime('released_at')->nullable();

            $table->enum('status', [
                'active',
                'released',
            ])->default('active');

            $table->timestamps();

            /*
             * Polymorphic lookup
             */

            $table->index(
                [
                    'assignable_type',
                    'assignable_id',
                ],
                'plat_team_assignable_idx'
            );

            /*
             * Useful for checking active assignments.
             */

            $table->index([
                'platform_team_id',
                'status',
            ]);
        });


        Schema::create('platform_api_tokens', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('token_hash', 128)->unique();
            $table->text('encrypted_token_preview')->nullable();
            $table->json('abilities')->nullable();
            $table->dateTime('last_used_at')->nullable();
            $table->dateTime('expires_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('platform_users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['expires_at']);
        });
    }


    public function down(): void
    {
        /*
        |--------------------------------------------------------------------------
        | DROP TABLES IN REVERSE DEPENDENCY ORDER
        |--------------------------------------------------------------------------
        */
        Schema::dropIfExists('platform_api_tokens');

        Schema::dropIfExists('platform_team_assignments');

        Schema::dropIfExists('platform_team_members');

        /*
         * Drop team table after team members/assignments.
         */

        Schema::dropIfExists('platform_team_roles');

        /*
         * Remove team lead FK before dropping users.
         */

        Schema::table('platform_teams', function (Blueprint $table) {
            $table->dropForeign([
                'lead_platform_user_id',
            ]);
        });

        Schema::dropIfExists('platform_teams');

        /*
         * RBAC polymorphic tables.
         */

        Schema::dropIfExists('platform_model_has_permissions');

        Schema::dropIfExists('platform_model_has_roles');

        Schema::dropIfExists('platform_role_has_permissions');

        /*
         * Department manager FK references users.
         */

        Schema::table('platform_departments', function (Blueprint $table) {
            $table->dropForeign([
                'platform_manager_user_id',
            ]);
        });

        /*
         * Users.
         */

        Schema::dropIfExists('platform_users');

        /*
         * Organizational tables.
         */

        Schema::dropIfExists('platform_designations');

        Schema::dropIfExists('platform_departments');

        /*
         * RBAC base tables.
         */

        Schema::dropIfExists('platform_permissions');

        Schema::dropIfExists('platform_roles');
    }
};
