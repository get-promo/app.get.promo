<?php

namespace App\Http\Controllers;

use App\Models\Place;
use App\Models\LandingLog;
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
     * Strona debug dla in-app browser (bez logowania)
     */
    public function debugPage()
    {
        return view('content.landing.debug');
    }

    /**
     * Endpoint debugujący request (bez logowania)
     */
    public function debugRequest(Request $request)
    {
        return response()->json([
            'message' => '✅ Request received successfully!',
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'headers' => $request->headers->all(),
            'input' => $request->all(),
            'session_id' => $request->session()->getId(),
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Wyszukiwanie miejsc - najpierw w bazie, potem przez Serper API
     */
    public function searchPlaces(Request $request)
    {
        // DEBUG: Loguj WSZYSTKO
        $userAgent = $request->userAgent();
        $isFacebookBrowser = preg_match('/FBAN|FBAV|FB_IAB|FB4A/i', $userAgent);
        $isInstagramBrowser = preg_match('/Instagram/i', $userAgent);
        
        Log::info('=== LANDING SEARCH REQUEST START ===', [
            'query' => $request->input('query'),
            'ip' => $request->ip(),
            'user_agent' => $userAgent,
            'is_facebook_browser' => $isFacebookBrowser,
            'is_instagram_browser' => $isInstagramBrowser,
            'all_headers' => $request->headers->all(),
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'has_session' => $request->hasSession(),
            'session_id' => $request->hasSession() ? $request->session()->getId() : 'NO SESSION',
        ]);

        try {
            $request->validate([
                'query' => 'required|string|min:4'
            ]);
        } catch (\Exception $e) {
            Log::error('Validation failed', [
                'error' => $e->getMessage(),
                'input' => $request->all()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Błąd walidacji: ' . $e->getMessage()
            ], 422);
        }

        $query = $request->input('query');
        
        try {
            // 1. NAJPIERW szukaj w lokalnej bazie danych
            Log::info('Searching in local database', ['query' => $query]);
            
            $localPlaces = Place::search($query)
                ->limit(10)
                ->get();

            $source = 'database';
            $places = [];

            if ($localPlaces->isNotEmpty()) {
                // Znaleziono w lokalnej bazie - zwróć wyniki
                Log::info('Places found in local database', [
                    'query' => $query,
                    'count' => $localPlaces->count()
                ]);

                $places = $localPlaces->map(function ($place) {
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
                })->toArray();
            }

            if (empty($places)) {
                // 2. Nie znaleziono w bazie - szukaj przez Serper API
                Log::info('No places in database, searching via Serper API', [
                    'query' => $query
                ]);

                $response = Http::withHeaders([
                    'X-API-KEY' => config('services.serper.api_key'),
                    'Content-Type' => 'application/json'
                ])->post('https://google.serper.dev/places', [
                    'q' => $query,
                    'gl' => 'pl', // Kod kraju: Polska
                    'hl' => 'pl'  // Język wyników: polski
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    $places = $data['places'] ?? [];
                    $source = 'serper';
                    
                    Log::info('Places found via Serper API', [
                        'query' => $query,
                        'count' => count($places)
                    ]);
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'Błąd podczas wyszukiwania miejsc'
                    ], $response->status());
                }
            }

            // Loguj wyszukiwanie w bazie
            LandingLog::create([
                'action_type' => 'search',
                'search_query' => $query,
                'source' => $source,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'session_id' => $request->session()->getId(),
            ]);

            $response = [
                'success' => true,
                'source' => $source,
                'data' => [
                    'places' => $places
                ]
            ];

            Log::info('=== LANDING SEARCH SUCCESS ===', [
                'query' => $query,
                'source' => $source,
                'places_count' => count($places),
                'response' => $response
            ]);

            return response()->json($response);
        } catch (\Exception $e) {
            Log::error('=== LANDING SEARCH ERROR ===', [
                'query' => $query ?? 'N/A',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Wystąpił błąd: ' . $e->getMessage(),
                'debug' => config('app.debug') ? $e->getTraceAsString() : null
            ], 500);
        }
    }

    /**
     * Logowanie wyboru miejsca (selected)
     */
    public function logSelected(Request $request)
    {
        $request->validate([
            'place' => 'required|array'
        ]);

        $place = $request->input('place');

        LandingLog::create([
            'action_type' => 'selected',
            'place_title' => $place['title'] ?? null,
            'place_address' => $place['address'] ?? null,
            'place_cid' => $place['cid'] ?? null,
            'place_data' => $place,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'session_id' => $request->session()->getId(),
        ]);

        return response()->json([
            'success' => true
        ]);
    }

    /**
     * Logowanie kliknięcia "Sprawdź" (checked)
     */
    public function logChecked(Request $request)
    {
        $request->validate([
            'place' => 'required|array'
        ]);

        $place = $request->input('place');

        LandingLog::create([
            'action_type' => 'checked',
            'place_title' => $place['title'] ?? null,
            'place_address' => $place['address'] ?? null,
            'place_cid' => $place['cid'] ?? null,
            'place_data' => $place,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'session_id' => $request->session()->getId(),
        ]);

        return response()->json([
            'success' => true
        ]);
    }

    /**
     * Obsługa wysyłania formularza z numerem telefonu
     */
    public function submitPhone(Request $request)
    {
        $request->validate([
            'phone' => 'required|string|min:9|max:15'
        ]);

        $phone = $request->input('phone');
        $place = $request->input('place'); // Opcjonalnie - jeśli wybrano miejsce

        // Loguj wysłanie telefonu
        LandingLog::create([
            'action_type' => 'phone_submitted',
            'phone_number' => $phone,
            'place_title' => $place['title'] ?? null,
            'place_address' => $place['address'] ?? null,
            'place_cid' => $place['cid'] ?? null,
            'place_data' => $place,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'session_id' => $request->session()->getId(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Numer telefonu został zapisany'
        ]);
    }
}

