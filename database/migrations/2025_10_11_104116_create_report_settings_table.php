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
        Schema::create('report_settings', function (Blueprint $table) {
            $table->id();
            
            // Wagi komponentów (sumują się do 100)
            $table->integer('weight_opinions')->default(25);
            $table->integer('weight_photos')->default(15);
            $table->integer('weight_description')->default(10);
            $table->integer('weight_categories')->default(10);
            $table->integer('weight_products_services')->default(10);
            $table->integer('weight_posts')->default(10);
            $table->integer('weight_nap')->default(0); // Na razie wyłączone
            $table->integer('weight_hours_url')->default(15);
            $table->integer('weight_owner_replies')->default(5);
            
            // API Settings
            $table->string('google_places_api_key')->nullable();
            $table->boolean('enable_cache')->default(true);
            $table->integer('cache_ttl_hours')->default(24);
            
            $table->timestamps();
        });
        
        // Wstaw domyślne ustawienia
        DB::table('report_settings')->insert([
            'weight_opinions' => 25,
            'weight_photos' => 15,
            'weight_description' => 10,
            'weight_categories' => 10,
            'weight_products_services' => 10,
            'weight_posts' => 10,
            'weight_nap' => 0,
            'weight_hours_url' => 15,
            'weight_owner_replies' => 5,
            'enable_cache' => true,
            'cache_ttl_hours' => 24,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_settings');
    }
};
