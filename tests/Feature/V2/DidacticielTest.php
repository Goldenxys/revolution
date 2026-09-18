<?php

namespace Tests\Feature\V2;

use App\Filament\Pages\TableauDeBord;
use App\Filament\Resources\ArticleResource\Pages\ListArticles;
use App\Filament\Resources\CollectionResource\Pages\ListCollections;
use App\Filament\Resources\CommandeResource\Pages\ListCommandes;
use App\Filament\Resources\CouleurResource\Pages\ManageCouleurs;
use App\Filament\Resources\DemandeResource\Pages\ComposerDemande;
use App\Filament\Resources\DemandeResource\Pages\ListDemandes;
use App\Filament\Resources\StockResource\Pages\ListStock;
use App\Filament\Resources\TailleResource\Pages\ManageTailles;
use App\Filament\Resources\TypeArticleResource\Pages\ManageTypesArticles;
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

    public function test_le_bouton_guide_fonctionne_sur_les_articles(): void
    {
        $gerante = User::factory()->create();

        Livewire::actingAs($gerante)
            ->test(ListArticles::class)
            ->assertActionExists('guide_articles')
            ->callAction('guide_articles')
            ->assertSuccessful();
    }

    public function test_le_bouton_guide_fonctionne_sur_les_collections(): void
    {
        $gerante = User::factory()->create();

        Livewire::actingAs($gerante)
            ->test(ListCollections::class)
            ->assertActionExists('guide_collections')
            ->callAction('guide_collections')
            ->assertSuccessful();
    }

    public function test_le_bouton_guide_fonctionne_sur_les_types_darticle(): void
    {
        $gerante = User::factory()->create();

        Livewire::actingAs($gerante)
            ->test(ManageTypesArticles::class)
            ->assertActionExists('guide_types')
            ->callAction('guide_types')
            ->assertSuccessful();
    }

    public function test_le_bouton_guide_fonctionne_sur_les_tailles(): void
    {
        $gerante = User::factory()->create();

        Livewire::actingAs($gerante)
            ->test(ManageTailles::class)
            ->assertActionExists('guide_tailles')
            ->callAction('guide_tailles')
            ->assertSuccessful();
    }

    public function test_le_bouton_guide_fonctionne_sur_les_couleurs(): void
    {
        $gerante = User::factory()->create();

        Livewire::actingAs($gerante)
            ->test(ManageCouleurs::class)
            ->assertActionExists('guide_couleurs')
            ->callAction('guide_couleurs')
            ->assertSuccessful();
    }

    public function test_le_script_de_la_visite_guidee_est_charge(): void
    {
        $gerante = User::factory()->create();

        $this->actingAs($gerante)
            ->get('/'.config('revolution.admin_path'))
            ->assertOk()
            ->assertSee('window.RevoTour', false)
            ->assertSee('data-tour="demandes-banner"', false)
            // Chargé sous le package 'app' (et non un nom de paquet fixe) :
            // c'est ce qui permet à FilamentAsset::appVersion() de casser le
            // cache CDN/navigateur à chaque déploiement — voir
            // AdminPanelProvider::versionApplicative().
            ->assertSee('/js/app/revo-tour.js?v=', false);
    }
}
