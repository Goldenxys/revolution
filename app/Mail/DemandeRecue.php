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
 * « Votre demande est bien reçue » — envoyé à la cliente à la soumission,
 * si elle a laissé un e-mail (§7.1, ligne 1). Récapitulatif de ses
 * souhaits, mention que la gérante confirme sous peu. AUCUN montant ferme.
 */
class DemandeRecue extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Commande $commande)
    {
        $this->commande->loadMissing('client');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Votre demande est bien reçue — RÉVOLUTION',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.demande-recue-cliente',
            with: [
                'commande' => $this->commande,
                'client' => $this->commande->client,
            ],
        );
    }
}
