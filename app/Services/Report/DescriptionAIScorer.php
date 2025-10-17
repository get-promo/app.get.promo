<?php

namespace App\Services\Report;

/**
 * Scorer dla jakości opisu (ocena AI)
 * Używany w filarze: Prezentacja (20%)
 * 
 * TODO: Integracja z OpenAI API do oceny jakości opisu
 */
class DescriptionAIScorer
{
    /**
     * Oblicz score AI dla opisu (0.0-5.0)
     * 
     * @param string|null $description Tekst opisu
     * @param array|null $categories Kategorie biznesu
     * @return array ['score' => float, 'note' => string]
     */
    public static function calculate(?string $description, ?array $categories = []): array
    {
        if ($description === null || trim($description) === '') {
            return [
                'score' => 0.5,
                'note' => 'Brak opisu do oceny'
            ];
        }

        // TODO: Implementacja AI scoring przez OpenAI API
        // Na razie prosty heurystyczny algorytm
        
        $score = 3.0; // bazowy
        $notes = [];

        // 1. Długość (optymalnie 150-500 znaków)
        $length = mb_strlen($description);
        if ($length >= 150 && $length <= 500) {
            $score += 0.5;
            $notes[] = 'optymalna długość';
        } elseif ($length < 50) {
            $score -= 1.0;
            $notes[] = 'za krótki';
        }

        // 2. Kompletność zdań (kończy się kropką, wykrzyknikiem lub pytajnikiem)
        if (preg_match('/[.!?]$/', trim($description))) {
            $score += 0.3;
        } else {
            $notes[] = 'niekompletne zdania';
        }

        // 3. Obecność słów kluczowych biznesowych
        $businessKeywords = ['oferujemy', 'specjalizujemy', 'zapewniamy', 'serwis', 'usługi', 'produkty', 'doświadczenie', 'jakość', 'profesjonalnie'];
        $hasKeywords = false;
        foreach ($businessKeywords as $keyword) {
            if (mb_stripos($description, $keyword) !== false) {
                $hasKeywords = true;
                break;
            }
        }
        if ($hasKeywords) {
            $score += 0.4;
            $notes[] = 'zawiera słowa kluczowe';
        }

        // 4. Unika CAPSLOCK i nadmiernej interpunkcji
        $uppercaseRatio = preg_match_all('/[A-ZĘÓĄŚŁŻŹĆŃ]/', $description) / max(1, mb_strlen($description));
        if ($uppercaseRatio > 0.3) {
            $score -= 0.5;
            $notes[] = 'nadmiar wielkich liter';
        }

        if (preg_match('/[!]{2,}/', $description)) {
            $score -= 0.3;
            $notes[] = 'nadmierna interpunkcja';
        }

        // 5. Struktura zdaniowa (zawiera przynajmniej 2 zdania)
        $sentences = preg_split('/[.!?]+/', $description);
        $sentences = array_filter($sentences, function($s) { return trim($s) !== ''; });
        if (count($sentences) >= 2) {
            $score += 0.3;
        }

        $score = max(0.5, min(5.0, $score));
        $note = empty($notes) ? 'Opis standardowy' : 'Opis: ' . implode(', ', $notes);

        return [
            'score' => round($score, 1),
            'note' => $note
        ];
    }

    /**
     * Ocena opisu przez OpenAI API (przyszła implementacja)
     * 
     * @param string $description
     * @param array $categories
     * @return float Score 0.0-5.0
     */
    private static function evaluateWithOpenAI(string $description, array $categories): float
    {
        // TODO: Implementacja
        // Prompt: "Oceń jakość opisu biznesu w skali 0-5 pod względem klarowności, profesjonalizmu i dopasowania do branży: {categories}"
        
        return 3.0;
    }
}

