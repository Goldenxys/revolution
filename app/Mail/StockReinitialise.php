<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

/**
 * « Stock réinitialisé » — envoyé après un clic sur « Réinitialiser le
 * stock complet » (Mon stock, StockResource) : la gérante vient de
 * supprimer d'un coup toutes les désignations de stock (taille, couleur,
 * quantité) de tous les articles au stock géré. L'action elle-même ne
 * garde aucune trace de ce qui existait avant — cet e-mail, avec le détail
 * complet article par article, en est la seule.
 */
class StockReinitialise extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * @param  Collection<int, array{nom: string, lignes: Collection<int, array{taille: ?string, couleur: ?string, stock: ?int}>}>  $articles
     */
    public function __construct(
        public Collection $articles,
        public int $nombreDesignations,
        public int $totalPieces,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '⚠️ Stock réinitialisé — toutes les désignations supprimées',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.stock-reinitialise',
            with: [
                'articles' => $this->articles,
                'nombreDesignations' => $this->nombreDesignations,
                'totalPieces' => $this->totalPieces,
            ],
        );
    }
}
