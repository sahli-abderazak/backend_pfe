<?php

namespace App\Http\Controllers;

use App\Models\Candidat;
use Illuminate\Http\Request;
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


}
