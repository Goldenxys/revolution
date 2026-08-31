<?php

namespace App\Filament\Widgets;

use App\Models\Client;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

/**
 * Fichier clients complet, affiché sous le tableau des commandes du jour
 * sur le tableau de bord de l'Espace RÉVOLUTION.
 */
class ClientsWidget extends TableWidget
{
    protected static ?string $heading = 'Fichier clients';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 30;

    public function table(Table $table): Table
    {
        return $table
            ->query(Client::query()->orderByDesc('derniere_commande_at'))
            ->columns([
                TextColumn::make('nom')->label('Nom')->searchable(),
                TextColumn::make('telephone')->label('Téléphone')->searchable(),
                TextColumn::make('email')->label('Email')->searchable()->placeholder('—'),
                TextColumn::make('commune')->label('Commune')->placeholder('—'),
                TextColumn::make('nb_commandes')->label('Commandes')->numeric(),
                TextColumn::make('palier')->label('Palier')->state(fn (Client $client) => "{$client->palier}/8"),
                TextColumn::make('avantage')
                    ->label('Avantage')
                    ->badge()
                    ->color(fn (Client $client) => $client->avantage ? 'gold' : 'gray')
                    ->formatStateUsing(fn (Client $client) => $client->avantage ? "−{$client->avantage} %" : 'Aucun'),
                TextColumn::make('premiere_commande_at')->label('1ʳᵉ commande')->date('d/m/Y'),
                TextColumn::make('derniere_commande_at')->label('Dernière commande')->date('d/m/Y'),
            ])
            ->paginated([10, 25, 50]);
    }
}
