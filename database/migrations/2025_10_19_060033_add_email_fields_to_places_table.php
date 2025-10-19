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
        Schema::table('places', function (Blueprint $table) {
            // Email i status weryfikacji
            $table->string('email')->nullable()->after('website');
            $table->boolean('email_checked')->default(false)->after('email');
            $table->timestamp('email_checked_at')->nullable()->after('email_checked');
            $table->string('email_source')->nullable()->after('email_checked_at');
            // źródło: 'website', 'whois', 'ai_scan', 'manual', etc.
            
            // Dodatkowy JSON dla danych email (opcjonalnie)
            $table->json('email_scan_data')->nullable()->after('email_source');
            
            // Indexes dla wydajności
            $table->index('email');
            $table->index('email_checked');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('places', function (Blueprint $table) {
            $table->dropIndex(['email']);
            $table->dropIndex(['email_checked']);
            $table->dropColumn([
                'email',
                'email_checked',
                'email_checked_at',
                'email_source',
                'email_scan_data'
            ]);
        });
    }
};
