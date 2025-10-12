<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Usuń istniejącego użytkownika jeśli istnieje
        User::where('email', 'info@get.promo')->delete();

        // Utwórz konto administratora
        User::create([
            'name' => 'Administrator',
            'email' => 'info@get.promo',
            'password' => Hash::make('5AgMaIsMa7#'),
            'email_verified_at' => now(),
        ]);
    }
}
