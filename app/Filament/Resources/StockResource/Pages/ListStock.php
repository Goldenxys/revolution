<?php

namespace App\Filament\Resources\StockResource\Pages;

use App\Filament\Resources\StockResource;
use App\Filament\Resources\StockResource\Widgets\StockStatsWidget;
use App\Filament\Support\GuideAction;
use Filament\Resources\Pages\ListRecords;

class ListStock extends ListRecords
{
    protected static string $resource = StockResource::class;

    public function getTitle(): string
    {
        return 'Mon stock';
    }

    protected function getHeaderActions(): array
    {
        return [
            GuideAction::make('stock'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            StockStatsWidget::class,
        ];
    }
}
