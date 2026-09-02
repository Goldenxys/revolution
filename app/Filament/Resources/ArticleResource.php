<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ArticleResource\Pages;
use App\Models\Article;
use App\Models\ArticleVariante;
use App\Models\Couleur;
use App\Models\Taille;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section as FormSection;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ViewField;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use App\Support\Francais;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Str;

class ArticleResource extends Resource
{
    protected static ?string $model = Article::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?string $navigationGroup = 'Catalogue';

    protected static ?string $navigationLabel = 'Articles';

    protected static ?string $modelLabel = 'article';

    protected static ?string $pluralModelLabel = 'articles';

    protected static ?int $navigationSort = 40;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Tabs::make('Article')
                ->columnSpanFull()
                ->tabs([
                    Tab::make('Informations')
                        ->schema([
                            FormSection::make('Identité')
                                ->columns(2)
                                ->schema([
                                    Select::make('collection_id')
                                        ->label('Collection')
                                        ->relationship('collection', 'nom')
                                        ->required()
                                        ->searchable()
                                        ->preload(),

                                    Select::make('type_article_id')
                                        ->label('Type d\'article')
                                        ->relationship('typeArticle', 'nom')
                                        ->required()
                                        ->searchable()
                                        ->preload()
                                        ->helperText('Détermine si l\'onglet Disponibilité proposera des tailles et/ou des couleurs.'),

                                    TextInput::make('nom')
                                        ->label('Nom')
                                        ->required()
                                        ->maxLength(190)
                                        ->placeholder('Ex. Tee-shirt Couronne d\'épines')
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(function (string $operation, $state, Set $set) {
                                            if ($operation === 'create') {
                                                $set('slug', Str::slug((string) $state));
                                            }
                                        }),

                                    TextInput::make('slug')
                                        ->label('Identifiant technique (slug)')
                                        ->required()
                                        ->maxLength(190)
                                        ->rules(['alpha_dash'])
                                        ->unique(ignoreRecord: true)
                                        ->helperText('Généré depuis le nom. Apparaît dans l\'adresse de la page.'),

                                    TextInput::make('prix')
                                        ->label('Prix')
                                        ->required()
                                        ->numeric()
                                        ->minValue(0)
                                        ->suffix('F CFA'),

                                    TextInput::make('ordre')
                                        ->label('Ordre d\'affichage')
                                        ->numeric()
                                        ->default(0)
                                        ->minValue(0),

                                    Textarea::make('description')
                                        ->label('Description')
                                        ->rows(3)
                                        ->maxLength(1000)
                                        ->columnSpanFull(),

                                    FileUpload::make('photo')
                                        ->label('Photo')
                                        ->image()
                                        ->imageEditor()
                                        ->directory('articles')
                                        ->disk('public')
                                        ->maxSize(4096)
                                        ->helperText('JPG ou PNG, 4 Mo maximum.')
                                        ->columnSpanFull(),

                                    Toggle::make('active')
                                        ->label('Visible sur le site')
                                        ->helperText('Un article reste masqué automatiquement tant qu\'aucune combinaison n\'est disponible, même actif.')
                                        ->default(true)
                                        ->columnSpanFull(),
                                ]),
                        ]),

                    Tab::make('Disponibilité')
                        ->schema([
                            ViewField::make('matrice_disponibilite')
                                ->label(null)
                                ->view('filament.forms.components.matrice-disponibilite')
                                ->dehydrated(false),
                        ]),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('ordre')
            ->columns([
                ImageColumn::make('photo')
                    ->label('')
                    ->disk('public')
                    ->height(40),

                TextColumn::make('nom')
                    ->label('Nom')
                    ->description(fn (Article $record) => $record->collection?->nom)
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),

                TextColumn::make('typeArticle.nom')
                    ->label('Type'),

                TextColumn::make('prix')
                    ->label('Prix')
                    ->sortable()
                    ->formatStateUsing(fn (int $state) => Francais::frais($state)),

                TextColumn::make('disponibilite')
                    ->label('Disponibilité')
                    ->state(fn (Article $record) => $record->ratioDisponibilite())
                    ->formatStateUsing(fn (array $state) => "{$state['disponibles']}/{$state['total']}")
                    ->badge()
                    ->color(fn (array $state) => match (true) {
                        $state['disponibles'] === 0 => 'danger',
                        $state['disponibles'] < $state['total'] => 'warning',
                        default => 'success',
                    }),

                ToggleColumn::make('active')
                    ->label('Visible'),
            ])
            ->filters([
                SelectFilter::make('collection_id')
                    ->label('Collection')
                    ->relationship('collection', 'nom'),

                SelectFilter::make('type_article_id')
                    ->label('Type')
                    ->relationship('typeArticle', 'nom'),

                TernaryFilter::make('active')
                    ->label('Visible'),

                Filter::make('epuises')
                    ->label('Articles épuisés')
                    ->query(fn (Builder $query) => $query->whereDoesntHave('variantes', fn (Builder $q) => $q->where('disponible', true))),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),

                Tables\Actions\Action::make('dupliquer')
                    ->label('Dupliquer')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->modalDescription('Crée une copie de cet article avec toute sa grille de disponibilité, à renommer ensuite.')
                    ->action(function (Article $record) {
                        $copie = $record->replicate(['slug']);
                        $copie->nom = $record->nom.' (copie)';
                        $copie->slug = Str::slug($copie->nom).'-'.Str::random(5);
                        $copie->active = false;
                        $copie->save();

                        foreach ($record->variantes as $variante) {
                            $copie->variantes()->create([
                                'taille_id' => $variante->taille_id,
                                'couleur_id' => $variante->couleur_id,
                                'disponible' => $variante->disponible,
                                'stock' => $variante->stock,
                            ]);
                        }

                        Notification::make()
                            ->title('Article dupliqué')
                            ->body('« '.$copie->nom.' » a été créé, désactivé le temps de le renommer.')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\DeleteAction::make()
                    ->modalHeading('Supprimer cet article ?')
                    ->modalDescription('Sa grille de disponibilité sera supprimée avec lui. Les commandes déjà passées ne sont pas affectées (l\'article y est copié).'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('indisponible_taille')
                        ->label('Rendre indisponible dans une taille')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->form([
                            Select::make('taille_id')
                                ->label('Taille')
                                ->options(fn () => Taille::query()->actives()->orderBy('ordre')->pluck('libelle', 'id'))
                                ->required(),
                        ])
                        ->action(function (SupportCollection $records, array $data) {
                            ArticleVariante::query()
                                ->whereIn('article_id', $records->pluck('id'))
                                ->where('taille_id', $data['taille_id'])
                                ->update(['disponible' => false]);
                        })
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\BulkAction::make('indisponible_couleur')
                        ->label('Rendre indisponible dans une couleur')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->form([
                            Select::make('couleur_id')
                                ->label('Couleur')
                                ->options(fn () => Couleur::query()->actives()->orderBy('ordre')->pluck('nom', 'id'))
                                ->required(),
                        ])
                        ->action(function (SupportCollection $records, array $data) {
                            ArticleVariante::query()
                                ->whereIn('article_id', $records->pluck('id'))
                                ->where('couleur_id', $data['couleur_id'])
                                ->update(['disponible' => false]);
                        })
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\BulkAction::make('changer_collection')
                        ->label('Changer de collection')
                        ->icon('heroicon-o-rectangle-stack')
                        ->form([
                            Select::make('collection_id')
                                ->label('Nouvelle collection')
                                ->relationship('collection', 'nom')
                                ->required(),
                        ])
                        ->action(fn (SupportCollection $records, array $data) => $records->each->update(['collection_id' => $data['collection_id']]))
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\BulkAction::make('activer')
                        ->label('Activer')
                        ->icon('heroicon-o-eye')
                        ->action(fn (SupportCollection $records) => $records->each->update(['active' => true]))
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\BulkAction::make('desactiver')
                        ->label('Désactiver')
                        ->icon('heroicon-o-eye-slash')
                        ->action(fn (SupportCollection $records) => $records->each->update(['active' => false]))
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\DeleteBulkAction::make()
                        ->modalHeading('Supprimer les articles sélectionnés ?'),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListArticles::route('/'),
            'create' => Pages\CreateArticle::route('/creer'),
            'edit' => Pages\EditArticle::route('/{record}/modifier'),
        ];
    }
}
