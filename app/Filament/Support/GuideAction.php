<?php

namespace App\Filament\Support;

use Filament\Actions\Action;

/**
 * Bouton « Revoir le guide », identique sur chaque écran clé de l'Espace
 * RÉVOLUTION (tableau de bord, demandes, compositeur, stock, commandes).
 * Ne fait aucun aller-retour serveur utile : il relance simplement la
 * visite guidée correspondante côté client (resources/js/filament/revo-tour.js),
 * via le pont `$this->js()` de Livewire 3.
 */
class GuideAction
{
    public static function make(string $page): Action
    {
        return Action::make('guide_'.$page)
            ->label('Revoir le guide')
            ->icon('heroicon-o-light-bulb')
            ->color('gray')
            ->action(fn (Action $action) => $action->getLivewire()->js(
                'window.RevoTour && window.RevoTour.start('.json_encode($page).')'
            ));
    }
}
