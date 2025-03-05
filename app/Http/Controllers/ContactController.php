<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function store(Request $request)
    {
        // Validation des données
        $request->validate([
            'nom' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'sujet' => 'required|string|max:255',
            'message' => 'required|string',
        ]);

        // Création du contact dans la base de données
        $contact = Contact::create([
            'nom' => $request->nom,
            'email' => $request->email,
            'sujet' => $request->sujet,
            'message' => $request->message,
        ]);

        // Retourner une réponse JSON (ou redirection si utilisé en web)
        return response()->json([
            'message' => 'Message envoyé avec succès !',
            'contact' => $contact
        ], 201);
    }
}