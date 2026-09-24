<?php

namespace Tests\Feature\V2;

use App\Filament\Pages\TableauDeBord;
use App\Filament\Resources\StockResource\Pages\ListStock;
use App\Models\Article;
use App\Models\ArticleVariante;
use App\Models\Client;
use App\Models\CollectionCatalogue;
use App\Models\Commande;
use App\Models\Couleur;
use App\Models\Taille;
use App\Models\TypeArticle;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class StockEtTableauDeBordTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Une commande réellement livrée — le seul fait comptable (V2 révisé) :
     * `livree_at` renseigné, pas seulement `validee_at`.
     */
    private function venteLivree(int $totalArticles, int $frais, ?\DateTimeInterface $quand = null): Commande
    {
        $client = Client::create([
            'cle' => (string) random_int(10000000, 99999999),
            'nom' => 'Cliente '.uniqid(), 'telephone' => (string) random_int(10000000, 99999999),
            'nb_commandes' => 1, 'premiere_commande_at' => $quand ?? now(),
        ]);

        return Commande::create([
            'client_id' => $client->id, 'commune' => 'Cocody', 'frais_livraison' => $frais,
            'mode_livraison' => 'livreur', 'statut' => 'livree',
            'validee_at' => $quand ?? now(), 'livree_at' => $quand ?? now(),
            'sous_total' => $totalArticles, 'total_articles' => $totalArticles,
            'total_a_payer' => $totalArticles + $frais, 'numero_commande_client' => 1,
        ]);
    }

    public function test_le_ca_du_jour_exclut_la_livraison_et_les_demandes(): void
    {
        $gerante = User::factory()->create();

        $this->venteLivree(10000, 1500);
        $this->venteLivree(5000, 2000);

        // Une demande en attente ne compte pas.
        $prospect = Client::create(['cle' => '11111111', 'nom' => 'P', 'telephone' => '11111111', 'nb_commandes' => 0]);
        Commande::create([
            'client_id' => $prospect->id, 'commune' => 'Cocody', 'frais_livraison' => 1500,
            'mode_livraison' => 'livreur', 'statut' => 'en_attente',
        ]);

        $composant = Livewire::actingAs($gerante)->test(TableauDeBord::class);
        $tendances = $composant->instance()->indicateursAvecTendance();

        $this->assertSame(15000, $tendances['ca']['valeur']);       // 10000 + 5000, PAS les frais
        $this->assertSame(2, $tendances['ventes']['valeur']);
        $this->assertArrayNotHasKey('total_frais', $tendances);      // plus de comptabilisation de la livraison
        $this->assertSame(1, $composant->instance()->demandesAValider());
    }

    public function test_lecran_mon_stock_filtre_le_stock_faible(): void
    {
        $gerante = User::factory()->create();
        $collection = CollectionCatalogue::create(['nom' => 'C', 'slug' => 'c']);
        $type = TypeArticle::create(['nom' => 'T', 'slug' => 't', 'gere_tailles' => false, 'gere_couleurs' => false]);
        $article = Article::create(['collection_id' => $collection->id, 'type_article_id' => $type->id, 'nom' => 'Art', 'slug' => 'art', 'prix' => 7000]);

        $faible = ArticleVariante::create(['article_id' => $article->id, 'disponible' => true, 'stock' => 2, 'seuil_alerte' => 3]);
        $ok = ArticleVariante::create(['article_id' => $article->id, 'disponible' => true, 'stock' => 40, 'seuil_alerte' => 3]);

        Livewire::actingAs($gerante)->test(ListStock::class)
            ->assertCanSeeTableRecords([$faible, $ok])
            ->filterTable('etat', ['etat' => 'faible'])
            ->assertCanSeeTableRecords([$faible])
            ->assertCanNotSeeTableRecords([$ok]);
    }

    /**
     * Régression : la recherche par nom d'article plantait en production
     * (closure ->searchable(query: fn (Builder $q, string $s) => ...) —
     * `$s` n'est pas un nom de paramètre que Filament sait injecter, seul
     * `$search` l'est). Ce test tape effectivement un terme de recherche,
     * ce que l'ancien test du filtre `etat` ne faisait jamais.
     */
    public function test_lecran_mon_stock_recherche_par_nom_darticle_sans_erreur(): void
    {
        $gerante = User::factory()->create();
        $collection = CollectionCatalogue::create(['nom' => 'C', 'slug' => 'c']);
        $type = TypeArticle::create(['nom' => 'T', 'slug' => 't', 'gere_tailles' => true, 'gere_couleurs' => true]);

        $robe = Article::create(['collection_id' => $collection->id, 'type_article_id' => $type->id, 'nom' => 'Robe Couronne', 'slug' => 'robe-couronne', 'prix' => 9000]);
        $pull = Article::create(['collection_id' => $collection->id, 'type_article_id' => $type->id, 'nom' => 'Pull Écriture', 'slug' => 'pull-ecriture', 'prix' => 8000]);

        $varianteRobe = ArticleVariante::create(['article_id' => $robe->id, 'disponible' => true, 'stock' => 5]);
        $variantePull = ArticleVariante::create(['article_id' => $pull->id, 'disponible' => true, 'stock' => 5]);

        Livewire::actingAs($gerante)->test(ListStock::class)
            ->searchTable('Couronne')
            ->assertCanSeeTableRecords([$varianteRobe])
            ->assertCanNotSeeTableRecords([$variantePull]);
    }

    public function test_lecran_mon_stock_filtre_par_taille_et_couleur(): void
    {
        $gerante = User::factory()->create();
        $collection = CollectionCatalogue::create(['nom' => 'C', 'slug' => 'c']);
        $type = TypeArticle::create(['nom' => 'T', 'slug' => 't', 'gere_tailles' => true, 'gere_couleurs' => true]);
        $article = Article::create(['collection_id' => $collection->id, 'type_article_id' => $type->id, 'nom' => 'Art', 'slug' => 'art', 'prix' => 7000]);

        $xl = Taille::create(['libelle' => 'XL']);
        $m = Taille::create(['libelle' => 'M']);
        $noir = Couleur::create(['nom' => 'Noir']);

        $varianteXl = ArticleVariante::create(['article_id' => $article->id, 'taille_id' => $xl->id, 'couleur_id' => $noir->id, 'disponible' => true, 'stock' => 5]);
        $varianteM = ArticleVariante::create(['article_id' => $article->id, 'taille_id' => $m->id, 'couleur_id' => $noir->id, 'disponible' => true, 'stock' => 5]);

        Livewire::actingAs($gerante)->test(ListStock::class)
            ->filterTable('taille_id', $xl->id)
            ->assertCanSeeTableRecords([$varianteXl])
            ->assertCanNotSeeTableRecords([$varianteM]);
    }

    /**
     * Supprimer le stock repasse la variante à « non suivi » (NULL) —
     * ArticleVariante::booted() en déduit alors disponible=false tout seul,
     * exactement comme si aucun stock n'avait jamais été enregistré.
     */
    public function test_supprimer_le_stock_repasse_la_variante_a_non_suivie(): void
    {
        $gerante = User::factory()->create();
        $collection = CollectionCatalogue::create(['nom' => 'C', 'slug' => 'c']);
        $type = TypeArticle::create(['nom' => 'T', 'slug' => 't', 'gere_tailles' => false, 'gere_couleurs' => false]);
        $article = Article::create(['collection_id' => $collection->id, 'type_article_id' => $type->id, 'nom' => 'Art', 'slug' => 'art', 'prix' => 7000]);
        $variante = ArticleVariante::create(['article_id' => $article->id, 'disponible' => true, 'stock' => 10]);

        Livewire::actingAs($gerante)->test(ListStock::class)
            ->callTableBulkAction('supprimer_stock', [$variante]);

        $variante->refresh();
        $this->assertNull($variante->stock);
        $this->assertFalse($variante->disponible);
    }

    /**
     * My verse est fabriqué à la demande, sans pièces en réserve : ses
     * variantes n'ont rien à faire sur l'écran « Mon stock ».
     */
    public function test_lecran_mon_stock_exclut_les_collections_fabriquees_a_la_demande(): void
    {
        $gerante = User::factory()->create();
        $collectionStockee = CollectionCatalogue::create(['nom' => 'C', 'slug' => 'c']);
        $myVerse = CollectionCatalogue::create(['nom' => 'My verse', 'slug' => 'my_verse', 'gere_stock' => false]);
        $type = TypeArticle::create(['nom' => 'T', 'slug' => 't', 'gere_tailles' => false, 'gere_couleurs' => false]);

        $articleStocke = Article::create(['collection_id' => $collectionStockee->id, 'type_article_id' => $type->id, 'nom' => 'Art', 'slug' => 'art', 'prix' => 7000]);
        $articleMyVerse = Article::create(['collection_id' => $myVerse->id, 'type_article_id' => $type->id, 'nom' => 'Tee my verse', 'slug' => 'tee-my-verse', 'prix' => 7000]);

        $varianteStockee = ArticleVariante::create(['article_id' => $articleStocke->id, 'disponible' => true, 'stock' => 5]);
        $varianteMyVerse = ArticleVariante::create(['article_id' => $articleMyVerse->id, 'disponible' => true, 'stock' => null]);

        Livewire::actingAs($gerante)->test(ListStock::class)
            ->assertCanSeeTableRecords([$varianteStockee])
            ->assertCanNotSeeTableRecords([$varianteMyVerse]);
    }

    public function test_le_tableau_de_bord_se_charge(): void
    {
        Storage::fake('local');
        Mail::fake();
        $gerante = User::factory()->create();
        $this->venteLivree(9000, 1500);

        $this->actingAs($gerante)->get('/'.config('revolution.admin_path'))->assertOk();
    }
}
