<?php

namespace App\Filament\Resources\StockResource\Widgets;

use App\Models\ArticleVariante;
use App\Models\CommandeLigne;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Les trois chiffres de pilotage de « Mon stock » (restructuration stock) :
 * pièces en stock (= restant, le stock suivi ne garde pas d'historique de
 * réception séparé), pièces vendues (commandes réellement livrées — le
 * fait comptable, cf. Commande::confirmerLivraison()), et variantes non
 * suivies laissées de côté du total (NULL = non suivi, jamais dans les
 * totaux — même philosophie que ArticleVariante::decrementerStock()).
 */
class StockStatsWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $enStock = (int) ArticleVariante::query()->whereNotNull('stock')->sum('stock');
        $nonSuivies = ArticleVariante::query()->whereNull('stock')->count();

        $vendu = (int) CommandeLigne::query()
            ->whereHas('commande', fn ($q) => $q->whereNotNull('livree_at')->where('statut', '!=', 'annulee'))
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
