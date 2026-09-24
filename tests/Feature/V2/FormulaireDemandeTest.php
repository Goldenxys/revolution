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

    public function test_les_deux_pages_se_chargent(): void
    {
        $this->get(route('commande.demande.creer'))->assertOk()->assertSee('Je passe ma commande My Verse');
        $this->get(route('commande.demande.autre'))->assertOk()->assertSee('Je passe ma commande');
    }

    public function test_l_accueil_propose_les_deux_boutons(): void
    {
        $this->get(route('accueil'))
            ->assertSee(route('commande.demande.creer'), false)
            ->assertSee(route('commande.demande.autre'), false);
    }

    public function test_une_demande_my_verse_enregistre_plusieurs_versets(): void
    {
        Mail::fake();
        Notification::fake();
        User::factory()->create();

        $response = $this->post(route('commande.demande.store'), [
            'nom' => 'Aya Kouassi',
            'telephone' => '0102030405',
            'email' => 'aya@example.com',
            'collection' => 'my_verse',
            'versets' => [
                ['reference' => 'Philippiens 4:13', 'texte' => 'Je puis tout par celui qui me fortifie.'],
                ['reference' => 'Jean 3:16', 'texte' => null],
            ],
            'precisions' => 'Écriture dorée',
            'commune' => 'Cocody',
            'mode_livraison' => 'livreur',
        ]);

        $commande = Commande::first();
        $response->assertRedirect(route('commande.demande.merci', $commande->reference));

        $this->assertSame('en_attente', $commande->statut);
        $this->assertSame('my_verse', $commande->collection);
        $this->assertNull($commande->taille);          // réglée par la gérante
        $this->assertNull($commande->couleur);
        $this->assertCount(2, $commande->souhaits_client['versets']);
        $this->assertSame('Philippiens 4:13', $commande->souhaits_client['versets'][0]['reference']);
        $this->assertSame('Philippiens 4:13', $commande->verset_reference); // 1er verset sur la colonne historique
        $this->assertSame('Écriture dorée', $commande->message_client);
        $this->assertSame(0, $commande->total_articles);

        $this->assertSame('prospect', $commande->client->statut);
        $this->assertNotNull($commande->client->numero_client);

        Mail::assertQueued(DemandeDeposee::class);
    }

    public function test_une_demande_autre_collection_ne_demande_que_les_infos(): void
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
        $this->assertNull($commande->souhaits_client);
        $this->assertNull($commande->verset_reference);
        $this->assertSame('Le pull beige vu sur WhatsApp, taille L', $commande->message_client);
        $this->assertNotNull($commande->client->numero_client); // carte de fidélité aussi
    }

    public function test_my_verse_exige_au_moins_un_verset_renseigne(): void
    {
        User::factory()->create();

        $this->post(route('commande.demande.store'), [
            'nom' => 'Aya', 'telephone' => '0102030405',
            'collection' => 'my_verse',
            'versets' => [['reference' => '', 'texte' => '']],
            'commune' => 'Cocody', 'mode_livraison' => 'livreur',
        ])->assertSessionHasErrors('versets.0.texte');

        $this->assertSame(0, Commande::count());
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
            ->assertSee('comptée une fois livrée');
    }

    /**
     * Geste de clôture (§ mise à jour) : la carte téléchargeable compte la
     * demande tout juste déposée comme si elle était déjà validée, même si
     * l'écran affiché reste honnête sur ce qui est réellement validé.
     */
    public function test_le_bouton_de_telechargement_projette_la_commande_en_cours_comme_validee(): void
    {
        Mail::fake();
        Notification::fake();
        User::factory()->create();

        // Cliente déjà à 1 commande validée : la demande qu'elle vient de
        // déposer serait sa 2ᵉ, palier qui débloque -15 %.
        $client = Client::create([
            'cle' => Client::cleDepuisTelephone('0102030405'),
            'nom' => 'Aya Kouassi', 'telephone' => '0102030405',
            'nb_commandes' => 1, 'statut' => 'client', 'numero_client' => 'REV-C-0001',
        ]);

        $this->post(route('commande.demande.store'), [
            'nom' => 'Aya Kouassi', 'telephone' => '0102030405',
            'collection' => 'autre', 'commune' => 'Cocody', 'mode_livraison' => 'livreur',
        ]);

        $commande = Commande::first();

        $response = $this->get(route('commande.demande.merci', $commande->reference));
        $response->assertOk()
            ->assertSee('Télécharger ma carte de fidélité')
            ->assertSee(route('commande.demande.carte', $commande->reference), false);

        // L'écran, lui, reste honnête : encore 1 commande validée affichée.
        $response->assertSee('1 commande validée');
    }

    /**
     * Le nouveau gabarit remplace le canvas partout : le lien vers la carte
     * doit apparaître même quand cette demande ne fait franchir aucun
     * palier (route commande.demande.carte sert alors la variante
     * « progression », jamais un 404 — voir CarteFideliteTest).
     */
    public function test_le_lien_de_telechargement_apparait_meme_sans_palier_franchi(): void
    {
        Mail::fake();
        Notification::fake();
        User::factory()->create();

        // Cliente sans commande antérieure : cette demande serait sa 1ʳᵉ,
        // aucun palier ne se débloque.
        $this->post(route('commande.demande.store'), [
            'nom' => 'Aya Kouassi', 'telephone' => '0102030405',
            'collection' => 'autre', 'commune' => 'Cocody', 'mode_livraison' => 'livreur',
        ]);

        $commande = Commande::first();

        $this->get(route('commande.demande.merci', $commande->reference))
            ->assertOk()
            ->assertSee('Télécharger ma carte de fidélité')
            ->assertSee(route('commande.demande.carte', $commande->reference), false);
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
