<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
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
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_conversion_history');
    }
};
