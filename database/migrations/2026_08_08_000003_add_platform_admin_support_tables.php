<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('remote_login_sessions')) {
            Schema::create('remote_login_sessions', function (Blueprint $table): void {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->foreignId('platform_user_id')->constrained('platform_users')->cascadeOnDelete();
                $table->foreignId('target_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->text('reason');
                $table->unsignedInteger('duration_minutes');
                $table->dateTime('expires_at');
                $table->dateTime('ended_at')->nullable();
                $table->string('status', 50)->default('active');
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->timestamps();
                $table->index(['tenant_id', 'status', 'expires_at']);
            });
        }

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
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_module_overrides');
        Schema::dropIfExists('remote_login_sessions');
    }
};
