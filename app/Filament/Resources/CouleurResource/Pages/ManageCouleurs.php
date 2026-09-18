<?php

namespace App\Filament\Resources\CouleurResource\Pages;

use App\Filament\Resources\CouleurResource;
use App\Filament\Support\GuideAction;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageCouleurs extends ManageRecords
{
    protected static string $resource = CouleurResource::class;

    protected function getHeaderActions(): array
    {
        return [
            GuideAction::make('couleurs'),

            Actions\CreateAction::make()
                ->label('Ajouter une couleur'),
        ];
    }
}
