<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('calendars', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('owner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('team_id')->nullable()->constrained('teams')->nullOnDelete();
            $table->string('name');
            $table->string('calendar_type', 80);
            $table->string('color', 30)->nullable();
            $table->string('timezone', 100)->default('UTC');
            $table->string('visibility', 50)->default('private');
            $table->string('status', 50)->default('active');
            $table->timestamps();
        });
        Schema::create('calendar_events', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('calendar_id')->constrained('calendars')->cascadeOnDelete();
            $table->string('related_type', 120)->nullable();
            $table->unsignedBigInteger('related_id')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('location')->nullable();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at')->nullable();
            $table->string('timezone', 100)->default('UTC');
            $table->boolean('all_day')->default(false);
            $table->json('recurrence_rule')->nullable();
            $table->string('status', 50)->default('scheduled');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['tenant_id', 'starts_at', 'status']);
            $table->index(['tenant_id', 'related_type', 'related_id']);
        });
        Schema::create('calendar_event_attendees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('event_id')->constrained('calendar_events')->cascadeOnDelete();
            $table->string('attendee_type', 50);
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('team_id')->nullable()->constrained('teams')->nullOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained('party_contacts')->nullOnDelete();
            $table->string('email')->nullable();
            $table->string('response_status', 50)->default('pending');
        });
        Schema::create('calendar_event_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('event_id')->constrained('calendar_events')->cascadeOnDelete();
            $table->string('channel', 50);
            $table->dateTime('remind_at');
            $table->dateTime('sent_at')->nullable();
            $table->string('status', 50)->default('pending');
        });
        Schema::create('meeting_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('name');
            $table->foreignId('office_id')->nullable()->constrained('tenant_offices')->nullOnDelete();
            $table->string('location')->nullable();
            $table->unsignedInteger('capacity')->nullable();
            $table->string('status', 50)->default('active');
        });
        Schema::create('meeting_room_bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('room_id')->constrained('meeting_rooms')->restrictOnDelete();
            $table->foreignId('event_id')->constrained('calendar_events')->cascadeOnDelete();
            $table->foreignId('booked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 50)->default('booked');
        });
        Schema::create('video_meetings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('event_id')->constrained('calendar_events')->cascadeOnDelete();
            $table->string('provider', 80);
            $table->string('meeting_id')->nullable();
            $table->text('meeting_url')->nullable();
            $table->text('passcode')->nullable();
        });
        Schema::create('calendar_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('calendar_id')->constrained('calendars')->cascadeOnDelete();
            $table->string('provider', 80);
            $table->string('external_event_id')->nullable();
            $table->string('sync_status', 50);
            $table->dateTime('synced_at')->nullable();
        });
        Schema::create('reminders', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('remindable_type', 120);
            $table->unsignedBigInteger('remindable_id');
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('channel', 50);
            $table->dateTime('remind_at');
            $table->dateTime('sent_at')->nullable();
            $table->string('status', 50)->default('pending');
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'remindable_type', 'remindable_id']);
            $table->index(['tenant_id', 'user_id', 'remind_at']);
        });

        
        Schema::create('tenant_invoices', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->string('invoice_number', 80);
            $table->foreignId('client_party_id')->constrained('parties')->restrictOnDelete();
            $table->foreignId('project_id')->nullable()->constrained('projects')->restrictOnDelete();
            $table->date('invoice_date');
            $table->date('due_date')->nullable();
            foreach (['subtotal', 'discount_amount', 'taxable_amount', 'tax_amount', 'total_amount', 'paid_amount', 'balance_amount'] as $column) {
                $table->decimal($column, 18, 2)->default(0);
            }
            $table->char('currency', 3)->default('INR');
            $table->string('status', 50)->default('draft');
            $table->foreignId('pdf_file_id')->nullable()->constrained('files')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['tenant_id', 'invoice_number']);
            $table->index(['tenant_id', 'status', 'invoice_date']);
        });
        Schema::create('tenant_invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->foreignId('invoice_id')->constrained('tenant_invoices')->restrictOnDelete();
            $table->string('item_name');
            $table->text('description')->nullable();
            $table->decimal('quantity', 18, 2)->default(1);
            $table->decimal('unit_price', 18, 2)->default(0);
            $table->decimal('tax_rate', 8, 2)->default(0);
            $table->decimal('amount', 18, 2)->default(0);
        });
        Schema::create('tenant_payments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained('tenant_invoices')->restrictOnDelete();
            $table->foreignId('client_party_id')->nullable()->constrained('parties')->restrictOnDelete();
            $table->string('payment_number', 80);
            $table->decimal('amount', 18, 2);
            $table->char('currency', 3)->default('INR');
            $table->string('method', 80)->nullable();
            $table->string('reference')->nullable();
            $table->string('status', 50)->default('pending');
            $table->dateTime('paid_at')->nullable();
            $table->timestamps();
            $table->unique(['tenant_id', 'payment_number']);
            $table->index(['tenant_id', 'status', 'paid_at']);
        });
        Schema::create('tenant_expenses', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->foreignId('vendor_party_id')->nullable()->constrained('parties')->restrictOnDelete();
            $table->foreignId('project_id')->nullable()->constrained('projects')->restrictOnDelete();
            $table->string('expense_number', 80);
            $table->foreignId('category_id')->nullable()->constrained('tenant_lookups')->nullOnDelete();
            $table->decimal('amount', 18, 2);
            $table->char('currency', 3)->default('INR');
            $table->date('expense_date');
            $table->foreignId('status_id')->nullable()->constrained('tenant_lookups')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['tenant_id', 'expense_number']);
            $table->index(['tenant_id', 'expense_date', 'status_id']);
        });
        Schema::create('tenant_expense_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->foreignId('expense_id')->constrained('tenant_expenses')->restrictOnDelete();
            $table->string('description');
            $table->decimal('quantity', 18, 2)->default(1);
            $table->decimal('unit_price', 18, 2)->default(0);
            $table->decimal('tax_amount', 18, 2)->default(0);
            $table->decimal('amount', 18, 2)->default(0);
        });
        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->string('owner_type', 120);
            $table->unsignedBigInteger('owner_id');
            $table->string('bank_name');
            $table->text('account_number_encrypted');
            $table->text('routing_number_encrypted')->nullable();
            $table->string('ifsc_code', 30)->nullable();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
            $table->index(['tenant_id', 'owner_type', 'owner_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {}
};
