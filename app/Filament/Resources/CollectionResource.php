<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CollectionResource\Pages;
use App\Models\CollectionCatalogue;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section as FormSection;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class CollectionResource extends Resource
{
    protected static ?string $model = CollectionCatalogue::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Catalogue';

    protected static ?string $navigationLabel = 'Collections';

    protected static ?string $modelLabel = 'collection';

    protected static ?string $pluralModelLabel = 'collections';

    protected static ?int $navigationSort = 41;

    public static function form(Form $form): Form
    {
        return $form->schema([
            FormSection::make('Identité')
                ->columns(2)
                ->schema([
                    TextInput::make('nom')
                        ->label('Nom')
                        ->required()
                        ->maxLength(80)
                        ->placeholder('Ex. My verse by RÉVOLUTION')
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (string $operation, $state, Set $set) {
                            if ($operation === 'create') {
                                $set('slug', Str::slug((string) $state));
                            }
                        }),

                    TextInput::make('slug')
                        ->label('Identifiant technique (slug)')
                        ->required()
                        ->maxLength(80)
                        ->rules(['alpha_dash'])
                        ->unique(ignoreRecord: true)
                        ->helperText('Généré depuis le nom. Apparaît dans l\'adresse de la page ; évitez de le changer ensuite.'),

                    Textarea::make('description')
                        ->label('Description')
                        ->rows(3)
                        ->maxLength(500)
                        ->columnSpanFull(),

                    FileUpload::make('image')
                        ->label('Visuel de la collection')
                        ->image()
                        ->imageEditor()
                        ->directory('collections')
                        ->disk('public')
                        ->maxSize(4096)
                        ->helperText('JPG ou PNG, 4 Mo maximum.')
                        ->columnSpanFull(),
                ]),

            FormSection::make('Personnalisation « My verse »')
                ->description('Pour les collections où la cliente choisit un verset et un modèle.')
                ->columns(2)
                ->schema([
                    Toggle::make('verset_requis')
                        ->label('Demander un verset à la cliente')
                        ->helperText('Affiche le champ verset dans le formulaire de commande.')
                        ->default(false),

                    TagsInput::make('modeles_disponibles')
                        ->label('Modèles disponibles')
                        ->placeholder('Ajouter un modèle')
                        ->helperText('Entrée pour valider chaque modèle. Laissez vide s\'il n\'y a pas de choix de modèle.')
                        ->columnSpanFull(),
                ]),

            FormSection::make('Affichage')
                ->columns(2)
                ->schema([
                    TextInput::make('ordre')
                        ->label('Ordre d\'affichage')
                        ->helperText('Réglable aussi par glisser-déposer dans la liste.')
                        ->numeric()
                        ->default(0)
                        ->minValue(0),

                    Toggle::make('active')
                        ->label('Visible sur le site')
                        ->helperText('Désactivez pour préparer une collection sans la publier.')
                        ->default(true),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->reorderable('ordre')
            ->defaultSort('ordre')
            ->columns([
                ImageColumn::make('image')
                    ->label('')
                    ->disk('public')
                    ->height(40),

                TextColumn::make('nom')
                    ->label('Nom')
                    ->description(fn (CollectionCatalogue $record) => $record->slug)
                    ->searchable()
                    ->weight('medium'),

                IconColumn::make('verset_requis')
                    ->label('Verset')
                    ->boolean(),

                TextColumn::make('articles_count')
                    ->label('Articles')
                    ->counts('articles')
                    ->badge()
                    ->color('gray'),

                ToggleColumn::make('active')
                    ->label('Visible'),

                TextColumn::make('ordre')
                    ->label('Ordre')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),

                Tables\Actions\DeleteAction::make()
                    ->modalHeading('Supprimer cette collection ?')
                    ->before(function (CollectionCatalogue $record, Tables\Actions\DeleteAction $action) {
                        if (! $record->estSupprimable()) {
                            Notification::make()
                                ->danger()
                                ->title('Suppression impossible')
                                ->body('Cette collection contient des articles. Désactivez-la plutôt pour la masquer du site.')
                                ->send();

                            $action->halt();
                        }
                    }),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCollections::route('/'),
            'create' => Pages\CreateCollection::route('/creer'),
            'edit' => Pages\EditCollection::route('/{record}/modifier'),
        ];
    }
}
