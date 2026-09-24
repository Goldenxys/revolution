<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\ReductionFidelite;
use App\Support\CarteFidelite;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Carte de fidélité PNG (App\Support\CarteFidelite) — déclenchée uniquement
 * par l'ancien formulaire libre (CommandeController::store()), seul
 * parcours où une commande est finale dès le clic du client.
 */
class CarteFideliteTest extends TestCase
{
    use RefreshDatabase;

    private function commander(array $donnees = []): \Illuminate\Testing\TestResponse
    {
        Mail::fake();
        Notification::fake();

        return $this->post(route('commande.store'), array_merge([
            'collection' => 'autre',
            'nom' => 'Aya Kouassi',
            'telephone' => '0102030405',
            'type_article' => 'Tee-shirt',
            'nom_article' => 'Couronne d\'épines',
            'taille' => 'M',
            'commune' => 'Cocody',
            'mode_livraison' => 'livreur',
        ], $donnees));
    }

    public function test_une_commande_qui_atteint_un_palier_genere_et_affiche_la_carte(): void
    {
        Storage::fake('local');

        // Cliente déjà à 1 commande : celle-ci sera sa 2ᵉ, palier -15 %.
        Client::create([
            'cle' => Client::cleDepuisTelephone('0102030405'),
            'nom' => 'Aya Kouassi', 'telephone' => '0102030405',
            'nb_commandes' => 1, 'statut' => 'client', 'numero_client' => 'REV-C-0001',
        ]);

        $reponse = $this->commander();
        $reponse->assertRedirect();

        $commande = \App\Models\Commande::first();
        $this->assertSame(2, $commande->numero_commande_client);

        $reduction = ReductionFidelite::where('commande_id', $commande->id)->first();
        $this->assertNotNull($reduction);
        $this->assertSame(2, $reduction->numero_commande);
        $this->assertSame(2, $reduction->palier);
        $this->assertSame(15, $reduction->pourcentage);
        $this->assertTrue(Storage::disk('local')->exists($reduction->chemin_fichier));
        $this->assertFalse($reduction->estUtilisee());

        $this->get(route('commande.confirmation', $commande->reference))
            ->assertOk()
            ->assertSee('Félicitations')
            ->assertSee('−15 %', false)
            ->assertSee('Télécharger ma carte de fidélité')
            ->assertSee(route('fidelite.telecharger', $reduction->token), false);
    }

    public function test_une_commande_qui_natteint_pas_de_palier_naffiche_pas_de_carte(): void
    {
        // 1ʳᵉ commande de la cliente : palier 1, pas de remise.
        $reponse = $this->commander();
        $reponse->assertRedirect();

        $commande = \App\Models\Commande::first();

        $this->assertNull(ReductionFidelite::where('commande_id', $commande->id)->first());

        $this->get(route('commande.confirmation', $commande->reference))
            ->assertOk()
            ->assertDontSee('Télécharger ma carte de fidélité')
            ->assertSee('Encore 1 commande');
    }

    public function test_un_meme_palier_nest_jamais_debloque_deux_fois(): void
    {
        Storage::fake('local');

        $client = Client::create([
            'cle' => '11112222', 'nom' => 'Test', 'telephone' => '11112222', 'nb_commandes' => 2,
        ]);
        $commande = \App\Models\Commande::create([
            'client_id' => $client->id, 'commune' => 'Cocody', 'frais_livraison' => 1500,
            'mode_livraison' => 'livreur', 'numero_commande_client' => 2,
        ]);

        $premiere = CarteFidelite::genererSiPalierAtteint($commande, $client);
        $seconde = CarteFidelite::genererSiPalierAtteint($commande, $client);

        $this->assertSame($premiere->id, $seconde->id);
        $this->assertSame(1, ReductionFidelite::where('client_id', $client->id)->count());
    }

    public function test_le_telechargement_fonctionne_avec_le_bon_jeton(): void
    {
        Storage::fake('local');

        $client = Client::create(['cle' => '33334444', 'nom' => 'Test', 'telephone' => '33334444', 'nb_commandes' => 2]);
        $commande = \App\Models\Commande::create([
            'client_id' => $client->id, 'commune' => 'Cocody', 'frais_livraison' => 1500,
            'mode_livraison' => 'livreur', 'numero_commande_client' => 2,
        ]);
        $reduction = CarteFidelite::genererSiPalierAtteint($commande, $client);

        $reponse = $this->get(route('fidelite.telecharger', $reduction->token));

        $reponse->assertOk();
        $reponse->assertHeader('Content-Type', 'image/png');
    }

    public function test_le_telechargement_echoue_avec_un_jeton_inconnu(): void
    {
        $this->get(route('fidelite.telecharger', 'jeton-invente'))->assertNotFound();
    }
}
