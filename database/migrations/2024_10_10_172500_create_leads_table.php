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
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            
            // Dane z Serper (Google Places)
            $table->string('title')->nullable(); // Nazwa lokalu
            $table->string('address')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->decimal('rating', 2, 1)->nullable();
            $table->integer('rating_count')->nullable();
            $table->string('price_level')->nullable();
            $table->string('category')->nullable();
            $table->string('phone_number')->nullable(); // Publiczny numer do lokalu
            $table->string('website')->nullable();
            $table->string('cid')->nullable(); // Google Place ID
            
            // Cała odpowiedź z Serper w formacie JSON
            $table->json('serper_response')->nullable();
            
            // Dane kontaktowe osoby
            $table->string('contact_first_name')->nullable();
            $table->string('contact_last_name')->nullable();
            $table->enum('contact_position', ['właściciel', 'manager', 'sekretarka', 'pracownik'])->nullable();
            $table->string('contact_phone')->nullable(); // Prywatny numer osoby kontaktowej
            $table->string('contact_email')->nullable();
            
            // Metadata
            $table->string('status')->default('new'); // new, contacted, qualified, converted, rejected
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('title');
            $table->index('status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};

