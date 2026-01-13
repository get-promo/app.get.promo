<?php

namespace App\Http\Controllers;

use App\Models\Place;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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
     * Wyszukiwanie miejsc - najpierw w bazie, potem przez Serper API
     */
    public function searchPlaces(Request $request)
    {
        $request->validate([
            'query' => 'required|string|min:4'
        ]);

        $query = $request->input('query');
        
        try {
            // 1. NAJPIERW szukaj w lokalnej bazie danych
            $localPlaces = Place::search($query)
                ->limit(10)
                ->get();

            if ($localPlaces->isNotEmpty()) {
                // Znaleziono w lokalnej bazie - zwróć wyniki
                Log::info('Places found in local database', [
                    'query' => $query,
                    'count' => $localPlaces->count()
                ]);

                return response()->json([
                    'success' => true,
                    'source' => 'database',
                    'data' => [
                        'places' => $localPlaces->map(function ($place) {
                            return [
                                'title' => $place->title,
                                'address' => $place->address,
                                'rating' => $place->rating,
                                'ratingCount' => $place->rating_count,
                                'category' => $place->category,
                                'phoneNumber' => $place->phone_number,
                                'website' => $place->website,
                                'cid' => $place->cid,
                                'latitude' => $place->latitude,
                                'longitude' => $place->longitude,
                            ];
                        })->toArray()
                    ]
                ]);
            }

            // 2. Nie znaleziono w bazie - szukaj przez Serper API
            Log::info('No places in database, searching via Serper API', [
                'query' => $query
            ]);

            $response = Http::withHeaders([
                'X-API-KEY' => config('services.serper.api_key'),
                'Content-Type' => 'application/json'
            ])->post('https://google.serper.dev/places', [
                'q' => $query,
                'gl' => 'pl', // Szukaj tylko w Polsce
                'hl' => 'pl', // Język polski
                'location' => 'Poland' // Dodatkowo lokalizacja: Polska
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                Log::info('Places found via Serper API', [
                    'query' => $query,
                    'count' => count($data['places'] ?? [])
                ]);

                return response()->json([
                    'success' => true,
                    'source' => 'serper',
                    'data' => $data
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Błąd podczas wyszukiwania miejsc'
                ], $response->status());
            }
        } catch (\Exception $e) {
            Log::error('Error searching places', [
                'query' => $query,
                'error' => $e->getMessage()
            ]);

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

