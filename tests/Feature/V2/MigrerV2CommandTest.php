<?php

namespace Tests\Feature\V2;

use App\Models\Client;
use App\Models\Commande;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MigrerV2CommandTest extends TestCase
{
    use RefreshDatabase;

    private function commande(Client $client, string $statut, int $sousTotal = 0, int $remise = 0): Commande
    {
        return Commande::create([
            'client_id' => $client->id,
            'commune' => 'Cocody',
            'frais_livraison' => 1500,
            'mode_livraison' => 'livreur',
            'statut' => $statut,
            'sous_total' => $sousTotal,
            'remise_montant' => $remise,
            'numero_commande_client' => 1,
        ]);
    }

    public function test_sans_execute_rien_n_est_ecrit(): void
    {
        $client = Client::create(['cle' => '00000001', 'nom' => 'A', 'telephone' => '00000001', 'nb_commandes' => 1]);
        $this->commande($client, 'confirmee', 8000);

        $this->artisan('revolution:migrer-v2')->assertSuccessful();

        $this->assertDatabaseHas('commandes', ['client_id' => $client->id, 'statut' => 'confirmee']);
        $this->assertNull($client->refresh()->numero_client);
    }

    public function test_execute_remappe_les_statuts_et_backfill_les_montants(): void
    {
        $client = Client::create(['cle' => '00000002', 'nom' => 'B', 'telephone' => '00000002', 'nb_commandes' => 3]);
        $nouvelle = $this->commande($client, 'nouvelle', 5000);
        $confirmee = $this->commande($client, 'confirmee', 8000, 1200);
        $preparation = $this->commande($client, 'preparation', 10000);
        $livree = $this->commande($client, 'livree', 7000);
        $annulee = $this->commande($client, 'annulee', 9000);

        $this->artisan('revolution:migrer-v2 --execute')->assertSuccessful();

        $this->assertSame('en_attente', $nouvelle->refresh()->statut);
        $this->assertNull($nouvelle->validee_at);

        $this->assertSame('validee', $confirmee->refresh()->statut);
        $this->assertNotNull($confirmee->validee_at);
        $this->assertSame(6800, $confirmee->total_articles);            // 8000 − 1200
        $this->assertSame(8300, $confirmee->total_a_payer);             // + 1500 livraison
        $this->assertNotNull($confirmee->recu_token);

        $this->assertSame('en_livraison', $preparation->refresh()->statut);
        $this->assertNotNull($preparation->validee_at);

        $this->assertSame('livree', $livree->refresh()->statut);
        $this->assertSame('annulee', $annulee->refresh()->statut);
        $this->assertNull($annulee->refresh()->validee_at);

        $client->refresh();
        $this->assertSame('client', $client->statut);
        $this->assertSame('REV-C-'.sprintf('%04d', $client->id), $client->numero_client);
        $this->assertSame(3, $client->nb_commandes);                    // confirmee + preparation + livree
        $this->assertSame(6800 + 10000 + 7000, $client->ca_cumule);
    }

    public function test_execute_est_idempotente(): void
    {
        $client = Client::create(['cle' => '00000003', 'nom' => 'C', 'telephone' => '00000003', 'nb_commandes' => 1]);
        $this->commande($client, 'confirmee', 8000, 1000);

        $this->artisan('revolution:migrer-v2 --execute')->assertSuccessful();
        $caApres1 = $client->refresh()->ca_cumule;
        $tokenApres1 = Commande::where('client_id', $client->id)->value('recu_token');

        $this->artisan('revolution:migrer-v2 --execute')->assertSuccessful();

        $this->assertSame($caApres1, $client->refresh()->ca_cumule);
        $this->assertSame($tokenApres1, Commande::where('client_id', $client->id)->value('recu_token'));
    }
}
