<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Carbon\Carbon;

use Illuminate\Http\Request;
use App\Models\Offre;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use OpenApi\Annotations as OA;
class OffreController extends Controller
{
  /**
 * @OA\Post(
 *     path="/api/addOffres",
 *     summary="Ajouter une offre",
 *     tags={"Offres"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"departement", "poste", "description", "dateExpiration"},
 *             @OA\Property(property="departement", type="string", example="Informatique"),
 *             @OA\Property(property="poste", type="string", example="Développeur Backend"),
 *             @OA\Property(property="description", type="string", example="Développement en Laravel"),
 *             @OA\Property(property="dateExpiration", type="string", format="date", example="2025-05-01")
 *         )
 *     ),
 *     @OA\Response(response=201, description="Offre ajoutée avec succès"),
 *     @OA\Response(response=422, description="Erreur de validation"),
 * )
 */  
public function ajoutOffre(Request $request)
{
    $request->validate([
        'departement' => 'required|string|max:255',
        'poste' => 'required|string|max:255',
        'description' => 'required|string',
        'dateExpiration' => 'required|date|after:today',
        'typePoste' => 'required|string|max:255',
        'typeTravail' => 'required|string|max:255',
        'heureTravail' => 'required|string|max:255',
        'niveauExperience' => 'required|string|max:255',
        'niveauEtude' => 'required|string|max:255',
        'pays' => 'required|string|max:255',
        'ville' => 'required|string|max:255',
        'societe' => 'required|string|max:255',
        'domaine' => 'required|string|max:255',
        'responsabilite' => 'required|string',
        'experience' => 'required|string',
    ]);

    $offre = Offre::create([
        'departement' => $request->departement,
        'poste' => $request->poste,
        'description' => $request->description,
        'datePublication' => now(), // Date du jour
        'dateExpiration' => $request->dateExpiration,
        'valider' => false, // Par défaut, l'offre n'est pas validée
        'typePoste' => $request->typePoste,
        'typeTravail' => $request->typeTravail,
        'heureTravail' => $request->heureTravail,
        'niveauExperience' => $request->niveauExperience,
        'niveauEtude' => $request->niveauEtude,
        'pays' => $request->pays,
        'ville' => $request->ville,
        'societe' => $request->societe,
        'domaine' => $request->domaine,
        'responsabilite' => $request->responsabilite,
        'experience' => $request->experience,
    ]);

    // Get the authenticated user (recruiter)
    $recruiter = auth()->user();
    
    // Create notifications for all admins
    $admins = User::where('role', 'admin')->get();
    foreach ($admins as $admin) {
        Notification::create([
            'type' => 'new_job_offer',
            'message' => "Nouvelle offre d'emploi ajoutée: {$request->poste} chez {$request->societe}",
            'data' => [
                'offer_id' => $offre->id,
                'position' => $offre->poste,
                'company' => $offre->societe,
                'department' => $offre->departement,
                'recruiter_id' => $recruiter->id,
                'recruiter_name' => $recruiter->nom . ' ' . $recruiter->prenom,
            ],
            'user_id' => $admin->id,
            'read' => false,
        ]);
    }

    return response()->json([
        'message' => 'Offre ajoutée avec succès',
        'offre' => $offre
    ], 201);
}


/**
 * @OA\Get(
 *     path="/api/AlloffresValide",
 *     summary="Récupérer toutes les offres validées et non expirées",
     *     @OA\Response(
     *         response=200,
     *         description="Liste des offres valides",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/Offre")
     *         )
     *     )
     * )
     */
 
    public function afficheOffreValide()
    {
        // Récupérer uniquement les offres validées
        $offres = Offre::where('valider', true)->where('dateExpiration', '>', now())  // Exclure les offres expirées
        ->get();
    
        return response()->json($offres);
    }

    /**
 * @OA\Get(
 *     path="/api/Alloffresnvalide",
 *     summary="Afficher les offres non validées et non expirées",
 *     tags={"Offres"},
 *     @OA\Response(response=200, description="Liste des offres non validées et non expirées"),
 * )
 */
    public function afficheOffreNValider()
    {
        $offres = Offre::where('valider', false)->where('dateExpiration', '>', now())  // Exclure les offres expirées
        ->get();
        return response()->json($offres);
    }



/**
 * @OA\Get(
 *     path="/api/offres-departement",
 *     summary="Récupérer les offres selon le département de l'utilisateur connecté",
 *     tags={"Offres"},
 *     security={{"sanctum":{}}},
 *     @OA\Response(response=200, description="Liste des offres du département"),
 *     @OA\Response(response=401, description="Utilisateur non authentifié"),
 * )
 */

 public function offresParSociete() {
    // Récupérer l'utilisateur connecté
    $user = Auth::user();

    // Vérifier que l'utilisateur est authentifié
    if (!$user) {
        return response()->json(['error' => 'Utilisateur non authentifié'], 401);
    }

    // Récupérer les offres correspondant à la société de l'utilisateur
    $offres = Offre::where('societe', $user->nom_societe)->where('valider', 0)->get();

    // Retourner les offres au format JSON
    return response()->json($offres);
}


/**
 * @OA\Put(
 *     path="/api/validerOffre/{id}",
 *     summary="Valider une offre par son ID",
 *     tags={"Offres"},
 *     security={{"sanctum":{}}},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID de l'offre à valider",
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\Response(response=200, description="Offre validée avec succès"),
 *     @OA\Response(response=404, description="Offre non trouvée"),
 * )
 */


 public function validerOffre($id)
 {
     // Récupérer l'offre par son ID
     $offre = Offre::find($id);
 
     // Vérifier si l'offre existe
     if (!$offre) {
         return response()->json(['error' => 'Offre non trouvée.'], 404);
     }
 
     // Mettre à jour l'état de l'offre pour la marquer comme validée
     $offre->valider = true;
     $offre->save();
 
     // Trouver le recruteur qui a la même société que l'offre
     // Nous supposons que le recruteur a le rôle 'recruteur'
     $recruiter = User::where('nom_societe', $offre->societe)
                      ->where('role', 'recruteur')
                      ->first();
 
     if ($recruiter) {
         // Créer une notification pour le recruteur
         Notification::create([
             'type' => 'offer_validated',
             'message' => "Votre offre d'emploi '{$offre->poste}' a été validée",
             'data' => [
                 'offer_id' => $offre->id,
                 'position' => $offre->poste,
                 'department' => $offre->departement,
                 'company' => $offre->societe,
             ],
             'user_id' => $recruiter->id,
             'read' => false,
         ]);
     }
 
     // Retourner une réponse
     return response()->json([
         'message' => 'Offre validée avec succès.',
         'offre' => $offre
     ], 200);
 }


 

/**
 * @OA\Delete(
 *     path="/api/supprimerOffre/{id}",
 *     summary="Supprimer une offre par son ID",
 *     tags={"Offres"},
 *     security={{"sanctum":{}}},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID de l'offre à supprimer",
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\Response(response=200, description="Offre supprimée avec succès"),
 *     @OA\Response(response=404, description="Offre non trouvée"),
 * )
 */



    public function supprimerOffre($id)
    {
        
        // Récupérer l'offre par son ID
        $offre = Offre::find($id);

        // Vérifier si l'offre existe
        if (!$offre) {
            return response()->json(['error' => 'Offre non trouvée.'], 404);
        }

        // Supprimer l'offre
        $offre->delete();

        // Retourner une réponse de succès
        return response()->json([
            'message' => 'Offre supprimée avec succès.'
        ], 200);
    }


  /**
 * @OA\Put(
 *     path="/api/offres-departement/{id}",
 *     summary="Modifier une offre non validée",
 *     tags={"Offres"},
 *     security={{"sanctum":{}}},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID de l'offre à modifier",
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             @OA\Property(property="departement", type="string", example="RH"),
 *             @OA\Property(property="poste", type="string", example="Manager"),
 *             @OA\Property(property="description", type="string", example="Gestion des équipes"),
 *             @OA\Property(property="dateExpiration", type="string", format="date", example="2025-07-01")
 *         )
 *     ),
 *     @OA\Response(response=200, description="Offre modifiée avec succès"),
 *     @OA\Response(response=404, description="Offre non trouvée"),
 * )
 */

 public function modifierOffre(Request $request, $id)
 {
     try {
         // Trouver l'offre par son ID ou renvoyer une erreur 404 si elle n'existe pas
         $offre = Offre::findOrFail($id);
 
         // Vérifier si l'offre est déjà validée
         if ($offre->valider) {
             return response()->json(['error' => 'Cette offre ne peut pas être modifiée car elle est déjà validée.'], 400);
         }
 
         // Validation des données envoyées par la requête
         $validatedData = $request->validate([
             'departement' => 'nullable|string|max:255',
             'poste' => 'nullable|string|max:255',
             'description' => 'nullable|string',
             'dateExpiration' => 'nullable|date|after:today',
             'typePoste' => 'nullable|string|max:255',
             'typeTravail' => 'nullable|string|max:255',
             'heureTravail' => 'nullable|string|max:255',
             'niveauExperience' => 'nullable|string|max:255',
             'niveauEtude' => 'nullable|string|max:255',
             'pays' => 'nullable|string|max:255',
             'ville' => 'nullable|string|max:255',
             'societe' => 'nullable|string|max:255',
             'domaine' => 'nullable|string|max:255',
             'responsabilite' => 'nullable|string',
             'experience' => 'nullable|string',
         ]);
 
         // Mise à jour des champs fournis par la requête
         $offre->update($validatedData);
 
         return response()->json([
             'message' => 'Offre modifiée avec succès.',
             'offre' => $offre
         ], 200);
 
     } catch (\Exception $e) {
         return response()->json([
             'error' => 'Une erreur est survenue lors de la modification de l\'offre.',
             'details' => $e->getMessage()
         ], 500);
     }
 }
 
    

    /**
 * @OA\Put(
 *     path="/api/prolongerOffre/{id}",
 *     summary="Prolonger la date d'expiration d'une offre validée",
 *     tags={"Offres"},
 *     security={{"sanctum":{}}},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID de l'offre à prolonger",
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"dateExpiration"},
 *             @OA\Property(property="dateExpiration", type="string", format="date", example="2025-08-01")
 *         )
 *     ),
 *     @OA\Response(response=200, description="La date d'expiration de l'offre a été prolongée avec succès"),
 *     @OA\Response(response=400, description="Erreur de validation ou l'offre n'est pas validée"),
 *     @OA\Response(response=404, description="Offre non trouvée"),
 * )
 */

    public function prolongerOffre(Request $request, $id)
    {
        // Validation de la date d'expiration
        $validator = Validator::make($request->all(), [
            'dateExpiration' => 'required|date|after:today',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'La date d\'expiration doit être postérieure à aujourd\'hui.',
                'details' => $validator->errors()
            ], 400);
        }

        // Récupération de l'offre
        $offre = Offre::find($id);

        // Vérification de l'existence de l'offre
        if (!$offre) {
            return response()->json([
                'error' => 'Offre non trouvée.'
            ], 404);
        }

        // Vérification que l'offre est validée
        if (!$offre->valider) {
            return response()->json([
                'error' => 'Seules les offres validées peuvent être prolongées.'
            ], 400);
        }

        // Mise à jour de la date d'expiration uniquement
        $offre->dateExpiration = $request->dateExpiration;
        $offre->save();

        return response()->json([
            'message' => 'La date d\'expiration de l\'offre a été prolongée avec succès.',
            'offre' => $offre
        ], 200);
    }



    /**
 * @OA\Get(
 *     path="/api/AlloffresExpiree",
 *     summary="Récupérer toutes les offres expirées",
 *     tags={"Offres"},
 *     @OA\Response(response=200, description="Liste des offres expirées"),
 * )
 */
public function afficheOffreExpiree()
{
    // Récupérer uniquement les offres dont la date d'expiration est passée
    $offres = Offre::where('dateExpiration', '<', now())->get();
    
    // Retourner les offres expirées au format JSON
    return response()->json($offres);
}



/**
 * @OA\Get(
 *     path="/api/offres-expirees",
 *     summary="Afficher les offres expirées pour le département du recruteur",
 *     tags={"Offres"},
 *     security={{"sanctum":{}}},
 *     @OA\Response(
 *         response=200,
 *         description="Liste des offres expirées",
 *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Offre"))
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Le département de l'utilisateur n'est pas défini"
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Non authentifié"
 *     )
 * )
 */

 public function afficheOffreExpireeRec()
 {
     // Récupérer l'utilisateur connecté
     $user = Auth::user();
 
     // Vérifier si l'utilisateur a une société associée
     if (!$user || !$user->nom_societe) {
         return response()->json(['error' => 'La société de l\'utilisateur n\'est pas définie.'], 400);
     }
 
     // Récupérer les offres expirées appartenant à la société de l'utilisateur
     $offres = Offre::where('societe', $user->nom_societe)
                    ->where('dateExpiration', '<', now())  // Vérifier que la date d'expiration est passée
                    ->get();
 
     return response()->json($offres);
 }

//offre-candidat

public function afficherOffreCandidat()
{
    // Récupérer les offres qui ne sont pas encore expirées et qui sont validées
    $offres = Offre::where('dateExpiration', '>', now())
        ->where('valider', 1)
        ->get();

    // Parcourir les offres pour ajouter une clé dynamique "statut" dans la réponse
    $offres->transform(function ($offre) {
        // Utilisation de Carbon pour manipuler la date d'expiration
        $expiration = \Carbon\Carbon::parse($offre->dateExpiration);
        
        // Calculer la différence entre la date actuelle et la date d'expiration
        $diffInDays = now()->diffInDays($expiration, false);
        
        // Ajouter une clé dynamique 'statut' avec la valeur 'urgent' ou 'normal'
        $offre->statut = $diffInDays <= 3 ? 'urgent' : 'normal';

        return $offre;
    });

    // Retourner les offres avec la clé 'statut' dynamique
    return response()->json($offres);
}

public function afficheVillesEtDomainesDistincts()
{
    $villes = Offre::where('valider', 1)->distinct()->pluck('ville');
    $domaines = Offre::where('valider', 1)->distinct()->pluck('domaine');

    return response()->json([
        'villes' => $villes,
        'domaines' => $domaines
    ]);
}
public function rechercheOffresss(Request $request)
{
    $query = Offre::where('valider', 1);

    // Filtrer par poste
    if ($request->has('poste')) {
        $query->where('poste', 'like', '%' . $request->input('poste') . '%');
    }

    // Filtrer par ville
    if ($request->has('ville')) {
        $query->where('ville', 'like', '%' . $request->input('ville') . '%');
    }

    // Filtrer par domaine
    if ($request->has('domaine')) {
        $query->where('domaine', 'like', '%' . $request->input('domaine') . '%');
    }

    // Filtrer par type de poste
    if ($request->has('typePoste')) {
        $typePoste = explode(',', $request->input('typePoste'));
        $query->whereIn('typePoste', $typePoste);
    }

    // Filtrer par date de publication
    if ($request->has('datePublication')) {
        $datePublication = $request->input('datePublication');
        switch ($datePublication) {
            case 'derniere_heure':
                $query->where('created_at', '>=', Carbon::now()->subHour());
                break;
            case '24_heure':
                $query->where('created_at', '>=', Carbon::now()->subDay());
                break;
            case 'derniers_7_jours':
                $query->where('created_at', '>=', Carbon::now()->subDays(7));
                break;
        }
    }

    // Filtrer par niveau d'expérience
    if ($request->has('niveauExperience')) {
        if ($request->input('niveauExperience') === 'plus_de_3ans') {
            // Pour "Plus de 3 ans", filtrer tous les niveaux supérieurs à 3ans
            $query->whereIn('niveauExperience', ['4ans', '5ans', '6ans', '7ans', '8ans', '9ans', '10ans']);
        } elseif ($request->input('niveauExperience') === 'sans_experience') {
            // Pour "Sans expérience", utiliser la valeur exacte de la base de données
            $query->where('niveauExperience', 'Sans expérience');
        } elseif ($request->input('niveauExperience') !== 'tous') {
            // Pour les autres niveaux spécifiques
            $query->where('niveauExperience', $request->input('niveauExperience'));
        }
    }
    // Vérifier également le paramètre niveauExperience_min (pour compatibilité)
    elseif ($request->has('niveauExperience_min')) {
        $query->whereIn('niveauExperience', ['4ans', '5ans', '6ans', '7ans', '8ans', '9ans', '10ans']);
    }

    // Filtrer par type de travail
    if ($request->has('typeTravail')) {
        $query->where('typeTravail', $request->input('typeTravail'));
    }

    $offres = $query->get();
    return response()->json($offres);
}

public function rechercheAcceuil(Request $request)
{
    $query = Offre::where('valider', 1); // Filtrer uniquement les offres validées

    if ($request->has('domaine')) {
        $query->where('domaine', 'like', '%' . $request->input('domaine') . '%');
    }
    if ($request->has('departement')) {
        $query->where('departement', 'like', '%' . $request->input('departement') . '%');
    }

    $offres = $query->get();
    return response()->json($offres);
}

public function afficheDepartementsEtDomainesDistincts()
{
    $departements = Offre::where('valider', 1)->distinct()->pluck('departement');
    $domaines = Offre::where('valider', 1)->distinct()->pluck('domaine');

    return response()->json([
        'departements' => $departements,
        'domaines' => $domaines
    ]);
}

public function showDetail($id)
{
    // Trouver l'offre par son ID
    $offre = Offre::find($id);

    // Vérifier si l'offre existe
    if (!$offre) {
        return response()->json(['message' => 'Offre non trouvée'], 404);
    }

    // Retourner les données de l'offre en JSON
    return response()->json($offre);
}
  

public function getByDepartement($domaine)
{
    // Récupérer les offres du département donné
    $offres = Offre::where('domaine', $domaine)->get();

    // Vérifier si des offres existent
    if ($offres->isEmpty()) {
        return response()->json(['message' => 'Aucune offre trouvée pour ce département'], 404);
    }

    // Retourner les offres en JSON
    return response()->json($offres);
}

public function offreValideRecruteur(Request $request)
{
    $user = $request->user(); // Récupérer l'utilisateur authentifié
    
    // Vérifier si l'utilisateur a une société associée
    if (!$user || !$user->nom_societe) {
        return response()->json(['message' => 'Aucune société associée à cet utilisateur.'], 403);
    }

    // Récupérer les offres validées, non expirées et appartenant à la société du recruteur
    $offres = Offre::where('valider', true)
        ->where('dateExpiration', '>', now())
        ->where('societe', $user->nom_societe) // Filtrer par le nom de la société
        ->get();

    return response()->json($offres);
}

}