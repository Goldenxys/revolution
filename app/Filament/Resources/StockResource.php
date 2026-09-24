<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StockResource\Pages;
use App\Mail\StockReinitialise;
use App\Models\Article;
use App\Models\ArticleVariante;
use App\Models\CollectionCatalogue;
use App\Models\Couleur;
use App\Models\Parametre;
use App\Models\Taille;
use App\Models\TypeArticle;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Mail;

/**
 * « Mon stock » (V2 §8.2, restructuré) — conçu pour le téléphone, consulté
 * debout dans un stand. Une ligne par variante, recherche/filtrage par
 * article + taille + couleur + type + collection, modification du stock en
 * ligne, entrée de marchandise en trois clics. « En vente » n'est plus une
 * case à cocher ici : elle est dérivée du stock à chaque sauvegarde
 * (ArticleVariante::booted()) — c'est le compositeur de commande
 * (ComposerDemande) qui bloque réellement la vente d'une variante en
 * rupture.
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
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->with(['article.collection', 'taille', 'couleur'])
                // Une collection fabriquée à la demande (My verse) n'a rien
                // à faire ici : pas de pièces en réserve à piloter.
                ->whereDoesntHave('article.collection', fn (Builder $qc) => $qc->where('gere_stock', false)))
            ->defaultSort('article_id')
            ->columns([
                TextColumn::make('article.nom')
                    ->label('Article')
                    ->description(fn (ArticleVariante $v) => $v->article?->collection?->nom)
                    ->searchable(query: fn (Builder $query, string $search) => $query->whereHas('article', fn (Builder $qa) => $qa->where('nom', 'like', "%{$search}%")))
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
                    // `disponible` est désormais un pur miroir du stock
                    // (ArticleVariante::booted()) : plus la peine de la
                    // tester séparément, le stock suffit à tout distinguer.
                    ->state(fn (ArticleVariante $v) => match (true) {
                        $v->stock === null => 'non suivi',
                        $v->stock <= 0 => 'rupture',
                        $v->stock <= $v->seuil_alerte => 'stock faible',
                        default => 'ok',
                    })
                    ->color(fn (string $state) => match ($state) {
                        'rupture' => 'danger',
                        'stock faible' => 'warning',
                        'non suivi' => 'gray',
                        default => 'success',
                    }),

                IconColumn::make('disponible')
                    ->label('En vente')
                    ->boolean()
                    ->tooltip(fn (ArticleVariante $v) => $v->disponible ? 'En stock, en vente' : 'Pas de stock enregistré — enregistrez-en pour la remettre en vente'),
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

                SelectFilter::make('type_article_id')
                    ->label('Type d\'article')
                    ->options(fn () => TypeArticle::query()->actifs()->orderBy('ordre')->pluck('nom', 'id'))
                    ->query(fn (Builder $query, array $data) => $query->when(
                        $data['value'] ?? null,
                        fn (Builder $q, $value) => $q->whereHas('article', fn (Builder $qa) => $qa->where('type_article_id', $value)),
                    )),

                SelectFilter::make('collection_id')
                    ->label('Collection')
                    ->options(fn () => CollectionCatalogue::query()->actives()->orderBy('ordre')->pluck('nom', 'id'))
                    ->query(fn (Builder $query, array $data) => $query->when(
                        $data['value'] ?? null,
                        fn (Builder $q, $value) => $q->whereHas('article', fn (Builder $qa) => $qa->where('collection_id', $value)),
                    )),
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

                // Supprime carrément la ligne de désignation (taille, couleur,
                // stock — toute la variante) : pas seulement le stock remis à
                // zéro. La combinaison disparaît de « Mon stock », des choix
                // achetables (compositeur, recherche publique) et de la
                // grille de disponibilité (qui affiche « décoché » pour toute
                // case sans ligne — voir MatriceDisponibiliteArticle). Une
                // commande passée déjà comptée n'est pas affectée : Commande
                // retrouve/décrémente ses variantes via $variante?->, jamais
                // une FK vers cette ligne.
                Tables\Actions\BulkAction::make('supprimer_stock')
                    ->label('Supprimer le stock')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Supprimer cette désignation de stock ?')
                    ->modalDescription('La ligne (taille, couleur et stock) sera définitivement supprimée. Cette combinaison ne sera plus en vente tant qu\'une nouvelle entrée de stock n\'est pas enregistrée pour elle.')
                    ->modalSubmitActionLabel('Oui, supprimer')
                    ->action(function ($records) {
                        $nombre = $records->count();

                        foreach ($records as $variante) {
                            $variante->delete();
                        }

                        Notification::make()->title('Désignation supprimée sur '.$nombre.' variante(s).')->success()->send();
                    })
                    ->deselectRecordsAfterCompletion(),
            ])
            ->headerActions([
                // Taille/couleur saisies librement plutôt que choisies dans
                // une liste de variantes déjà existantes : depuis que
                // « Supprimer le stock » retire carrément la ligne, il faut
                // pouvoir recréer une combinaison qui n'existe plus (ou en
                // créer une nouvelle) — firstOrNew() ci-dessous retrouve la
                // ligne si elle existe encore, sinon en crée une neuve.
                Tables\Actions\Action::make('entree_stock')
                    ->label('Entrée de stock')
                    ->icon('heroicon-o-inbox-arrow-down')
                    ->extraAttributes(['data-tour' => 'entree-stock'])
                    ->form([
                        Select::make('article_id')
                            ->label('Article')
                            ->options(fn () => Article::query()
                                ->where('active', true)
                                ->whereDoesntHave('collection', fn (Builder $qc) => $qc->where('gere_stock', false))
                                ->orderBy('nom')
                                ->pluck('nom', 'id'))
                            ->searchable()
                            ->required()
                            ->live(),

                        Select::make('taille_id')
                            ->label('Taille')
                            ->options(fn () => Taille::query()->actives()->orderBy('ordre')->pluck('libelle', 'id'))
                            ->native(false)
                            ->visible(fn (Get $get) => (bool) Article::find($get('article_id'))?->gere_tailles)
                            ->required(fn (Get $get) => (bool) Article::find($get('article_id'))?->gere_tailles),

                        Select::make('couleur_id')
                            ->label('Couleur')
                            ->options(fn () => Couleur::query()->actives()->orderBy('ordre')->pluck('nom', 'id'))
                            ->native(false)
                            ->visible(fn (Get $get) => (bool) Article::find($get('article_id'))?->gere_couleurs)
                            ->required(fn (Get $get) => (bool) Article::find($get('article_id'))?->gere_couleurs),

                        TextInput::make('quantite')->label('Quantité reçue')->numeric()->minValue(1)->required(),
                    ])
                    ->action(function (array $data) {
                        $article = Article::find($data['article_id']);

                        $variante = ArticleVariante::firstOrNew([
                            'article_id' => $article->id,
                            'taille_id' => $article->gere_tailles ? $data['taille_id'] : null,
                            'couleur_id' => $article->gere_couleurs ? $data['couleur_id'] : null,
                        ]);

                        // `disponible` n'est pas renseigné ici : la sauvegarde
                        // la dérive automatiquement (ArticleVariante::booted()).
                        $variante->stock = (int) ($variante->stock ?? 0) + (int) $data['quantite'];
                        $variante->save();

                        Notification::make()->title('Entrée de stock enregistrée.')->success()->send();
                    }),

                // Même portée que le tableau lui-même (whereDoesntHave gere_stock
                // false, ligne ~74) : My verse, fabriqué à la demande, n'a pas de
                // stock à réinitialiser. Snapshot du détail AVANT suppression —
                // c'est la seule trace qui subsiste, elle part par e-mail.
                Tables\Actions\Action::make('reinitialiser_stock_complet')
                    ->label('Réinitialiser le stock complet')
                    ->icon('heroicon-o-exclamation-triangle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Réinitialiser tout le stock ?')
                    ->modalDescription('Toutes les désignations de stock (taille, couleur, quantité) de tous les articles seront supprimées d\'un coup. Cette action est irréversible — un e-mail récapitulatif partira avec le détail de ce qui a été supprimé.')
                    ->modalSubmitActionLabel('Oui, tout réinitialiser')
                    ->action(function () {
                        $variantes = ArticleVariante::query()
                            ->whereDoesntHave('article.collection', fn (Builder $qc) => $qc->where('gere_stock', false))
                            ->with(['article', 'taille', 'couleur'])
                            ->get();

                        if ($variantes->isEmpty()) {
                            Notification::make()->title('Aucune désignation de stock à réinitialiser.')->warning()->send();

                            return;
                        }

                        $articlesDetail = $variantes
                            ->groupBy(fn (ArticleVariante $v) => $v->article?->nom ?? 'Article supprimé')
                            ->map(fn ($lignes, $nom) => [
                                'nom' => $nom,
                                'lignes' => $lignes->map(fn (ArticleVariante $v) => [
                                    'taille' => $v->taille?->libelle,
                                    'couleur' => $v->couleur?->nom,
                                    'stock' => $v->stock,
                                ])->values(),
                            ])
                            ->values();

                        $nombreDesignations = $variantes->count();
                        $totalPieces = (int) $variantes->sum('stock');

                        ArticleVariante::query()->whereIn('id', $variantes->pluck('id'))->delete();

                        Mail::to(Parametre::emailReception())
                            ->queue(new StockReinitialise($articlesDetail, $nombreDesignations, $totalPieces));

                        Notification::make()
                            ->title('Stock complet réinitialisé.')
                            ->body($nombreDesignations.' désignation(s) supprimée(s). Un e-mail récapitulatif a été envoyé.')
                            ->success()
                            ->send();
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
