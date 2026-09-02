<?php

namespace Database\Seeders;

use App\Models\TypeArticle;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TypeArticleSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['nom' => 'Tee-shirt', 'gere_tailles' => true, 'gere_couleurs' => true],
            ['nom' => 'Pull', 'gere_tailles' => true, 'gere_couleurs' => true],
            ['nom' => 'Chemise', 'gere_tailles' => true, 'gere_couleurs' => true],
            ['nom' => 'Surchemise', 'gere_tailles' => true, 'gere_couleurs' => true],
            ['nom' => 'Tote bag', 'gere_tailles' => false, 'gere_couleurs' => true],
            ['nom' => 'Chaussette', 'gere_tailles' => false, 'gere_couleurs' => true],
            ['nom' => 'Casquette', 'gere_tailles' => false, 'gere_couleurs' => true],
        ];

        foreach ($types as $ordre => $type) {
            TypeArticle::query()->updateOrCreate(
                ['slug' => Str::slug($type['nom'])],
                [
                    'nom' => $type['nom'],
                    'gere_tailles' => $type['gere_tailles'],
                    'gere_couleurs' => $type['gere_couleurs'],
                    'ordre' => $ordre,
                    'active' => true,
                ]
            );
        }
    }
}
