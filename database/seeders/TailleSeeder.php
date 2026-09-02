<?php

namespace Database\Seeders;

use App\Models\Taille;
use Illuminate\Database\Seeder;

class TailleSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['M', 'L', 'XL', 'XXL'] as $ordre => $libelle) {
            Taille::query()->updateOrCreate(
                ['libelle' => $libelle],
                ['ordre' => $ordre, 'active' => true]
            );
        }
    }
}
