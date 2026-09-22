<?php

namespace App\Listeners;

use App\Events\CommandeLivree;
use App\Mail\VenteRealisee;
use App\Models\Commande;
use App\Models\CommandeJournal;
use App\Models\Parametre;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Auditeur de CommandeLivree : envoie à la gérante l'e-mail « nouvelle
 * vente réalisée » avec le cumul du jour — désormais rattaché à la
 * livraison confirmée, le vrai fait comptable, pas à la validation.
 */
class NotifierVenteLivree implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(CommandeLivree $event): void
    {
        $commande = $event->commande->fresh(['client', 'lignes']);

        if (! $commande || $commande->statut === 'annulee') {
            return;
        }

        if ($this->dejaEnvoye($commande, 'vente_realisee')) {
            return;
        }

        try {
            Mail::to(Parametre::emailReception())->queue(new VenteRealisee($commande, $this->caDuJour($commande)));
            CommandeJournal::consigner($commande, 'email_envoye', ['destinataire' => 'gerante', 'type' => 'vente_realisee']);
        } catch (\Throwable $e) {
            Log::error('E-mail « vente réalisée » non envoyé', ['commande' => $commande->reference, 'erreur' => $e->getMessage()]);
        }
    }

    private function dejaEnvoye(Commande $commande, string $type): bool
    {
        return CommandeJournal::query()
            ->where('commande_id', $commande->id)
            ->where('evenement', 'email_envoye')
            ->where('details->type', $type)
            ->exists();
    }

    /**
     * Chiffre d'affaires cumulé du jour de livraison — commandes annulées
     * exclues (scope comptees()).
     */
    private function caDuJour(Commande $commande): int
    {
        return (int) Commande::query()
            ->comptees()
            ->whereDate('livree_at', $commande->livree_at->toDateString())
            ->sum('total_articles');
    }
}
