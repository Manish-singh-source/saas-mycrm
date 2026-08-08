<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('platform_users', function (Blueprint $table): void {
            if (! Schema::hasColumn('platform_users', 'two_factor_required')) {
                $table->boolean('two_factor_required')->default(false)->after('two_factor_enabled');
            }
        });
    }

    public function down(): void
    {
        Schema::table('platform_users', function (Blueprint $table): void {
            if (Schema::hasColumn('platform_users', 'two_factor_required')) {
                $table->dropColumn('two_factor_required');
            }
        });
    }
};
