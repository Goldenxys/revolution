<?php

namespace App\Listeners;

use App\Events\CommandeValidee;
use App\Mail\RecuCommande;
use App\Mail\VenteRealisee;
use App\Models\Commande;
use App\Models\CommandeJournal;
use App\Models\Parametre;
use App\Support\RecuPdf;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Auditeur de CommandeValidee (§5.3, dernière ligne : « après le commit, en
 * file d'attente ») : génère le reçu PDF, envoie le reçu à la cliente et
 * l'e-mail « nouvelle vente réalisée » à la gérante avec le cumul du jour.
 * Une vente ne doit jamais dépendre d'un SMTP lent — d'où la file.
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
        // rejoué — aucun des deux e-mails ne repart une seconde fois. Le
        // bouton « Renvoyer le reçu » (ViewCommande) reste, lui, volontaire
        // et non concerné : il consigne un type distinct ('recu_renvoi').
        if (filled($commande->client?->email) && ! $this->dejaEnvoye($commande, 'recu')) {
            try {
                Mail::to($commande->client->email)->queue(new RecuCommande($commande));
                CommandeJournal::consigner($commande, 'email_envoye', ['destinataire' => 'cliente', 'type' => 'recu']);
            } catch (\Throwable $e) {
                Log::error('Reçu cliente non envoyé', ['commande' => $commande->reference, 'erreur' => $e->getMessage()]);
            }
        }

        if (! $this->dejaEnvoye($commande, 'vente_realisee')) {
            try {
                Mail::to(Parametre::emailReception())->queue(new VenteRealisee($commande, $this->caDuJour($commande)));
                CommandeJournal::consigner($commande, 'email_envoye', ['destinataire' => 'gerante', 'type' => 'vente_realisee']);
            } catch (\Throwable $e) {
                Log::error('E-mail « vente réalisée » non envoyé', ['commande' => $commande->reference, 'erreur' => $e->getMessage()]);
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

    /**
     * Chiffre d'affaires cumulé du jour de validation — hors livraison,
     * commandes annulées exclues (scope validees()).
     */
    private function caDuJour(Commande $commande): int
    {
        return (int) Commande::query()
            ->validees()
            ->whereDate('validee_at', $commande->validee_at->toDateString())
            ->sum('total_articles');
    }
}
