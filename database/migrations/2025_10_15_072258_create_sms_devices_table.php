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
        Schema::create('sms_devices', function (Blueprint $table) {
            $table->id();
            $table->string('device_id')->unique(); // Unikalny ID urządzenia Android
            $table->string('api_key')->unique(); // API key dla tego urządzenia
            $table->string('phone_number'); // Numer telefonu urządzenia
            $table->string('device_name')->nullable(); // Nazwa urządzenia
            $table->boolean('is_active')->default(true); // Czy urządzenie jest aktywne
            $table->timestamp('last_seen_at')->nullable(); // Ostatni kontakt z urządzeniem
            $table->json('device_info')->nullable(); // Informacje o urządzeniu (model, Android version, etc.)
            $table->timestamps();
            
            $table->index(['api_key', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sms_devices');
    }
};
