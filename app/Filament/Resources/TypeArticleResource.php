<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TypeArticleResource\Pages;
use App\Models\TypeArticle;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class TypeArticleResource extends Resource
{
    protected static ?string $model = TypeArticle::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';

    protected static ?string $navigationGroup = 'Catalogue';

    protected static ?string $navigationLabel = 'Types d\'articles';

    protected static ?string $modelLabel = 'type d\'article';

    protected static ?string $pluralModelLabel = 'types d\'articles';

    protected static ?int $navigationSort = 42;

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('nom')
                ->label('Nom')
                ->required()
                ->maxLength(60)
                ->placeholder('Ex. Tee-shirt, Casquette')
                ->live(onBlur: true)
                ->afterStateUpdated(function (string $operation, $state, Set $set) {
                    if ($operation === 'create') {
                        $set('slug', Str::slug((string) $state));
                    }
                }),

            TextInput::make('slug')
                ->label('Identifiant technique (slug)')
                ->required()
                ->maxLength(60)
                ->rules(['alpha_dash'])
                ->unique(ignoreRecord: true)
                ->helperText('Généré depuis le nom. Utilisé dans les adresses de pages ; évitez de le modifier après coup.'),

            Toggle::make('gere_tailles')
                ->label('Ce type a des tailles')
                ->helperText('Décochez pour un article en taille unique (tote bag, casquette, chaussette…).')
                ->default(true),

            Toggle::make('gere_couleurs')
                ->label('Ce type a des couleurs')
                ->default(true),

            TextInput::make('ordre')
                ->label('Ordre d\'affichage')
                ->helperText('Réglable aussi par glisser-déposer dans la liste.')
                ->numeric()
                ->default(0)
                ->minValue(0),

            Toggle::make('active')
                ->label('Disponible pour de nouveaux articles')
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->reorderable('ordre')
            ->defaultSort('ordre')
            ->columns([
                TextColumn::make('nom')
                    ->label('Nom')
                    ->searchable()
                    ->weight('medium'),

                IconColumn::make('gere_tailles')
                    ->label('Tailles')
                    ->boolean(),

                IconColumn::make('gere_couleurs')
                    ->label('Couleurs')
                    ->boolean(),

                TextColumn::make('articles_count')
                    ->label('Articles')
                    ->counts('articles')
                    ->badge()
                    ->color('gray'),

                ToggleColumn::make('active')
                    ->label('Actif'),

                TextColumn::make('ordre')
                    ->label('Ordre')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),

                Tables\Actions\DeleteAction::make()
                    ->modalHeading('Supprimer ce type d\'article ?')
                    ->before(function (TypeArticle $record, Tables\Actions\DeleteAction $action) {
                        if (! $record->estSupprimable()) {
                            Notification::make()
                                ->danger()
                                ->title('Suppression impossible')
                                ->body('Des articles utilisent ce type. Désactivez-le plutôt pour empêcher de nouveaux articles.')
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
            'index' => Pages\ManageTypesArticles::route('/'),
        ];
    }
}
