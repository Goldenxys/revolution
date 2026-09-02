<?php

namespace App\Filament\Resources\CollectionResource\Pages;

use App\Filament\Resources\CollectionResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditCollection extends EditRecord
{
    protected static string $resource = CollectionResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->modalHeading('Supprimer cette collection ?')
                ->before(function (Actions\DeleteAction $action) {
                    if (! $this->record->estSupprimable()) {
                        Notification::make()
                            ->danger()
                            ->title('Suppression impossible')
                            ->body('Cette collection contient des articles. Désactivez-la plutôt pour la masquer du site.')
                            ->send();

                        $action->halt();
                    }
                }),
        ];
    }
}
