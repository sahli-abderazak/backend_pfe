<?php

namespace App\Http\Controllers;

use App\Events\UserTyping;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TypingController extends Controller
{
    public function typing(Request $request)
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'Non authentifié'], 401);
        }

        $request->validate([
            'to_user_id' => 'required|exists:users,id',
            'is_typing' => 'required|boolean'
        ]);

        try {
            // Déclencher l'événement de typing
            event(new UserTyping(Auth::id(), $request->to_user_id, $request->is_typing));
            
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}

