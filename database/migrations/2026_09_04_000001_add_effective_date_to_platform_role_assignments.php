<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('platform_model_has_roles', function (Blueprint $table): void {
            $table->date('effective_date')->nullable()->after('model_type');
        });
    }

    public function down(): void
    {
        Schema::table('platform_model_has_roles', function (Blueprint $table): void {
            $table->dropColumn('effective_date');
        });
    }
};