<?php

namespace App\Filament\Resources\ArticleResource\Pages;

use App\Filament\Resources\ArticleResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateArticle extends CreateRecord
{
    protected static string $resource = ArticleResource::class;

    /**
     * La grille de disponibilité (onglet 2) n'existe qu'une fois l'article
     * enregistré : on renvoie directement vers sa fiche d'édition plutôt que
     * vers la liste, pour que la gérante puisse cocher les tailles/couleurs
     * disponibles dans la foulée.
     */
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->getRecord()]);
    }

    protected function afterCreate(): void
    {
        Notification::make()
            ->title('Article créé')
            ->body('Passez à l\'onglet « Disponibilité » pour cocher les tailles et couleurs proposées.')
            ->success()
            ->send();
    }
}
