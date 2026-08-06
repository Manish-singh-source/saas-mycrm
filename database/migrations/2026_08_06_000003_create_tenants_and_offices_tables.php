<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('organization_name', 200);
            $table->string('legal_name', 200)->nullable();
            $table->string('display_name', 200);
            $table->string('organization_code', 50)->unique();
            $table->string('slug', 150)->unique();
            $table->foreignId('business_type_id')->nullable()->constrained('business_types')->nullOnDelete();
            $table->foreignId('industry_id')->nullable()->constrained('industries')->nullOnDelete();
            $table->enum('company_size', ['self', 'small', 'medium', 'large', 'enterprise'])->nullable();
            $table->string('gst_number', 30)->nullable();
            $table->string('pan_number', 30)->nullable();
            $table->string('registration_number', 80)->nullable();
            $table->string('website')->nullable();
            $table->unsignedBigInteger('logo_file_id')->nullable()->index();
            $table->unsignedBigInteger('favicon_file_id')->nullable()->index();
            $table->char('default_currency', 3)->default('INR');
            $table->string('default_timezone', 100)->default('Asia/Kolkata');
            $table->dateTime('onboarded_at')->nullable();
            $table->dateTime('trial_ends_at')->nullable();
            $table->enum('status', ['pending', 'trial', 'active', 'suspended', 'expired', 'cancelled', 'archived'])->default('pending');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['status', 'trial_ends_at']);
            $table->index(['business_type_id', 'industry_id']);
        });

        Schema::create('tenant_offices', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('office_name', 150);
            $table->string('office_code', 50);
            $table->enum('office_type', ['head_office', 'branch', 'regional', 'warehouse', 'factory', 'store', 'remote', 'franchise'])->default('branch');
            $table->boolean('is_head_office')->default(false);
            $table->boolean('is_default')->default(false);
            $table->string('address_line_1')->nullable();
            $table->string('address_line_2')->nullable();
            $table->string('landmark')->nullable();
            $table->foreignId('country_id')->nullable()->constrained('countries')->nullOnDelete();
            $table->foreignId('state_id')->nullable()->constrained('states')->nullOnDelete();
            $table->foreignId('city_id')->nullable()->constrained('cities')->nullOnDelete();
            $table->string('postal_code', 20)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('contact_person', 150)->nullable();
            $table->string('contact_email', 150)->nullable();
            $table->string('contact_phone', 20)->nullable();
            $table->string('timezone', 100)->nullable();
            $table->json('working_hours')->nullable();
            $table->string('gst_number', 30)->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->unsignedBigInteger('updated_by')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['tenant_id', 'office_code']);
            $table->index(['tenant_id', 'office_type']);
            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_offices');
        Schema::dropIfExists('tenants');
    }
};