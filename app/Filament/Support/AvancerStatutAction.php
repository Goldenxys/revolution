<?php

namespace App\Filament\Support;

use App\Models\Commande;
use App\Models\CommandeJournal;
use Filament\Actions\Action as PageAction;
use Filament\Actions\MountableAction;
use Filament\Tables\Actions\Action as TableAction;

/**
 * Fait avancer une commande de « validée » à « en livraison » puis à
 * « livrée » — un seul point d'entrée pour CommandeResource (action de
 * ligne) et ViewCommande (action d'en-tête), qui affichaient auparavant
 * chacun leur propre copie de cette logique. Le dernier passage,
 * en_livraison → livree, est le vrai fait comptable : il délègue à
 * Commande::confirmerLivraison() (stock décrémenté, CA et fidélité de la
 * cliente mis à jour) plutôt qu'un simple ->update() sans effet.
 */
class AvancerStatutAction
{
    public static function pourTable(): TableAction
    {
        return static::configurer(TableAction::make('avancer_statut'));
    }

    public static function pourPage(): PageAction
    {
        return static::configurer(PageAction::make('avancer_statut'));
    }

    private static function configurer(MountableAction $action): MountableAction
    {
        return $action
            ->label(fn (Commande $record) => $record->statut === 'en_livraison' ? 'Marquer livrée' : 'Passer en livraison')
            ->icon('heroicon-o-truck')
            ->color('gray')
            ->requiresConfirmation()
            ->modalDescription(fn (Commande $record) => $record->statut === 'en_livraison'
                ? 'Le stock des variantes vendues sera décrémenté, et le chiffre d\'affaires ainsi que la fidélité de la cliente seront mis à jour.'
                : null)
            ->visible(fn (Commande $record) => in_array($record->statut, ['validee', 'en_livraison'], true))
            ->action(function (Commande $record) {
                if ($record->statut === 'en_livraison') {
                    $record->confirmerLivraison(auth()->user());

                    return;
                }

                $record->update(['statut' => 'en_livraison']);
                CommandeJournal::consigner($record, 'statut_en_livraison', [], auth()->id());
            });
    }
}
