<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::create('amc_services', function(Blueprint $t){$t->id();$t->uuid('uuid')->unique();$t->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();$t->foreignId('renewal_id')->unique()->constrained('renewals')->cascadeOnDelete();$t->unsignedInteger('total_visits');$t->date('amc_start_date');$t->date('amc_end_date');$t->timestamps();});
  Schema::create('amc_visits', function(Blueprint $t){$t->id();$t->uuid('uuid')->unique();$t->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();$t->foreignId('amc_service_id')->constrained('amc_services')->cascadeOnDelete();$t->unsignedInteger('visit_number');$t->date('visit_date');$t->string('status',30)->default('pending');$t->text('details')->nullable();$t->dateTime('completed_at')->nullable();$t->dateTime('before_visit_reminder_sent_at')->nullable();$t->dateTime('same_day_reminder_sent_at')->nullable();$t->timestamps();$t->unique(['amc_service_id','visit_number']);});
 }
 public function down(): void {Schema::dropIfExists('amc_visits');Schema::dropIfExists('amc_services');}
};
