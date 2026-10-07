<x-filament-panels::page>
    <form wire:submit="enregistrer">
        {{ $this->form }}

        <x-filament::button type="submit" class="mt-6">
            Enregistrer
        </x-filament::button>
    </form>

    <x-filament::section class="mt-8">
        <x-slot name="heading">Mot de passe</x-slot>
        <x-slot name="description">Pour vous connecter à l'Espace RÉVOLUTION.</x-slot>

        <form wire:submit="changerMotDePasse">
            {{ $this->passwordForm }}

            <x-filament::button type="submit" class="mt-6">
                Changer le mot de passe
            </x-filament::button>
        </form>
    </x-filament::section>
</x-filament-panels::page>
