<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Ustaw weight_description na 0 i przepakuj wagi proporcjonalnie
        // Stare wagi: opinions=25, photos=15, description=10, categories=10, 
        //             products_services=10, posts=10, nap=0, hours_url=15, owner_replies=5
        // Po usunięciu description (10), przepakujemy proporcjonalnie do 100
        
        DB::table('report_settings')->update([
            'weight_opinions' => 28,          // było 25, +3
            'weight_photos' => 17,            // było 15, +2
            'weight_description' => 0,        // było 10, USUNIĘTE
            'weight_categories' => 11,        // było 10, +1
            'weight_products_services' => 11, // było 10, +1
            'weight_posts' => 11,             // było 10, +1
            'weight_nap' => 0,                // bez zmian (wyłączone)
            'weight_hours_url' => 17,         // było 15, +2
            'weight_owner_replies' => 5,      // bez zmian
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Przywróć stare wagi
        DB::table('report_settings')->update([
            'weight_opinions' => 25,
            'weight_photos' => 15,
            'weight_description' => 10,
            'weight_categories' => 10,
            'weight_products_services' => 10,
            'weight_posts' => 10,
            'weight_nap' => 0,
            'weight_hours_url' => 15,
            'weight_owner_replies' => 5,
        ]);
    }
};
