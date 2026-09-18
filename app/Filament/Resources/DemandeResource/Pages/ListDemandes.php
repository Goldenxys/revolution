<?php

namespace App\Filament\Resources\DemandeResource\Pages;

use App\Filament\Resources\DemandeResource;
use App\Filament\Support\GuideAction;
use Filament\Resources\Pages\ListRecords;

class ListDemandes extends ListRecords
{
    protected static string $resource = DemandeResource::class;

    public function getTitle(): string
    {
        return 'Demandes à valider';
    }

    protected function getHeaderActions(): array
    {
        return [
            GuideAction::make('demandes'),
        ];
    }
}
