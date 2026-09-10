<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::dropIfExists('todo_lists');
    }

    public function down(): void
    {
        // The standalone todo_items schema replaces this legacy table.
    }
};
