<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
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
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_settings');
        Schema::dropIfExists('platform_user_preferences');
        Schema::dropIfExists('user_preferences');
        Schema::dropIfExists('tenant_settings');
    }
};