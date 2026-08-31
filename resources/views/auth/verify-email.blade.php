<x-guest-layout>
    <div class="mb-5 text-sm text-texte-secondaire leading-relaxed">
        Merci ! Confirmez votre adresse email en cliquant sur le lien que nous venons de vous envoyer. Rien reçu ? Nous pouvons vous en renvoyer un.
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-4 text-sm text-green-700 font-medium">
            Un nouveau lien de vérification a été envoyé à votre adresse email.
        </div>
    @endif

    <div class="flex items-center justify-between gap-4">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <x-primary-button>
                Renvoyer l'email
            </x-primary-button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="text-sm text-texte-secondaire hover:text-rouille transition underline underline-offset-4 decoration-filet min-h-[44px]">
                Se déconnecter
            </button>
        </form>
    </div>
</x-guest-layout>
