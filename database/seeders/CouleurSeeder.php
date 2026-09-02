<?php

namespace Database\Seeders;

use App\Models\Couleur;
use Illuminate\Database\Seeder;

class CouleurSeeder extends Seeder
{
    public function run(): void
    {
        $couleurs = [
            'Blanc' => '#FFFFFF',
            'Noir' => '#111111',
            'Beige' => '#D9C7A6',
            'Terracotta' => '#B5563A',
            'Kaki' => '#5C5A3E',
            'Bleu nuit' => '#101830',
            'Denim' => '#4A5B75',
        ];

        $ordre = 0;

        foreach ($couleurs as $nom => $codeHex) {
            Couleur::query()->updateOrCreate(
                ['nom' => $nom],
                ['code_hex' => $codeHex, 'ordre' => $ordre, 'active' => true]
            );
            $ordre++;
        }
    }
}
