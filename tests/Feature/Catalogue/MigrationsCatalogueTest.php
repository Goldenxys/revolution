<?php

namespace Tests\Feature\Catalogue;

use App\Mail\CommandeRecue;
use App\Models\Commande;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MigrationsCatalogueTest extends TestCase
{
    use RefreshDatabase;

    public function test_les_tables_catalogue_existent_avec_les_bonnes_colonnes(): void
    {
        $this->assertTrue(Schema::hasTable('types_articles'));
        $this->assertTrue(Schema::hasColumns('types_articles', ['nom', 'slug', 'gere_tailles', 'gere_couleurs', 'ordre', 'active']));

        $this->assertTrue(Schema::hasTable('tailles'));
        $this->assertTrue(Schema::hasColumns('tailles', ['libelle', 'ordre', 'active']));

        $this->assertTrue(Schema::hasTable('couleurs'));
        $this->assertTrue(Schema::hasColumns('couleurs', ['nom', 'code_hex', 'ordre', 'active']));

        $this->assertTrue(Schema::hasTable('collections'));
        $this->assertTrue(Schema::hasColumns('collections', ['nom', 'slug', 'description', 'image', 'verset_requis', 'modeles_disponibles', 'ordre', 'active']));

        $this->assertTrue(Schema::hasTable('articles'));
        $this->assertTrue(Schema::hasColumns('articles', ['collection_id', 'type_article_id', 'nom', 'slug', 'prix', 'active', 'deleted_at']));

        $this->assertTrue(Schema::hasTable('article_photos'));
        $this->assertTrue(Schema::hasTable('article_variantes'));
        $this->assertTrue(Schema::hasColumns('article_variantes', ['article_id', 'taille_id', 'couleur_id', 'disponible', 'stock']));

        $this->assertTrue(Schema::hasTable('commande_lignes'));
        $this->assertTrue(Schema::hasColumns('commande_lignes', [
            'commande_id', 'article_id', 'article_nom', 'taille_libelle', 'couleur_nom', 'quantite', 'prix_unitaire', 'verset', 'modele',
        ]));

        $this->assertTrue(Schema::hasColumns('commandes', [
            'sous_total', 'remise_pourcentage', 'remise_montant', 'total', 'statut', 'notes', 'utilise_catalogue',
        ]));
    }

    public function test_une_commande_legacy_nest_pas_affectee_par_les_nouvelles_colonnes(): void
    {
        Mail::fake();

        $this->post(route('commande.store'), [
            'collection' => 'my_verse',
            'nom' => 'Djiehi Carine',
            'telephone' => '0700000000',
            'commune' => 'Cocody',
            'mode_livraison' => 'livreur',
            'taille' => 'L',
            'couleur' => 'Blanc',
            'verset_reference' => 'Philippiens 4:13',
            'verset_texte' => 'Je puis tout par celui qui me fortifie.',
        ]);

        $commande = Commande::first();

        $this->assertNotNull($commande);
        $this->assertSame('nouvelle', $commande->statut);
        $this->assertFalse($commande->utilise_catalogue);
        $this->assertNull($commande->sous_total);
        $this->assertNull($commande->total);
        $this->assertTrue($commande->lignes->isEmpty());

        // L'accessor legacy reste inchangé tant qu'aucune ligne n'existe.
        $this->assertSame('Tee-shirt MY VERSE · Blanc', $commande->libelle_article);

        Mail::assertQueued(CommandeRecue::class);
    }
}
