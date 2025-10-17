<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\Report;
use App\Models\ReportGenerationJob;
use App\Jobs\GenerateReportJob;
use App\Services\Report\ReportPublicTransformer;
use App\Services\Report\FourPillarTransformer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ReportController extends Controller
{
    /**
     * Uruchom generowanie raportu asynchronicznie
     */
    public function generate(Request $request, Lead $lead)
    {
        try {
            // Sprawdź czy lead ma frazy
            $firstPhrase = $lead->phrases()->first();
            if (!$firstPhrase) {
                return redirect()->route('leads.show', $lead)
                    ->with('error', 'Lead nie ma przypisanych fraz. Dodaj frazę aby wygenerować raport.');
            }

            // Sprawdź czy nie ma już aktywnego zadania dla tego leada
            $activeJob = ReportGenerationJob::where('lead_id', $lead->id)
                ->whereIn('status', ['pending', 'processing'])
                ->first();

            if ($activeJob) {
                return redirect()->route('leads.show', $lead)
                    ->with('info', 'Raport dla tego leada jest już generowany.')
                    ->with('job_id', $activeJob->job_id);
            }

            // Utwórz rekord zadania
            $jobRecord = ReportGenerationJob::create([
                'lead_id' => $lead->id,
                'job_id' => ReportGenerationJob::generateJobId(),
                'status' => 'pending',
                'progress_percentage' => 0,
            ]);

            // Uruchom job
            GenerateReportJob::dispatch($lead, $jobRecord);

            return redirect()->route('leads.show', $lead)
                ->with('success', 'Generowanie raportu zostało uruchomione!')
                ->with('job_id', $jobRecord->job_id);

        } catch (\Exception $e) {
            Log::error("Failed to start report generation: " . $e->getMessage());
            return redirect()->route('leads.show', $lead)
                ->with('error', 'Nie udało się uruchomić generowania raportu: ' . $e->getMessage());
        }
    }

    /**
     * Sprawdź status generowania raportu (API endpoint dla AJAX)
     */
    public function checkStatus(string $jobId)
    {
        $jobRecord = ReportGenerationJob::where('job_id', $jobId)->firstOrFail();

        $response = [
            'status' => $jobRecord->status,
            'progress' => $jobRecord->progress_percentage,
            'current_step' => $jobRecord->current_step,
            'error_message' => $jobRecord->error_message,
        ];

        // Jeśli zakończony, dodaj URL raportu
        if ($jobRecord->status === 'completed' && $jobRecord->report_id) {
            $report = Report::find($jobRecord->report_id);
            if ($report) {
                $response['report_url'] = route('reports.show', ['key' => $report->key]);
                $response['report_key'] = $report->key;
            }
        }

        return response()->json($response);
    }

    /**
     * Pokaż publiczny raport
     */
    public function show(string $key)
    {
        $report = Report::with(['competitors'])->where('key', $key)->firstOrFail();
        
        $report->incrementViews();
        
        // Transformuj dane techniczne na format PUBLIC - NOWY MODEL 4-FILAROWY
        $publicData = FourPillarTransformer::transform([
            'places_data' => $report->places_data,
            'position_score' => $report->position_score,
            'search_query' => $report->search_query,
        ]);

        return view('content.reports.show', compact('report', 'publicData'));
    }
}
