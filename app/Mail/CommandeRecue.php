<?php

namespace App\Mail;

use App\Models\Commande;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CommandeRecue extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Mise en file d'attente pour que la cliente n'attende jamais le SMTP :
     * la commande se confirme immédiatement, le mail part en tâche de fond.
     */
    public function __construct(public Commande $commande)
    {
        $this->commande->loadMissing(['client', 'lignes.article.collection']);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Super Nouvelle commande — {$this->commande->client->nom} · {$this->commande->libelleCollection()}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.commande-recue',
            with: [
                'commande' => $this->commande,
                'client' => $this->commande->client,
            ],
        );
    }

    /**
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
