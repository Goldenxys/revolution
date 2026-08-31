<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EspaceRevolutionTest extends TestCase
{
    use RefreshDatabase;

    private function chemin(string $sousChemin = ''): string
    {
        return '/'.config('revolution.admin_path').$sousChemin;
    }

    public function test_admin_est_inaccessible_sans_connexion(): void
    {
        $reponse = $this->get($this->chemin());

        $reponse->assertRedirect('/login');
    }

    public function test_admin_nest_pas_accessible_sur_slash_admin(): void
    {
        // /admin ne doit plus rien exposer : le chemin est désormais secret.
        $reponse = $this->get('/admin');

        $reponse->assertNotFound();
    }

    public function test_admin_accessible_une_fois_connectee(): void
    {
        $gerante = User::factory()->create();

        $reponse = $this->actingAs($gerante)->get($this->chemin());

        $reponse->assertOk();
        $reponse->assertSee('Tableau de bord');
    }

    public function test_admin_commandes_et_clients_accessibles_une_fois_connectee(): void
    {
        $gerante = User::factory()->create();

        $this->actingAs($gerante)->get($this->chemin('/commandes'))->assertOk();
        $this->actingAs($gerante)->get($this->chemin('/clients'))->assertOk();
        $this->actingAs($gerante)->get($this->chemin('/reglages'))->assertOk();
    }

    public function test_le_lien_vers_lespace_revolution_nest_plus_sur_la_page_daccueil(): void
    {
        $reponse = $this->get('/');

        $reponse->assertOk();
        $reponse->assertDontSee('Espace RÉVOLUTION');
    }
}
