<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StockResource\Pages;
use App\Models\Article;
use App\Models\ArticleVariante;
use App\Models\Couleur;
use App\Models\Taille;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * « Mon stock » (V2 §8.2, restructuré) — conçu pour le téléphone, consulté
 * debout dans un stand. Une ligne par variante, recherche/filtrage par
 * article + taille + couleur, modification du stock en ligne, entrée de
 * marchandise en trois clics. Le stock n'est plus qu'un outil de pilotage
 * sur cet écran : c'est le compositeur de commande (ComposerDemande) qui
 * bloque réellement la vente d'une variante en rupture.
 */
class StockResource extends Resource
{
    protected static ?string $model = ArticleVariante::class;

    protected static ?string $navigationIcon = 'heroicon-o-archive-box';

    protected static ?string $navigationGroup = 'Catalogue';

    protected static ?string $navigationLabel = 'Mon stock';

    protected static ?string $modelLabel = 'variante';

    protected static ?string $pluralModelLabel = 'stock';

    protected static ?int $navigationSort = 45;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) (ArticleVariante::query()->stockFaible()->count() ?: '');
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['article.collection', 'taille', 'couleur']))
            ->defaultSort('article_id')
            ->columns([
                TextColumn::make('article.nom')
                    ->label('Article')
                    ->description(fn (ArticleVariante $v) => $v->article?->collection?->nom)
                    ->searchable(query: fn (Builder $q, string $s) => $q->whereHas('article', fn (Builder $qa) => $qa->where('nom', 'like', "%{$s}%")))
                    ->sortable(),

                TextColumn::make('taille.libelle')->label('Taille')->placeholder('—'),
                TextColumn::make('couleur.nom')->label('Couleur')->placeholder('—'),

                TextInputColumn::make('stock')
                    ->label('Stock')
                    ->type('number')
                    ->rules(['nullable', 'integer', 'min:0'])
                    ->placeholder('non suivi'),

                TextColumn::make('etat')
                    ->label('État')
                    ->badge()
                    ->state(fn (ArticleVariante $v) => match (true) {
                        ! $v->disponible => 'indisponible',
                        $v->stock === null => 'non suivi',
                        $v->stock <= 0 => 'rupture',
                        $v->stock <= $v->seuil_alerte => 'stock faible',
                        default => 'ok',
                    })
                    ->color(fn (string $state) => match ($state) {
                        'rupture', 'indisponible' => 'danger',
                        'stock faible' => 'warning',
                        'non suivi' => 'gray',
                        default => 'success',
                    }),

                ToggleColumn::make('disponible')
                    ->label('En vente'),
            ])
            ->filters([
                Filter::make('etat')
                    ->form([
                        Select::make('etat')
                            ->hiddenLabel()
                            ->options([
                                'tout' => 'Tout',
                                'faible' => 'Stock faible',
                                'rupture' => 'Rupture',
                            ])
                            ->default('tout')
                            ->selectablePlaceholder(false),
                    ])
                    ->query(fn (Builder $query, array $data) => match ($data['etat'] ?? 'tout') {
                        'faible' => $query->stockFaible(),
                        'rupture' => $query->rupture(),
                        default => $query,
                    })
                    ->indicateUsing(fn (array $data) => match ($data['etat'] ?? 'tout') {
                        'faible' => 'Stock faible',
                        'rupture' => 'Rupture',
                        default => null,
                    }),

                SelectFilter::make('taille_id')
                    ->label('Taille')
                    ->options(fn () => Taille::query()->actives()->orderBy('ordre')->pluck('libelle', 'id')),

                SelectFilter::make('couleur_id')
                    ->label('Couleur')
                    ->options(fn () => Couleur::query()->actives()->orderBy('ordre')->pluck('nom', 'id')),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('reapprovisionner')
                    ->label('Ajouter au stock')
                    ->icon('heroicon-o-plus')
                    ->form([
                        TextInput::make('quantite')->label('Quantité à ajouter')->numeric()->minValue(1)->required(),
                    ])
                    ->action(function (array $data, $records) {
                        foreach ($records as $variante) {
                            $variante->update(['stock' => (int) ($variante->stock ?? 0) + (int) $data['quantite']]);
                        }
                        Notification::make()->title('Stock ajouté sur '.$records->count().' variante(s).')->success()->send();
                    })
                    ->deselectRecordsAfterCompletion(),

                Tables\Actions\BulkAction::make('mettre_en_vente')
                    ->label('Mettre en vente')
                    ->icon('heroicon-o-check')
                    ->action(fn ($records) => $records->each->update(['disponible' => true]))
                    ->deselectRecordsAfterCompletion(),

                Tables\Actions\BulkAction::make('retirer_de_la_vente')
                    ->label('Retirer de la vente')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->action(fn ($records) => $records->each->update(['disponible' => false]))
                    ->deselectRecordsAfterCompletion(),
            ])
            ->headerActions([
                Tables\Actions\Action::make('entree_stock')
                    ->label('Entrée de stock')
                    ->icon('heroicon-o-inbox-arrow-down')
                    ->extraAttributes(['data-tour' => 'entree-stock'])
                    ->form([
                        Select::make('article_id')
                            ->label('Article')
                            ->options(fn () => Article::query()->where('active', true)->orderBy('nom')->pluck('nom', 'id'))
                            ->searchable()
                            ->required()
                            ->live(),

                        Select::make('variantes')
                            ->label('Variantes concernées')
                            ->multiple()
                            ->options(fn (Get $get) => ArticleVariante::query()
                                ->where('article_id', $get('article_id'))
                                ->with(['taille', 'couleur'])
                                ->get()
                                ->mapWithKeys(fn (ArticleVariante $v) => [
                                    $v->id => collect([$v->taille?->libelle, $v->couleur?->nom])->filter()->implode(' / ') ?: 'Taille unique',
                                ]))
                            ->required()
                            ->visible(fn (Get $get) => filled($get('article_id'))),

                        TextInput::make('quantite')->label('Quantité reçue par variante')->numeric()->minValue(1)->required(),
                    ])
                    ->action(function (array $data) {
                        $variantes = ArticleVariante::query()->whereIn('id', $data['variantes'])->get();
                        foreach ($variantes as $v) {
                            $v->update([
                                'stock' => (int) ($v->stock ?? 0) + (int) $data['quantite'],
                                'disponible' => true,
                            ]);
                        }
                        Notification::make()->title('Entrée enregistrée sur '.$variantes->count().' variante(s).')->success()->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStock::route('/'),
        ];
    }
}
