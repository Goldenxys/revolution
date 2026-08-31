<?php

namespace App\Filament\Resources\CommandeResource\Pages;

use App\Filament\Resources\CommandeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCommandes extends ListRecords
{
    protected static string $resource = CommandeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('exporter')
                ->label('Exporter la journée (CSV)')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->url(fn () => route('admin.export.commandes', [
                    'date' => $this->tableFilters['jour']['jour'] ?? now()->toDateString(),
                ]))
                ->openUrlInNewTab(),
        ];
    }
}
