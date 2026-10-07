<?php

namespace App\Filament\Pages;

use App\Models\Parametre;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class Reglages extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'Réglages';

    protected static ?int $navigationSort = 90;

    protected static string $view = 'filament.pages.reglages';

    /** @var array<string, mixed> */
    public ?array $data = [];

    /** @var array<string, mixed> */
    public ?array $passwordData = [];

    public function mount(): void
    {
        $this->form->fill(Parametre::actuel()->only(['email_reception', 'mail_cle', 'code_acces']));
        $this->passwordForm->fill();
    }

    protected function getForms(): array
    {
        return [
            'form',
            'passwordForm',
        ];
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

    /**
     * Formulaire séparé (pas mélangé à Parametre::actuel()) : la gérante
     * change son mot de passe de connexion à l'Espace RÉVOLUTION sans
     * toucher aux autres réglages. Mêmes règles que le formulaire Breeze
     * historique (app/Http/Controllers/Auth/PasswordController.php),
     * jusqu'ici seulement accessible via /profile, jamais lié depuis le
     * panneau — la gérante n'avait donc aucun moyen de le trouver.
     */
    public function passwordForm(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('current_password')
                    ->label('Mot de passe actuel')
                    ->password()
                    ->revealable()
                    ->required()
                    ->currentPassword(),

                TextInput::make('password')
                    ->label('Nouveau mot de passe')
                    ->password()
                    ->revealable()
                    ->required()
                    ->rule(Password::defaults())
                    ->same('password_confirmation'),

                TextInput::make('password_confirmation')
                    ->label('Confirmer le nouveau mot de passe')
                    ->password()
                    ->revealable()
                    ->required()
                    ->dehydrated(false),
            ])
            ->statePath('passwordData');
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

    public function changerMotDePasse(): void
    {
        $donnees = $this->passwordForm->getState();

        auth()->user()->update([
            'password' => Hash::make($donnees['password']),
        ]);

        $this->passwordForm->fill();

        Notification::make()
            ->title('Mot de passe mis à jour')
            ->success()
            ->send();
    }
}
