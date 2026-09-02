<div wire:key="matrice-{{ $article->id }}" class="space-y-4">

    @if ($this->epuise)
        <div class="flex items-start gap-2 rounded-lg border border-danger-300 bg-danger-50 dark:border-danger-700 dark:bg-danger-500/10 px-4 py-3 text-sm text-danger-700 dark:text-danger-300">
            @svg('heroicon-o-exclamation-triangle', 'w-5 h-5 shrink-0 mt-0.5')
            <span>Aucune combinaison n'est disponible : cet article est actuellement <strong>masqué du site public</strong>. Cochez au moins une case ci-dessous pour le rendre à nouveau visible.</span>
        </div>
    @endif

    <div class="flex items-center gap-2">
        <button type="button" wire:click="toutCocher"
                class="fi-btn fi-btn-size-sm inline-flex items-center gap-1 rounded-lg border border-gray-300 dark:border-gray-600 px-3 py-1.5 text-xs font-medium hover:bg-gray-50 dark:hover:bg-white/5 transition">
            Tout cocher
        </button>
        <button type="button" wire:click="toutDecocher"
                class="fi-btn fi-btn-size-sm inline-flex items-center gap-1 rounded-lg border border-gray-300 dark:border-gray-600 px-3 py-1.5 text-xs font-medium hover:bg-gray-50 dark:hover:bg-white/5 transition">
            Tout décocher
        </button>
    </div>

    <div class="overflow-x-auto -mx-2 px-2">
        <table class="min-w-full border-separate" style="border-spacing: 4px;">
            <thead>
                <tr>
                    <th class="text-left text-xs font-medium text-gray-500 dark:text-gray-400 px-2 py-1"></th>
                    @foreach ($this->tailles as $taille)
                        <th class="text-center px-2 py-1">
                            @if ($this->gereTailles)
                                <button type="button" wire:click="cocherColonne({{ $taille->id }})"
                                        title="Cocher toute la colonne {{ $taille->libelle }}"
                                        class="block w-full text-xs font-semibold text-gray-700 dark:text-gray-200 hover:text-primary-600 dark:hover:text-primary-400 transition">
                                    {{ $taille->libelle }}
                                </button>
                                <div class="flex justify-center gap-1 mt-0.5">
                                    <button type="button" wire:click="cocherColonne({{ $taille->id }})" title="Cocher la colonne"
                                            class="text-[10px] text-success-600 dark:text-success-400 hover:underline">tout</button>
                                    <button type="button" wire:click="decocherColonne({{ $taille->id }})" title="Décocher la colonne"
                                            class="text-[10px] text-gray-400 hover:underline">aucun</button>
                                </div>
                            @endif
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($this->couleurs as $couleur)
                    <tr>
                        <td class="px-2 py-1 whitespace-nowrap">
                            @if ($this->gereCouleurs)
                                <div class="flex items-center gap-1.5">
                                    @if ($couleur->code_hex)
                                        <span class="inline-block w-3 h-3 rounded-full ring-1 ring-gray-950/10 dark:ring-white/20 shrink-0" style="background-color: {{ $couleur->code_hex }};"></span>
                                    @endif
                                    <span class="text-xs font-medium text-gray-700 dark:text-gray-200">{{ $couleur->nom }}</span>
                                </div>
                                <div class="flex gap-1 mt-0.5">
                                    <button type="button" wire:click="cocherLigne({{ $couleur->id }})" title="Cocher toute la ligne"
                                            class="text-[10px] text-success-600 dark:text-success-400 hover:underline">tout</button>
                                    <button type="button" wire:click="decocherLigne({{ $couleur->id }})" title="Décocher toute la ligne"
                                            class="text-[10px] text-gray-400 hover:underline">aucun</button>
                                </div>
                            @else
                                <span class="text-xs font-medium text-gray-700 dark:text-gray-200">Disponible</span>
                            @endif
                        </td>

                        @foreach ($this->tailles as $taille)
                            @php $coche = $this->estCoche($taille?->id, $couleur?->id); @endphp
                            <td class="px-2 py-1 text-center">
                                <button type="button"
                                        wire:click="toggleCase({{ $taille?->id ?? 'null' }}, {{ $couleur?->id ?? 'null' }})"
                                        wire:loading.attr="disabled"
                                        aria-pressed="{{ $coche ? 'true' : 'false' }}"
                                        title="{{ $coche ? 'Disponible — cliquer pour rendre indisponible' : 'Indisponible — cliquer pour rendre disponible' }}"
                                        class="w-8 h-8 rounded-md border transition flex items-center justify-center
                                            {{ $coche
                                                ? 'bg-success-500 border-success-600 hover:bg-success-600'
                                                : 'bg-gray-100 dark:bg-white/5 border-gray-300 dark:border-gray-600 hover:border-gray-400' }}">
                                    @if ($coche)
                                        @svg('heroicon-s-check', 'w-4 h-4 text-white')
                                    @endif
                                </button>
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
