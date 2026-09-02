<?php

namespace Database\Seeders;

use App\Models\CollectionCatalogue;
use Illuminate\Database\Seeder;

class CollectionSeeder extends Seeder
{
    /**
     * `my_verse` et `autre` portent les mêmes slugs que les valeurs de la
     * colonne legacy `commandes.collection` : le futur backfill des
     * commandes de l'ancien formulaire pourra s'y raccrocher. `autre` reste
     * un panier legacy — le catalogue réel (CatalogueInitialSeeder) range
     * ses articles dans les collections suivantes, plus fines.
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
                'slug' => 'prestige-premium',
                'nom' => 'Prestige premium',
                'description' => 'Chemises et surchemises haut de gamme.',
                'verset_requis' => false,
                'ordre' => 1,
            ],
            [
                'slug' => 'identite',
                'nom' => 'Identité',
                'description' => 'Affirmer qui l\'on est en Christ.',
                'verset_requis' => false,
                'ordre' => 2,
            ],
            [
                'slug' => 'prestige',
                'nom' => 'Prestige',
                'description' => 'La gamme Prestige.',
                'verset_requis' => false,
                'ordre' => 3,
            ],
            [
                'slug' => 'christ-au-centre',
                'nom' => 'Christ au centre',
                'description' => 'La collection courante, tee-shirts, pulls et accessoires.',
                'verset_requis' => false,
                'ordre' => 4,
            ],
            [
                'slug' => 'autre',
                'nom' => 'Autre collection',
                'description' => 'Panier historique de l\'ancien formulaire de commande — ne reçoit plus de nouveaux articles.',
                'verset_requis' => false,
                'ordre' => 99,
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
