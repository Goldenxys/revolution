<?php

namespace App\Mail;

use App\Models\Commande;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * E-mail à la gérante à la soumission d'une demande V2 (§7.1, ligne 2) :
 * coordonnées, souhaits, livraison, et un lien direct vers le compositeur.
 * Aucun montant ferme — la demande n'est pas encore une vente.
 */
class DemandeDeposee extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Commande $commande,
        public string $urlCompositeur,
    ) {
        $this->commande->loadMissing('client');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "🔔 Nouvelle commande à valider — {$this->commande->reference}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.demande-deposee',
            with: [
                'commande' => $this->commande,
                'client' => $this->commande->client,
                'url' => $this->urlCompositeur,
            ],
        );
    }
}
