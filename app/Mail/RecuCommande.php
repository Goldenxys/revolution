<?php

namespace App\Mail;

use App\Models\Commande;
use App\Support\RecuPdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Le reçu envoyé à la cliente à la validation (§7.1, ligne 3) : lignes
 * détaillées, remise, chiffre d'affaires et livraison identifiés
 * séparément, total à payer, lien vers le PDF (joint également).
 */
class RecuCommande extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Commande $commande)
    {
        $this->commande->loadMissing(['client', 'lignes']);
    }

    public function envelope(): Envelope
    {
        // Volontairement sans référence : « Reçu RÉVOLUTION » suffit, la
        // cliente reconnaît l'e-mail au premier coup d'œil dans sa boîte.
        return new Envelope(
            subject: 'Reçu RÉVOLUTION',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.recu-commande',
            with: [
                'commande' => $this->commande,
                'client' => $this->commande->client,
                'lienRecu' => route('recu.afficher', $this->commande->recu_token),
            ],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [
            Attachment::fromData(fn () => RecuPdf::contenu($this->commande), $this->commande->reference.'.pdf')
                ->withMime('application/pdf'),
        ];
    }
}
