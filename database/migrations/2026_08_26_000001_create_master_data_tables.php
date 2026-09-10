<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
        |--------------------------------------------------------------------------
        | COUNTRIES
        |--------------------------------------------------------------------------
        */

        Schema::create('countries', function (Blueprint $table) {
            $table->id();

            $table->string('name', 150);

            // ISO 3166-1 Alpha-2
            // Example: IN, US, GB
            $table->char('iso2', 2)->unique();

            // ISO 3166-1 Alpha-3
            // Example: IND, USA, GBR
            $table->char('iso3', 3)->nullable()->unique();

            // International dialing code
            // Example: +91, +1, +44
            $table->string('phone_code', 20)->nullable();

            // ISO 4217
            // Example: INR, USD, GBP
            $table->char('currency_code', 3)->nullable();

            $table->enum('status', [
                'active',
                'inactive',
            ])->default('active');

            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();

            $table->index([
                'status',
                'sort_order',
            ]);
        });


        /*
        |--------------------------------------------------------------------------
        | STATES
        |--------------------------------------------------------------------------
        */

        Schema::create('states', function (Blueprint $table) {
            $table->id();

            $table->foreignId('country_id')
                ->constrained('countries')
                ->restrictOnDelete();

            $table->string('name', 150);

            // State / province code
            // Example: MH, GJ, CA, NY
            $table->string('code', 50)->nullable();

            $table->enum('status', [
                'active',
                'inactive',
            ])->default('active');

            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();

            $table->unique([
                'country_id',
                'code',
            ]);

            $table->index([
                'country_id',
                'status',
                'sort_order',
            ]);
        });


        /*
        |--------------------------------------------------------------------------
        | CITIES
        |--------------------------------------------------------------------------
        |
        | Country is intentionally not stored here.
        |
        | City → State → Country
        |
        */

        Schema::create('cities', function (Blueprint $table) {
            $table->id();

            $table->foreignId('state_id')
                ->constrained('states')
                ->restrictOnDelete();

            $table->string('name', 150);

            $table->enum('status', [
                'active',
                'inactive',
            ])->default('active');

            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();

            $table->unique([
                'state_id',
                'name',
            ]);

            $table->index([
                'state_id',
                'status',
                'sort_order',
            ]);
        });


        /*
        |--------------------------------------------------------------------------
        | BUSINESS TYPES
        |--------------------------------------------------------------------------
        |
        | Global business/entity types.
        |
        | Examples:
        | - Sole Proprietorship
        | - Partnership
        | - LLP
        | - Private Limited
        | - Public Limited
        | - Non-Profit
        |
        */

        Schema::create('business_types', function (Blueprint $table) {
            $table->id();

            $table->string('name', 150);
            $table->string('code', 80)->unique();

            $table->text('description')->nullable();

            $table->enum('status', [
                'active',
                'inactive',
            ])->default('active');

            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();

            $table->index([
                'status',
                'sort_order',
            ]);
        });


        /*
        |--------------------------------------------------------------------------
        | INDUSTRIES
        |--------------------------------------------------------------------------
        |
        | Global industry classifications.
        |
        | Examples:
        | - Information Technology
        | - Healthcare
        | - Finance
        | - Education
        | - Manufacturing
        | - Retail
        |
        */

        Schema::create('industries', function (Blueprint $table) {
            $table->id();

            $table->string('name', 150);
            $table->string('code', 80)->unique();

            $table->text('description')->nullable();

            $table->enum('status', [
                'active',
                'inactive',
            ])->default('active');

            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();

            $table->index([
                'status',
                'sort_order',
            ]);
        });


        /*
        |--------------------------------------------------------------------------
        | CURRENCIES
        |--------------------------------------------------------------------------
        |
        | ISO 4217 currencies.
        |
        | Examples:
        | INR - Indian Rupee
        | USD - US Dollar
        | GBP - Pound Sterling
        | EUR - Euro
        |
        */

        Schema::create('currencies', function (Blueprint $table) {
            $table->id();

            $table->string('name', 100);

            // ISO 4217
            $table->char('code', 3)->unique();

            // Example: ₹, $, €, £
            $table->string('symbol', 10)->nullable();

            // Example: 2 for INR/USD/EUR
            $table->unsignedTinyInteger('decimal_places')->default(2);

            $table->enum('status', [
                'active',
                'inactive',
            ])->default('active');

            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();

            $table->index([
                'status',
                'sort_order',
            ]);
        });


        /*
        |--------------------------------------------------------------------------
        | LANGUAGES
        |--------------------------------------------------------------------------
        |
        | Global supported application/user languages.
        |
        | Examples:
        | en - English
        | hi - Hindi
        | mr - Marathi
        |
        */

        Schema::create('languages', function (Blueprint $table) {
            $table->id();

            $table->string('name', 100);

            // ISO 639-1
            $table->char('code', 2)->unique();

            // Optional ISO 639-3
            $table->char('iso3', 3)->nullable()->unique();

            // Native language name
            // Example: English, हिन्दी, मराठी
            $table->string('native_name', 100)->nullable();

            $table->enum('status', [
                'active',
                'inactive',
            ])->default('active');

            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();

            $table->index([
                'status',
                'sort_order',
            ]);
        });


        /*
        |--------------------------------------------------------------------------
        | TIMEZONES
        |--------------------------------------------------------------------------
        |
        | IANA timezone identifiers.
        |
        | Examples:
        | Asia/Kolkata
        | America/New_York
        | Europe/London
        |
        */

        Schema::create('timezones', function (Blueprint $table) {
            $table->id();

            $table->string('name', 150)->unique();

            // Example:
            // Asia/Kolkata
            // America/New_York
            $table->string('identifier', 100)->unique();

            // Example: UTC+05:30
            $table->string('utc_offset', 10)->nullable();

            $table->enum('status', [
                'active',
                'inactive',
            ])->default('active');

            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();

            $table->index([
                'status',
                'sort_order',
            ]);
        });

        Schema::create('date_formats', function (Blueprint $table) {
            $table->id();

            $table->string('name', 100);
            $table->string('code', 50)->unique();

            // PHP date format
            // Examples:
            // d/m/Y
            // m/d/Y
            // Y-m-d
            // d M Y
            $table->string('format', 50);

            // Example displayed value:
            // 26/08/2026
            // 08/26/2026
            // 2026-08-26
            $table->string('example', 100)->nullable();

            $table->enum('status', [
                'active',
                'inactive',
            ])->default('active');

            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();

            $table->index([
                'status',
                'sort_order',
            ]);
        });

        Schema::create('time_formats', function (Blueprint $table) {
            $table->id();

            $table->string('name', 100);
            $table->string('code', 50)->unique();

            // PHP date/time format
            // Examples:
            // H:i
            // h:i A
            // H:i:s
            // h:i:s A
            $table->string('format', 50);

            // Example displayed value:
            // 14:30
            // 02:30 PM
            // 14:30:45
            // 02:30:45 PM
            $table->string('example', 100)->nullable();

            $table->enum('status', [
                'active',
                'inactive',
            ])->default('active');

            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();

            $table->index([
                'status',
                'sort_order',
            ]);
        });
    }


    public function down(): void
    {
        /*
        |--------------------------------------------------------------------------
        | DROP IN REVERSE DEPENDENCY ORDER
        |--------------------------------------------------------------------------
        */

        Schema::dropIfExists('time_formats');
        
        Schema::dropIfExists('date_formats');

        Schema::dropIfExists('timezones');

        Schema::dropIfExists('languages');

        Schema::dropIfExists('currencies');

        Schema::dropIfExists('industries');

        Schema::dropIfExists('business_types');

        Schema::dropIfExists('cities');

        Schema::dropIfExists('states');

        Schema::dropIfExists('countries');
    }
};
