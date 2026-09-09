<?php

namespace Tests\Feature\V2;

use App\Mail\DemandeDeposee;
use App\Models\Client;
use App\Models\Commande;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class FormulaireDemandeTest extends TestCase
{
    use RefreshDatabase;

    public function test_la_page_du_formulaire_se_charge(): void
    {
        $this->get(route('commande.demande.creer'))->assertOk()->assertSee('On enregistre votre commande');
    }

    public function test_une_demande_my_verse_fige_le_verset_la_taille_et_la_couleur(): void
    {
        Mail::fake();
        Notification::fake();
        User::factory()->create();

        $response = $this->post(route('commande.demande.store'), [
            'nom' => 'Aya Kouassi',
            'telephone' => '0102030405',
            'email' => 'aya@example.com',
            'collection' => 'my_verse',
            'taille' => 'XL',
            'couleur' => 'Blanc',
            'verset_reference' => 'Philippiens 4:13',
            'verset_texte' => 'Je puis tout par celui qui me fortifie.',
            'precisions' => 'Écriture dorée',
            'commune' => 'Cocody',
            'mode_livraison' => 'livreur',
        ]);

        $commande = Commande::first();
        $response->assertRedirect(route('commande.demande.merci', $commande->reference));

        $this->assertSame('en_attente', $commande->statut);
        $this->assertSame('my_verse', $commande->collection);
        $this->assertSame('XL', $commande->taille);
        $this->assertSame('Blanc', $commande->couleur);
        $this->assertSame('Philippiens 4:13', $commande->verset_reference);
        $this->assertSame('Écriture dorée', $commande->message_client);
        $this->assertSame(0, $commande->total_articles);
        $this->assertSame(1500, $commande->frais_livraison);
        $this->assertNull($commande->validee_at);

        $this->assertSame('prospect', $commande->client->statut);
        $this->assertSame(0, $commande->client->nb_commandes);
        $this->assertNotNull($commande->client->numero_client);

        Mail::assertQueued(DemandeDeposee::class);
        $this->assertDatabaseHas('commande_journal', ['commande_id' => $commande->id, 'evenement' => 'creee']);
    }

    public function test_une_demande_autre_collection_ne_demande_que_les_infos_et_la_livraison(): void
    {
        Mail::fake();
        Notification::fake();
        User::factory()->create();

        $this->post(route('commande.demande.store'), [
            'nom' => 'Koffi', 'telephone' => '0102030406',
            'collection' => 'autre',
            'precisions' => 'Le pull beige vu sur WhatsApp, taille L',
            'commune' => 'Yopougon', 'mode_livraison' => 'livreur',
        ])->assertRedirect();

        $commande = Commande::first();
        $this->assertSame('autre', $commande->collection);
        $this->assertNull($commande->taille);
        $this->assertNull($commande->verset_reference);
        $this->assertSame('Le pull beige vu sur WhatsApp, taille L', $commande->message_client);
    }

    public function test_my_verse_exige_la_taille(): void
    {
        User::factory()->create();

        $this->post(route('commande.demande.store'), [
            'nom' => 'Aya', 'telephone' => '0102030405',
            'collection' => 'my_verse',
            'commune' => 'Cocody', 'mode_livraison' => 'livreur',
        ])->assertSessionHasErrors('taille');

        $this->assertSame(0, Commande::count());
    }

    public function test_le_type_de_commande_est_obligatoire(): void
    {
        User::factory()->create();

        $this->post(route('commande.demande.store'), [
            'nom' => 'Aya', 'telephone' => '0102030405',
            'commune' => 'Cocody', 'mode_livraison' => 'livreur',
        ])->assertSessionHasErrors('collection');
    }

    public function test_anti_doublon_90_secondes(): void
    {
        Mail::fake();
        Notification::fake();
        User::factory()->create();

        $payload = [
            'nom' => 'Aya Kouassi', 'telephone' => '0102030405',
            'collection' => 'autre', 'commune' => 'Cocody', 'mode_livraison' => 'livreur',
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

        $this->post(route('commande.demande.store'), [
            'nom' => 'Aya Kouassi', 'telephone' => '0102030405',
            'collection' => 'autre', 'commune' => 'Cocody', 'mode_livraison' => 'livreur',
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
