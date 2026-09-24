<?php

namespace Tests\Feature\V2;

use App\Models\Client;
use App\Models\Commande;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CarteFideliteTest extends TestCase
{
    use RefreshDatabase;

    private function creerDemande(int $nbCommandesDejaValidees): Commande
    {
        $client = Client::create([
            'cle' => Client::cleDepuisTelephone('0102030405'),
            'nom' => 'Aya Kouassi',
            'telephone' => '0102030405',
            'nb_commandes' => $nbCommandesDejaValidees,
            'statut' => $nbCommandesDejaValidees > 0 ? 'client' : 'prospect',
            'numero_client' => 'REV-C-0001',
        ]);

        return Commande::create([
            'client_id' => $client->id,
            'commune' => 'Cocody',
            'frais_livraison' => 1500,
            'mode_livraison' => 'livreur',
            'statut' => 'en_attente',
            'sous_total' => 0,
            'remise_pourcentage' => 0,
            'remise_montant' => 0,
            'total' => 0,
            'total_articles' => 0,
            'total_a_payer' => 0,
        ]);
    }

    public function test_la_carte_png_est_servie_quand_la_demande_fait_franchir_un_palier(): void
    {
        // 1 commande déjà validée : cette demande serait la 2ᵉ, palier -15 %.
        $commande = $this->creerDemande(1);

        $reponse = $this->get(route('commande.demande.carte', $commande->reference));

        $reponse->assertOk();
        $reponse->assertHeader('Content-Type', 'image/png');

        $image = imagecreatefromstring($reponse->getContent());
        $this->assertNotFalse($image);
        $this->assertSame(2480, imagesx($image));
        $this->assertSame(3508, imagesy($image));
    }

    public function test_la_carte_png_sert_la_variante_progression_si_aucun_palier_nest_franchi(): void
    {
        // 0 commande validée : cette demande serait la 1ʳᵉ, aucun palier —
        // sert désormais la variante « progression », pas un 404.
        $commande = $this->creerDemande(0);

        $reponse = $this->get(route('commande.demande.carte', $commande->reference));

        $reponse->assertOk();
        $reponse->assertHeader('Content-Type', 'image/png');

        $image = imagecreatefromstring($reponse->getContent());
        $this->assertNotFalse($image);
        $this->assertSame(2480, imagesx($image));
        $this->assertSame(3508, imagesy($image));
    }

    public function test_la_carte_png_porte_un_cache_control_prive(): void
    {
        $commande = $this->creerDemande(1);

        $this->get(route('commande.demande.carte', $commande->reference))
            ->assertHeader('Cache-Control', 'max-age=300, no-transform, private');
    }

    public function test_la_carte_png_est_introuvable_pour_une_reference_inconnue(): void
    {
        $this->get(route('commande.demande.carte', 'ZZZZZZ'))->assertNotFound();
    }

    public function test_la_carte_png_est_introuvable_pour_une_demande_deja_validee(): void
    {
        $client = Client::create(['cle' => '00000009', 'nom' => 'X', 'telephone' => '0700000009', 'nb_commandes' => 1]);
        $commande = Commande::create([
            'client_id' => $client->id, 'commune' => 'Cocody', 'frais_livraison' => 1500,
            'mode_livraison' => 'livreur', 'statut' => 'validee', 'validee_at' => now(),
        ]);

        $this->get(route('commande.demande.carte', $commande->reference))->assertNotFound();
    }

    public function test_la_carte_png_du_flux_legacy_se_base_sur_letat_reel_du_client(): void
    {
        $client = Client::create([
            'cle' => Client::cleDepuisTelephone('0102030406'),
            'nom' => 'Koffi', 'telephone' => '0102030406',
            'nb_commandes' => 2, 'statut' => 'client', 'numero_client' => 'REV-C-0002',
        ]);
        $commande = Commande::create([
            'client_id' => $client->id, 'commune' => 'Cocody', 'frais_livraison' => 1500,
            'mode_livraison' => 'livreur', 'statut' => 'nouvelle', 'numero_commande_client' => 2,
        ]);

        $reponse = $this->get(route('commande.carte', $commande->reference));

        $reponse->assertOk();
        $reponse->assertHeader('Content-Type', 'image/png');
    }

    public function test_la_carte_png_du_flux_legacy_est_introuvable_pour_une_reference_inconnue(): void
    {
        $this->get(route('commande.carte', 'ZZZZZZ'))->assertNotFound();
    }
}
