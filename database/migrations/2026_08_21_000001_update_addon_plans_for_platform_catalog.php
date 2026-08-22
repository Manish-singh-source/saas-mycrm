<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('addon_plans', function (Blueprint $table): void {
            if (! Schema::hasColumn('addon_plans', 'is_public')) {
                $table->boolean('is_public')->default(true)->after('currency');
            }
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE addon_plans MODIFY status ENUM('active','inactive','archived') NOT NULL DEFAULT 'active'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("UPDATE addon_plans SET status = 'inactive' WHERE status = 'archived'");
            DB::statement("ALTER TABLE addon_plans MODIFY status ENUM('active','inactive') NOT NULL DEFAULT 'active'");
        }

        Schema::table('addon_plans', function (Blueprint $table): void {
            if (Schema::hasColumn('addon_plans', 'is_public')) {
                $table->dropColumn('is_public');
            }
        });
    }
};