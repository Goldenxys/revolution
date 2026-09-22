<?php

namespace App\Filament\Resources\StockResource\Widgets;

use App\Models\ArticleVariante;
use App\Models\CommandeLigne;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

/**
 * Les trois chiffres de pilotage de « Mon stock » (restructuration stock) :
 * pièces en stock (= restant, le stock suivi ne garde pas d'historique de
 * réception séparé), pièces vendues (commandes réellement livrées — le
 * fait comptable, cf. Commande::confirmerLivraison()), et variantes non
 * suivies laissées de côté du total (NULL = non suivi, jamais dans les
 * totaux — même philosophie que ArticleVariante::decrementerStock()).
 * Les collections fabriquées à la demande (My verse) sont exclues partout
 * ici, comme du tableau lui-même : elles n'ont pas de pièces en réserve.
 */
class StockStatsWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $variantesSuivies = fn (Builder $q) => $q->whereDoesntHave(
            'article.collection',
            fn (Builder $qc) => $qc->where('gere_stock', false)
        );

        $enStock = (int) ArticleVariante::query()->whereNotNull('stock')->tap($variantesSuivies)->sum('stock');
        $nonSuivies = ArticleVariante::query()->whereNull('stock')->tap($variantesSuivies)->count();

        $vendu = (int) CommandeLigne::query()
            ->whereHas('commande', fn ($q) => $q->whereNotNull('livree_at')->where('statut', '!=', 'annulee'))
            ->whereDoesntHave('article.collection', fn (Builder $qc) => $qc->where('gere_stock', false))
            ->sum('quantite');

        return [
            Stat::make('Pièces en stock', number_format($enStock, 0, ',', ' '))
                ->description($nonSuivies > 0 ? "{$nonSuivies} variante(s) non suivie(s), hors total" : 'Toutes les variantes sont suivies')
                ->color('success'),

            Stat::make('Pièces vendues', number_format($vendu, 0, ',', ' '))
                ->description('Commandes livrées uniquement')
                ->color('primary'),

            Stat::make('Pièces restantes', number_format($enStock, 0, ',', ' '))
                ->description('= stock actuel')
                ->color('gray'),
        ];
    }
}
