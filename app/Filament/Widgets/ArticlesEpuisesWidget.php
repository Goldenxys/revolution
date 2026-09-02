<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\ArticleResource;
use App\Models\Article;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

/**
 * Articles actifs mais entièrement épuisés (aucune combinaison disponible)
 * — masqués du site public sans que la gérante ait eu à y toucher, donc
 * faciles à perdre de vue. Affiché sous le tableau des commandes du jour.
 */
class ArticlesEpuisesWidget extends TableWidget
{
    protected static ?string $heading = 'Articles épuisés à réapprovisionner';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 40;

    public static function canView(): bool
    {
        return Article::query()
            ->where('active', true)
            ->whereDoesntHave('variantes', fn (Builder $query) => $query->where('disponible', true))
            ->exists();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Article::query()
                    ->with(['collection', 'typeArticle'])
                    ->where('active', true)
                    ->whereDoesntHave('variantes', fn (Builder $query) => $query->where('disponible', true))
                    ->orderBy('nom')
            )
            ->columns([
                ImageColumn::make('photo')->label('')->disk('public')->height(32),
                TextColumn::make('nom')->label('Article'),
                TextColumn::make('collection.nom')->label('Collection'),
                TextColumn::make('typeArticle.nom')->label('Type'),
            ])
            ->actions([
                Action::make('reapprovisionner')
                    ->label('Mettre à jour')
                    ->icon('heroicon-o-pencil-square')
                    ->url(fn (Article $record) => ArticleResource::getUrl('edit', ['record' => $record])),
            ])
            ->paginated(false);
    }
}
