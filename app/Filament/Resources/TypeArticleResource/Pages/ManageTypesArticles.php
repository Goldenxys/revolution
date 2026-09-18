<?php

namespace App\Filament\Resources\TypeArticleResource\Pages;

use App\Filament\Resources\TypeArticleResource;
use App\Filament\Support\GuideAction;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageTypesArticles extends ManageRecords
{
    protected static string $resource = TypeArticleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            GuideAction::make('types'),

            Actions\CreateAction::make()
                ->label('Ajouter un type'),
        ];
    }
}
