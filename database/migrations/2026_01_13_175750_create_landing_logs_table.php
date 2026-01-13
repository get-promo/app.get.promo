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
        Schema::create('landing_logs', function (Blueprint $table) {
            $table->id();
            
            // Typ akcji: search, selected, checked, phone_submitted
            $table->enum('action_type', ['search', 'selected', 'checked', 'phone_submitted']);
            
            // Dane wyszukiwania
            $table->string('search_query')->nullable(); // Co wyszukiwał
            $table->string('source')->nullable(); // database lub serper
            
            // Wybrane miejsce (jeśli applicable)
            $table->string('place_title')->nullable();
            $table->string('place_address')->nullable();
            $table->string('place_cid')->nullable();
            $table->json('place_data')->nullable(); // Pełne dane miejsca
            
            // Numer telefonu (jeśli submitted)
            $table->string('phone_number')->nullable();
            
            // Techniczne
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->string('session_id')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index('action_type');
            $table->index('session_id');
            $table->index('phone_number');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('landing_logs');
    }
};
