<?php

namespace Database\Seeders;

use App\Models\CollectionCatalogue;
use Illuminate\Database\Seeder;

class CollectionSeeder extends Seeder
{
    /**
     * Les deux collections du parcours actuel, avec les mêmes slugs que les
     * valeurs de la colonne `commandes.collection` ('my_verse' / 'autre') :
     * le futur backfill des commandes legacy pourra s'y raccrocher.
     */
    public function run(): void
    {
        $collections = [
            [
                'slug' => 'my_verse',
                'nom' => 'My verse by RÉVOLUTION',
                'description' => 'Une pièce personnalisée avec le verset choisi par la cliente.',
                'verset_requis' => true,
                'ordre' => 0,
            ],
            [
                'slug' => 'autre',
                'nom' => 'Autre collection',
                'description' => 'Les pièces de la collection courante, sans personnalisation de verset.',
                'verset_requis' => false,
                'ordre' => 1,
            ],
        ];

        foreach ($collections as $collection) {
            CollectionCatalogue::query()->updateOrCreate(
                ['slug' => $collection['slug']],
                [
                    'nom' => $collection['nom'],
                    'description' => $collection['description'],
                    'verset_requis' => $collection['verset_requis'],
                    'ordre' => $collection['ordre'],
                    'active' => true,
                ]
            );
        }
    }
}
