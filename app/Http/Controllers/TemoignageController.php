<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Temoignage;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class TemoignageController extends Controller
{
    public function store(Request $request)
{
    // Validation des données
    $request->validate([
        'nom' => 'required|string|max:255',
        'email' => 'required|email|max:255',
        'temoignage' => 'required|string',
    ]);

    // Création du témoignage avec le champ "valider" défini à false
    $temoignage = Temoignage::create([
        'nom' => $request->nom,
        'email' => $request->email,
        'temoignage' => $request->temoignage,
        'valider' => false, // Assurer que le témoignage n'est pas validé au départ
    ]);

    // Retourner une réponse JSON
    return response()->json([
        'message' => 'Témoignage ajouté avec succès, en attente de validation !',
        'temoignage' => $temoignage
    ], 201);
}
public function showTemoin()
{
    // Récupérer uniquement les témoignages validés
    $temoignages = Temoignage::where('valider', true)->get();

    // Retourner une réponse JSON
    return response()->json([
        'message' => 'Liste des témoignages validés',
        'temoignages' => $temoignages
    ], 200);
}

public function getAllTemoiniages()
{
    // Vérifier si l'utilisateur est authentifié
    if (!Auth::check()) {
        return response()->json(['error' => 'Utilisateur non authentifié.'], Response::HTTP_UNAUTHORIZED);
    }

    // Récupérer tous les témoignages
    $temoiniages = Temoignage::all();

    return response()->json($temoiniages, Response::HTTP_OK);
}

public function validerTemoiniage($id)
{
    // Vérifier si l'utilisateur est authentifié
    if (!Auth::check()) {
        return response()->json(['error' => 'Utilisateur non authentifié.'], Response::HTTP_UNAUTHORIZED);
    }

    // Trouver le témoignage par ID
    $temoiniage = Temoignage::find($id);

    // Vérifier si le témoignage existe
    if (!$temoiniage) {
        return response()->json(['error' => 'Témoignage introuvable.'], Response::HTTP_NOT_FOUND);
    }

    // Mettre à jour le champ "valider" à true
    $temoiniage->valider = true;
    $temoiniage->save();

    return response()->json(['message' => 'Témoignage validé avec succès.', 'temoiniage' => $temoiniage], Response::HTTP_OK);
}
public function deleteTemoignage($id)
{
    // Récupérer le témoignage par son ID
    $temoignage = Temoignage::find($id);

    if (!$temoignage) {
        return response()->json(['message' => 'Témoignage non trouvé'], 404);
    }

    // Supprimer le témoignage
    $temoignage->delete();

    return response()->json(['message' => 'Témoignage supprimé avec succès'], 200);
}


}
