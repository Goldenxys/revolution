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
        $d = Commande::create(['client_id' => $client->id, 'commune' => 'Cocody', 'frais_livraison' => 1500, 'mode_livraison' => 'livreur', 'statut' => 'en_attente', 'collection' => 'my_verse', 'souhaits_client' => ['collection' => 'my_verse', 'versets' => [['reference' => 'Ps 23', 'texte' => 'L\'Éternel est mon berger.'], ['reference' => 'Jean 3:16', 'texte' => null]]]]);
        $autre = Commande::create(['client_id' => $client->id, 'commune' => 'Cocody', 'frais_livraison' => 1500, 'mode_livraison' => 'livreur', 'statut' => 'en_attente', 'collection' => 'autre', 'message_client' => 'le pull beige']);
        $base = '/'.config('revolution.admin_path');
        $this->actingAs($g)->get($base.'/demandes')->assertOk();
        $this->actingAs($g)->get($base.'/demandes/'.$d->id.'/composer')->assertOk();
        $this->actingAs($g)->get($base.'/demandes/'.$autre->id.'/composer')->assertOk();
        $this->get(route('commande.demande.creer'))->assertOk()->assertSee('Ajouter un autre tee-shirt My Verse');
        $this->get(route('commande.demande.autre'))->assertOk();
    }
}
