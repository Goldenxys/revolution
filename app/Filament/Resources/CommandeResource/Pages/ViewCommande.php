<?php

namespace App\Filament\Resources\CommandeResource\Pages;

use App\Filament\Resources\CommandeResource;
use App\Models\Commande;
use App\Models\CommandeJournal;
use Filament\Actions;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Pages\ViewRecord;

class ViewCommande extends ViewRecord
{
    protected static string $resource = CommandeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('avancer_statut')
                ->label(fn (Commande $r) => $r->statut === 'en_livraison' ? 'Marquer livrée' : 'Passer en livraison')
                ->icon('heroicon-o-truck')
                ->color('gray')
                ->requiresConfirmation()
                ->visible(fn (Commande $r) => in_array($r->statut, ['validee', 'en_livraison'], true))
                ->action(function (Commande $r) {
                    $r->update(['statut' => $r->statut === 'en_livraison' ? 'livree' : 'en_livraison']);
                    CommandeJournal::consigner($r, 'statut_'.$r->statut, [], auth()->id());
                }),

            Actions\Action::make('annuler')
                ->label('Annuler la commande')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn (Commande $r) => $r->validee_at !== null && $r->statut !== 'annulee')
                ->form([
                    Textarea::make('motif')
                        ->label('Motif de l\'annulation')
                        ->required()
                        ->helperText('Le chiffre d\'affaires, la fidélité et le stock seront défaits. La commande est conservée, jamais supprimée.'),
                ])
                ->requiresConfirmation()
                ->modalHeading('Annuler cette commande validée ?')
                ->action(fn (Commande $r, array $data) => $r->annuler($data['motif'], auth()->user())),

            Actions\EditAction::make()
                ->modalHeading('Modifier la commande')
                ->modalDescription('Corrigez une faute de frappe, une taille ou un verset mal saisi, etc.')
                ->modalWidth('2xl')
                ->modalSubmitActionLabel('Enregistrer les modifications')
                ->using(fn (Commande $record, array $data): Commande => CommandeResource::sauvegarderModification($record, $data)),

            Actions\DeleteAction::make()
                ->modalHeading('Supprimer cette commande ?')
                ->modalDescription('Cette action est définitive. Le compteur de fidélité et les dates de la cliente seront recalculés automatiquement à partir de ses commandes restantes.')
                ->modalSubmitActionLabel('Supprimer définitivement')
                ->successRedirectUrl(CommandeResource::getUrl('index')),
        ];
    }
}
