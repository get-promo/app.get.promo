<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Przywracamy weight_description i rebalansujemy wagi do oryginalnych wartości.
     * Teraz description jest pobierane z Bright Data (nie z editorial_summary).
     */
    public function up(): void
    {
        // Przywróć oryginalne wagi z przed usunięcia description
        DB::table('report_settings')->update([
            'weight_opinions' => 25,              // było 28, wracamy do 25
            'weight_photos' => 15,                // było 17, wracamy do 15
            'weight_description' => 10,           // było 0, PRZYWRACAMY
            'weight_categories' => 10,            // było 11, wracamy do 10
            'weight_products_services' => 10,     // było 11, wracamy do 10
            'weight_posts' => 10,                 // było 11, wracamy do 10
            'weight_nap' => 0,                    // bez zmian (wyłączone)
            'weight_hours_url' => 15,             // było 17, wracamy do 15
            'weight_owner_replies' => 5,          // bez zmian
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Wróć do wag bez description
        DB::table('report_settings')->update([
            'weight_opinions' => 28,
            'weight_photos' => 17,
            'weight_description' => 0,
            'weight_categories' => 11,
            'weight_products_services' => 11,
            'weight_posts' => 11,
            'weight_nap' => 0,
            'weight_hours_url' => 17,
            'weight_owner_replies' => 5,
        ]);
    }
};
