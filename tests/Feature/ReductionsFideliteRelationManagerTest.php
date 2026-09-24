<?php

namespace Tests\Feature;

use App\Filament\Resources\ClientResource\Pages\ViewClient;
use App\Filament\Resources\ClientResource\RelationManagers\ReductionsFideliteRelationManager;
use App\Models\Client;
use App\Models\Commande;
use App\Models\ReductionFidelite;
use App\Models\User;
use App\Support\CarteFidelite;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ReductionsFideliteRelationManagerTest extends TestCase
{
    use RefreshDatabase;

    private function clientAvecReduction(): array
    {
        Storage::fake('local');

        $client = Client::create(['cle' => '55556666', 'nom' => 'Test', 'telephone' => '55556666', 'nb_commandes' => 2]);
        $commande = Commande::create([
            'client_id' => $client->id, 'commune' => 'Cocody', 'frais_livraison' => 1500,
            'mode_livraison' => 'livreur', 'numero_commande_client' => 2,
        ]);
        $reduction = CarteFidelite::genererSiPalierAtteint($commande, $client);

        return [$client, $reduction];
    }

    public function test_la_fiche_client_liste_ses_reductions_de_fidelite(): void
    {
        $gerante = User::factory()->create();
        [$client, $reduction] = $this->clientAvecReduction();

        Livewire::actingAs($gerante)
            ->test(ReductionsFideliteRelationManager::class, [
                'ownerRecord' => $client,
                'pageClass' => ViewClient::class,
            ])
            ->assertCanSeeTableRecords([$reduction])
            ->assertSee('−15 %', false);
    }

    public function test_marquer_une_reduction_comme_utilisee(): void
    {
        $gerante = User::factory()->create();
        [$client, $reduction] = $this->clientAvecReduction();

        Livewire::actingAs($gerante)
            ->test(ReductionsFideliteRelationManager::class, [
                'ownerRecord' => $client,
                'pageClass' => ViewClient::class,
            ])
            ->callTableAction('marquer_utilisee', $reduction);

        $this->assertTrue($reduction->fresh()->estUtilisee());
    }

    public function test_annuler_lutilisation_dune_reduction(): void
    {
        $gerante = User::factory()->create();
        [$client, $reduction] = $this->clientAvecReduction();
        $reduction->update(['utilisee_at' => now()]);

        Livewire::actingAs($gerante)
            ->test(ReductionsFideliteRelationManager::class, [
                'ownerRecord' => $client,
                'pageClass' => ViewClient::class,
            ])
            ->callTableAction('annuler_utilisation', $reduction);

        $this->assertFalse($reduction->fresh()->estUtilisee());
    }

    /**
     * Rendu HTTP complet, pas seulement Livewire::test() : seul moyen de
     * détecter une erreur Blade dans l'imbrication réelle de la page.
     */
    public function test_la_page_client_se_charge_sans_erreur(): void
    {
        $gerante = User::factory()->create();
        [$client] = $this->clientAvecReduction();

        $this->actingAs($gerante)
            ->get('/'.config('revolution.admin_path')."/clients/{$client->id}")
            ->assertOk();
    }
}
