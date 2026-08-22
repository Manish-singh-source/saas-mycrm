<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('plan_addons')) {
            return;
        }

        Schema::create('plan_addons', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('plan_id')->constrained('plans')->cascadeOnDelete();
            $table->foreignId('addon_plan_id')->constrained('addon_plans')->restrictOnDelete();
            $table->timestamps();
            $table->unique(['plan_id', 'addon_plan_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_addons');
    }
};
