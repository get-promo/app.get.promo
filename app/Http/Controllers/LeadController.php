<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class LeadController extends Controller
{
    /**
     * Display a listing of leads.
     */
    public function index(Request $request)
    {
        $query = Lead::with('phrases');

        // Wyszukiwanie
        if ($request->has('search')) {
            $query->search($request->search);
        }

        // Filtrowanie po statusie
        if ($request->has('status') && $request->status != '') {
            $query->status($request->status);
        }

        // Sortowanie
        $query->orderBy('created_at', 'desc');

        // Paginacja
        $leads = $query->paginate(15);

        return view('content.leads.index', compact('leads'));
    }

    /**
     * Show the form for creating a new lead.
     */
    public function create()
    {
        return view('content.leads.create');
    }

    /**
     * Store a newly created lead.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'address' => 'nullable|string|max:500',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'rating' => 'nullable|numeric|min:0|max:5',
            'rating_count' => 'nullable|integer|min:0',
            'price_level' => 'nullable|string|max:100',
            'category' => 'nullable|string|max:100',
            'phone_number' => 'nullable|string|max:50',
            'website' => 'nullable|url|max:500',
            'cid' => 'nullable|string|max:100',
            'serper_response' => 'nullable|json',
            'contact_first_name' => 'required|string|max:100',
            'contact_last_name' => 'required|string|max:100',
            'contact_position' => 'nullable|in:właściciel,manager,sekretarka,pracownik',
            'contact_phone' => 'required|string|max:50',
            'contact_email' => 'nullable|email|max:255',
            'notes' => 'nullable|string',
            'phrases' => 'required|string', // JSON string z Tagify
        ]);

        // Dekoduj JSON jeśli jest string
        if (isset($validated['serper_response']) && is_string($validated['serper_response'])) {
            $validated['serper_response'] = json_decode($validated['serper_response'], true);
        }

        // Zapisz frazy osobno
        $phrasesData = $validated['phrases'];
        unset($validated['phrases']);

        $lead = Lead::create($validated);

        // Zapisz frazy
        if (!empty($phrasesData)) {
            $phrases = json_decode($phrasesData, true);
            if (is_array($phrases)) {
                foreach ($phrases as $phraseItem) {
                    $phraseValue = is_array($phraseItem) ? ($phraseItem['value'] ?? '') : $phraseItem;
                    if (!empty($phraseValue)) {
                        $lead->phrases()->create(['phrase' => $phraseValue]);
                    }
                }
            }
        }

        return redirect()->route('leads.index')->with('success', 'Lead został pomyślnie dodany!');
    }

    /**
     * Display the specified lead.
     */
    public function show(Lead $lead)
    {
        $lead->load('phrases');
        return view('content.leads.show', compact('lead'));
    }

    /**
     * Show the form for editing the specified lead.
     */
    public function edit(Lead $lead)
    {
        $lead->load('phrases');
        return view('content.leads.edit', compact('lead'));
    }

    /**
     * Update the specified lead.
     */
    public function update(Request $request, Lead $lead)
    {
        $validated = $request->validate([
            'contact_first_name' => 'sometimes|required|string|max:100',
            'contact_last_name' => 'sometimes|required|string|max:100',
            'contact_position' => 'nullable|in:właściciel,manager,sekretarka,pracownik',
            'contact_phone' => 'sometimes|required|string|max:50',
            'contact_email' => 'nullable|email|max:255',
            'status' => 'sometimes|required|string|max:50',
            'notes' => 'nullable|string',
            'phrases' => 'required|string', // JSON string z Tagify
        ]);

        // Zapisz frazy osobno
        $phrasesData = $validated['phrases'];
        unset($validated['phrases']);

        $lead->update($validated);

        // Aktualizuj frazy - usuń stare i dodaj nowe
        $lead->phrases()->delete();
        
        if (!empty($phrasesData)) {
            $phrases = json_decode($phrasesData, true);
            if (is_array($phrases)) {
                foreach ($phrases as $phraseItem) {
                    $phraseValue = is_array($phraseItem) ? ($phraseItem['value'] ?? '') : $phraseItem;
                    if (!empty($phraseValue)) {
                        $lead->phrases()->create(['phrase' => $phraseValue]);
                    }
                }
            }
        }

        return redirect()->route('leads.index')->with('success', 'Lead został zaktualizowany!');
    }

    /**
     * Remove the specified lead.
     */
    public function destroy(Lead $lead)
    {
        $lead->delete();

        return redirect()->route('leads.index')->with('success', 'Lead został usunięty!');
    }

    /**
     * Search for places using Serper API (AJAX endpoint).
     */
    public function searchPlaces(Request $request)
    {
        $request->validate([
            'query' => 'required|string|min:2'
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
}

