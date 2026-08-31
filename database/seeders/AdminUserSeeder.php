<?php

namespace Database\Seeders;

use App\Models\Parametre;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Crée l'unique compte de l'Espace RÉVOLUTION (la gérante) et la ligne
     * de réglages par défaut. Aucune inscription publique n'existe : c'est
     * la seule façon de créer un compte admin.
     */
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => env('REVO_ADMIN_EMAIL', 'djiehic@gmail.com')],
            [
                'name' => env('REVO_ADMIN_NAME', 'Gérante RÉVOLUTION'),
                'password' => Hash::make(env('REVO_ADMIN_PASSWORD', 'Revolution2026!')),
                'email_verified_at' => now(),
            ]
        );

        Parametre::actuel();
    }
}
