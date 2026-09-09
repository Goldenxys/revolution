@php
    use App\Support\Francais;
    $c = $this->record;
    $client = $c->client;
    $souhaits = $c->souhaits_client ?? [];
@endphp

<x-filament-panels::page>
    <div class="grid gap-6 lg:grid-cols-[minmax(0,22rem),1fr]">

        {{-- À gauche — ce que la cliente a dit. Lecture seule, jamais modifiable. --}}
        <aside class="space-y-4">
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
                <x-slot name="heading">Ses souhaits</x-slot>
                <x-slot name="description">Tels qu'elle les a saisis — la vente se fait dans la conversation.</x-slot>

                @if (empty($souhaits))
                    <p class="text-sm text-gray-500">Aucun article coché. Voir ses précisions ci-dessous.</p>
                @else
                    <ul class="space-y-2 text-sm">
                        @foreach ($souhaits as $s)
                            <li class="rounded-lg bg-gray-50 dark:bg-white/5 px-3 py-2">
                                <span class="font-medium">{{ $s['article_nom'] ?? 'Article' }}</span>
                                @php
                                    $meta = array_filter([$s['taille'] ?? null, $s['couleur'] ?? null]);
                                @endphp
                                @if ($meta)
                                    <span class="text-gray-500">· {{ implode(' · ', $meta) }}</span>
                                @endif
                                <span class="text-gray-500">× {{ (int) ($s['quantite'] ?? 1) }}</span>
                                @if (!empty($s['note']))
                                    <p class="text-gray-500 mt-1">« {{ $s['note'] }} »</p>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-filament::section>

            @if ($c->message_client)
                <x-filament::section>
                    <x-slot name="heading">Ses précisions</x-slot>
                    <p class="text-sm whitespace-pre-line">{{ $c->message_client }}</p>
                </x-filament::section>
            @endif

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
