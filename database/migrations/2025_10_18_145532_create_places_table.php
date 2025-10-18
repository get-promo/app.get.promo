<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('places', function (Blueprint $table) {
            $table->id();
            
            // Dane z Serper (Google Places)
            $table->string('cid')->unique()->nullable(); // Google Place ID
            $table->string('title')->nullable(); // Nazwa lokalu
            $table->string('address')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->decimal('rating', 2, 1)->nullable();
            $table->integer('rating_count')->nullable();
            $table->string('price_level')->nullable();
            $table->string('category')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('website')->nullable();
            
            // Cała odpowiedź z Serper w formacie JSON
            $table->json('serper_response')->nullable();
            
            // Dodatkowe pola - fraza i miasto
            $table->string('search_phrase')->nullable();
            $table->string('city_name')->nullable();
            $table->string('city_size')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index('cid');
            $table->index('search_phrase');
            $table->index('city_name');
            $table->index('title');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('places');
    }
};
