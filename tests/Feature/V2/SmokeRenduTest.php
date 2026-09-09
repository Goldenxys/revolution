<?php

namespace Tests\Feature\V2;

use App\Models\Client;
use App\Models\Commande;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SmokeRenduTest extends TestCase
{
    use RefreshDatabase;

    public function test_les_ecrans_v2_se_chargent(): void
    {
        $g = User::factory()->create();
        $client = Client::create(['cle' => '00000009', 'nom' => 'M', 'telephone' => '0700000009', 'statut' => 'prospect', 'numero_client' => 'REV-C-0009', 'nb_commandes' => 0]);
        $d = Commande::create(['client_id' => $client->id, 'commune' => 'Cocody', 'frais_livraison' => 1500, 'mode_livraison' => 'livreur', 'statut' => 'en_attente', 'souhaits_client' => [['article_nom' => 'X', 'taille' => 'XL', 'couleur' => 'Blanc', 'quantite' => 1]]]);
        $base = '/'.config('revolution.admin_path');
        $this->actingAs($g)->get($base.'/demandes')->assertOk();
        $this->actingAs($g)->get($base.'/demandes/'.$d->id.'/composer')->assertOk();
    }
}
