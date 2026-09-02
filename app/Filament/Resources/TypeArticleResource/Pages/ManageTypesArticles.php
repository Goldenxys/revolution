<?php

namespace App\Filament\Resources\TypeArticleResource\Pages;

use App\Filament\Resources\TypeArticleResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageTypesArticles extends ManageRecords
{
    protected static string $resource = TypeArticleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Ajouter un type'),
        ];
    }
}
