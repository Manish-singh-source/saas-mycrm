<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('platform_teams', function (Blueprint $table): void {
            if (! Schema::hasColumn('platform_teams', 'assistant_lead_platform_user_id')) {
                $table->foreignId('assistant_lead_platform_user_id')->nullable()->after('lead_platform_user_id')->constrained('platform_users')->nullOnDelete();
            }
            if (! Schema::hasColumn('platform_teams', 'phone')) {
                $table->string('phone', 40)->nullable()->after('email');
            }
            if (! Schema::hasColumn('platform_teams', 'icon')) {
                $table->string('icon', 80)->nullable()->after('color');
            }
            if (! Schema::hasColumn('platform_teams', 'visibility')) {
                $table->string('visibility', 40)->default('internal')->after('icon');
            }
        });

        Schema::table('platform_team_roles', function (Blueprint $table): void {
            if (! Schema::hasColumn('platform_team_roles', 'description')) {
                $table->string('description')->nullable()->after('code');
            }
            if (! Schema::hasColumn('platform_team_roles', 'sort_order')) {
                $table->integer('sort_order')->default(0)->after('permissions');
            }
            if (! Schema::hasColumn('platform_team_roles', 'is_system')) {
                $table->boolean('is_system')->default(false)->after('sort_order');
            }
            if (! Schema::hasColumn('platform_team_roles', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    public function down(): void
    {
        Schema::table('platform_team_roles', function (Blueprint $table): void {
            foreach (['description', 'sort_order', 'is_system', 'deleted_at'] as $column) {
                if (Schema::hasColumn('platform_team_roles', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('platform_teams', function (Blueprint $table): void {
            if (Schema::hasColumn('platform_teams', 'assistant_lead_platform_user_id')) {
                $table->dropConstrainedForeignId('assistant_lead_platform_user_id');
            }
            foreach (['phone', 'icon', 'visibility'] as $column) {
                if (Schema::hasColumn('platform_teams', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};