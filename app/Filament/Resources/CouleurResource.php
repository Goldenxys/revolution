<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CouleurResource\Pages;
use App\Models\Couleur;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class CouleurResource extends Resource
{
    protected static ?string $model = Couleur::class;

    protected static ?string $navigationIcon = 'heroicon-o-paint-brush';

    protected static ?string $navigationGroup = 'Catalogue';

    protected static ?string $navigationLabel = 'Couleurs';

    protected static ?string $modelLabel = 'couleur';

    protected static ?string $pluralModelLabel = 'couleurs';

    protected static ?int $navigationSort = 44;

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('nom')
                ->label('Nom')
                ->required()
                ->maxLength(40)
                ->placeholder('Ex. Terracotta'),

            ColorPicker::make('code_hex')
                ->label('Pastille de couleur')
                ->helperText('Affichée à côté du nom sur le site. Facultatif : le nom écrit reste la référence.'),

            TextInput::make('ordre')
                ->label('Ordre d\'affichage')
                ->helperText('Réglable aussi par glisser-déposer dans la liste.')
                ->numeric()
                ->default(0)
                ->minValue(0),

            Toggle::make('active')
                ->label('Proposée aux clientes')
                ->helperText('Désactivez une couleur pour la retirer des formulaires sans perdre l\'historique.')
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->reorderable('ordre')
            ->defaultSort('ordre')
            ->columns([
                ColorColumn::make('code_hex')
                    ->label(''),

                TextColumn::make('nom')
                    ->label('Nom')
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
                    ->modalHeading('Supprimer cette couleur ?')
                    ->before(function (Couleur $record, Tables\Actions\DeleteAction $action) {
                        if (! $record->estSupprimable()) {
                            Notification::make()
                                ->danger()
                                ->title('Suppression impossible')
                                ->body('Cette couleur est utilisée par des articles. Désactivez-la plutôt pour la masquer.')
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
            'index' => Pages\ManageCouleurs::route('/'),
        ];
    }
}
