<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('team_roles', function (Blueprint $table): void {
            $table->unique(['tenant_id', 'name'], 'team_roles_tenant_name_unique');
        });
    }

    public function down(): void
    {
        Schema::table('team_roles', function (Blueprint $table): void {
            $table->dropUnique('team_roles_tenant_name_unique');
        });
    }
};