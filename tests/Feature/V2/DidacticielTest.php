<?php

namespace Tests\Feature\V2;

use App\Filament\Pages\TableauDeBord;
use App\Filament\Resources\CommandeResource\Pages\ListCommandes;
use App\Filament\Resources\DemandeResource\Pages\ComposerDemande;
use App\Filament\Resources\DemandeResource\Pages\ListDemandes;
use App\Filament\Resources\StockResource\Pages\ListStock;
use App\Models\Client;
use App\Models\Commande;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Le bouton « Revoir le guide » (App\Filament\Support\GuideAction) doit être
 * présent et actionnable sur les cinq écrans clés, sans jamais planter même
 * quand certains points d'ancrage (data-tour) n'existent pas sur l'écran.
 */
class DidacticielTest extends TestCase
{
    use RefreshDatabase;

    public function test_le_bouton_guide_fonctionne_sur_le_tableau_de_bord(): void
    {
        $gerante = User::factory()->create();

        Livewire::actingAs($gerante)
            ->test(TableauDeBord::class)
            ->assertActionExists('guide_dashboard')
            ->callAction('guide_dashboard')
            ->assertSuccessful();
    }

    public function test_le_bouton_guide_fonctionne_sur_demandes_a_valider(): void
    {
        $gerante = User::factory()->create();

        Livewire::actingAs($gerante)
            ->test(ListDemandes::class)
            ->assertActionExists('guide_demandes')
            ->callAction('guide_demandes')
            ->assertSuccessful();
    }

    public function test_le_bouton_guide_fonctionne_sur_le_compositeur(): void
    {
        $gerante = User::factory()->create();
        $client = Client::create(['cle' => '00000099', 'nom' => 'X', 'telephone' => '0700000099', 'nb_commandes' => 0]);
        $commande = Commande::create([
            'client_id' => $client->id, 'commune' => 'Cocody', 'frais_livraison' => 1500,
            'mode_livraison' => 'livreur', 'statut' => 'en_attente', 'collection' => 'autre',
        ]);

        Livewire::actingAs($gerante)
            ->test(ComposerDemande::class, ['record' => $commande->getKey()])
            ->assertActionExists('guide_composer')
            ->callAction('guide_composer')
            ->assertSuccessful();
    }

    public function test_le_bouton_guide_fonctionne_sur_mon_stock(): void
    {
        $gerante = User::factory()->create();

        Livewire::actingAs($gerante)
            ->test(ListStock::class)
            ->assertActionExists('guide_stock')
            ->callAction('guide_stock')
            ->assertSuccessful();
    }

    public function test_le_bouton_guide_fonctionne_sur_commandes(): void
    {
        $gerante = User::factory()->create();

        Livewire::actingAs($gerante)
            ->test(ListCommandes::class)
            ->assertActionExists('guide_commandes')
            ->callAction('guide_commandes')
            ->assertSuccessful();
    }

    public function test_le_script_de_la_visite_guidee_est_charge(): void
    {
        $gerante = User::factory()->create();

        $this->actingAs($gerante)
            ->get('/'.config('revolution.admin_path'))
            ->assertOk()
            ->assertSee('window.RevoTour', false)
            ->assertSee('data-tour="demandes-banner"', false);
    }
}
