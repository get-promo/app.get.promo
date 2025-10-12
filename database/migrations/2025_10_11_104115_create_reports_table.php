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
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained('leads')->onDelete('cascade');
            $table->string('key', 32)->unique(); // Klucz publiczny a-z0-9
            
            // Dane leada (snapshot w momencie generowania)
            $table->string('business_name');
            $table->string('search_query')->nullable();
            $table->integer('position')->nullable();
            $table->integer('total_results')->nullable();
            
            // Scores
            $table->decimal('position_score', 3, 1); // 0.0-5.0
            $table->decimal('profile_quality_score', 3, 1); // 0.0-5.0
            
            // Snapshot danych z Google Places (JSON)
            $table->json('places_data')->nullable();
            
            // Breakdown składowych (JSON)
            $table->json('score_breakdown');
            
            // Statystyki konkurencji
            $table->decimal('avg_competitor_position_score', 3, 1)->nullable();
            $table->decimal('avg_competitor_quality_score', 3, 1)->nullable();
            $table->integer('competitors_count')->default(0);
            
            // Snapshot wag użytych do liczenia
            $table->json('weights_snapshot');
            
            // Meta
            $table->timestamp('generated_at');
            $table->integer('views_count')->default(0);
            $table->timestamps();
            
            // Indexes
            $table->index('key');
            $table->index('lead_id');
            $table->index('generated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
