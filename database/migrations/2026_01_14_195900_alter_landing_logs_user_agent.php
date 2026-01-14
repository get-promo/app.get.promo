<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('landing_logs')) {
            DB::statement('ALTER TABLE `landing_logs` MODIFY `user_agent` TEXT NULL');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('landing_logs')) {
            DB::statement('ALTER TABLE `landing_logs` MODIFY `user_agent` VARCHAR(255) NULL');
        }
    }
};

