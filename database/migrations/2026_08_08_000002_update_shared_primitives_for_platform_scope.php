<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_logs', function (Blueprint $table): void {
            if (! Schema::hasColumn('activity_logs', 'request_id')) {
                $table->string('request_id', 120)->nullable()->after('user_agent');
            }
        });

        Schema::table('notes', function (Blueprint $table): void {
            if (! Schema::hasColumn('notes', 'platform_created_by')) {
                $table->foreignId('platform_created_by')->nullable()->after('updated_by')->constrained('platform_users')->nullOnDelete();
            }
            if (! Schema::hasColumn('notes', 'platform_updated_by')) {
                $table->foreignId('platform_updated_by')->nullable()->after('platform_created_by')->constrained('platform_users')->nullOnDelete();
            }
        });

        Schema::table('notes', function (Blueprint $table): void {
            $table->foreignId('tenant_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('activity_logs', function (Blueprint $table): void {
            if (Schema::hasColumn('activity_logs', 'request_id')) {
                $table->dropColumn('request_id');
            }
        });

        Schema::table('notes', function (Blueprint $table): void {
            if (Schema::hasColumn('notes', 'platform_updated_by')) {
                $table->dropConstrainedForeignId('platform_updated_by');
            }
            if (Schema::hasColumn('notes', 'platform_created_by')) {
                $table->dropConstrainedForeignId('platform_created_by');
            }
        });
    }
};
