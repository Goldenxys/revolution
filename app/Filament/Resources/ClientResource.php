<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClientResource\Pages;
use App\Models\Client;
use Filament\Infolists\Components\Section as InfolistSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ClientResource extends Resource
{
    protected static ?string $model = Client::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Clients';

    protected static ?string $modelLabel = 'client';

    protected static ?string $pluralModelLabel = 'clients';

    protected static ?int $navigationSort = 30;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('derniere_commande_at', 'desc')
            ->columns([
                TextColumn::make('nom')
                    ->label('Nom')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('telephone')
                    ->label('Téléphone')
                    ->searchable(),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->placeholder('—'),

                TextColumn::make('commune')
                    ->label('Commune')
                    ->placeholder('—'),

                TextColumn::make('nb_commandes')
                    ->label('Commandes')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('palier')
                    ->label('Palier')
                    ->state(fn (Client $client) => "{$client->palier}/8"),

                TextColumn::make('avantage')
                    ->label('Avantage en cours')
                    ->badge()
                    ->color(fn (Client $client) => $client->avantage ? 'gold' : 'gray')
                    ->formatStateUsing(fn (Client $client) => $client->avantage ? "−{$client->avantage} %" : 'Aucun'),

                TextColumn::make('premiere_commande_at')
                    ->label('1ʳᵉ commande')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('derniere_commande_at')
                    ->label('Dernière commande')
                    ->date('d/m/Y')
                    ->sortable(),
            ])
            ->actions([
                ViewAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            InfolistSection::make('Client')
                ->columns(2)
                ->schema([
                    TextEntry::make('nom')->label('Nom'),
                    TextEntry::make('telephone')->label('Téléphone'),
                    TextEntry::make('email')->label('Email')->placeholder('—'),
                    TextEntry::make('commune')->label('Commune')->placeholder('—'),
                ]),

            InfolistSection::make('Fidélité')
                ->columns(2)
                ->schema([
                    TextEntry::make('nb_commandes')->label('Nombre de commandes'),
                    TextEntry::make('palier')->label('Palier')->state(fn (Client $client) => "{$client->palier}/8"),
                    TextEntry::make('avantage')->label('Avantage en cours')
                        ->state(fn (Client $client) => $client->avantage ? "−{$client->avantage} %" : 'Aucun'),
                    TextEntry::make('prochain_avantage')->label('Prochain avantage')
                        ->state(fn (Client $client) => "−{$client->prochain_avantage} %"),
                    TextEntry::make('premiere_commande_at')->label('Première commande')->date('d/m/Y'),
                    TextEntry::make('derniere_commande_at')->label('Dernière commande')->date('d/m/Y'),
                ]),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListClients::route('/'),
            'view' => Pages\ViewClient::route('/{record}'),
        ];
    }
}
