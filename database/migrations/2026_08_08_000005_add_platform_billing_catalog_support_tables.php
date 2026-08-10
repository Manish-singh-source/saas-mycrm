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
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_idempotency_keys');
        Schema::dropIfExists('modules');
    }
};
