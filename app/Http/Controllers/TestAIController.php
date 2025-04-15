<?php

namespace App\Http\Controllers;

use App\Models\PersonnaliteAnalyse;
use App\Models\ScoreTest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Smalot\PdfParser\Parser;
use App\Models\Candidat;
use App\Models\Offre;
class TestAIController extends Controller
{
    public function generateTest(Request $request)
    {
        // Vérifier si le candidat et l'offre existent
        $candidat = Candidat::find($request->candidat_id);
        $offre = Offre::find($request->offre_id);
    
        if (!$candidat || !$offre) {
            return response()->json(['error' => 'Candidat ou offre non trouvé'], 404);
        }
    
        // Vérifier si le candidat a déjà passé le test pour cette offre
        $existingScore = ScoreTest::where('candidat_id', $request->candidat_id)
            ->where('offre_id', $request->offre_id)
            ->first();
    
        if ($existingScore) {
            return response()->json([
                'error' => 'Vous avez déjà passé le test pour cette offre.',
                'score' => $existingScore->score_total
            ], 403);
        }
    
        // Appeler FastAPI (qui ne prend pas de paramètres pour cette route)
        try {
            $response = Http::timeout(60)->post('http://127.0.0.1:8002/generate-test');
        } catch (\Exception $e) {
            return response()->json(['error' => 'Erreur lors de l\'appel à FastAPI: ' . $e->getMessage()], 500);
        }
    
        if (!$response->successful()) {
            return response()->json([
                'error' => 'Erreur lors de la génération du test.',
                'details' => $response->body()
            ], $response->status());
        }
    
        $questions = $response->json();
    
        return response()->json($questions);
    }
    public function storeScore(Request $request)
    {
        // Validate request
        $validated = $request->validate([
            'candidat_id' => 'required|exists:candidats,id',
            'offre_id' => 'required|exists:offres,id',
            'score' => 'required|integer|min:0',
        ]);

        try {            // Check if a score already exists for this candidate and offer
            $existingScore = ScoreTest::where('candidat_id', $request->candidat_id)
                ->where('offre_id', $request->offre_id)
                ->first();
                
            if ($existingScore) {
                // Update existing score
                $existingScore->score_total = $request->score;
                $existingScore->save();
                
                return response()->json([
                    'message' => 'Score mis à jour avec succès',
                    'score' => $existingScore
                ]);
            } else {
                // Create new score
                $score = new ScoreTest();
                $score->candidat_id = $request->candidat_id;
                $score->offre_id = $request->offre_id;
                $score->score_total = $request->score;
                $score->save();
                
                return response()->json([
                    'message' => 'Score enregistré avec succès',
                    'score' => $score
                ]);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => 'Erreur lors de l\'enregistrement du score: ' . $e->getMessage()], 500);
        }
    }


    public function generateImageQuestion(Request $request)
    {
        // Récupérer le candidat et l'offre depuis la base de données
        $candidat = Candidat::find($request->candidat_id);
        $offre = Offre::find($request->offre_id);
    
        if (!$candidat || !$offre) {
            return response()->json(['error' => 'Candidat ou offre non trouvé'], 404);
        }
    
        // Vérifier si le fichier PDF du CV existe
        $cvPath = storage_path('app/public/' . $candidat->cv);
        if (!file_exists($cvPath)) {
            return response()->json(['error' => 'Fichier CV introuvable'], 404);
        }
    
        // Convertir le CV PDF en texte
        try {
            $pdfParser = new Parser();
            $pdf = $pdfParser->parseFile($cvPath);
            $cv_text = $pdf->getText();
        } catch (\Exception $e) {
            return response()->json(['error' => 'Erreur lors de la lecture du CV : ' . $e->getMessage()], 500);
        }
    
        // Envoyer à FastAPI pour générer la question-image
        $response = Http::post('http://127.0.0.1:8002/generate-image-question', [
            'cv' => $cv_text,
            'offre' => $offre->description
        ]);
    
        if ($response->successful()) {
            return response()->json($response->json());
        } else {
            return response()->json(['error' => 'Erreur lors de la génération de l\'image'], 500);
        }
    }
    public function analyzePersonality(Request $request)
    {
        // Validation des entrées
        $validated = $request->validate([
            'image_url' => 'required|string',
            'image_prompt' => 'required|string',
            'description' => 'required|string',
            'candidat_id' => 'required|exists:candidats,id', // Assurez-vous que candidat existe
            'offre_id' => 'required|exists:offres,id', // Assurez-vous que l'offre existe
        ]);

        // Préparer les données pour l'appel API
        $data = [
            'image_url' => $validated['image_url'],
            'image_prompt' => $validated['image_prompt'],
            'description' => $validated['description'],
        ];

        // Effectuer la requête HTTP vers l'API FastAPI
        try {
            $response = Http::post('http://127.0.0.1:8002/analyze-personality', $data);

            // Vérifier si la requête a réussi
            if ($response->successful()) {
                $personalityAnalysis = $response->json()['personality_analysis'];

                // Enregistrer l'analyse de personnalité dans la base de données
                PersonnaliteAnalyse::create([
                    'candidat_id' => $validated['candidat_id'],
                    'offre_id' => $validated['offre_id'],
                    'personnalite' => $personalityAnalysis,
                ]);

                // Retourner la réponse JSON
                return response()->json([
                    'personality_analysis' => $personalityAnalysis
                ]);
            } else {
                return response()->json(['error' => 'Erreur lors de l\'analyse de la personnalité'], 500);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => 'Erreur interne : ' . $e->getMessage()], 500);
        }
    }
    
}
