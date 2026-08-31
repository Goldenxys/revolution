<x-guest-layout>
    <div class="mb-5 text-sm text-texte-secondaire leading-relaxed">
        Zone sécurisée : confirmez votre mot de passe avant de continuer.
    </div>

    <form method="POST" action="{{ route('password.confirm') }}" class="space-y-5">
        @csrf

        <div>
            <x-input-label for="password" value="Mot de passe" />
            <x-text-input id="password" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <x-primary-button class="w-full">
            Confirmer
        </x-primary-button>
    </form>
</x-guest-layout>
