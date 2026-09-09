<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\StockResource;
use App\Models\ArticleVariante;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

/**
 * Stock faible et ruptures (V2 §9, widget 6) — liste courte des variantes
 * à réapprovisionner. N'apparaît que s'il y en a.
 */
class StockAlerteWidget extends TableWidget
{
    protected static ?string $heading = 'Stock faible et ruptures';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 35;

    public static function canView(): bool
    {
        return ArticleVariante::query()->stockFaible()->exists();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                ArticleVariante::query()
                    ->stockFaible()
                    ->with(['article', 'taille', 'couleur'])
                    ->orderBy('stock')
            )
            ->columns([
                TextColumn::make('article.nom')->label('Article'),
                TextColumn::make('taille.libelle')->label('Taille')->placeholder('—'),
                TextColumn::make('couleur.nom')->label('Couleur')->placeholder('—'),
                TextColumn::make('stock')
                    ->label('Stock')
                    ->badge()
                    ->color(fn (ArticleVariante $v) => $v->stock <= 0 ? 'danger' : 'warning'),
            ])
            ->actions([
                Action::make('gerer')
                    ->label('Mon stock')
                    ->icon('heroicon-o-archive-box')
                    ->url(StockResource::getUrl('index')),
            ])
            ->paginated(false);
    }
}
