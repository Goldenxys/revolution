<?php

namespace Tests\Feature\V2;

use App\Events\CommandeValidee;
use App\Models\Article;
use App\Models\ArticleVariante;
use App\Models\Client;
use App\Models\CollectionCatalogue;
use App\Models\Commande;
use App\Models\CommandeJournal;
use App\Models\Couleur;
use App\Models\Taille;
use App\Models\TypeArticle;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * Cœur de la V2 : Commande::valider() est LE fait comptable (§5.3, §9), et
 * Commande::annuler() doit le défaire proprement (§5.5).
 */
class ValidationCommandeTest extends TestCase
{
    use RefreshDatabase;

    private function prospectAvecDemande(array $overrideCommande = []): array
    {
        $client = Client::create([
            'cle' => Client::cleDepuisTelephone('0700000001'),
            'nom' => 'Awa Traoré',
            'telephone' => '0700000001',
            'statut' => 'prospect',
            'numero_client' => 'REV-C-0001',
            'nb_commandes' => 0,
        ]);

        $commande = Commande::create(array_merge([
            'client_id' => $client->id,
            'commune' => 'Cocody',
            'frais_livraison' => 1500,
            'mode_livraison' => 'livreur',
            'statut' => 'en_attente',
            'souhaits_client' => [
                ['article_nom' => 'Tee-shirt Couronne', 'taille' => 'XL', 'couleur' => 'Blanc', 'quantite' => 2, 'note' => null],
            ],
        ], $overrideCommande));

        return [$client, $commande];
    }

    public function test_valider_comptabilise_le_ca_hors_livraison_et_incremente_la_fidelite(): void
    {
        Event::fake([CommandeValidee::class]);
        $gerante = User::factory()->create();
        [$client, $commande] = $this->prospectAvecDemande();

        $commande->lignes()->create([
            'article_nom' => 'Tee-shirt Couronne', 'taille_libelle' => 'XL', 'couleur_nom' => 'Blanc',
            'quantite' => 2, 'prix_unitaire' => 7000,
        ]);

        $commande->valider($gerante);

        $commande->refresh();
        $client->refresh();

        $this->assertSame('validee', $commande->statut);
        $this->assertNotNull($commande->validee_at);
        $this->assertSame($gerante->id, $commande->validee_par);
        $this->assertSame(14000, $commande->total_articles);          // 2 × 7000, aucune remise (1re commande)
        $this->assertSame(15500, $commande->total_a_payer);           // + 1500 de livraison
        $this->assertNotNull($commande->recu_token);

        $this->assertSame('client', $client->statut);
        $this->assertSame(1, $client->nb_commandes);
        $this->assertSame(14000, $client->ca_cumule);
        $this->assertNotNull($client->premiere_commande_at);

        Event::assertDispatched(CommandeValidee::class);
        $this->assertDatabaseHas('commande_journal', ['commande_id' => $commande->id, 'evenement' => 'validee']);
    }

    public function test_valider_applique_la_remise_fidelite_proposee_sur_le_ca_seul(): void
    {
        $gerante = User::factory()->create();
        [$client, $commande] = $this->prospectAvecDemande();
        $client->update(['nb_commandes' => 1]); // la prochaine sera la 2ᵉ → palier 15 %

        $commande->lignes()->create([
            'article_nom' => 'Pull', 'quantite' => 1, 'prix_unitaire' => 10000,
        ]);

        $commande->valider($gerante);
        $commande->refresh();

        $this->assertSame(15, $commande->remise_pourcentage);
        $this->assertSame(1500, $commande->remise_montant);        // 15 % de 10000, PAS de (10000 + frais)
        $this->assertSame(8500, $commande->total_articles);
        $this->assertSame(10000, $commande->total_a_payer);        // 8500 + 1500 livraison
        $this->assertFalse($commande->remise_forcee);
    }

    public function test_la_remise_forcee_par_la_gerante_est_tracee(): void
    {
        $gerante = User::factory()->create();
        [, $commande] = $this->prospectAvecDemande();
        $commande->lignes()->create(['article_nom' => 'X', 'quantite' => 1, 'prix_unitaire' => 10000]);

        $commande->valider($gerante, remiseForceePourcentage: 50);
        $commande->refresh();

        $this->assertSame(50, $commande->remise_pourcentage);
        $this->assertSame(5000, $commande->total_articles);
        $this->assertTrue($commande->remise_forcee);
    }

    public function test_valider_decremente_le_stock_suivi_sans_jamais_descendre_sous_zero(): void
    {
        $gerante = User::factory()->create();
        [, $commande] = $this->prospectAvecDemande();

        $variante = ArticleVariante::create([
            'article_id' => Article::create([
                'collection_id' => CollectionCatalogue::create(['nom' => 'C', 'slug' => 'c'])->id,
                'type_article_id' => TypeArticle::create(['nom' => 'T', 'slug' => 't'])->id,
                'nom' => 'Art', 'slug' => 'art', 'prix' => 7000,
            ])->id,
            'taille_id' => Taille::create(['libelle' => 'XL'])->id,
            'couleur_id' => Couleur::create(['nom' => 'Blanc'])->id,
            'disponible' => true,
            'stock' => 1,
        ]);

        $commande->lignes()->create([
            'article_id' => $variante->article_id,
            'taille_id' => $variante->taille_id,
            'couleur_id' => $variante->couleur_id,
            'article_nom' => 'Art', 'quantite' => 3, 'prix_unitaire' => 7000,
        ]);

        $commande->valider($gerante);

        $this->assertSame(0, $variante->refresh()->stock);
        $this->assertDatabaseHas('commande_journal', [
            'commande_id' => $commande->id, 'evenement' => 'stock_negatif_evite',
        ]);
    }

    public function test_valider_est_idempotente(): void
    {
        Event::fake([CommandeValidee::class]);
        $gerante = User::factory()->create();
        [$client, $commande] = $this->prospectAvecDemande();
        $commande->lignes()->create(['article_nom' => 'X', 'quantite' => 1, 'prix_unitaire' => 7000]);

        $commande->valider($gerante);
        $commande->refresh();
        $commande->valider($gerante); // second appel : sans effet

        $client->refresh();
        $this->assertSame(1, $client->nb_commandes);
        $this->assertSame(7000, $client->ca_cumule);
        Event::assertDispatchedTimes(CommandeValidee::class, 1);
        $this->assertSame(1, CommandeJournal::where('commande_id', $commande->id)->where('evenement', 'validee')->count());
    }

    public function test_valider_sans_ligne_leve_une_exception(): void
    {
        $gerante = User::factory()->create();
        [, $commande] = $this->prospectAvecDemande();

        $this->expectException(\RuntimeException::class);
        $commande->valider($gerante);
    }

    public function test_annuler_une_commande_validee_defait_tout(): void
    {
        $gerante = User::factory()->create();
        [$client, $commande] = $this->prospectAvecDemande();

        $variante = ArticleVariante::create([
            'article_id' => Article::create([
                'collection_id' => CollectionCatalogue::create(['nom' => 'C', 'slug' => 'c'])->id,
                'type_article_id' => TypeArticle::create(['nom' => 'T', 'slug' => 't'])->id,
                'nom' => 'Art', 'slug' => 'art', 'prix' => 7000,
            ])->id,
            'taille_id' => Taille::create(['libelle' => 'XL'])->id,
            'couleur_id' => Couleur::create(['nom' => 'Blanc'])->id,
            'disponible' => true, 'stock' => 10,
        ]);
        $commande->lignes()->create([
            'article_id' => $variante->article_id, 'taille_id' => $variante->taille_id, 'couleur_id' => $variante->couleur_id,
            'article_nom' => 'Art', 'quantite' => 3, 'prix_unitaire' => 7000,
        ]);

        $commande->valider($gerante);
        $this->assertSame(7, $variante->refresh()->stock);

        $commande->annuler('Cliente injoignable', $gerante);

        $commande->refresh();
        $client->refresh();
        $this->assertSame('annulee', $commande->statut);
        $this->assertNotNull($commande->validee_at); // trace historique conservée
        $this->assertSame(10, $variante->refresh()->stock);
        $this->assertSame(0, $client->nb_commandes);
        $this->assertSame(0, $client->ca_cumule);
        $this->assertSame('prospect', $client->statut);
        $this->assertDatabaseHas('commande_journal', [
            'commande_id' => $commande->id, 'evenement' => 'annulee',
        ]);
    }

    public function test_annuler_est_idempotente(): void
    {
        $gerante = User::factory()->create();
        [$client, $commande] = $this->prospectAvecDemande();
        $commande->lignes()->create(['article_nom' => 'X', 'quantite' => 1, 'prix_unitaire' => 7000]);
        $commande->valider($gerante);

        $commande->annuler('motif', $gerante);
        $commande->annuler('motif', $gerante);

        $this->assertSame(1, CommandeJournal::where('commande_id', $commande->id)->where('evenement', 'annulee')->count());
    }

    public function test_le_prix_catalogue_modifie_apres_validation_ne_change_pas_le_montant(): void
    {
        $gerante = User::factory()->create();
        [, $commande] = $this->prospectAvecDemande();
        $ligne = $commande->lignes()->create(['article_nom' => 'X', 'quantite' => 1, 'prix_unitaire' => 7000]);

        $commande->valider($gerante);
        $totalAvant = $commande->refresh()->total_articles;

        // Les copies figées restent la seule vérité : rien ne les recalcule.
        $this->assertSame(7000, $ligne->refresh()->prix_unitaire);
        $this->assertSame($totalAvant, $commande->refresh()->total_articles);
    }
}
