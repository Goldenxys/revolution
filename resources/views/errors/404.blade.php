<x-public-layout :titre="'RÉVOLUTION — Page introuvable'">
    <x-colonne>
        <div class="flex flex-col items-center text-center px-2">

            <img src="{{ asset('img/404-illustration.png') }}" alt=""
                 class="w-52 sm:w-64 h-auto mb-6 sm:mb-8 select-none pointer-events-none revo-apparition"
                 draggable="false">

            <p class="text-xs uppercase tracking-[0.18em] text-or font-semibold mb-3 revo-apparition" style="animation-delay:.05s">
                Erreur 404
            </p>

            <h1 class="text-2xl sm:text-3xl font-semibold text-encre tracking-tight text-balance mb-4 revo-apparition" style="animation-delay:.1s">
                Ce cintre est vide
            </h1>

            <p class="text-[15px] leading-relaxed text-encre/80 max-w-[420px] mx-auto mb-10 text-pretty revo-apparition" style="animation-delay:.15s">
                La page que vous cherchez n'existe pas, plus, ou son adresse comporte une erreur.
                Revenez à l'accueil pour découvrir nos collections et passer votre commande.
            </p>

            <div class="w-full max-w-xs space-y-3 revo-apparition" style="animation-delay:.2s">
                <a href="{{ route('accueil') }}"
                   class="block w-full min-h-[52px] leading-[52px] text-center bg-rouille text-white text-sm uppercase tracking-wide font-medium transition hover:bg-rouille/90 active:scale-[0.99]">
                    Retour à l'accueil
                </a>
                <a href="{{ route('commande.catalogue.creer') }}"
                   class="block w-full min-h-[52px] leading-[52px] text-center border border-filet text-encre text-sm uppercase tracking-wide font-medium transition hover:border-rouille active:scale-[0.99]">
                    Je passe ma commande
                </a>
            </div>

        </div>
    </x-colonne>
</x-public-layout>
