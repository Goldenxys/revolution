<?php

namespace Tests\Feature\V2;

use App\Filament\Resources\CommandeResource\Pages\ListCommandes;
use App\Filament\Resources\CommandeResource\Pages\ViewCommande;
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
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Régression : une commande composée au compositeur (V2) range son article
 * dans `lignes`, jamais dans les colonnes legacy (nom_article, taille,
 * couleur, collection). La liste et la fiche « Commandes » lisaient
 * pourtant ces colonnes en dur, restant vides pour toute commande passée
 * par le catalogue — reproduit avec la toute première commande réelle
 * livrée en production (Tee-shirt God's Daughter, L, Beige).
 */
class AffichageCommandeCatalogueTest extends TestCase
{
    use RefreshDatabase;

    private function commandeCatalogueComposee(): Commande
    {
        $taille = Taille::create(['libelle' => 'L']);
        $couleur = Couleur::create(['nom' => 'Beige']);
        $collection = CollectionCatalogue::create(['nom' => 'Identité', 'slug' => 'identite']);
        $type = TypeArticle::create(['nom' => 'Tee-shirt', 'slug' => 'tee', 'gere_tailles' => true, 'gere_couleurs' => true]);
        $article = Article::create([
            'collection_id' => $collection->id, 'type_article_id' => $type->id,
            'nom' => 'Tee-shirt God\'s Daughter', 'slug' => 'tee-gods-daughter', 'prix' => 6000,
        ]); // génère la variante L/Beige à la création

        ArticleVariante::where('article_id', $article->id)
            ->where('taille_id', $taille->id)->where('couleur_id', $couleur->id)
            ->first()->update(['stock' => 5]);

        $client = Client::create([
            'cle' => Client::cleDepuisTelephone('0700000099'),
            'nom' => 'Allah Koffi Jean', 'telephone' => '0700000099',
            'statut' => 'prospect', 'numero_client' => 'REV-C-0099', 'nb_commandes' => 0,
        ]);
        $commande = Commande::create([
            'client_id' => $client->id, 'commune' => 'Cocody', 'frais_livraison' => 1500,
            'mode_livraison' => 'livreur', 'statut' => 'en_attente', 'collection' => 'autre',
        ]);
        $commande->lignes()->create([
            'article_id' => $article->id, 'taille_id' => $taille->id, 'couleur_id' => $couleur->id,
            'article_nom' => $article->nom, 'taille_libelle' => $taille->libelle, 'couleur_nom' => $couleur->nom,
            'quantite' => 1, 'prix_unitaire' => 6000,
        ]);

        $gerante = User::factory()->create();
        $commande->valider($gerante);

        return $commande->fresh();
    }

    public function test_la_liste_des_commandes_affiche_larticle_reel_dune_commande_composee(): void
    {
        $gerante = User::factory()->create();
        $commande = $this->commandeCatalogueComposee();

        Livewire::actingAs($gerante)->test(ListCommandes::class)
            ->assertSee('Tee-shirt God\'s Daughter')
            ->assertSee('Identité')
            ->assertSee('L · Beige')
            ->assertDontSee('« »');
    }

    public function test_la_fiche_commande_affiche_les_lignes_reelles_dune_commande_composee(): void
    {
        $gerante = User::factory()->create();
        $commande = $this->commandeCatalogueComposee();

        Livewire::actingAs($gerante)
            ->test(ViewCommande::class, ['record' => $commande->getKey()])
            ->assertSee('Tee-shirt God\'s Daughter')
            ->assertSee('Identité')
            ->assertSee('L')
            ->assertSee('Beige');
    }
}
