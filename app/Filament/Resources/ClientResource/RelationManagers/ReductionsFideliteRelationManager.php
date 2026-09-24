<?php

namespace App\Filament\Resources\ClientResource\RelationManagers;

use App\Models\ReductionFidelite;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * Réductions de fidélité débloquées par cette cliente (App\Support\CarteFidelite,
 * ancien formulaire libre) — la gérante y consulte la carte générée et
 * marque une réduction comme utilisée une fois appliquée sur une commande.
 */
class ReductionsFideliteRelationManager extends RelationManager
{
    protected static string $relationship = 'reductionsFidelite';

    protected static ?string $title = 'Réductions de fidélité';

    protected static ?string $modelLabel = 'réduction';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('numero_commande')
            ->columns([
                TextColumn::make('numero_commande')
                    ->label('Commande n°')
                    ->formatStateUsing(fn (ReductionFidelite $record) => $record->numero_commande.'ᵉ'),

                TextColumn::make('pourcentage')
                    ->label('Réduction')
                    ->badge()
                    ->color('gold')
                    ->formatStateUsing(fn (int $state) => "−{$state} %"),

                TextColumn::make('commande.reference')
                    ->label('Commande liée')
                    ->url(fn (ReductionFidelite $record) => $record->commande
                        ? route('filament.admin.resources.commandes.view', $record->commande)
                        : null)
                    ->placeholder('—'),

                TextColumn::make('created_at')
                    ->label('Débloquée le')
                    ->date('d/m/Y'),

                IconColumn::make('utilisee')
                    ->label('Utilisée')
                    ->boolean()
                    ->state(fn (ReductionFidelite $record) => $record->estUtilisee()),

                TextColumn::make('utilisee_at')
                    ->label('Utilisée le')
                    ->date('d/m/Y')
                    ->placeholder('—'),
            ])
            ->actions([
                Tables\Actions\Action::make('telecharger')
                    ->label('Voir la carte')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->url(fn (ReductionFidelite $record) => route('fidelite.telecharger', $record->token))
                    ->openUrlInNewTab(),

                Tables\Actions\Action::make('marquer_utilisee')
                    ->label('Marquer comme utilisée')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (ReductionFidelite $record) => ! $record->estUtilisee())
                    ->requiresConfirmation()
                    ->action(fn (ReductionFidelite $record) => $record->update(['utilisee_at' => now()])),

                Tables\Actions\Action::make('annuler_utilisation')
                    ->label('Annuler l\'utilisation')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('gray')
                    ->visible(fn (ReductionFidelite $record) => $record->estUtilisee())
                    ->requiresConfirmation()
                    ->action(fn (ReductionFidelite $record) => $record->update(['utilisee_at' => null])),
            ])
            ->headerActions([])
            ->paginated(false);
    }

    protected function canCreate(): bool
    {
        return false;
    }

    protected function canDelete(Model $record): bool
    {
        return false;
    }
}
