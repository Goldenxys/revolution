<?php

namespace App\Http\Controllers;

use App\Models\ReductionFidelite;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

/**
 * Téléchargement de la carte de fidélité — porté par un jeton opaque
 * (jamais un id devinable), même principe que le reçu (RecuController).
 * Fichier stocké sur le disque local, jamais exposé par son chemin.
 */
class CarteFideliteController extends Controller
{
    public function telecharger(string $token): Response
    {
        $reduction = ReductionFidelite::where('token', $token)->firstOrFail();

        abort_unless(Storage::disk('local')->exists($reduction->chemin_fichier), 404);

        $nomFichier = 'carte-fidelite-revolution-'.$reduction->numero_commande.'.png';

        return response(Storage::disk('local')->get($reduction->chemin_fichier), 200, [
            'Content-Type' => 'image/png',
            'Content-Disposition' => 'attachment; filename="'.$nomFichier.'"',
            'X-Robots-Tag' => 'noindex, nofollow',
        ]);
    }
}
