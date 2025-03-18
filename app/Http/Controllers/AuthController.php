<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use App\Mail\RecruiterAdded;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use OpenApi\Annotations as OA;
use Illuminate\Support\Str;
use App\Models\Notification;

/**
 * @OA\Info(
 *      version="1.0.0",
 *      title="API Documentation",
 *      description="Documentation de l'API pour la gestion des utilisateurs et l'authentification.",
 *      @OA\Contact(
 *          email="sahliabderazak530@gmail.com"
 *      ),
 *      @OA\License(
 *          name="MIT",
 *          url="https://opensource.org/licenses/MIT"
 *      )
 * )
 */

class AuthController extends Controller
{
    /**
 * @OA\Get(
 *     path="/api/profile",
 *     summary="Afficher le profil de l'utilisateur connecté",
 *     tags={"Authentification"},
 *     security={{"sanctum": {}}},
 *     @OA\Response(
 *         response=200,
 *         description="Profil utilisateur récupéré avec succès",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="id", type="integer"),
 *             @OA\Property(property="email", type="string"),
 *             @OA\Property(property="departement", type="string"),
 *             @OA\Property(property="nom", type="string"),
 *             @OA\Property(property="prenom", type="string"),
 *             @OA\Property(property="numTel", type="string"),
 *             @OA\Property(property="poste", type="string"),
 *             @OA\Property(property="adresse", type="string"),
 *             @OA\Property(property="image", type="string"),
 *             @OA\Property(property="cv", type="string")
 *         )
 *     ),
 *     @OA\Response(response=401, description="Utilisateur non authentifié"),
 *     @OA\Response(response=404, description="Utilisateur non trouvé")
 * )
 */
public function showProfile(Request $request)
{
    $user = $request->user(); // Récupère l'utilisateur connecté

    if (!$user) {
        return response()->json(['error' => 'Utilisateur non trouvé.'], 404);
    }

    // Vérifie si un CV existe et génère un lien public vers le fichier
    $userData = $user->only([
        'id', 'email', 'departement', 'nom', 'prenom', 'numTel', 'poste', 'adresse', 'image' ,'role' , 'nom_societe' , 'cv'
    ]);

    // Ajoute l'URL du CV si disponible
    $userData['cv'] = $user->cv ? asset('storage/' . $user->cv) : null;
    $userData['image'] = $user->image ? asset('storage/' . $user->image) : null;


    return response()->json($userData, 200);
}


/**
 * @OA\Put(
 *     path="/api/admins/{id}",
 *     summary="Mettre à jour le département et le poste d'un administrateur",
 *     tags={"Administrateurs"},
 *     security={{"sanctum": {}}},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID de l'administrateur à mettre à jour",
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={},
 *             @OA\Property(property="departement", type="string", example="Informatique"),
 *             @OA\Property(property="poste", type="string", example="Manager")
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Mise à jour réussie",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Département et poste mis à jour avec succès.")
 *         )
 *     ),
 *     @OA\Response(response=404, description="Utilisateur non trouvé"),
 *     @OA\Response(response=400, description="Erreur de validation")
 * )
 */

    public function updateAdmin(Request $request, $id)
{
    // Validation des données d'entrée
    $validatedData = $request->validate([
        'departement' => 'nullable|string',
        'poste' => 'nullable|string',
    ]);

    // Récupérer l'utilisateur par son ID
    $user = User::find($id);

    if (!$user) {
        return response()->json(['error' => 'Utilisateur non trouvé.'], 404);
    }

    // Vérifier si le champ département est fourni
    if ($request->has('departement')) {
        $user->departement = $validatedData['departement'];
    }

    // Vérifier si le champ poste est fourni
    if ($request->has('poste')) {
        $user->poste = $validatedData['poste'];
    }

    // Sauvegarder les modifications dans la base de données
    $user->save();

    // Retourner une réponse de succès
    return response()->json(['message' => 'Département et poste mis à jour avec succès.'], 200);
}
/**
 * @OA\Post(
 *     path="/api/logout",
 *     summary="Déconnexion de l'utilisateur",
 *     tags={"Authentification"},
 *     security={{"sanctum": {}}},
 *     @OA\Response(
 *         response=200,
 *         description="Déconnexion réussie",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Logged out successfully")
 *         )
 *     ),
 *     @OA\Response(response=401, description="Utilisateur non authentifié")
 * )
 */


public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out successfully'], 200);
    }


/**
 * @OA\Post(
 *     path="/api/login",
 *     summary="Connexion d'un utilisateur",
 *     tags={"Authentification"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"email", "password"},
 *             @OA\Property(property="email", type="string", format="email", example="user@example.com"),
 *             @OA\Property(property="password", type="string", format="password", example="password123")
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Connexion réussie",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string"),
 *             @OA\Property(property="token", type="string"),
 *             @OA\Property(property="user", type="object")
 *         )
 *     ),
 *     @OA\Response(response=401, description="Identifiants invalides"),
 *     @OA\Response(response=400, description="Erreur de validation")
 * )
 */


 public function login(Request $request)
 {
     $validator = Validator::make($request->all(), [
         'email' => 'required|email',
         'password' => 'required',
     ]);
 
     if ($validator->fails()) {
         return response()->json(['error' => $validator->errors()], 400);
     }
 
     $user = User::where('email', $request->email)->first();
 
     // Vérification si l'utilisateur existe et si le mot de passe est correct
     if (!$user || !Hash::check($request->password, $user->password)) {
         return response()->json(['error' => 'Invalid credentials'], 401);
     }
 
     // Vérification si le compte est actif
     if (!$user->active) {
        return response()->json(['error' => 'Votre compte est inactif. Veuillez vérifier votre compte.'], 403);
    }
 
     $token = $user->createToken('backendPFE')->plainTextToken;
 
     return response()->json([
         'message' => 'Login successful',
         'token' => $token,
         'user' => $user,
     ], 200);
 }
 
 


 

/**
 * @OA\Post(
 *     path="/api/register",
 *     summary="Enregistrer un nouvel utilisateur",
 *     description="Créer un nouvel utilisateur avec une image et un CV",
 *     tags={"Authentification"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="multipart/form-data",
 *             @OA\Schema(
 *                 required={"email", "password", "departement", "nom", "prenom", "numTel", "poste", "adresse", "role", "image", "cv"},
 *                 @OA\Property(property="email", type="string", format="email", example="user@example.com"),
 *                 @OA\Property(property="password", type="string", format="password", example="12345678"),
 *                 @OA\Property(property="departement", type="string", example="Informatique"),
 *                 @OA\Property(property="nom", type="string", example="Doe"),
 *                 @OA\Property(property="prenom", type="string", example="John"),
 *                 @OA\Property(property="numTel", type="string", example="98765432"),
 *                 @OA\Property(property="poste", type="string", example="Développeur"),
 *                 @OA\Property(property="adresse", type="string", example="123 Rue Exemple"),
 *                 @OA\Property(property="role", type="string", example="admin"),
 *                 @OA\Property(property="image", type="string", format="binary"),
 *                 @OA\Property(property="cv", type="string", format="binary")
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=201,
 *         description="Inscription réussie",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Registration successful and email sent!"),
 *             @OA\Property(property="token", type="string", example="2|somerandomtoken"),
 *             @OA\Property(property="user", type="object",
 *                 @OA\Property(property="id", type="integer", example=1),
 *                 @OA\Property(property="email", type="string", example="user@example.com"),
 *                 @OA\Property(property="nom", type="string", example="Doe"),
 *                 @OA\Property(property="prenom", type="string", example="John"),
 *                 @OA\Property(property="image", type="string", example="http://example.com/storage/images/user.jpg"),
 *                 @OA\Property(property="cv", type="string", example="http://example.com/storage/cv/user.pdf")
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Erreur de validation",
 *         @OA\JsonContent(
 *             @OA\Property(property="error", type="object",
 *                 @OA\AdditionalProperties(type="array", @OA\Items(type="string"))
 *             )
 *         )
 *     )
 * )
 */

 public function register(Request $request)
{
    $validator = Validator::make($request->all(), [
        'email' => 'required|email|unique:users,email',
        'password' => 'required|min:8',
        'nom' => 'required|string',
        'prenom' => 'required|string',
        'numTel' => 'required|string',
        'departement' => 'required|string',
        'poste' => 'required|string',
        'adresse' => 'required|string',
        'role' => 'required|string',
        'image' => 'required|file|mimes:jpeg,png,jpg|max:2048',
        'cv' => 'required|file|mimes:pdf|max:5120',
        'nom_societe' => 'required|string|unique:users,nom_societe',
    ]);

    if ($validator->fails()) {
        return response()->json(['error' => $validator->errors()], 400);
    }

    // Stockage des fichiers
    $imagePath = $request->file('image')->store('images', 'public');
    $cvPath = $request->file('cv')->store('cv', 'public');

    // Générer un code de vérification unique
    $verificationCode = Str::random(6);

    // Création de l'utilisateur
    $user = User::create([
        'email' => $request->email,
        'password' => Hash::make($request->password),
        'departement' => $request->departement,
        'nom' => $request->nom,
        'prenom' => $request->prenom,
        'numTel' => $request->numTel,
        'poste' => $request->poste,
        'adresse' => $request->adresse,
        'role' => $request->role,
        'image' => $imagePath,
        'cv' => $cvPath,
        'nom_societe' => $request->nom_societe,
        'active' => false,
        'code_verification' => $verificationCode,
    ]);

    // Envoi de l'email avec le code de vérification
    Mail::to($user->email)->send(new RecruiterAdded($user->nom . ' ' . $user->prenom, $verificationCode));

    // Créer une notification pour les admins
    User::where('role', 'admin')->each(function ($admin) use ($user) {
        Notification::create([
            'type' => 'new_recruiter',
            'message' => "Nouveau recruteur inscrit: {$user->nom} {$user->prenom}",
            'data' => [
                'recruiter_id' => $user->id,
                'recruiter_name' => $user->nom . ' ' . $user->prenom,
                'recruiter_email' => $user->email,
                'company' => $user->nom_societe,
            ],
            'user_id' => $admin->id,
            'read' => false,
        ]);
    });

    // Génération du token d'authentification
    $token = $user->createToken('backendPFE')->plainTextToken;

    return response()->json([
        'message' => 'Registration successful and email sent!',
        'token' => $token,
        'user' => $user,
    ], 201);
}


public function verifyCode(Request $request)
{
    $user = User::where('email', $request->email)->first();

    if (!$user || $user->code_verification !== $request->verification_code) {
        return response()->json(['error' => 'Code de vérification incorrect.'], 400);
    }

    // Mise à jour du statut de l'utilisateur pour activer le compte
    $user->active = true;
    $user->code_verification = null; // Supprimer le code après vérification
    $user->save();

    return response()->json(['message' => 'Votre compte a été activé avec succès.'], 200);
}

public function resendVerificationCodeLogin(Request $request)
{
    // Valider la requête
    $validator = Validator::make($request->all(), [
        'email' => 'required|email|exists:users,email'
    ]);

    if ($validator->fails()) {
        return response()->json(['error' => 'Email non trouvé'], 404);
    }

    // Récupérer l'utilisateur
    $user = User::where('email', $request->email)->first();

    // Vérifier si le compte est déjà actif
    if ($user->active) {
        return response()->json(['error' => 'Ce compte est déjà vérifié'], 400);
    }

    // Générer un nouveau code de vérification
    $newVerificationCode = Str::random(6);

    // Mettre à jour le code de vérification de l'utilisateur
    $user->code_verification = $newVerificationCode;
    $user->save();

    // Renvoyer l'email avec le nouveau code
    Mail::to($user->email)->send(new RecruiterAdded($user->nom . ' ' . $user->prenom, $newVerificationCode));

    return response()->json(['message' => 'Un nouveau code de vérification a été envoyé'], 200);
}




public function updateRec(Request $request)
{
    try {
        // Récupérer l'utilisateur à partir du token
        $user = $request->user();

        if (!$user) {
            return response()->json(['error' => 'Utilisateur non authentifié.'], 401);
        }

        // Validation des données entrantes
        $validatedData = $request->validate([
            'password' => 'nullable|min:8',
            'nom' => 'nullable|string',
            'prenom' => 'nullable|string',
            'numTel' => 'nullable|string',
            'adresse' => 'nullable|string',
            'image' => 'nullable|file|mimes:jpeg,png,jpg|max:2048',
            'cv' => 'nullable|file|mimes:pdf|max:5120',
        ]);

       

        if (isset($validatedData['password'])) {
            $user->password = Hash::make($validatedData['password']);
        }

        if (isset($validatedData['nom'])) {
            $user->nom = $validatedData['nom'];
        }

        if (isset($validatedData['prenom'])) {
            $user->prenom = $validatedData['prenom'];
        }

        if (isset($validatedData['numTel'])) {
            $user->numTel = $validatedData['numTel'];
        }

        if (isset($validatedData['adresse'])) {
            $user->adresse = $validatedData['adresse'];
        }

        // Gérer l'upload de l'image
        if ($request->hasFile('image') && $request->file('image')->isValid()) {
            if ($user->image) {
                Storage::disk('public')->delete($user->image);
            }
            $imagePath = $request->file('image')->store('images', 'public');
            $user->image = $imagePath;
        }

        // Gérer l'upload du CV
        if ($request->hasFile('cv') && $request->file('cv')->isValid()) {
            if ($user->cv) {
                Storage::disk('public')->delete($user->cv);
            }
            $cvPath = $request->file('cv')->store('cv', 'public');
            $user->cv = $cvPath;
        }

        // Sauvegarde des modifications
        $user->save();

        // Préparer la réponse
        $userData = [
            'id' => $user->id,
            'nom' => $user->nom,
            'prenom' => $user->prenom,
            'numTel' => $user->numTel,
            'adresse' => $user->adresse,
            'image' => $user->image ? asset('storage/' . $user->image) : null,
            'cv' => $user->cv ? asset('storage/' . $user->cv) : null,
        ];

        return response()->json([
            'message' => 'Utilisateur mis à jour avec succès.',
            'user' => $userData
        ], 200);

    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json(['error' => $e->errors()], 422);
    } catch (\Exception $e) {
        return response()->json(['error' => 'Une erreur est survenue'], 500);
    }
}






// ✅ 1️⃣ Fonction pour envoyer un code aléatoire par email
public function sendVerificationCode(Request $request)
{
    $request->validate(['email' => 'required|email']);

    $user = User::where('email', $request->email)->first();
    if (!$user) {
        return response()->json(['error' => 'Email non trouvé'], 404);
    }

    $code = rand(100000, 999999); // Générer un code à 6 chiffres
    $user->update(['code_verification' => $code]);

    Mail::raw("Votre code de réinitialisation est : $code", function ($message) use ($user) {
        $message->to($user->email)
            ->subject('Réinitialisation de mot de passe');
    });

    return response()->json(['message' => 'Code envoyé par email'], 200);
}

// ✅ 2️⃣ Fonction pour vérifier si le code est valide
public function verifyCoderest(Request $request)
{
    $request->validate([
        'email' => 'required|email',
        'code_verification' => 'required',
    ]);

    $user = User::where('email', $request->email)
        ->where('code_verification', $request->code_verification)
        ->first();

    if (!$user) {
        return response()->json(['error' => 'Code incorrect ou email invalide'], 400);
    }

    return response()->json(['message' => 'Code valide'], 200);
}

// ✅ 3️⃣ Fonction pour réinitialiser le mot de passe après vérification du code
public function resetPassword(Request $request)
{
    $request->validate([
        'email' => 'required|email',
        'new_password' => 'required|min:6',
    ]);

    $user = User::where('email', $request->email)->first();
    if (!$user) {
        return response()->json(['error' => 'Email non trouvé'], 404);
    }

    if (!$user->code_verification) {
        return response()->json(['error' => 'Aucun code de vérification actif'], 400);
    }

    $user->update([
        'password' => Hash::make($request->new_password),
        'code_verification' => null, // Réinitialiser le code après utilisation
    ]);

    return response()->json(['message' => 'Mot de passe réinitialisé avec succès'], 200);
}

}