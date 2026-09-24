@php
    use App\Support\Francais;

    $messagePartage = "Je viens de commander chez RÉVOLUTION — même ta garde-robe intéresse JÉSUS ! Découvrez la marque : ".url('/');
    $lienWhatsapp = 'https://wa.me/?text='.rawurlencode($messagePartage);

    // Honnête : nombre de commandes réellement validées/livrées. La demande
    // tout juste déposée ne compte pas encore (voir la mention ci-dessous).
    $nbValidees = $client->nb_commandes ?? 0;

    // La carte (route commande.demande.carte), elle, compte la demande tout
    // juste déposée comme si elle était déjà livrée — projection calculée
    // côté serveur par DemandeController::carte().
    $urlCarte = route('commande.demande.carte', $commande->reference);
@endphp

<x-public-layout :titre="'RÉVOLUTION — Commande enregistrée'">
    <x-colonne>

        <div class="text-center mb-8 sm:mb-10 px-2">
            <svg viewBox="0 0 52 52" class="w-14 h-14 mx-auto mb-5">
                <circle cx="26" cy="26" r="25" fill="none" stroke="#8E3914" stroke-width="2"/>
                <path class="revo-coche" d="M15 27l7 7 15-15" fill="none" stroke="#8E3914" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <h1 class="text-2xl sm:text-3xl font-semibold text-encre mb-4 tracking-tight">Commande enregistrée</h1>
            <p class="text-[15px] leading-relaxed text-encre/90 max-w-[520px] mx-auto text-pretty">
                Merci d'avoir rempli le formulaire. Le livreur vous contactera à la date indiquée pour la livraison.
                Votre reçu de paiement vous sera envoyé par e-mail ou par WhatsApp.
            </p>
        </div>

        {{-- Statut honnête : jamais dupliqué avec le visuel de la carte ci-dessous. --}}
        <div class="text-center mb-6 px-2">
            <p class="text-[13px] text-texte-secondaire">
                Client·e n° {{ $client->numero_client }} ·
                {{ $nbValidees }} commande{{ $nbValidees > 1 ? 's' : '' }} validée{{ $nbValidees > 1 ? 's' : '' }}
            </p>
            <p class="text-[13px] text-texte-secondaire mt-1">
                Votre commande en cours sera comptée une fois livrée.
            </p>
        </div>

        {{-- Carte de fidélité --}}
        <div class="space-y-3">
            <img src="{{ $urlCarte }}" alt="Carte de fidélité RÉVOLUTION" class="w-full border border-filet shadow-[0_18px_45px_-25px_rgba(23,18,14,0.35)] mb-1">

            <a href="{{ $urlCarte }}" download="carte-fidelite-revolution.png"
               class="block w-full text-center bg-rouille text-white py-4 text-sm uppercase tracking-wide font-medium transition hover:bg-rouille/90 rounded-none">
                Télécharger ma carte de fidélité
            </a>

            <a href="{{ $lienWhatsapp }}" target="_blank" rel="noopener"
               class="block w-full text-center border border-filet text-encre py-4 text-sm uppercase tracking-wide font-medium transition hover:border-rouille rounded-none">
                Partager sur WhatsApp
            </a>
            <a href="{{ route('accueil') }}"
               class="block w-full text-center text-sm text-texte-secondaire hover:text-rouille transition py-3">
                Retour à l'accueil
            </a>
        </div>

    </x-colonne>
</x-public-layout>
