<?php

namespace App\Filament\Pages;

use App\Models\Parametre;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class Reglages extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'Réglages';

    protected static ?int $navigationSort = 90;

    protected static string $view = 'filament.pages.reglages';

    /** @var array<string, mixed> */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill(Parametre::actuel()->only(['email_reception', 'mail_cle', 'code_acces']));
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('email_reception')
                    ->label('Adresse de réception des commandes')
                    ->email()
                    ->required()
                    ->helperText('Chaque commande enregistrée envoie un mail à cette adresse.'),

                TextInput::make('mail_cle')
                    ->label('Clé du service d\'envoi de mail')
                    ->password()
                    ->revealable()
                    ->helperText('Optionnel : clé d\'API du service transactionnel utilisé, si différent du SMTP configuré sur le serveur.'),

                TextInput::make('code_acces')
                    ->label('Code d\'accès')
                    ->required()
                    ->helperText('Valeur de départ : REVO2026. Réservé pour une future protection additionnelle de l\'Espace RÉVOLUTION.'),
            ])
            ->statePath('data');
    }

    public function enregistrer(): void
    {
        $donnees = $this->form->getState();

        Parametre::actuel()->update($donnees);

        Notification::make()
            ->title('Réglages enregistrés')
            ->success()
            ->send();
    }
}
