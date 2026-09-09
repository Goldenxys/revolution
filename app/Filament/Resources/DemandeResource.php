<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DemandeResource\Pages;
use App\Models\Commande;
use App\Support\Francais;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * « Demandes à valider » (V2 §5.1) — le nouveau centre de gravité du
 * panneau. Ne montre que les demandes déposées par les clientes et pas
 * encore composées : statut `en_attente`. Une fois validée, la commande
 * quitte cet écran et rejoint « Commandes » (CommandeResource).
 */
class DemandeResource extends Resource
{
    protected static ?string $model = Commande::class;

    protected static ?string $navigationIcon = 'heroicon-o-inbox-arrow-down';

    protected static ?string $navigationLabel = 'Demandes à valider';

    protected static ?string $modelLabel = 'demande';

    protected static ?string $pluralModelLabel = 'demandes à valider';

    // Juste après le tableau de bord (navigationSort -10), avant tout le reste.
    protected static ?int $navigationSort = -9;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('statut', 'en_attente');
    }

    public static function canCreate(): bool
    {
        // Une demande naît toujours du formulaire public (ou, en Phase 2,
        // d'une insertion manuelle en base par la gérante).
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) (static::getModel()::query()->where('statut', 'en_attente')->count() ?: '');
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('client'))
            // La plus ancienne demande en premier : on traite dans l'ordre d'arrivée.
            ->defaultSort('created_at', 'asc')
            ->columns([
                TextColumn::make('reference')
                    ->label('Réf.')
                    ->searchable(),

                TextColumn::make('created_at')
                    ->label('Reçue')
                    ->dateTime('d/m à H:i')
                    ->sortable(),

                TextColumn::make('client.nom')
                    ->label('Cliente')
                    ->description(fn (Commande $c) => $c->client?->telephone)
                    ->badge(fn (Commande $c) => $c->client?->estFidele()
                        ? 'fidèle — '.Francais::ordinal(($c->client->nb_commandes ?? 0) + 1)
                        : 'nouvelle')
                    ->color(fn (Commande $c) => $c->client?->estFidele() ? 'gold' : 'success')
                    ->searchable(query: fn (Builder $q, string $s) => $q->whereHas(
                        'client',
                        fn (Builder $qc) => $qc->where('nom', 'like', "%{$s}%")->orWhere('telephone', 'like', "%{$s}%")
                    )),

                TextColumn::make('demande')
                    ->label('Demande')
                    ->state(fn (Commande $c) => $c->estMyVerse()
                        ? count($c->souhaits_client['versets'] ?? []).' tee-shirt My Verse'
                        : 'Autre article')
                    ->description(fn (Commande $c) => static::resumeSouhaits($c))
                    ->wrap(),

                TextColumn::make('commune')
                    ->label('Livraison')
                    ->description(fn (Commande $c) => $c->estYango() ? 'Yango' : 'Livreur'),
            ])
            ->actions([
                Tables\Actions\Action::make('composer')
                    ->label('Composer et valider')
                    ->icon('heroicon-o-pencil-square')
                    ->button()
                    ->url(fn (Commande $record) => Pages\ComposerDemande::getUrl(['record' => $record])),
            ])
            ->emptyStateHeading('Aucune demande en attente')
            ->emptyStateDescription('Les nouvelles demandes déposées par les clientes apparaîtront ici.')
            ->emptyStateIcon('heroicon-o-check-circle');
    }

    /**
     * Résumé de ce que la cliente a saisi (jamais des lignes fermes —
     * celles-ci n'existent qu'à la validation). Pour My Verse : la liste
     * des versets. Pour un autre article : ses précisions.
     */
    public static function resumeSouhaits(Commande $commande): string
    {
        if ($commande->estMyVerse()) {
            $versets = collect($commande->souhaits_client['versets'] ?? [])
                ->map(fn (array $v) => $v['reference'] ?: Str::limit($v['texte'] ?? '', 40))
                ->filter()
                ->implode(' | ');

            $resume = $versets ?: 'verset à préciser avec la cliente';

            return $commande->message_client
                ? $resume.' — « '.Str::limit($commande->message_client, 60).' »'
                : $resume;
        }

        return $commande->message_client
            ? '« '.Str::limit($commande->message_client, 90).' »'
            : 'Article convenu sur WhatsApp';
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDemandes::route('/'),
            'composer' => Pages\ComposerDemande::route('/{record}/composer'),
        ];
    }
}
