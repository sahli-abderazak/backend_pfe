<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Offre;
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
        ]);

        $offre = Offre::create([
            'departement' => $request->departement,
            'poste' => $request->poste,
            'description' => $request->description,
            'datePublication' => now(), // Date du jour
            'dateExpiration' => $request->dateExpiration,
        ]);

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

    public function offresParDepartement()
    {
        // Récupérer l'utilisateur connecté
        $user = Auth::user();  // Assurez-vous que l'utilisateur est authentifié

        // Récupérer les offres correspondant au département de l'utilisateur
        $offres = Offre::where('departement', $user->departement)->get();

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
        // Trouver l'offre par son ID
        $offre = Offre::find($id);
    
        if (!$offre) {
            return response()->json(['error' => 'Offre non trouvée'], 404);
        }
    
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
        ]);
    
        // Mise à jour des champs envoyés par le client (si ils sont fournis)
        if ($request->has('departement')) {
            $offre->departement = $validatedData['departement'];
        }
    
        if ($request->has('poste')) {
            $offre->poste = $validatedData['poste'];
        }
    
        if ($request->has('description')) {
            $offre->description = $validatedData['description'];
        }
    
        if ($request->has('dateExpiration')) {
            $offre->dateExpiration = $validatedData['dateExpiration'];
        }
    
        // Sauvegarde des modifications
        $offre->save();
    
        return response()->json([
            'message' => 'Offre modifiée avec succès.',
            'offre' => $offre
        ], 200);
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

    // Vérifier si l'utilisateur a un département
    if (!$user->departement) {
        return response()->json(['error' => 'Le département de l\'utilisateur n\'est pas défini.'], 400);
    }

    // Récupérer les offres expirées dont le département correspond à celui de l'utilisateur
    $offres = Offre::where('departement', $user->departement)
                   ->where('dateExpiration', '<', now())  // Vérifier que la date d'expiration est passée
                   ->get();

    return response()->json($offres);
}


    
}