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
 * « ✅ Nouvelle vente réalisée » — l'e-mail que la gérante lira le plus
 * (§7.1, ligne 4). Court, avec le CA de la vente, les frais de livraison à
 * part, et surtout le CUMUL DU JOUR : c'est son tableau de bord de poche.
 */
class VenteRealisee extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Commande $commande,
        public int $caDuJour,
    ) {
        $this->commande->loadMissing(['client', 'lignes']);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '✅ Nouvelle vente réalisée — '.number_format($this->commande->total_articles, 0, ',', ' ').' F',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.vente-realisee',
            with: [
                'commande' => $this->commande,
                'client' => $this->commande->client,
                'caDuJour' => $this->caDuJour,
            ],
        );
    }
}
