<?php

namespace App\Mail;

use App\Models\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

/**
 * « Anniversaire(s) dans 2 jours » — envoyé à la gérante
 * (Parametre::emailReception()) par la commande planifiée
 * revolution:alerter-anniversaires-proches, pour qu'elle puisse proposer
 * la carte anniversaire (+500 F) au bon moment.
 */
class AnniversairesProches extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * @param  Collection<int, Client>  $clients
     */
    public function __construct(public Collection $clients) {}

    public function envelope(): Envelope
    {
        $nombre = $this->clients->count();

        return new Envelope(
            subject: $nombre > 1
                ? "🎂 {$nombre} anniversaires dans 2 jours"
                : '🎂 Un anniversaire dans 2 jours',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.anniversaires-proches',
            with: ['clients' => $this->clients],
        );
    }
}
