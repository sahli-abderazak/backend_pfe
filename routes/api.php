<?php
use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\OffreController;


Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->post('register', [AuthController::class, 'register']);
Route::middleware('auth:sanctum')->get('users', [UserController::class, 'index']);
Route::middleware('auth:sanctum')->post('logout', [AuthController::class, 'logout']);
Route::delete('users/{id}', [UserController::class, 'destroy']);
Route::middleware('auth:sanctum')->put('/user/update/{id}', [AuthController::class, 'updateAdmin']);



Route::put('/user/updateRec/{id}', [AuthController::class, 'updateRec']);
Route::middleware('auth:sanctum')->put('users/archive/{id}', [UserController::class, 'archiveUser']);

Route::middleware('auth:sanctum')->get('users/archived', [UserController::class, 'getArchivedUsers']);

Route::middleware('auth:sanctum')->get('/user/info', [UserController::class, 'getCurrentUserInfo']);
Route::middleware('auth:sanctum')->put('users/unarchive/{id}', [UserController::class, 'unarchiveUser']);
Route::middleware('auth:sanctum')->get('users/profile', [AuthController::class, 'showProfile']);

//offre
Route::middleware('auth:sanctum')->post('/addOffres', [OffreController::class, 'ajoutOffre']); // Ajouter une offre
Route::middleware('auth:sanctum')->get('/Alloffresnvalide', [OffreController::class, 'afficheOffreNValider']); // Afficher toutes les offres non validée
Route::middleware('auth:sanctum')->get('/AlloffresValide', [OffreController::class, 'afficheOffreValide']); // Afficher toutes les offres validée
Route::middleware('auth:sanctum')->get('/offres-departement', [OffreController::class, 'offresParDepartement']);
Route::middleware('auth:sanctum')->put('/validerOffre/{id}', [OffreController::class, 'validerOffre']);
Route::middleware('auth:sanctum')->delete('/supprimerOffre/{id}', [OffreController::class, 'supprimerOffre']);
Route::middleware('auth:sanctum')->put('/offres-departement/{id}', [OffreController::class, 'modifierOffre']);
Route::middleware('auth:sanctum')->put('/prolonger-offre/{id}', [OffreController::class, 'prolongerOffre']);
Route::middleware('auth:sanctum')->get('/AlloffresExpiree', [OffreController::class, 'afficheOffreExpiree']); // Afficher toutes les offres expirées
Route::middleware('auth:sanctum')->get('/offres-expirees-departement', [OffreController::class, 'afficheOffreExpireeRec']);




