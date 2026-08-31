@php
    use App\Support\Francais;

    $phraseLivraison = $commande->estYango()
        ? 'la livraison Yango du '.Francais::dateHeureLongue($commande->date_souhaitee, $commande->heure_souhaitee)
        : 'la livraison (le livreur passe selon les zones, nous vous appelons à l\'approche)';

    $phrase = "Merci {$client->nom}, votre commande est bien enregistrée. Nous vous contactons au {$client->telephone} pour la confirmation et {$phraseLivraison}. Livraison à {$commande->commune} : ".Francais::frais($commande->frais_livraison).'.';

    $paliers = config('revolution.paliers');
    $messagePartage = "Je viens de commander chez RÉVOLUTION — même ta garde-robe intéresse JÉSUS ! Découvrez la marque : ".url('/');
    $lienWhatsapp = 'https://wa.me/?text='.rawurlencode($messagePartage);
@endphp

<x-public-layout :titre="'RÉVOLUTION — Commande enregistrée'">
    <x-colonne>

        <div class="text-center mb-8 sm:mb-10 px-2">
            <svg viewBox="0 0 52 52" class="w-14 h-14 mx-auto mb-5">
                <circle cx="26" cy="26" r="25" fill="none" stroke="#8E3914" stroke-width="2"/>
                <path class="revo-coche" d="M15 27l7 7 15-15" fill="none" stroke="#8E3914" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <h1 class="text-2xl sm:text-3xl font-semibold text-encre mb-4 tracking-tight">Commande enregistrée</h1>
            <p class="text-[15px] leading-relaxed text-encre/90 max-w-[520px] mx-auto text-pretty">{{ $phrase }}</p>
            <p class="mt-3 text-xs uppercase tracking-wide text-texte-secondaire">Référence {{ $commande->reference }}</p>
        </div>

        {{-- Carte de fidélité --}}
        <div class="bg-carte border border-filet shadow-[0_18px_45px_-25px_rgba(23,18,14,0.35)] px-5 py-8 sm:px-8 sm:py-10 mb-10">
            <img src="{{ asset('img/logo-revolution.png') }}" alt="RÉVOLUTION" class="w-32 sm:w-40 mx-auto mb-6">

            <p class="text-center text-xs uppercase tracking-[0.22em] text-or font-semibold mb-6">Carte de fidélité REVO.</p>

            <p class="text-center text-xl sm:text-3xl font-semibold text-encre uppercase mb-4 tracking-tight text-balance">{{ $client->nom }}</p>

            <p class="text-center text-[14px] text-texte-secondaire leading-relaxed max-w-[420px] mx-auto mb-8 sm:mb-10 text-pretty">
                Vous faites partie de nos précieux clients RÉVOLUTION. Merci pour votre confiance et pour chacune de vos commandes.
            </p>

            {{-- Pastilles --}}
            <div class="flex items-start justify-between gap-1.5 sm:gap-4 mb-8 sm:mb-10">
                @foreach ($paliers as $seuil => $pourcentage)
                    @php $atteint = $client->palier >= $seuil; @endphp
                    <div class="flex-1 flex flex-col items-center min-w-0 {{ $atteint ? 'revo-pastille-atteinte' : '' }}">
                        <div class="w-12 h-12 sm:w-20 sm:h-20 rounded-full flex items-center justify-center text-base sm:text-2xl font-semibold shrink-0
                            {{ $atteint ? 'bg-rouille text-white' : 'border-2 border-gray-300 text-texte-secondaire' }}">
                            {{ $seuil }}
                        </div>
                        <div class="h-4 sm:h-5 mt-1.5">
                            @if ($atteint)
                                <svg viewBox="0 0 20 20" class="w-3.5 h-3.5 sm:w-4 sm:h-4 mx-auto"><path d="M4 10l4 4 8-8" fill="none" stroke="#17120E" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            @endif
                        </div>
                        <p class="text-[11px] sm:text-sm text-encre mt-0.5 whitespace-nowrap">−{{ $pourcentage }} %</p>
                    </div>
                @endforeach
            </div>

            <p class="text-center text-[15px] font-medium mb-4">
                {{ $client->nb_commandes }} commande{{ $client->nb_commandes > 1 ? 's' : '' }} enregistrée{{ $client->nb_commandes > 1 ? 's' : '' }} · palier {{ $client->palier }}/8
            </p>

            <p class="text-center text-[14px] text-rouille leading-relaxed max-w-[440px] mx-auto mb-8 text-pretty">
                @if ($client->avantage)
                    Vous venez de débloquer −{{ $client->avantage }} % sur votre prochaine commande RÉVOLUTION. Il vous suffira de nous envoyer une capture de votre carte de fidélité au moment de votre prochaine commande.
                @else
                    Encore {{ $client->commandes_restantes }} commande{{ $client->commandes_restantes > 1 ? 's' : '' }} et vous débloquez −{{ $client->prochain_avantage }} % sur votre commande suivante.
                @endif
            </p>

            <div class="border-t border-filet pt-5">
                <p class="text-center text-[11px] sm:text-[12px] text-texte-secondaire leading-relaxed">
                    @foreach ($paliers as $seuil => $pourcentage)
                        {{ $seuil }}ᵉ cde → −{{ $pourcentage }} %{{ !$loop->last ? ' · ' : '' }}
                    @endforeach
                </p>
            </div>
        </div>

        {{-- Actions --}}
        <div
            x-data="carteFidelite({
                nom: @js($client->nom),
                nbCommandes: {{ $client->nb_commandes }},
                palier: {{ $client->palier }},
                avantage: {{ $client->avantage ?? 'null' }},
                prochainAvantage: {{ $client->prochain_avantage }},
                commandesRestantes: {{ $client->commandes_restantes }},
                paliers: @js($paliers),
                logoUrl: @js(asset('img/logo-revolution.png')),
            })"
            class="space-y-3"
        >
            <button type="button" @click="telecharger()" :disabled="telechargementEnCours"
                    class="w-full bg-rouille text-white py-4 text-sm uppercase tracking-wide font-medium transition hover:bg-rouille/90 disabled:opacity-60 rounded-none">
                <span x-show="!telechargementEnCours">Télécharger ma carte</span>
                <span x-show="telechargementEnCours" x-cloak>Génération…</span>
            </button>

            <a href="{{ $lienWhatsapp }}" target="_blank" rel="noopener"
               class="block w-full text-center border border-filet text-encre py-4 text-sm uppercase tracking-wide font-medium transition hover:border-rouille rounded-none">
                Partager sur WhatsApp
            </a>

            <a href="{{ route('accueil') }}"
               class="block w-full text-center text-sm text-texte-secondaire hover:text-rouille transition py-3">
                Nouvelle commande
            </a>
        </div>

    </x-colonne>
</x-public-layout>
