@php
    $messagePartage = "Découvrez RÉVOLUTION — passez votre commande en moins de 2 minutes et recevez votre carte de fidélité personnalisée : ".url('/');
    $lienWhatsapp = 'https://wa.me/?text='.rawurlencode($messagePartage);
    $paliers = config('revolution.paliers');
@endphp

<x-public-layout :titre="'RÉVOLUTION — Prise de commande'">

    {{-- Bandeau pleine largeur --}}
    <div class="w-full bg-rouille py-4 sm:py-5 mb-10 sm:mb-12">
        <p class="max-w-colonne mx-auto px-5 sm:px-6 text-center text-white uppercase tracking-wide text-[13px] sm:text-base font-medium leading-snug text-balance">
            Bienvenue sur notre site de prise de commande RÉVOLUTION
        </p>
    </div>

    <x-colonne>

        <div class="text-center space-y-3 sm:space-y-4 mb-10 sm:mb-14 text-[15px] leading-relaxed text-encre/90 text-pretty">
            <p>En remplissant ce formulaire, vous enregistrez votre commande.</p>
            <p>Vous bénéficiez ainsi de votre carte de fidélité RÉVOLUTION et des avantages réservés à notre communauté.</p>
        </div>

        {{-- Deux grandes cartes cliquables — pointent vers le nouveau parcours
             catalogue (/commande) depuis la bascule. Les anciennes routes
             (/commande/my-verse, /commande/autre) restent en ligne une
             semaine en secours, mais ne sont plus liées depuis ici. --}}
        <div class="space-y-4 sm:space-y-5 mb-12 sm:mb-16">
            <a href="{{ route('commande.catalogue.creer', ['collection' => 'my_verse']) }}"
               class="group block border border-filet border-l-4 border-l-rouille bg-carte px-5 py-6 sm:px-6 sm:py-7 transition hover:border-l-[6px] hover:bg-creme active:scale-[0.99]">
                <p class="text-xs uppercase tracking-[0.18em] text-or font-semibold mb-2">My Verse</p>
                <h2 class="text-xl sm:text-2xl font-semibold text-encre mb-1 tracking-tight">Je passe ma commande</h2>
                <p class="text-sm text-texte-secondaire">Votre tee-shirt, votre verset, écrit par vous.</p>
            </a>

            <a href="{{ route('commande.catalogue.creer') }}"
               class="group block border border-filet border-l-4 border-l-rouille bg-carte px-5 py-6 sm:px-6 sm:py-7 transition hover:border-l-[6px] hover:bg-creme active:scale-[0.99]">
                <p class="text-xs uppercase tracking-[0.18em] text-or font-semibold mb-2">Découvrir</p>
                <h2 class="text-xl sm:text-2xl font-semibold text-encre mb-1 tracking-tight">Je passe ma commande</h2>
                <p class="text-sm text-texte-secondaire">Tee-shirts, pulls, chemises, accessoires — toute la collection RÉVOLUTION.</p>
            </a>
        </div>

        {{-- Bloc fidélité --}}
        <div class="border border-filet bg-carte px-4 py-7 sm:px-6 sm:py-8 mb-10">
            <p class="text-center text-[15px] mb-1">Votre fidélité est récompensée 🤎</p>
            <p class="text-center text-[15px] text-texte-secondaire mb-6 text-pretty">
                À chaque étape, profitez d'un avantage sur votre prochaine commande :
            </p>

            <div class="divide-y divide-filet border-y border-filet mb-6">
                @foreach ($paliers as $numero => $pourcentage)
                    <p class="py-3 text-center text-[14px] sm:text-[15px] text-pretty">
                        {{ $numero }}ᵉ commande effectuée → −{{ $pourcentage }} % sur votre prochaine commande
                    </p>
                @endforeach
            </div>

            <p class="text-center text-[15px] text-pretty">
                Merci de faire confiance à RÉVOLUTION et de grandir avec nous.
            </p>

            {{-- Graphique en escalier --}}
            <div class="mt-10 flex items-end justify-between gap-1.5 sm:gap-5 px-0 sm:px-2">
                @php $couleurs = ['#AB6715', '#A15815', '#984814', '#8E3914']; @endphp
                @foreach ($paliers as $numero => $pourcentage)
                    @php $i = $loop->index; @endphp
                    <div class="flex-1 flex flex-col items-center min-w-0">
                        <p class="text-base sm:text-xl font-semibold text-encre mb-2 tracking-tight">−{{ $pourcentage }}%</p>
                        <div class="w-full max-w-[64px] rounded-none" style="height: {{ 34 + $pourcentage * 1.35 }}px; background-color: {{ $couleurs[$i] }};"></div>
                        <p class="mt-2 text-[10px] sm:text-xs text-texte-secondaire text-center whitespace-nowrap">{{ $numero }}ᵉ cde</p>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Partage WhatsApp --}}
        <div class="text-center pb-4">
            <a href="{{ $lienWhatsapp }}" target="_blank" rel="noopener"
               class="inline-flex items-center justify-center gap-2 min-h-[44px] px-4 text-sm text-texte-secondaire hover:text-rouille transition border-b border-filet hover:border-rouille">
                Partager le site sur WhatsApp
            </a>
        </div>

    </x-colonne>
</x-public-layout>
