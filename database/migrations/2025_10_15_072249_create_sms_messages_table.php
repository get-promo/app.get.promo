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
        Schema::create('sms_messages', function (Blueprint $table) {
            $table->id();
            $table->string('message_id')->unique(); // UUID dla wiadomości
            $table->string('device_id'); // ID urządzenia Android
            $table->string('from_number'); // Numer nadawcy
            $table->string('to_number'); // Numer odbiorcy
            $table->text('content'); // Treść wiadomości
            $table->enum('type', ['sent', 'received']); // Typ wiadomości
            $table->enum('status', ['pending', 'sent', 'delivered', 'failed'])->default('pending');
            $table->timestamp('sent_at')->nullable(); // Czas wysłania
            $table->timestamp('delivered_at')->nullable(); // Czas dostarczenia
            $table->json('metadata')->nullable(); // Dodatkowe dane (delivery report, etc.)
            $table->timestamps();
            
            $table->index(['device_id', 'status']);
            $table->index(['from_number', 'to_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sms_messages');
    }
};
