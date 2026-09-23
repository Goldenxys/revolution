<div wire:key="matrice-{{ $article->id }}" class="space-y-4">

    @if ($this->epuise)
        <div class="flex items-start gap-2 rounded-lg border border-danger-300 bg-danger-50 dark:border-danger-700 dark:bg-danger-500/10 px-4 py-3 text-sm text-danger-700 dark:text-danger-300">
            @svg('heroicon-o-exclamation-triangle', 'w-5 h-5 shrink-0 mt-0.5')
            <span>Aucune variante n'a de stock enregistré : cet article est actuellement <strong>masqué du site public</strong>. Enregistrez du stock dans <strong>Mon stock</strong> pour le rendre à nouveau visible.</span>
        </div>
    @else
        <div class="flex items-start gap-2 rounded-lg border border-gray-300 bg-gray-50 dark:border-gray-600 dark:bg-white/5 px-4 py-3 text-sm text-gray-600 dark:text-gray-300">
            @svg('heroicon-o-information-circle', 'w-5 h-5 shrink-0 mt-0.5')
            <span>Disponibilité calculée automatiquement depuis le stock enregistré dans <strong>Mon stock</strong> — coché dès qu'une variante a du stock, décoché sinon. Affichage seul ici.</span>
        </div>
    @endif

    <div class="overflow-x-auto -mx-2 px-2">
        <table class="min-w-full border-separate" style="border-spacing: 4px;">
            <thead>
                <tr>
                    <th class="text-left text-xs font-medium text-gray-500 dark:text-gray-400 px-2 py-1"></th>
                    @foreach ($this->tailles as $taille)
                        <th class="text-center px-2 py-1">
                            @if ($this->gereTailles)
                                <span class="block w-full text-xs font-semibold text-gray-700 dark:text-gray-200">{{ $taille->libelle }}</span>
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
                            @else
                                <span class="text-xs font-medium text-gray-700 dark:text-gray-200">Disponible</span>
                            @endif
                        </td>

                        @foreach ($this->tailles as $taille)
                            @php $coche = $this->estCoche($taille?->id, $couleur?->id); @endphp
                            <td class="px-2 py-1 text-center">
                                <div title="{{ $coche ? 'En stock' : 'Pas de stock enregistré' }}"
                                     class="w-8 h-8 rounded-md border flex items-center justify-center
                                        {{ $coche
                                            ? 'bg-success-500 border-success-600'
                                            : 'bg-gray-100 dark:bg-white/5 border-gray-300 dark:border-gray-600' }}">
                                    @if ($coche)
                                        @svg('heroicon-s-check', 'w-4 h-4 text-white')
                                    @endif
                                </div>
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
