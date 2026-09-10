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
        Schema::create('holiday_calendars', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('country_id')->nullable()->constrained('countries')->nullOnDelete();
            $table->foreignId('state_id')->nullable()->constrained('states')->nullOnDelete();
            $table->boolean('is_default')->default(false);
            $table->string('status', 50)->default('active');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['tenant_id', 'status']);
        });
        Schema::create('holidays', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('holiday_calendar_id')->constrained('holiday_calendars')->cascadeOnDelete();
            $table->string('name');
            $table->foreignId('type_id')->nullable()->constrained('tenant_lookups')->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('tenant_lookups')->nullOnDelete();
            $table->date('holiday_date');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->decimal('total_days', 8, 2)->default(1);
            $table->boolean('is_half_day')->default(false);
            $table->string('half_day_session', 50)->nullable();
            $table->boolean('recurring_yearly')->default(false);
            $table->boolean('optional_holiday')->default(false);
            $table->boolean('applicable_to_all')->default(true);
            $table->text('description')->nullable();
            $table->string('color', 30)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['tenant_id', 'holiday_date']);
        });
        Schema::create('holiday_applicabilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('holiday_id')->constrained('holidays')->cascadeOnDelete();
            $table->enum('applicable_type', ['country', 'state', 'city', 'office', 'department', 'team', 'staff', 'group']);
            $table->unsignedBigInteger('applicable_id');
            $table->dateTime('created_at')->nullable();
            $table->index(['tenant_id', 'applicable_type', 'applicable_id'], 'holiday_applicable_idx');
        });
        Schema::create('holiday_groups', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('status', 50)->default('active');
            $table->timestamps();
            $table->unique(['tenant_id', 'name']);
        });
        Schema::create('holiday_group_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('holiday_group_id')->constrained('holiday_groups')->cascadeOnDelete();
            $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
            $table->dateTime('assigned_at')->nullable();
            $table->unique(['tenant_id', 'holiday_group_id', 'staff_id'], 'holiday_group_staff_unique');
        });

        
        Schema::create('payroll_cycles', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->string('cycle_name');
            $table->unsignedTinyInteger('payroll_month');
            $table->unsignedSmallInteger('payroll_year');
            $table->date('period_start');
            $table->date('period_end');
            $table->date('payment_date')->nullable();
            $table->string('status', 50)->default('draft');
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('processed_at')->nullable();
            $table->dateTime('approved_at')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->unique(['tenant_id', 'payroll_month', 'payroll_year']);
        });
        Schema::create('payrolls', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->foreignId('payroll_cycle_id')->constrained('payroll_cycles')->restrictOnDelete();
            $table->foreignId('staff_id')->constrained('staff')->restrictOnDelete();
            $table->string('employee_code', 80);
            foreach (['working_days', 'present_days', 'leave_days', 'unpaid_leave_days', 'overtime_hours'] as $column) {
                $table->decimal($column, 8, 2)->default(0);
            }
            foreach (['gross_salary', 'total_earnings', 'total_deductions', 'taxable_income', 'tax_amount', 'net_salary'] as $column) {
                $table->decimal($column, 18, 2)->default(0);
            }
            $table->string('payment_status', 50)->default('pending');
            $table->string('payment_reference')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->unique(['tenant_id', 'payroll_cycle_id', 'staff_id']);
        });
        Schema::create('payroll_component_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->string('name');
            $table->string('code', 80);
            $table->string('calculation_side', 50);
            $table->string('status', 50)->default('active');
            $table->unique(['tenant_id', 'code']);
        });
        Schema::create('payroll_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->foreignId('component_type_id')->constrained('payroll_component_types')->restrictOnDelete();
            $table->string('name');
            $table->string('code', 80);
            $table->string('calculation_method', 80);
            $table->decimal('default_value', 18, 2)->default(0);
            $table->text('formula')->nullable();
            $table->boolean('taxable')->default(false);
            $table->boolean('affects_pf')->default(false);
            $table->boolean('affects_esi')->default(false);
            $table->string('status', 50)->default('active');
            $table->unique(['tenant_id', 'code']);
        });
        Schema::create('payroll_component_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->foreignId('staff_id')->constrained('staff')->restrictOnDelete();
            $table->foreignId('component_id')->constrained('payroll_components')->restrictOnDelete();
            $table->decimal('amount', 18, 2)->default(0);
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
        });
        Schema::create('payroll_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->foreignId('payroll_id')->constrained('payrolls')->restrictOnDelete();
            $table->foreignId('component_id')->constrained('payroll_components')->restrictOnDelete();
            $table->decimal('amount', 18, 2)->default(0);
            $table->string('calculation_type', 80)->nullable();
            $table->string('remarks')->nullable();
        });
        Schema::create('payroll_overtime', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->foreignId('payroll_id')->constrained('payrolls')->restrictOnDelete();
            $table->foreignId('attendance_record_id')->nullable()->constrained('attendance_records')->restrictOnDelete();
            $table->decimal('overtime_hours', 8, 2)->default(0);
            $table->decimal('hourly_rate', 18, 2)->default(0);
            $table->decimal('amount', 18, 2)->default(0);
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
        });
        Schema::create('payroll_loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->foreignId('staff_id')->constrained('staff')->restrictOnDelete();
            $table->string('loan_number', 80);
            foreach (['principal_amount', 'interest_rate', 'installment_amount', 'remaining_amount'] as $column) {
                $table->decimal($column, 18, 2)->default(0);
            }
            $table->unsignedInteger('total_installments')->default(0);
            $table->date('issued_date');
            $table->string('status', 50)->default('active');
            $table->unique(['tenant_id', 'loan_number']);
        });
        Schema::create('payroll_loan_installments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->foreignId('loan_id')->constrained('payroll_loans')->restrictOnDelete();
            $table->foreignId('payroll_id')->nullable()->constrained('payrolls')->restrictOnDelete();
            $table->unsignedInteger('installment_no');
            $table->decimal('amount', 18, 2);
            $table->dateTime('paid_at')->nullable();
        });
        Schema::create('payroll_reimbursements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->foreignId('payroll_id')->nullable()->constrained('payrolls')->restrictOnDelete();
            $table->foreignId('staff_id')->constrained('staff')->restrictOnDelete();
            $table->foreignId('expense_id')->nullable()->constrained('tenant_expenses')->restrictOnDelete();
            $table->decimal('amount', 18, 2);
            $table->string('approval_status', 50)->default('pending');
        });
        Schema::create('payroll_tax_slabs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->string('name');
            $table->decimal('min_amount', 18, 2)->default(0);
            $table->decimal('max_amount', 18, 2)->nullable();
            $table->decimal('tax_percentage', 8, 2)->default(0);
            $table->decimal('cess_percentage', 8, 2)->default(0);
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
        });
        Schema::create('payroll_tax_deductions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->foreignId('payroll_id')->constrained('payrolls')->restrictOnDelete();
            $table->foreignId('tax_slab_id')->nullable()->constrained('payroll_tax_slabs')->restrictOnDelete();
            $table->decimal('taxable_income', 18, 2)->default(0);
            $table->decimal('tax_amount', 18, 2)->default(0);
        });
        Schema::create('payroll_pf_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->decimal('employee_rate', 8, 2);
            $table->decimal('employer_rate', 8, 2);
            $table->decimal('wage_limit', 18, 2)->nullable();
            $table->date('effective_from');
        });
        Schema::create('payroll_esi_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->decimal('employee_rate', 8, 2);
            $table->decimal('employer_rate', 8, 2);
            $table->decimal('wage_limit', 18, 2)->nullable();
            $table->date('effective_from');
        });
        Schema::create('payroll_bank_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->foreignId('payroll_id')->constrained('payrolls')->restrictOnDelete();
            $table->foreignId('bank_account_id')->nullable()->constrained('bank_accounts')->restrictOnDelete();
            $table->string('reference')->nullable();
            $table->decimal('amount', 18, 2);
            $table->date('transfer_date')->nullable();
            $table->string('status', 50)->default('pending');
        });
        Schema::create('payroll_payslips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->foreignId('payroll_id')->constrained('payrolls')->restrictOnDelete();
            $table->string('payslip_number', 80);
            $table->foreignId('file_id')->nullable()->constrained('files')->nullOnDelete();
            $table->dateTime('generated_at')->nullable();
            $table->dateTime('emailed_at')->nullable();
            $table->unique(['tenant_id', 'payslip_number']);
        });
        Schema::create('payroll_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->foreignId('payroll_id')->constrained('payrolls')->restrictOnDelete();
            $table->unsignedInteger('approval_level');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 50)->default('pending');
            $table->string('remarks')->nullable();
            $table->dateTime('approved_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {}
};
