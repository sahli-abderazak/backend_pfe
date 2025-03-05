<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Temoignage;


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


}
