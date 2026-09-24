@php
    use App\Support\Francais;

    $phraseLivraison = $commande->estYango()
        ? 'la livraison Yango du '.Francais::dateHeureLongue($commande->date_souhaitee, $commande->heure_souhaitee)
        : 'la livraison (le livreur passe selon les zones, nous vous appelons à l\'approche)';

    $phrase = "Merci {$client->nom}, votre commande est bien enregistrée. Nous vous contactons au {$client->telephone} pour la confirmation et {$phraseLivraison}. Livraison à {$commande->commune} : ".Francais::frais($commande->frais_livraison).'.';

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

        @if ($reduction)
            {{-- Palier débloqué par cette commande précisément : la carte
                 générée (resources/loyalty, App\Support\CarteFidelite) sert
                 à la fois d'aperçu et de fichier téléchargé — jamais deux
                 rendus différents. --}}
            <div class="text-center mb-6 px-2">
                <p class="text-xs uppercase tracking-[0.22em] text-or font-semibold mb-2">🎉 Félicitations</p>
                <p class="text-[15px] text-encre/90 max-w-[480px] mx-auto text-pretty">
                    Cette commande débloque <strong>−{{ $reduction->pourcentage }} %</strong> sur votre prochaine commande RÉVOLUTION.
                </p>
            </div>

            <div class="mb-8">
                <img src="{{ route('fidelite.telecharger', $reduction->token) }}"
                     alt="Carte de fidélité RÉVOLUTION — {{ $client->nom }}"
                     class="w-full max-w-[420px] mx-auto border border-filet shadow-[0_18px_45px_-25px_rgba(23,18,14,0.35)] block">
            </div>
        @else
            <div class="text-center mb-8 px-2">
                <p class="text-[14px] text-texte-secondaire max-w-[440px] mx-auto text-pretty">
                    Encore {{ $client->commandes_restantes }} commande{{ $client->commandes_restantes > 1 ? 's' : '' }}
                    et vous débloquez −{{ $client->prochain_avantage }} % sur votre commande suivante.
                </p>
            </div>
        @endif

        {{-- Actions --}}
        <div class="space-y-3">
            @if ($reduction)
                <a href="{{ route('fidelite.telecharger', $reduction->token) }}"
                   class="block w-full text-center bg-rouille text-white py-4 text-sm uppercase tracking-wide font-medium transition hover:bg-rouille/90 rounded-none">
                    Télécharger ma carte de fidélité
                </a>
            @endif

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
