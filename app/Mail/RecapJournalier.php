<?php

namespace App\Mail;

use App\Support\Francais;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class RecapJournalier extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * @param  Collection<int, \App\Models\Commande>  $commandes
     * @param  array<string, int>  $indicateurs
     */
    public function __construct(
        public Carbon $date,
        public Collection $commandes,
        public array $indicateurs,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Récap RÉVOLUTION — '.Francais::dateLongue($this->date),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.recap-journalier',
            with: [
                'date' => $this->date,
                'commandes' => $this->commandes,
                'indicateurs' => $this->indicateurs,
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
