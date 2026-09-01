@php
    use App\Support\Francais;

    $tendances = $this->indicateursAvecTendance();

    $cartes = [
        ['cle' => 'commandes', 'label' => 'Commandes du jour', 'icone' => 'heroicon-o-shopping-bag', 'francs' => false],
        ['cle' => 'nouveaux_clients', 'label' => 'Nouveaux clients', 'icone' => 'heroicon-o-user-plus', 'francs' => false],
        ['cle' => 'my_verse', 'label' => 'My Verse', 'icone' => 'heroicon-o-book-open', 'francs' => false],
        ['cle' => 'total_frais', 'label' => 'Frais de livraison', 'icone' => 'heroicon-o-truck', 'francs' => true],
    ];

    $couleursSens = [
        'hausse' => 'text-emerald-600 dark:text-emerald-400',
        'baisse' => 'text-amber-600 dark:text-amber-400',
        'stable' => 'text-gray-400 dark:text-gray-500',
    ];
@endphp

<x-filament-panels::page>

    {{-- Navigation jour par jour --}}
    <div class="flex items-center justify-center gap-3 sm:gap-4 mb-5 px-2">
        <button type="button" wire:click="jourPrecedent" aria-label="Jour précédent"
                class="shrink-0 w-11 h-11 flex items-center justify-center border border-gray-300 dark:border-gray-600 rounded-none hover:border-primary-600 active:scale-95 transition text-lg">
            ‹
        </button>

        <p class="flex-1 max-w-xs text-sm font-medium text-center capitalize leading-snug">
            {{ $this->libelleJour() }}
        </p>

        <button type="button" wire:click="jourSuivant" @disabled(! $this->peutAvancer()) aria-label="Jour suivant"
                class="shrink-0 w-11 h-11 flex items-center justify-center border border-gray-300 dark:border-gray-600 rounded-none hover:border-primary-600 active:scale-95 transition disabled:opacity-30 disabled:cursor-not-allowed text-lg">
            ›
        </button>
    </div>

    {{-- Indicateurs --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-4 mb-2">
        @foreach ($cartes as $carte)
            @php $t = $tendances[$carte['cle']]; @endphp

            {{-- Double coque : coquille externe discrète + carte interne
                 surélevée — donne une impression de profondeur "premium"
                 sans ombre lourde, cohérente avec le thème sombre natif de Filament. --}}
            <div class="rounded-2xl bg-gray-950/[0.03] dark:bg-white/[0.03] ring-1 ring-gray-950/[0.06] dark:ring-white/10 p-1.5 transition duration-300 hover:ring-gray-950/[0.12] dark:hover:ring-white/20">
                <div class="rounded-[14px] bg-white dark:bg-white/[0.04] shadow-sm dark:shadow-[inset_0_1px_0_0_rgba(255,255,255,0.06)] px-4 py-4 sm:px-5 sm:py-5 h-full">
                    <div class="flex items-start justify-between gap-2 mb-3 sm:mb-4">
                        <span class="text-[10px] sm:text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide leading-snug">
                            {{ $carte['label'] }}
                        </span>
                        <span class="shrink-0 w-7 h-7 sm:w-8 sm:h-8 rounded-lg bg-primary-50 dark:bg-primary-400/10 flex items-center justify-center">
                            @svg($carte['icone'], 'w-3.5 h-3.5 sm:w-4 sm:h-4 text-primary-600 dark:text-primary-400')
                        </span>
                    </div>

                    <p class="text-xl sm:text-3xl font-bold tabular-nums text-gray-950 dark:text-white leading-none break-words">
                        {{ $carte['francs'] ? number_format($t['valeur'], 0, ',', ' ') : $t['valeur'] }}
                    </p>

                    <p class="mt-2.5 flex items-center gap-1 text-[10px] sm:text-xs font-medium {{ $couleursSens[$t['sens']] }}">
                        @if ($t['sens'] === 'hausse')
                            @svg('heroicon-m-arrow-trending-up', 'w-3.5 h-3.5 shrink-0')
                        @elseif ($t['sens'] === 'baisse')
                            @svg('heroicon-m-arrow-trending-down', 'w-3.5 h-3.5 shrink-0')
                        @else
                            @svg('heroicon-m-minus', 'w-3.5 h-3.5 shrink-0')
                        @endif
                        <span class="truncate">{{ $t['libelle_delta'] }}</span>
                    </p>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Commandes du jour --}}
    <div class="-mx-4 sm:mx-0 overflow-x-auto">
        {{ $this->table }}
    </div>

    {{-- Graphique 14 derniers jours --}}
    @livewire(\App\Filament\Widgets\CommandesRecentesWidget::class)

    {{-- Fichier clients --}}
    <div class="-mx-4 sm:mx-0 overflow-x-auto">
        @livewire(\App\Filament\Widgets\ClientsWidget::class)
    </div>

</x-filament-panels::page>
