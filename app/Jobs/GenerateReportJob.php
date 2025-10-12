<?php

namespace App\Jobs;

use App\Models\Lead;
use App\Models\Report;
use App\Models\ReportCompetitor;
use App\Models\ReportSetting;
use App\Models\ReportGenerationJob;
use App\Services\Report\PositionScorer;
use App\Services\Report\ProfileQualityScorer;
use App\Services\Report\GooglePlacesService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GenerateReportJob implements ShouldQueue
{
    use Queueable;

    public $timeout = 600; // 10 minut timeout
    public $tries = 1; // Jedna próba

    private Lead $lead;
    private ReportGenerationJob $jobRecord;

    /**
     * Create a new job instance.
     */
    public function __construct(Lead $lead, ReportGenerationJob $jobRecord)
    {
        $this->lead = $lead;
        $this->jobRecord = $jobRecord;
    }

    /**
     * Execute the job.
     */
    public function handle(GooglePlacesService $placesService): void
    {
        try {
            // Oznacz jako rozpoczęty
            $this->jobRecord->markAsStarted();
            $this->jobRecord->updateProgress(5, 'Pobieranie pierwszej frazy...');

            // Pobierz pierwszą frazę
            $firstPhrase = $this->lead->phrases()->first();
            if (!$firstPhrase) {
                throw new \Exception('Lead nie ma przypisanych fraz.');
            }

            // Pobierz ustawienia
            $this->jobRecord->updateProgress(10, 'Pobieranie ustawień...');
            $settings = ReportSetting::get();
            $weights = $settings->getWeightsArray();

            // Pobierz wszystkie wyniki z Serpera (z paginacją)
            $this->jobRecord->updateProgress(15, 'Szukanie w Serper API...');
            $serperResults = $this->fetchAllSerperResults($firstPhrase->phrase, $this->lead);
            
            if (!$serperResults['found']) {
                throw new \Exception('Nie znaleziono lokalu w wynikach Serper dla frazy: ' . $firstPhrase->phrase);
            }

            $realPosition = $serperResults['position'];
            $competitors = $serperResults['competitors'];

            // Pobierz dane Places dla leada
            $this->jobRecord->updateProgress(40, 'Pobieranie danych Google Places dla leada...');
            $leadPlaceId = $placesService->findPlaceId(
                $this->lead->title,
                $this->lead->address,
                $this->lead->latitude,
                $this->lead->longitude,
                $this->lead->cid
            );

            $leadPlacesData = [];
            if ($leadPlaceId) {
                $leadPlacesData = $placesService->getPlaceDetails($leadPlaceId);
            }

            // Oblicz scores dla leada
            $this->jobRecord->updateProgress(50, 'Obliczanie wyników dla leada...');
            $leadPositionScore = PositionScorer::calculate($realPosition);
            $leadQualityResult = ProfileQualityScorer::calculate($leadPlacesData, $weights);

            // Oblicz Position Score dla konkurentów (bez Profile Quality Score - tylko dla leada!)
            $this->jobRecord->updateProgress(55, 'Obliczanie Position Score dla konkurencji...');
            $competitorScores = [];
            $competitorsData = [];

            foreach ($competitors as $comp) {
                $compPositionScore = PositionScorer::calculate($comp['position'] ?? null);

                $competitorScores[] = [
                    'position_score' => $compPositionScore,
                ];

                $competitorsData[] = array_merge($comp, [
                    'place_id' => null, // Nie pobieramy place_id dla konkurentów
                    'position_score' => $compPositionScore,
                    'profile_quality_score' => null, // NIE liczymy dla konkurencji
                    'places_data' => null,
                    'score_breakdown' => null,
                ]);
            }

            // Oblicz średni Position Score konkurencji
            $this->jobRecord->updateProgress(85, 'Obliczanie statystyk konkurencji...');
            $avgCompPositionScore = !empty($competitorScores) 
                ? round(collect($competitorScores)->avg('position_score'), 1) 
                : null;

            // Utwórz raport
            $this->jobRecord->updateProgress(93, 'Tworzenie raportu...');
            $report = Report::create([
                'lead_id' => $this->lead->id,
                'key' => Report::generateKey(),
                'business_name' => $this->lead->title,
                'search_query' => $firstPhrase->phrase,
                'position' => $realPosition,
                'total_results' => $serperResults['total_results'],
                'position_score' => $leadPositionScore,
                'profile_quality_score' => $leadQualityResult['score'],
                'places_data' => $leadPlacesData,
                'score_breakdown' => $leadQualityResult['breakdown'],
                'avg_competitor_position_score' => $avgCompPositionScore,
                'avg_competitor_quality_score' => null, // NIE liczymy dla konkurencji
                'competitors_count' => count($competitorsData),
                'weights_snapshot' => $weights,
                'generated_at' => now(),
            ]);

            // Zapisz konkurentów
            $this->jobRecord->updateProgress(96, 'Zapisywanie konkurentów...');
            foreach ($competitorsData as $compData) {
                ReportCompetitor::create([
                    'report_id' => $report->id,
                    'name' => $compData['name'],
                    'address' => $compData['address'] ?? null,
                    'latitude' => $compData['latitude'] ?? null,
                    'longitude' => $compData['longitude'] ?? null,
                    'position' => $compData['position'] ?? null,
                    'cid' => $compData['cid'] ?? null,
                    'place_id' => $compData['place_id'] ?? null,
                    'position_score' => $compData['position_score'],
                    'profile_quality_score' => $compData['profile_quality_score'],
                    'places_data' => $compData['places_data'],
                    'score_breakdown' => $compData['score_breakdown'],
                ]);
            }

            // Oznacz jako zakończony
            $this->jobRecord->markAsCompleted($report->id);

        } catch (\Exception $e) {
            Log::error("Report generation job failed: " . $e->getMessage(), [
                'lead_id' => $this->lead->id,
                'job_id' => $this->jobRecord->job_id,
                'trace' => $e->getTraceAsString()
            ]);
            
            $this->jobRecord->markAsFailed($e->getMessage());
            throw $e; // Re-throw aby Laravel wiedział że job failed
        }
    }

    /**
     * Pobierz wszystkie wyniki z Serpera z paginacją i znajdź nasz lokal
     */
    private function fetchAllSerperResults(string $query, Lead $lead): array
    {
        $allPlaces = [];
        $page = 1;
        $found = false;
        $ourPosition = null;
        $ourCid = $lead->cid;

        try {
            do {
                // Update progress (15-35% dla Serper queries)
                $pageProgress = 15 + ($page * 2);
                $this->jobRecord->updateProgress(
                    min(35, $pageProgress), 
                    "Pobieranie wyników Serper - strona {$page}..."
                );

                // Zapytaj Serper z paginacją
                $response = Http::withHeaders([
                    'X-API-KEY' => config('services.serper.api_key'),
                    'Content-Type' => 'application/json'
                ])->timeout(30)->post('https://google.serper.dev/places', [
                    'q' => $query,
                    'gl' => 'pl',
                    'hl' => 'pl',
                    'page' => $page
                ]);

                if (!$response->successful()) {
                    Log::error("Serper API failed on page {$page}: " . $response->body());
                    break;
                }

                $data = $response->json();
                $places = $data['places'] ?? [];

                if (empty($places)) {
                    break;
                }

                // Dodaj miejsca do wyników
                foreach ($places as $index => $place) {
                    $globalPosition = (($page - 1) * 10) + $index + 1;
                    
                    $placeData = [
                        'name' => $place['title'] ?? '',
                        'address' => $place['address'] ?? null,
                        'latitude' => $place['latitude'] ?? null,
                        'longitude' => $place['longitude'] ?? null,
                        'position' => $globalPosition,
                        'cid' => $place['cid'] ?? null,
                        'rating' => $place['rating'] ?? null,
                        'rating_count' => $place['ratingCount'] ?? null,
                    ];

                    $allPlaces[] = $placeData;

                    // Sprawdź czy to nasz lokal (po CID)
                    if ($ourCid && isset($place['cid']) && $place['cid'] === $ourCid) {
                        $found = true;
                        $ourPosition = $globalPosition;
                    }
                }

                // Kontynuuj dopóki strona NIE JEST pusta (ma jakiekolwiek wyniki)
                $shouldContinue = count($places) > 0;
                
                $page++;
                
                // Dodaj małe opóźnienie aby nie przekroczyć rate limit
                usleep(500000); // 0.5s

            } while ($shouldContinue && $page <= 20); // Max 20 stron (200 wyników)

            // Usuń nasz lokal z konkurencji
            $competitors = collect($allPlaces)->filter(function($place) use ($ourCid) {
                return !($ourCid && isset($place['cid']) && $place['cid'] === $ourCid);
            })->values()->toArray();

            return [
                'found' => $found,
                'position' => $ourPosition,
                'competitors' => $competitors,
                'total_results' => count($allPlaces),
                'pages_fetched' => $page - 1,
            ];

        } catch (\Exception $e) {
            Log::error("Serper pagination failed: " . $e->getMessage());
            
            return [
                'found' => false,
                'position' => null,
                'competitors' => [],
                'total_results' => 0,
                'pages_fetched' => 0,
            ];
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        $this->jobRecord->markAsFailed($exception->getMessage());
    }
}
