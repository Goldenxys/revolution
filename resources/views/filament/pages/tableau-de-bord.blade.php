@php($indicateurs = $this->indicateurs())

<x-filament-panels::page>

    {{-- Navigation jour par jour --}}
    <div class="flex items-center justify-center gap-3 sm:gap-4 mb-2 px-2">
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
        <div class="border border-gray-200 dark:border-gray-700 p-3 sm:p-4 text-center">
            <p class="text-xl sm:text-2xl font-semibold tabular-nums">{{ $indicateurs['commandes'] }}</p>
            <p class="text-[11px] sm:text-xs text-gray-500 mt-1 leading-snug">Commandes du jour</p>
        </div>
        <div class="border border-gray-200 dark:border-gray-700 p-3 sm:p-4 text-center">
            <p class="text-xl sm:text-2xl font-semibold tabular-nums">{{ $indicateurs['nouveaux_clients'] }}</p>
            <p class="text-[11px] sm:text-xs text-gray-500 mt-1 leading-snug">Nouveaux clients</p>
        </div>
        <div class="border border-gray-200 dark:border-gray-700 p-3 sm:p-4 text-center">
            <p class="text-xl sm:text-2xl font-semibold tabular-nums">{{ $indicateurs['my_verse'] }}</p>
            <p class="text-[11px] sm:text-xs text-gray-500 mt-1 leading-snug">My Verse</p>
        </div>
        <div class="border border-gray-200 dark:border-gray-700 p-3 sm:p-4 text-center">
            <p class="text-xl sm:text-2xl font-semibold tabular-nums break-words">{{ number_format($indicateurs['total_frais'], 0, ',', ' ') }}</p>
            <p class="text-[11px] sm:text-xs text-gray-500 mt-1 leading-snug">Frais de livraison (F CFA)</p>
        </div>
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
