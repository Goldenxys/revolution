<?php

namespace App\Filament\Resources\TailleResource\Pages;

use App\Filament\Resources\TailleResource;
use App\Filament\Support\GuideAction;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageTailles extends ManageRecords
{
    protected static string $resource = TailleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            GuideAction::make('tailles'),

            Actions\CreateAction::make()
                ->label('Ajouter une taille'),
        ];
    }
}
