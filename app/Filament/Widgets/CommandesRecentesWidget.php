<?php

namespace App\Filament\Widgets;

use App\Models\Commande;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class CommandesRecentesWidget extends ChartWidget
{
    protected static ?string $heading = 'Commandes des 14 derniers jours';

    protected static ?int $sort = 20;

    protected int|string|array $columnSpan = 'full';

    protected function getData(): array
    {
        $debut = Carbon::today()->subDays(13);

        $parJour = Commande::query()
            ->selectRaw('DATE(created_at) as jour, COUNT(*) as total')
            ->where('created_at', '>=', $debut->copy()->startOfDay())
            ->groupBy('jour')
            ->pluck('total', 'jour');

        $labels = [];
        $valeurs = [];

        for ($i = 0; $i < 14; $i++) {
            $jour = $debut->copy()->addDays($i);
            $labels[] = $jour->locale('fr')->translatedFormat('d MMM');
            $valeurs[] = (int) ($parJour[$jour->toDateString()] ?? 0);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Commandes',
                    'data' => $valeurs,
                    'borderColor' => '#8E3914',
                    'backgroundColor' => 'rgba(142, 57, 20, 0.12)',
                    'fill' => true,
                    'tension' => 0.35,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
