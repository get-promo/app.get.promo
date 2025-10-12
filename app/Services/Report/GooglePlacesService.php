<?php

namespace App\Services\Report;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Google Places API (New) Service
 * Dokumentacja: https://developers.google.com/maps/documentation/places/web-service/op-overview
 */
class GooglePlacesService
{
    private string $apiKey;
    private bool $enableCache;
    private int $cacheTtl;

    public function __construct()
    {
        $this->apiKey = config('services.google_places.api_key');
        $this->enableCache = true;
        $this->cacheTtl = 24; // godziny
    }

    /**
     * Znajdź place_id na podstawie nazwy, adresu i współrzędnych
     */
    public function findPlaceId(string $name, ?string $address, ?float $lat, ?float $lng, ?string $cid = null): ?string
    {
        // Użyj Text Search (nowe API)
        $query = trim("$name $address");
        return $this->textSearch($query, $lat, $lng);
    }

    /**
     * Text Search - wyszukaj miejsce po tekście (nowe API)
     */
    private function textSearch(string $query, ?float $lat, ?float $lng): ?string
    {
        $cacheKey = "places_v1_text_" . md5($query . "_" . $lat . "_" . $lng);
        
        if ($this->enableCache && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            $body = [
                'textQuery' => $query,
            ];

            // Dodaj location bias jeśli mamy współrzędne
            if ($lat && $lng) {
                $body['locationBias'] = [
                    'circle' => [
                        'center' => [
                            'latitude' => $lat,
                            'longitude' => $lng
                        ],
                        'radius' => 500.0
                    ]
                ];
            }

            $response = Http::withHeaders([
                'X-Goog-Api-Key' => $this->apiKey,
                'X-Goog-FieldMask' => 'places.id,places.location',
                'Content-Type' => 'application/json'
            ])->post('https://places.googleapis.com/v1/places:searchText', $body);

            if ($response->successful() && isset($response['places'][0])) {
                $place = $response['places'][0];
                $placeId = $place['id'] ?? null;

                // Weryfikuj lokalizację jeśli mamy współrzędne
                if ($placeId && $lat && $lng && isset($place['location'])) {
                    $actualLat = $place['location']['latitude'] ?? null;
                    $actualLng = $place['location']['longitude'] ?? null;
                    
                    if ($actualLat && $actualLng) {
                        $distance = $this->calculateDistance($lat, $lng, $actualLat, $actualLng);
                        
                        // Jeśli za daleko, odrzuć (tolerancja 500m)
                        if ($distance > 500) {
                            Log::info("Place rejected due to distance: {$distance}m");
                            return null;
                        }
                    }
                }
                
                if ($placeId && $this->enableCache) {
                    Cache::put($cacheKey, $placeId, now()->addHours($this->cacheTtl));
                }
                
                return $placeId;
            }

            if ($response->status() === 429) {
                Log::warning("Google Places API rate limit exceeded");
            } else {
                Log::error("Text search failed", [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
            }

        } catch (\Exception $e) {
            Log::error("Google Places text search failed: " . $e->getMessage());
        }

        return null;
    }

    /**
     * Pobierz szczegóły miejsca (nowe API)
     */
    public function getPlaceDetails(string $placeId, ?array $fields = null, ?string $cid = null): array
    {
        $cacheKey = "places_v1_details_{$placeId}";
        
        if ($this->enableCache && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        // Domyślne pola w formacie nowego API
        if (!$fields) {
            $fieldMask = [
                'id',
                'displayName',
                'formattedAddress',
                'location',
                'rating',
                'userRatingCount',
                'editorialSummary',
                'businessStatus',
                'websiteUri',
                'internationalPhoneNumber',
                'regularOpeningHours',
                'currentOpeningHours',
                'utcOffsetMinutes',
                'types',
                'photos',
                'reviews',
                'priceLevel'
            ];
        } else {
            $fieldMask = $fields;
        }

        try {
            $response = Http::retry(3, 1000)->withHeaders([
                'X-Goog-Api-Key' => $this->apiKey,
                'X-Goog-FieldMask' => implode(',', $fieldMask)
            ])->get("https://places.googleapis.com/v1/places/{$placeId}");

            if ($response->successful()) {
                $result = $response->json();
                
                // Pobierz data_id z SerpApi
                $dataId = $this->getDataIdFromSerpApi($placeId);
                
                // Parsuj dane do naszego formatu
                $placesData = $this->parsePlacesData($result, $placeId, $dataId);
                
                if ($this->enableCache) {
                    Cache::put($cacheKey, $placesData, now()->addHours($this->cacheTtl));
                }
                
                return $placesData;
            }

            if ($response->status() === 429) {
                Log::warning("Google Places API rate limit exceeded");
                sleep(2); // Backoff
            } else {
                Log::error("Place details failed for {$placeId}", [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
            }

        } catch (\Exception $e) {
            Log::error("Google Places Details failed for {$placeId}: " . $e->getMessage());
        }

        return [];
    }

    /**
     * Pobierz data_id z SerpApi używając place_id
     */
    private function getDataIdFromSerpApi(string $placeId): ?string
    {
        $cacheKey = "serpapi_data_id_{$placeId}";
        
        if ($this->enableCache && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            $response = Http::timeout(30)->get('https://serpapi.com/search', [
                'engine' => 'google_maps',
                'type' => 'place',
                'place_id' => $placeId,
                'api_key' => config('services.serpapi.api_key'),
                'hl' => 'pl',
                'gl' => 'pl'
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $dataId = $data['place_results']['data_id'] ?? null;

                if ($dataId && $this->enableCache) {
                    Cache::put($cacheKey, $dataId, now()->addHours($this->cacheTtl));
                }

                return $dataId;
            }

        } catch (\Exception $e) {
            Log::error("SerpApi data_id lookup failed: " . $e->getMessage());
        }

        return null;
    }

    /**
     * Pobierz liczbę postów z ostatnich 30 dni z SerpApi
     */
    private function getPostsCountLast30DaysFromSerpApi(?string $dataId): ?int
    {
        if (!$dataId) {
            return null;
        }

        $cacheKey = "serpapi_posts_30d_{$dataId}";
        
        if ($this->enableCache && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            $response = Http::timeout(30)->get('https://serpapi.com/search', [
                'engine' => 'google_maps_posts',
                'data_id' => $dataId,
                'api_key' => config('services.serpapi.api_key'),
                'hl' => 'pl',
                'gl' => 'pl'
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $posts = $data['posts'] ?? [];
                
                // Policz posty z ostatnich 30 dni
                $thirtyDaysAgo = now()->subDays(30);
                $recentPostsCount = 0;
                
                foreach ($posts as $post) {
                    $postedAt = $post['posted_at'] ?? null;
                    if ($postedAt) {
                        try {
                            $postDate = \Carbon\Carbon::parse($postedAt);
                            if ($postDate->gte($thirtyDaysAgo)) {
                                $recentPostsCount++;
                            }
                        } catch (\Exception $e) {
                            // Pomiń błędne daty
                            continue;
                        }
                    }
                }

                if ($this->enableCache) {
                    Cache::put($cacheKey, $recentPostsCount, now()->addHours($this->cacheTtl));
                }

                Log::info("SerpApi Posts: {$recentPostsCount} posts in last 30 days for data_id={$dataId}");
                return $recentPostsCount;
            } else {
                Log::error("SerpApi Posts API failed: " . $response->body());
            }

        } catch (\Exception $e) {
            Log::error("SerpApi Posts API exception: " . $e->getMessage());
        }

        return null;
    }

    /**
     * Pobierz liczbę zdjęć z SerpApi (dokładniejsze niż Google Places API)
     */
    private function getPhotosCountFromSerpApi(?string $cid, ?string $dataId): ?int
    {
        if (!$cid && !$dataId) {
            return null;
        }

        // Przekonwertuj CID na data_id jeśli potrzeba
        if ($cid && !$dataId) {
            $dataId = $this->cidToDataId($cid);
        }

        if (!$dataId) {
            return null;
        }

        $cacheKey = "serpapi_photos_count_{$dataId}";
        
        if ($this->enableCache && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            $response = Http::timeout(30)->get('https://serpapi.com/search', [
                'engine' => 'google_maps_photos',
                'data_id' => $dataId,
                'api_key' => config('services.serpapi.api_key'),
                'hl' => 'pl',
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $photosCount = isset($data['photos']) ? count($data['photos']) : 0;

                if ($this->enableCache) {
                    Cache::put($cacheKey, $photosCount, now()->addHours($this->cacheTtl));
                }

                Log::info("SerpApi Photos: {$photosCount} photos found for data_id={$dataId}");
                return $photosCount;
            } else {
                Log::error("SerpApi Photos API failed: " . $response->body());
            }

        } catch (\Exception $e) {
            Log::error("SerpApi Photos API exception: " . $e->getMessage());
        }

        return null;
    }

    /**
     * Konwertuj CID na data_id (format SerpApi)
     */
    private function cidToDataId(string $cid): ?string
    {
        // CID to liczba, data_id to format 0x...:0x...
        // Konwersja wymaga znalezienia hex wartości
        // Na razie zwracamy null, bo nie mamy hex współrzędnych
        // Alternatywnie można pobrać data_id z pierwszego zapytania do SerpApi Place
        return null;
    }

    /**
     * Pobierz owner reply rate z Serper Reviews API (50 opinii z 5 stron)
     */
    private function getOwnerReplyRateFromSerper(string $placeId): ?float
    {
        $cacheKey = "serper_reviews_rate_{$placeId}";
        
        if ($this->enableCache && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            $totalReviews = 0;
            $reviewsWithResponse = 0;
            $nextPageToken = null;
            $maxPages = 5; // 5 stron = ~50 opinii
            
            for ($page = 1; $page <= $maxPages; $page++) {
                $requestBody = [
                    'placeId' => $placeId,
                    'gl' => 'pl',
                    'hl' => 'pl',
                    'sortBy' => 'newest',
                    'num' => 10
                ];
                
                if ($nextPageToken) {
                    $requestBody['nextPageToken'] = $nextPageToken;
                }
                
                $response = Http::withHeaders([
                    'X-API-KEY' => config('services.serper.api_key'),
                    'Content-Type' => 'application/json'
                ])->timeout(30)->post('https://google.serper.dev/reviews', $requestBody);
                
                if (!$response->successful()) {
                    Log::error("Serper Reviews API failed on page {$page}: " . $response->body());
                    break;
                }
                
                $data = $response->json();
                $reviews = $data['reviews'] ?? [];
                
                if (empty($reviews)) {
                    break;
                }
                
                // Przelicz opinie
                foreach ($reviews as $review) {
                    $totalReviews++;
                    
                    // Sprawdź czy ma odpowiedź właściciela
                    if (isset($review['response']) && !empty($review['response'])) {
                        $reviewsWithResponse++;
                    }
                }
                
                // Pobierz token do następnej strony
                $nextPageToken = $data['nextPageToken'] ?? null;
                
                if (!$nextPageToken) {
                    break; // Brak więcej stron
                }
                
                // Małe opóźnienie między requestami
                usleep(300000); // 0.3s
            }
            
            $replyRate = $totalReviews > 0 
                ? round(($reviewsWithResponse / $totalReviews) * 100, 1) 
                : null;
            
            if ($this->enableCache && $replyRate !== null) {
                Cache::put($cacheKey, $replyRate, now()->addHours($this->cacheTtl));
            }
            
            Log::info("Serper Reviews: {$reviewsWithResponse}/{$totalReviews} reviews with owner response ({$replyRate}%)");
            
            return $replyRate;
            
        } catch (\Exception $e) {
            Log::error("Serper Reviews API failed: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Parsuj dane z Places API (New) do naszego formatu
     */
    private function parsePlacesData(array $result, ?string $placeId = null, ?string $dataId = null): array
    {
        $data = [
            'name' => $result['displayName']['text'] ?? null,
            'formatted_address' => $result['formattedAddress'] ?? null,
            'rating' => $result['rating'] ?? null,
            'user_ratings_total' => $result['userRatingCount'] ?? null,
            'editorial_summary' => $result['editorialSummary']['text'] ?? null,
            'description' => null, // Będzie pobrane z Bright Data
            'website' => $result['websiteUri'] ?? null,
            'phone_intl' => $result['internationalPhoneNumber'] ?? null,
            'opening_hours_present' => isset($result['regularOpeningHours']) || isset($result['currentOpeningHours']),
            'types' => $result['types'] ?? [],
            'photos_count' => isset($result['photos']) ? count($result['photos']) : 0,
            'last_photo_epoch_days' => null,
            'products_count' => null,
            'services_count' => null,
            'posts_count_last_30d' => 'unknown',
            'owner_reply_rate_pct' => null,
        ];

        // Pobierz owner_reply_rate z Serper Reviews API (50 najnowszych opinii)
        if ($placeId) {
            $data['owner_reply_rate_pct'] = $this->getOwnerReplyRateFromSerper($placeId);
        }

        // Pobierz dokładną liczbę zdjęć z SerpApi (lepsze niż Google Places API)
        if ($dataId) {
            $serpApiPhotosCount = $this->getPhotosCountFromSerpApi(null, $dataId);
            if ($serpApiPhotosCount !== null) {
                $data['photos_count'] = $serpApiPhotosCount;
                Log::info("Using SerpApi photos count: {$serpApiPhotosCount}");
            }
            
            // Pobierz liczbę postów z ostatnich 30 dni
            $postsCount = $this->getPostsCountLast30DaysFromSerpApi($dataId);
            if ($postsCount !== null) {
                $data['posts_count_last_30d'] = $postsCount;
                Log::info("Using SerpApi posts count (30d): {$postsCount}");
            }
        }

        // Pobierz usługi i opis z Bright Data
        if ($placeId) {
            $brightDataService = new BrightDataService();
            $brightData = $brightDataService->getBusinessData($placeId);
            
            // Zawsze ustawiaj services_count, nawet jeśli jest 0
            if (isset($brightData['services'])) {
                $data['services_count'] = count($brightData['services']);
                Log::info("Bright Data: {$data['services_count']} services found for place_id={$placeId}");
            }
            
            if (!empty($brightData['description'])) {
                $data['description'] = $brightData['description'];
                Log::info("Bright Data: Description found for place_id={$placeId}");
            }
        }

        // Geometry (location)
        if (isset($result['location'])) {
            $data['latitude'] = $result['location']['latitude'] ?? null;
            $data['longitude'] = $result['location']['longitude'] ?? null;
        }

        // Oblicz świeżość ostatniego zdjęcia (jeśli dostępne)
        if (isset($result['photos']) && is_array($result['photos']) && count($result['photos']) > 0) {
            // Nowe API nie ma bezpośrednio daty zdjęć w podstawowym response
            // Można by było użyć authorAttributions[].uri do określenia świeżości
            // Na razie zostawiamy null
            $data['last_photo_epoch_days'] = null;
        }

        return $data;
    }

    /**
     * Oblicz odległość między dwoma punktami w metrach (Haversine)
     */
    private function calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000; // metry

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLng / 2) * sin($dLng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Ustaw konfigurację cache
     */
    public function setCacheConfig(bool $enable, int $ttlHours): void
    {
        $this->enableCache = $enable;
        $this->cacheTtl = $ttlHours;
    }
}
