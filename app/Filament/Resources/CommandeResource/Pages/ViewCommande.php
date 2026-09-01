<?php

namespace App\Filament\Resources\CommandeResource\Pages;

use App\Filament\Resources\CommandeResource;
use App\Models\Commande;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewCommande extends ViewRecord
{
    protected static string $resource = CommandeResource::class;

    protected function getHeaderActions(): array
    {
        return [
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
