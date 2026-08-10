<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tenant_import_export_jobs')) {
            Schema::create('tenant_import_export_jobs', function (Blueprint $table): void {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('type', 30);
                $table->string('module', 80);
                $table->string('status', 50)->default('queued');
                $table->json('payload')->nullable();
                $table->json('result')->nullable();
                $table->text('error_message')->nullable();
                $table->dateTime('started_at')->nullable();
                $table->dateTime('finished_at')->nullable();
                $table->timestamps();
                $table->index(['tenant_id', 'module', 'type', 'status']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_import_export_jobs');
    }
};
