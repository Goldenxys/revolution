<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\ArticleVariante;
use App\Models\CollectionCatalogue;
use App\Models\Couleur;
use App\Models\Taille;
use App\Models\TypeArticle;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Reprise du catalogue WhatsApp (relevé au 2 septembre 2026, voir
 * revolution-mise-a-jour-catalogue.md § Annexe A) — 39 articles + le tote
 * bag manquant, arbitrages tranchés avec la gérante :
 *
 * - Le doublon « Tee-shirt My verse Modèle 2 » (deux visuels) est scindé en
 *   Modèle 1 (première fiche du relevé) et Modèle 2 (seconde), même prix.
 * - Les écarts de prix tee-shirt (6 000 / 7 000 F) sont réels, conservés
 *   tels quels.
 * - « Christ au centre » garde ses accessoires (chaussette, casquette,
 *   tote bag) plutôt que d'en sortir une collection « Accessoires ».
 * - Tote bag : 6 000 F, nom choisi à l'identique du style des autres
 *   accessoires de la marque.
 * - Trois noms tronqués dans le relevé WhatsApp (lignes 30, 33, 37)
 *   complétés avec la gérante.
 *
 * Chaque article est créé avec sa grille de disponibilité entièrement
 * cochée (toutes tailles/couleurs gérées par son type) : c'est un point de
 * départ pratique — la gérante décoche ensuite ce qui n'est pas réellement
 * en stock, plutôt que de partir d'un catalogue invisible faute de
 * variante disponible.
 */
class CatalogueInitialSeeder extends Seeder
{
    public function run(): void
    {
        $articles = [
            // Collection My verse — 5 articles
            ['nom' => 'Pull-Over My verse Modèle 1', 'prix' => 20000, 'type' => 'pull', 'collection' => 'my_verse'],
            ['nom' => 'Pull-Over My verse Modèle 2', 'prix' => 20000, 'type' => 'pull', 'collection' => 'my_verse'],
            ['nom' => 'Tee-shirt My verse Modèle 1', 'prix' => 15000, 'type' => 'tee-shirt', 'collection' => 'my_verse'],
            ['nom' => 'Tee-shirt My verse Modèle 2', 'prix' => 15000, 'type' => 'tee-shirt', 'collection' => 'my_verse'],
            ['nom' => 'Tee-shirt My verse Modèle 3', 'prix' => 15000, 'type' => 'tee-shirt', 'collection' => 'my_verse'],

            // Collection Prestige premium — 4 articles
            ['nom' => 'Chemise Prestige Premium', 'prix' => 20000, 'type' => 'chemise', 'collection' => 'prestige-premium'],
            ['nom' => 'Chemise en Lin Prestige Premium', 'prix' => 20000, 'type' => 'chemise', 'collection' => 'prestige-premium'],
            ['nom' => 'Surchemise Prestige Premium', 'prix' => 35000, 'type' => 'surchemise', 'collection' => 'prestige-premium'],
            ['nom' => 'Surchemise en Denim Prestige Premium', 'prix' => 35000, 'type' => 'surchemise', 'collection' => 'prestige-premium'],

            // Collection Identité — 5 articles
            ['nom' => 'Tee-shirt God\'s Daughter', 'prix' => 6000, 'type' => 'tee-shirt', 'collection' => 'identite'],
            ['nom' => 'Tee-shirt God\'son', 'prix' => 6000, 'type' => 'tee-shirt', 'collection' => 'identite'],
            ['nom' => 'Tee-shirt Né de nouveau', 'prix' => 6000, 'type' => 'tee-shirt', 'collection' => 'identite'],
            ['nom' => 'Tee-shirt Marc 10:27', 'prix' => 6000, 'type' => 'tee-shirt', 'collection' => 'identite'],
            ['nom' => 'Tee-shirt HOLY Spirit', 'prix' => 6000, 'type' => 'tee-shirt', 'collection' => 'identite'],

            // Collection Prestige — 2 articles
            ['nom' => 'Tee-shirt Couronne d\'épine', 'prix' => 7000, 'type' => 'tee-shirt', 'collection' => 'prestige'],
            ['nom' => 'Tee-Shirt PRESTIGE', 'prix' => 6000, 'type' => 'tee-shirt', 'collection' => 'prestige'],

            // Collection Christ au centre — 21 articles + accessoires + tote bag
            ['nom' => 'Tee-shirt JEAN 11:25', 'prix' => 7000, 'type' => 'tee-shirt', 'collection' => 'christ-au-centre'],
            ['nom' => 'Tee-shirt Jesus Bis', 'prix' => 7000, 'type' => 'tee-shirt', 'collection' => 'christ-au-centre'],
            ['nom' => 'Tee-shirt Bonne Nouvelle', 'prix' => 7000, 'type' => 'tee-shirt', 'collection' => 'christ-au-centre'],
            ['nom' => 'Tee-shirt JESUS', 'prix' => 7000, 'type' => 'tee-shirt', 'collection' => 'christ-au-centre'],
            ['nom' => 'Tee-shirt Je vis pour JESUS', 'prix' => 7000, 'type' => 'tee-shirt', 'collection' => 'christ-au-centre'],
            ['nom' => 'Tee-shirt Romain 12:2', 'prix' => 7000, 'type' => 'tee-shirt', 'collection' => 'christ-au-centre'],
            ['nom' => 'Tee-shirt Yeshuah', 'prix' => 7000, 'type' => 'tee-shirt', 'collection' => 'christ-au-centre'],
            ['nom' => 'Tee-shirt Le feu qui brûle en moi', 'prix' => 7000, 'type' => 'tee-shirt', 'collection' => 'christ-au-centre'],
            ['nom' => 'Tee-shirt On accepte Jesus quand ?', 'prix' => 7000, 'type' => 'tee-shirt', 'collection' => 'christ-au-centre'],
            ['nom' => 'Tee-shirt Spirituel', 'prix' => 7000, 'type' => 'tee-shirt', 'collection' => 'christ-au-centre'],
            ['nom' => 'Tee-shirt Racheté', 'prix' => 7000, 'type' => 'tee-shirt', 'collection' => 'christ-au-centre'],
            ['nom' => 'Tee-shirt Consécration', 'prix' => 7000, 'type' => 'tee-shirt', 'collection' => 'christ-au-centre'],
            ['nom' => 'Tee-shirt 3:16', 'prix' => 7000, 'type' => 'tee-shirt', 'collection' => 'christ-au-centre'],
            ['nom' => 'Tee-shirt YOU ARE MY SAVIOR JESUS', 'prix' => 7000, 'type' => 'tee-shirt', 'collection' => 'christ-au-centre'],
            ['nom' => 'Tee-shirt Battements de croix', 'prix' => 6000, 'type' => 'tee-shirt', 'collection' => 'christ-au-centre'],
            ['nom' => 'Tee-shirt Le sang nous justifie', 'prix' => 6000, 'type' => 'tee-shirt', 'collection' => 'christ-au-centre'],
            ['nom' => 'Tee-shirt Plus de toi, moins de moi, plus de Jésus', 'prix' => 6000, 'type' => 'tee-shirt', 'collection' => 'christ-au-centre'],
            ['nom' => 'Pull JESUS Tag', 'prix' => 12000, 'type' => 'pull', 'collection' => 'christ-au-centre'],
            ['nom' => 'Pull Spirituel', 'prix' => 12000, 'type' => 'pull', 'collection' => 'christ-au-centre'],
            ['nom' => 'Pull-Over Bonne Nouvelle', 'prix' => 12000, 'type' => 'pull', 'collection' => 'christ-au-centre'],
            ['nom' => 'Pull-Over JESUS LE CHEMIN, LA VÉRITÉ, LA VIE', 'prix' => 12000, 'type' => 'pull', 'collection' => 'christ-au-centre'],

            // Accessoires (rattachés à Christ au centre, cf. décision produit)
            ['nom' => 'Chaussette RÉVOLUTION', 'prix' => 2000, 'type' => 'chaussette', 'collection' => 'christ-au-centre'],
            ['nom' => 'Casquette CASQUE DU SALUT', 'prix' => 5000, 'type' => 'casquette', 'collection' => 'christ-au-centre'],
            ['nom' => 'Tote bag RÉVOLUTION', 'prix' => 6000, 'type' => 'tote-bag', 'collection' => 'christ-au-centre'],
        ];

        $tailles = Taille::query()->actives()->orderBy('ordre')->get();
        $couleurs = Couleur::query()->actives()->orderBy('ordre')->get();

        foreach ($articles as $ordre => $donnees) {
            $collection = CollectionCatalogue::where('slug', $donnees['collection'])->firstOrFail();
            $type = TypeArticle::where('slug', $donnees['type'])->firstOrFail();

            $article = Article::query()->updateOrCreate(
                ['slug' => Str::slug($donnees['nom'])],
                [
                    'collection_id' => $collection->id,
                    'type_article_id' => $type->id,
                    'nom' => $donnees['nom'],
                    'prix' => $donnees['prix'],
                    'ordre' => $ordre,
                    'active' => true,
                ]
            );

            $this->cocherToutesLesVariantes($article, $type, $tailles, $couleurs);
        }
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Taille>  $tailles
     * @param  \Illuminate\Support\Collection<int, Couleur>  $couleurs
     */
    private function cocherToutesLesVariantes(Article $article, TypeArticle $type, $tailles, $couleurs): void
    {
        $lignesTailles = $type->gere_tailles ? $tailles : collect([null]);
        $lignesCouleurs = $type->gere_couleurs ? $couleurs : collect([null]);

        foreach ($lignesTailles as $taille) {
            foreach ($lignesCouleurs as $couleur) {
                ArticleVariante::query()->updateOrCreate(
                    ['article_id' => $article->id, 'taille_id' => $taille?->id, 'couleur_id' => $couleur?->id],
                    ['disponible' => true]
                );
            }
        }
    }
}
