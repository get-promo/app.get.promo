<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class LandingController extends Controller
{
    /**
     * Pokazuje landing page z wyszukiwarką
     */
    public function index()
    {
        return view('content.landing.index');
    }

    /**
     * Wyszukiwanie miejsc przez Serper API (publiczny endpoint)
     */
    public function searchPlaces(Request $request)
    {
        $request->validate([
            'query' => 'required|string|min:4'
        ]);

        try {
            $response = Http::withHeaders([
                'X-API-KEY' => config('services.serper.api_key'),
                'Content-Type' => 'application/json'
            ])->post('https://google.serper.dev/places', [
                'q' => $request->input('query'),
                'gl' => 'pl',
                'hl' => 'pl'
            ]);

            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'data' => $response->json()
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Błąd podczas wyszukiwania miejsc'
                ], $response->status());
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Wystąpił błąd: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obsługa wysyłania formularza z numerem telefonu
     */
    public function submitPhone(Request $request)
    {
        $request->validate([
            'phone' => 'required|string|min:9'
        ]);

        // Tutaj możesz dodać logikę zapisu numeru telefonu
        // Na razie tylko przekierowujemy z sukcesem
        
        return response()->json([
            'success' => true,
            'message' => 'Numer telefonu został zapisany'
        ]);
    }
}

