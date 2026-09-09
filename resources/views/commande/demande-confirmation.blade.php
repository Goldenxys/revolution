@php
    use App\Support\Francais;

    $paliers = config('revolution.paliers');
    $messagePartage = "Je viens de commander chez RÉVOLUTION — même ta garde-robe intéresse JÉSUS ! Découvrez la marque : ".url('/');
    $lienWhatsapp = 'https://wa.me/?text='.rawurlencode($messagePartage);

    // La carte annonce le palier issu des commandes VALIDÉES. La demande qui
    // vient d'être déposée ne compte pas encore.
    $nbValidees = $client->nb_commandes ?? 0;
    $palier = $nbValidees > 0 ? ((($nbValidees - 1) % 8) + 1) : 0;
    $avantage = \App\Models\Client::avantagePourNumero($nbValidees);
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
                Merci {{ $client->nom }}. Nous confirmons le contenu et le montant définitif très vite, puis nous vous
                recontactons au {{ $client->telephone }} pour la livraison à {{ $commande->commune }}.
            </p>
            <p class="mt-3 text-xs uppercase tracking-wide text-texte-secondaire">Référence {{ $commande->reference }}</p>
            @if ($client->email)
                <p class="mt-2 text-[13px] text-texte-secondaire">Votre reçu partira à {{ $client->email }} dès la validation.</p>
            @endif
        </div>

        {{-- Carte de fidélité --}}
        <div class="bg-carte border border-filet shadow-[0_18px_45px_-25px_rgba(23,18,14,0.35)] px-5 py-8 sm:px-8 sm:py-10 mb-8">
            <img src="{{ asset('img/logo-revolution.png') }}" alt="RÉVOLUTION" class="w-32 sm:w-40 mx-auto mb-6">

            <p class="text-center text-xs uppercase tracking-[0.22em] text-or font-semibold mb-4">Carte de fidélité REVO.</p>

            <p class="text-center text-xl sm:text-2xl font-semibold text-encre uppercase mb-1 tracking-tight text-balance">{{ $client->nom }}</p>
            <p class="text-center text-[13px] text-texte-secondaire mb-8">Client·e n° {{ $client->numero_client }}</p>

            <div class="flex items-start justify-between gap-1.5 sm:gap-4 mb-8">
                @foreach ($paliers as $seuil => $pourcentage)
                    @php $atteint = $palier >= $seuil; @endphp
                    <div class="flex-1 flex flex-col items-center min-w-0">
                        <div class="w-12 h-12 sm:w-20 sm:h-20 rounded-full flex items-center justify-center text-base sm:text-2xl font-semibold shrink-0
                            {{ $atteint ? 'bg-rouille text-white' : 'border-2 border-gray-300 text-texte-secondaire' }}">
                            {{ $seuil }}
                        </div>
                        <p class="text-[11px] sm:text-sm text-encre mt-1.5 whitespace-nowrap">−{{ $pourcentage }} %</p>
                    </div>
                @endforeach
            </div>

            <p class="text-center text-[15px] font-medium mb-3">
                {{ $nbValidees }} commande{{ $nbValidees > 1 ? 's' : '' }} validée{{ $nbValidees > 1 ? 's' : '' }}
                @if ($palier > 0) · palier {{ $palier }}/8 @endif
            </p>

            <p class="text-center text-[14px] text-rouille leading-relaxed max-w-[440px] mx-auto text-pretty">
                @if ($avantage)
                    Vous venez de débloquer −{{ $avantage }} % sur votre prochaine commande.
                @else
                    @php $prochainSeuil = collect(array_keys($paliers))->first(fn ($s) => $s > $palier) ?? array_key_first($paliers); @endphp
                    Encore {{ max(1, $prochainSeuil - $palier) }} commande{{ ($prochainSeuil - $palier) > 1 ? 's' : '' }}
                    et vous passez à −{{ $paliers[$prochainSeuil] }} %.
                @endif
            </p>

            <p class="text-center text-[13px] text-texte-secondaire mt-5 border-t border-filet pt-4">
                Votre commande en cours sera comptée dès sa validation par la gérante.
            </p>
        </div>

        <div class="space-y-3">
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
