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
        Schema::create('report_competitors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained('reports')->onDelete('cascade');
            
            // Dane z Serper
            $table->string('name');
            $table->string('address')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->integer('position')->nullable();
            $table->string('cid')->nullable();
            $table->string('place_id')->nullable();
            
            // Scores
            $table->decimal('position_score', 3, 1)->nullable();
            $table->decimal('profile_quality_score', 3, 1)->nullable();
            
            // Snapshot danych Places (JSON)
            $table->json('places_data')->nullable();
            
            // Breakdown (JSON)
            $table->json('score_breakdown')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index('report_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_competitors');
    }
};
