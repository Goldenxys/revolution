<?php

namespace Database\Seeders;

use App\Models\Parametre;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminUserSeeder extends Seeder
{
    /**
     * Crée l'unique compte de l'Espace RÉVOLUTION (la gérante) et la ligne
     * de réglages par défaut. Aucune inscription publique n'existe : c'est
     * la seule façon de créer un compte admin.
     *
     * Aucun mot de passe en dur dans le code : soit REVO_ADMIN_PASSWORD est
     * défini dans .env, soit un mot de passe aléatoire est généré et affiché
     * une seule fois ici pour être récupéré et changé.
     */
    public function run(): void
    {
        $email = env('REVO_ADMIN_EMAIL', 'djiehic@gmail.com');
        $password = env('REVO_ADMIN_PASSWORD');
        $motDePasseGenere = blank($password);

        if ($motDePasseGenere) {
            $password = Str::password(16);
        }

        User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => env('REVO_ADMIN_NAME', 'Gérante RÉVOLUTION'),
                'password' => Hash::make($password),
                'email_verified_at' => now(),
            ]
        );

        Parametre::actuel();

        if ($motDePasseGenere) {
            $this->command?->warn("Aucun REVO_ADMIN_PASSWORD défini : mot de passe généré pour {$email} :");
            $this->command?->warn($password);
            $this->command?->warn('Notez-le maintenant — ajoutez REVO_ADMIN_PASSWORD à votre .env pour le fixer durablement.');
        }
    }
}
