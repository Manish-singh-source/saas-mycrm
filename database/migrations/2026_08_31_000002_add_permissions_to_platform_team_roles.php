<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasColumn('platform_team_roles', 'permissions')) Schema::table('platform_team_roles', function (Blueprint $table): void {
            $table->json('permissions')->nullable()->after('description');
        });
    }
    public function down(): void
    {
        if (Schema::hasColumn('platform_team_roles', 'permissions')) Schema::table('platform_team_roles', function (Blueprint $table): void {
            $table->dropColumn('permissions');
        });
    }
};
