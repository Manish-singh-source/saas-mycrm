<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('platform_users', 'profile_photo_file_id')) {
            Schema::table('platform_users', function (Blueprint $table): void {
                $table->foreignId('profile_photo_file_id')->nullable()->after('profile_photo')->constrained('files')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('platform_users', 'profile_photo_file_id')) {
            Schema::table('platform_users', function (Blueprint $table): void {
                $table->dropForeign(['profile_photo_file_id']);
                $table->dropColumn('profile_photo_file_id');
            });
        }
    }
};
