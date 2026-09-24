@php
    use App\Support\Francais;
    $c = $this->record;
    $client = $c->client;
@endphp

<x-filament-panels::page>
    <div class="grid gap-6 lg:grid-cols-[minmax(0,22rem),1fr]">

        {{-- À gauche — ce que la cliente a dit. Lecture seule, jamais modifiable. --}}
        <aside data-tour="souhaits" class="space-y-4">
            <x-filament::section>
                <x-slot name="heading">La cliente</x-slot>

                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-500">Nom</dt>
                        <dd class="font-medium text-right">{{ $client?->nom }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-500">Téléphone</dt>
                        <dd class="text-right">
                            <a href="https://wa.me/{{ preg_replace('/\D+/', '', $client?->telephone ?? '') }}"
                               target="_blank" rel="noopener"
                               class="text-primary-600 dark:text-primary-400 hover:underline">
                                {{ $client?->telephone }}
                            </a>
                        </dd>
                    </div>
                    @if ($client?->email)
                        <div class="flex justify-between gap-3">
                            <dt class="text-gray-500">E-mail</dt>
                            <dd class="text-right break-all">{{ $client->email }}</dd>
                        </div>
                    @endif
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-500">Historique</dt>
                        <dd class="text-right">
                            {{ $client?->nb_commandes ?? 0 }} commande{{ ($client?->nb_commandes ?? 0) > 1 ? 's' : '' }} validée{{ ($client?->nb_commandes ?? 0) > 1 ? 's' : '' }}
                            @if (($client?->ca_cumule ?? 0) > 0)
                                <br><span class="text-gray-500">CA cumulé {{ Francais::frais($client->ca_cumule) }}</span>
                            @endif
                        </dd>
                    </div>
                </dl>
            </x-filament::section>

            <x-filament::section>
                <x-slot name="heading">Sa demande</x-slot>
                <x-slot name="description">Ce qu'elle a indiqué — la vente s'est faite dans la conversation.</x-slot>

                @if ($c->estMyVerse())
                    @php $versets = $c->souhaits_client['versets'] ?? []; @endphp
                    <p class="text-sm font-medium mb-2">{{ count($versets) }} tee-shirt My Verse — modèle à régler ici</p>
                    <ol class="space-y-2 text-sm">
                        @foreach ($versets as $i => $v)
                            <li class="rounded-lg bg-gray-50 dark:bg-white/5 px-3 py-2">
                                <span class="text-gray-500">Tee-shirt {{ $i + 1 }} —</span>
                                <span class="font-medium">{{ $v['reference'] ?: 'verset sans référence' }}</span>
                                @if (!empty($v['taille_libelle']) || !empty($v['couleur_nom']))
                                    <span class="text-gray-500"> · {{ collect([$v['taille_libelle'] ?? null, $v['couleur_nom'] ?? null])->filter()->implode(' / ') }}</span>
                                @endif
                                @if (!empty($v['texte']))
                                    <p class="text-gray-600 dark:text-gray-300 mt-1 whitespace-pre-line">{{ $v['texte'] }}</p>
                                @endif
                            </li>
                        @endforeach
                    </ol>
                @else
                    @php $articles = $c->souhaits_client['articles'] ?? []; @endphp
                    @if (count($articles))
                        <p class="text-sm font-medium mb-2">{{ count($articles) }} article{{ count($articles) > 1 ? 's' : '' }} demandé{{ count($articles) > 1 ? 's' : '' }}</p>
                        <ol class="space-y-2 text-sm">
                            @foreach ($articles as $a)
                                <li class="rounded-lg bg-gray-50 dark:bg-white/5 px-3 py-2">
                                    <span class="font-medium">{{ $a['nom'] ?? 'Article sans nom' }}</span>
                                    <span class="text-gray-500">× {{ $a['quantite'] ?? 1 }}</span>
                                    @if (!empty($a['taille_libelle']) || !empty($a['couleur_nom']))
                                        <p class="text-gray-600 dark:text-gray-300 mt-1">
                                            {{ collect([$a['taille_libelle'] ?? null, $a['couleur_nom'] ?? null])->filter()->implode(' / ') }}
                                        </p>
                                    @endif
                                    @unless (!empty($a['article_id']))
                                        <p class="text-warning-600 dark:text-warning-400 mt-1 text-xs">Pas encore reconnu dans le catalogue</p>
                                    @endunless
                                </li>
                            @endforeach
                        </ol>
                    @else
                        <p class="text-sm font-medium">Un autre article de la collection</p>
                        <p class="text-sm text-gray-500 mt-1">Aucun article détaillé — voir les précisions ci-dessous ou reprendre sur WhatsApp.</p>
                    @endif
                @endif

                @if ($c->message_client)
                    <p class="mt-3 pt-3 border-t border-gray-200 dark:border-white/10 text-sm">
                        <span class="text-gray-500">Précisions :</span> {{ $c->message_client }}
                    </p>
                @endif
            </x-filament::section>

            <x-filament::section>
                <x-slot name="heading">Livraison choisie</x-slot>
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-500">Commune</dt>
                        <dd class="text-right">{{ $c->commune }}@if ($c->quartier) · {{ $c->quartier }}@endif</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-500">Mode</dt>
                        <dd class="text-right">
                            {{ $c->estYango() ? 'Yango — '.Francais::dateHeureLongue($c->date_souhaitee, $c->heure_souhaitee) : 'Livreur normal' }}
                        </dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-500">Frais</dt>
                        <dd class="text-right">{{ Francais::frais($c->frais_livraison) }} <span class="text-gray-400">hors CA</span></dd>
                    </div>
                </dl>
            </x-filament::section>
        </aside>

        {{-- À droite — ce que la gérante décide. --}}
        <div>
            {{ $this->form }}
        </div>
    </div>
</x-filament-panels::page>
