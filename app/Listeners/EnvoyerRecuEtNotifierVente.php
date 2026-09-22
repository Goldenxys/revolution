<?php

namespace App\Listeners;

use App\Events\CommandeValidee;
use App\Mail\RecuCommande;
use App\Models\Commande;
use App\Models\CommandeJournal;
use App\Support\RecuPdf;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Auditeur de CommandeValidee (§5.3, dernière ligne : « après le commit, en
 * file d'attente ») : génère le reçu PDF et l'envoie à la cliente. Part dès
 * la validation — la gérante peut transmettre le reçu avant même que le
 * colis soit livré. La notification « vente réalisée » à la gérante, elle,
 * attend la livraison confirmée (App\Listeners\NotifierVenteLivree). Une
 * vente ne doit jamais dépendre d'un SMTP lent — d'où la file.
 */
class EnvoyerRecuEtNotifierVente implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(CommandeValidee $event): void
    {
        $commande = $event->commande->fresh(['client', 'lignes']);

        if (! $commande || $commande->statut === 'annulee') {
            return;
        }

        try {
            RecuPdf::generer($commande);
            CommandeJournal::consigner($commande, 'pdf_genere');
        } catch (\Throwable $e) {
            Log::error('Reçu PDF non généré', ['commande' => $commande->reference, 'erreur' => $e->getMessage()]);
        }

        // Garde-fou anti-doublon : si CommandeValidee était (re)livré une
        // deuxième fois pour la même commande — retry de file, événement
        // rejoué — l'e-mail ne repart pas une seconde fois. Le bouton
        // « Renvoyer le reçu » (ViewCommande) reste, lui, volontaire et non
        // concerné : il consigne un type distinct ('recu_renvoi').
        if (filled($commande->client?->email) && ! $this->dejaEnvoye($commande, 'recu')) {
            try {
                Mail::to($commande->client->email)->queue(new RecuCommande($commande));
                CommandeJournal::consigner($commande, 'email_envoye', ['destinataire' => 'cliente', 'type' => 'recu']);
            } catch (\Throwable $e) {
                Log::error('Reçu cliente non envoyé', ['commande' => $commande->reference, 'erreur' => $e->getMessage()]);
            }
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
}
