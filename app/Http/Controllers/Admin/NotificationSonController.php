<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationSonController extends Controller
{
    /**
     * Nombre de notifications non lues de la gérante connectée — utilisé par
     * le tableau de bord pour déclencher le son d'alerte dès qu'une nouvelle
     * commande arrive, sans attendre le prochain rechargement de page.
     */
    public function compte(Request $request): JsonResponse
    {
        return response()->json([
            'compte' => $request->user()->unreadNotifications()->count(),
        ]);
    }
}
