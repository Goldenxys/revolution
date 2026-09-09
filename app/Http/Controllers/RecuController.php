<?php

namespace App\Http\Controllers;

use App\Models\Commande;
use App\Support\RecuPdf;
use Symfony\Component\HttpFoundation\Response;

/**
 * Lien public du reçu (§7.3) — porté par le jeton `recu_token`, jamais par
 * un id devinable. Non indexé, affiché dans le navigateur. Le jeton lui-même
 * est le secret ; il reste valable 90 jours après la validation.
 */
class RecuController extends Controller
{
    public function afficher(string $token): Response
    {
        $commande = Commande::with(['client', 'lignes'])
            ->where('recu_token', $token)
            ->whereNotNull('validee_at')
            ->firstOrFail();

        abort_if($commande->validee_at->lt(now()->subDays(90)), 410, 'Ce lien de reçu a expiré.');

        return response(RecuPdf::contenu($commande), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$commande->reference.'.pdf"',
            'X-Robots-Tag' => 'noindex, nofollow',
        ]);
    }
}
