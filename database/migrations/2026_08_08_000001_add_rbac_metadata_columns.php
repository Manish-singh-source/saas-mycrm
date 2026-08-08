<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('platform_roles', function (Blueprint $table): void {
            if (! Schema::hasColumn('platform_roles', 'display_name')) {
                $table->string('display_name', 150)->nullable()->after('name');
            }
        });

        Schema::table('roles', function (Blueprint $table): void {
            if (! Schema::hasColumn('roles', 'display_name')) {
                $table->string('display_name', 150)->nullable()->after('name');
            }
        });

        Schema::table('platform_permissions', function (Blueprint $table): void {
            if (! Schema::hasColumn('platform_permissions', 'display_name')) {
                $table->string('display_name', 150)->nullable()->after('name');
            }
            if (! Schema::hasColumn('platform_permissions', 'is_system')) {
                $table->boolean('is_system')->default(false)->after('description');
            }
            if (! Schema::hasColumn('platform_permissions', 'status')) {
                $table->enum('status', ['active', 'inactive'])->default('active')->after('is_system');
            }
        });

        Schema::table('permissions', function (Blueprint $table): void {
            if (! Schema::hasColumn('permissions', 'display_name')) {
                $table->string('display_name', 150)->nullable()->after('name');
            }
            if (! Schema::hasColumn('permissions', 'is_system')) {
                $table->boolean('is_system')->default(false)->after('description');
            }
            if (! Schema::hasColumn('permissions', 'status')) {
                $table->enum('status', ['active', 'inactive'])->default('active')->after('is_system');
            }
        });
    }

    public function down(): void
    {
        Schema::table('permissions', function (Blueprint $table): void {
            foreach (['status', 'is_system', 'display_name'] as $column) {
                if (Schema::hasColumn('permissions', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('platform_permissions', function (Blueprint $table): void {
            foreach (['status', 'is_system', 'display_name'] as $column) {
                if (Schema::hasColumn('platform_permissions', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('roles', function (Blueprint $table): void {
            if (Schema::hasColumn('roles', 'display_name')) {
                $table->dropColumn('display_name');
            }
        });

        Schema::table('platform_roles', function (Blueprint $table): void {
            if (Schema::hasColumn('platform_roles', 'display_name')) {
                $table->dropColumn('display_name');
            }
        });
    }
};
