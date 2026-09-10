<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::create('todo_items', function (Blueprint $table) {
   $table->id(); $table->uuid('uuid')->unique(); $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete(); $table->foreignId('owner_user_id')->constrained('users')->cascadeOnDelete();
   $table->string('title', 255); $table->text('description')->nullable(); $table->string('status', 40)->default('pending'); $table->string('priority', 30)->default('normal');
   $table->dateTime('scheduled_at')->nullable(); $table->dateTime('due_at')->nullable(); $table->dateTime('completed_at')->nullable(); $table->boolean('is_recurring')->default(false); $table->string('recurrence_rule')->nullable(); $table->json('display_options')->nullable(); $table->timestamps(); $table->softDeletes();
   $table->index(['tenant_id','owner_user_id','status']); $table->index(['tenant_id','owner_user_id','scheduled_at']);
  });
  Schema::create('todo_reminders', function (Blueprint $table) {
   $table->id(); $table->uuid('uuid')->unique(); $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete(); $table->foreignId('todo_item_id')->constrained('todo_items')->cascadeOnDelete(); $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
   $table->dateTime('remind_at'); $table->string('channel', 30)->default('in_app'); $table->string('repeat')->nullable(); $table->json('metadata')->nullable(); $table->string('status', 30)->default('pending'); $table->dateTime('sent_at')->nullable(); $table->timestamps();
   $table->index(['tenant_id','user_id','remind_at']);
  });
 }
 public function down(): void { Schema::dropIfExists('todo_reminders'); Schema::dropIfExists('todo_items'); }
};
