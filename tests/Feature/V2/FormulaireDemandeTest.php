<?php

namespace Tests\Feature\V2;

use App\Mail\DemandeDeposee;
use App\Models\Article;
use App\Models\Client;
use App\Models\CollectionCatalogue;
use App\Models\Commande;
use App\Models\TypeArticle;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class FormulaireDemandeTest extends TestCase
{
    use RefreshDatabase;

    private function article(): Article
    {
        $collection = CollectionCatalogue::create(['nom' => 'MY VERSE', 'slug' => 'my_verse']);
        $type = TypeArticle::create(['nom' => 'Tee-shirt', 'slug' => 'tee', 'gere_tailles' => true, 'gere_couleurs' => true]);

        return Article::create([
            'collection_id' => $collection->id, 'type_article_id' => $type->id,
            'nom' => 'Tee-shirt Couronne d\'épines', 'slug' => 'tee-couronne', 'prix' => 7000,
        ]);
    }

    private function donnees(array $override = []): array
    {
        return array_merge([
            'nom' => 'Aya Kouassi',
            'telephone' => '0102030405',
            'commune' => 'Cocody',
            'mode_livraison' => 'livreur',
            'souhaits' => [
                ['article_id' => $this->article()->id, 'taille' => 'XL', 'couleur' => 'Blanc', 'quantite' => 2],
            ],
        ], $override);
    }

    public function test_la_page_du_formulaire_se_charge(): void
    {
        $this->article();
        $this->get(route('commande.demande.creer'))->assertOk()->assertSee('On enregistre votre commande');
    }

    public function test_une_demande_cree_une_commande_en_attente_sans_aucun_montant(): void
    {
        Mail::fake();
        Notification::fake();
        User::factory()->create();

        $article = $this->article();

        $response = $this->post(route('commande.demande.store'), [
            'nom' => 'Aya Kouassi',
            'telephone' => '0102030405',
            'email' => 'aya@example.com',
            'commune' => 'Cocody',
            'mode_livraison' => 'livreur',
            'precisions' => 'Verset Philippiens 4:13',
            'souhaits' => [
                ['article_id' => $article->id, 'taille' => 'XL', 'couleur' => 'Blanc', 'quantite' => 2],
            ],
        ]);

        $commande = Commande::first();
        $response->assertRedirect(route('commande.demande.merci', $commande->reference));

        $this->assertSame('en_attente', $commande->statut);
        $this->assertSame(0, $commande->total_articles);
        $this->assertSame(0, $commande->total);
        $this->assertSame(1500, $commande->frais_livraison); // recalculé serveur
        $this->assertNull($commande->validee_at);
        $this->assertSame('Tee-shirt Couronne d\'épines', $commande->souhaits_client[0]['article_nom']);
        $this->assertSame(2, $commande->souhaits_client[0]['quantite']);
        $this->assertSame('Verset Philippiens 4:13', $commande->message_client);

        $client = $commande->client;
        $this->assertSame('prospect', $client->statut);
        $this->assertSame(0, $client->nb_commandes);
        $this->assertNotNull($client->numero_client);

        $this->assertDatabaseHas('commande_journal', ['commande_id' => $commande->id, 'evenement' => 'creee']);
        Mail::assertQueued(DemandeDeposee::class);
    }

    public function test_le_formulaire_refuse_une_demande_sans_souhait(): void
    {
        User::factory()->create();

        $this->post(route('commande.demande.store'), [
            'nom' => 'Aya', 'telephone' => '0102030405', 'commune' => 'Cocody', 'mode_livraison' => 'livreur',
        ])->assertSessionHasErrors('souhaits');

        $this->assertSame(0, Commande::count());
    }

    public function test_anti_doublon_90_secondes(): void
    {
        Mail::fake();
        Notification::fake();
        User::factory()->create();
        $article = $this->article();

        $payload = [
            'nom' => 'Aya Kouassi', 'telephone' => '0102030405', 'commune' => 'Cocody', 'mode_livraison' => 'livreur',
            'souhaits' => [['article_id' => $article->id, 'quantite' => 1]],
        ];

        $this->post(route('commande.demande.store'), $payload)->assertRedirect();
        $this->post(route('commande.demande.store'), $payload)->assertSessionHasErrors('telephone');

        $this->assertSame(1, Commande::count());
    }

    public function test_la_confirmation_affiche_la_carte_de_fidelite(): void
    {
        Mail::fake();
        Notification::fake();
        User::factory()->create();
        $article = $this->article();

        $this->post(route('commande.demande.store'), [
            'nom' => 'Aya Kouassi', 'telephone' => '0102030405', 'commune' => 'Cocody', 'mode_livraison' => 'livreur',
            'souhaits' => [['article_id' => $article->id, 'quantite' => 1]],
        ]);

        $commande = Commande::first();
        $this->get(route('commande.demande.merci', $commande->reference))
            ->assertOk()
            ->assertSee('Carte de fidélité')
            ->assertSee($commande->client->numero_client)
            ->assertSee('comptée dès sa validation');
    }

    public function test_une_demande_deja_validee_ne_montre_plus_la_page_merci(): void
    {
        $client = Client::create(['cle' => '00000009', 'nom' => 'X', 'telephone' => '0700000009', 'nb_commandes' => 0]);
        $commande = Commande::create([
            'client_id' => $client->id, 'commune' => 'Cocody', 'frais_livraison' => 1500,
            'mode_livraison' => 'livreur', 'statut' => 'validee', 'validee_at' => now(),
        ]);

        $this->get(route('commande.demande.merci', $commande->reference))->assertNotFound();
    }
}
