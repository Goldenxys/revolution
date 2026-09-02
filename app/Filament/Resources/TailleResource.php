<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TailleResource\Pages;
use App\Models\Taille;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class TailleResource extends Resource
{
    protected static ?string $model = Taille::class;

    protected static ?string $navigationIcon = 'heroicon-o-swatch';

    protected static ?string $navigationGroup = 'Catalogue';

    protected static ?string $navigationLabel = 'Tailles';

    protected static ?string $modelLabel = 'taille';

    protected static ?string $pluralModelLabel = 'tailles';

    protected static ?int $navigationSort = 43;

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('libelle')
                ->label('Libellé')
                ->required()
                ->maxLength(20)
                ->placeholder('Ex. XL, 3XL, Taille unique'),

            TextInput::make('ordre')
                ->label('Ordre d\'affichage')
                ->helperText('Les petits nombres apparaissent en premier. Réglable aussi par glisser-déposer dans la liste.')
                ->numeric()
                ->default(0)
                ->minValue(0),

            Toggle::make('active')
                ->label('Proposée aux clientes')
                ->helperText('Désactivez une taille pour la retirer des formulaires sans perdre l\'historique.')
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->reorderable('ordre')
            ->defaultSort('ordre')
            ->columns([
                TextColumn::make('libelle')
                    ->label('Libellé')
                    ->searchable()
                    ->weight('medium'),

                TextColumn::make('variantes_count')
                    ->label('Articles')
                    ->counts('variantes')
                    ->badge()
                    ->color('gray'),

                ToggleColumn::make('active')
                    ->label('Proposée'),

                TextColumn::make('ordre')
                    ->label('Ordre')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),

                Tables\Actions\DeleteAction::make()
                    ->modalHeading('Supprimer cette taille ?')
                    ->before(function (Taille $record, Tables\Actions\DeleteAction $action) {
                        if (! $record->estSupprimable()) {
                            Notification::make()
                                ->danger()
                                ->title('Suppression impossible')
                                ->body('Cette taille est utilisée par des articles. Désactivez-la plutôt pour la masquer.')
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
            'index' => Pages\ManageTailles::route('/'),
        ];
    }
}
