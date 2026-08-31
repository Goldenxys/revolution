<x-guest-layout>
    <div class="mb-5 text-sm text-texte-secondaire leading-relaxed">
        Indiquez votre email et nous vous envoyons un lien pour choisir un nouveau mot de passe.
    </div>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
        @csrf

        <div>
            <x-input-label for="email" value="Email" />
            <x-text-input id="email" type="email" name="email" :value="old('email')" required autofocus />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <x-primary-button class="w-full">
            Envoyer le lien
        </x-primary-button>
    </form>
</x-guest-layout>
