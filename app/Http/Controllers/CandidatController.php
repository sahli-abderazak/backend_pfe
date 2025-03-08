<?php

namespace App\Http\Controllers;

use App\Models\Candidat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class CandidatController extends Controller
{

    /**
     * Ajouter un candidat.
     */
    public function storeCandidat(Request $request)
    {
        $request->validate([
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'email' => 'required|email|unique:candidats',
            'pays' => 'required|string|max:255',
            'ville' => 'required|string|max:255',
            'codePostal' => 'required|string|max:10',
            'tel' => 'required|string|max:20',
            'niveauEtude' => 'required|string|max:255',
            'niveauExperience' => 'nullable|string|max:255',
            'cv' => 'required|file|mimes:pdf,doc,docx|max:2048',
            'offre_id' => 'required|exists:offres,id',
        ]);

        if ($request->hasFile('cv')) {
            $cvPath = $request->file('cv')->store('cvs', 'public'); // Sauvegarde dans storage/app/public/cvs
        }

        $candidat = Candidat::create([
            'nom' => $request->nom,
            'prenom' => $request->prenom,
            'email' => $request->email,
            'pays' => $request->pays,
            'ville' => $request->ville,
            'codePostal' => $request->codePostal,
            'tel' => $request->tel,
            'niveauEtude' => $request->niveauEtude,
            'niveauExperience' => $request->niveauExperience,
            'cv' => $cvPath ?? null,
            'offre_id' => $request->offre_id,
        ]);

        return response()->json($candidat, 201);
    }
    public function showcandidatOffre()
    {
        $user = Auth::user(); // Récupérer l'utilisateur connecté
    
        if (!$user) {
            return response()->json(['message' => 'Utilisateur non authentifié'], 401);
        }
    
        // Filtrer les candidats non archivés dont l'offre appartient à la même société que l'utilisateur connecté
        $candidats = Candidat::where('archived', 0)
            ->whereHas('offre', function ($query) use ($user) {
                $query->where('societe', $user->nom_societe);
            })
            ->with(['offre:id,departement,domaine,datePublication,poste'])
            ->get();
    
        // Ajouter le chemin du CV pour chaque candidat
        foreach ($candidats as $candidat) {
            $candidat->cv = $candidat->cv ? asset('storage/' . $candidat->cv) : null;
        }
    
        return response()->json($candidats);
    }
    public function archiverCandidat($id)
    {
        // Récupérer le candidat par son ID
        $candidat = Candidat::find($id);
    
        if (!$candidat) {
            return response()->json(['message' => 'Candidat non trouvé'], 404);
        }
    
        // Mettre à jour le champ "archived"
        $candidat->archived = true;
        $candidat->save();
    
        return response()->json(['message' => 'Candidat archivé avec succès', 'candidat' => $candidat], 200);
    }

    public function getArchivedCandidatesByCompany(Request $request)
{
    $user = Auth::user(); // Récupérer l'utilisateur connecté

    if (!$user) {
        return response()->json(['message' => 'Utilisateur non authentifié'], 401);
    }

    // Filtrer les candidats non archivés dont l'offre appartient à la même société que l'utilisateur connecté
    $candidats = Candidat::where('archived', 1)
        ->whereHas('offre', function ($query) use ($user) {
            $query->where('societe', $user->nom_societe);
        })
        ->with(['offre:id,departement,domaine,datePublication,poste'])
        ->get();

    // Ajouter le chemin du CV pour chaque candidat
    foreach ($candidats as $candidat) {
        $candidat->cv = $candidat->cv ? asset('storage/' . $candidat->cv) : null;
    }

    return response()->json($candidats);
}

public function desarchiverCandidat($id)
    {
        // Récupérer le candidat par son ID
        $candidat = Candidat::find($id);
    
        if (!$candidat) {
            return response()->json(['message' => 'Candidat non trouvé'], 404);
        }
    
        // Mettre à jour le champ "archived"
        $candidat->archived = false;
        $candidat->save();
    
        return response()->json(['message' => 'Candidat archivé avec succès', 'candidat' => $candidat], 200);
    }
    
}
