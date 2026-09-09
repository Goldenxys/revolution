<?php

namespace Tests\Feature\V2;

use App\Filament\Pages\TableauDeBord;
use App\Filament\Resources\StockResource\Pages\ListStock;
use App\Models\Article;
use App\Models\ArticleVariante;
use App\Models\Client;
use App\Models\CollectionCatalogue;
use App\Models\Commande;
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

    private function venteValidee(int $totalArticles, int $frais, ?\DateTimeInterface $quand = null): Commande
    {
        $client = Client::create([
            'cle' => (string) random_int(10000000, 99999999),
            'nom' => 'Cliente '.uniqid(), 'telephone' => (string) random_int(10000000, 99999999),
            'nb_commandes' => 1, 'premiere_commande_at' => $quand ?? now(),
        ]);

        return Commande::create([
            'client_id' => $client->id, 'commune' => 'Cocody', 'frais_livraison' => $frais,
            'mode_livraison' => 'livreur', 'statut' => 'validee', 'validee_at' => $quand ?? now(),
            'sous_total' => $totalArticles, 'total_articles' => $totalArticles,
            'total_a_payer' => $totalArticles + $frais, 'numero_commande_client' => 1,
        ]);
    }

    public function test_le_ca_du_jour_exclut_la_livraison_et_les_demandes(): void
    {
        $gerante = User::factory()->create();

        $this->venteValidee(10000, 1500);
        $this->venteValidee(5000, 2000);

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

    public function test_le_tableau_de_bord_se_charge(): void
    {
        Storage::fake('local');
        Mail::fake();
        $gerante = User::factory()->create();
        $this->venteValidee(9000, 1500);

        $this->actingAs($gerante)->get('/'.config('revolution.admin_path'))->assertOk();
    }
}
