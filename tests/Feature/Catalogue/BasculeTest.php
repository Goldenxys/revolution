<?php

namespace Tests\Feature\Catalogue;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * L'accueil est repassé sur l'ancien formulaire libre (App\Support\CarteFidelite) :
 * c'est le seul parcours où une commande est finale dès le clic du client,
 * condition nécessaire pour délivrer la carte de fidélité immédiatement.
 */
class BasculeTest extends TestCase
{
    use RefreshDatabase;

    public function test_laccueil_pointe_vers_lancien_formulaire_libre(): void
    {
        $reponse = $this->get(route('accueil'));

        $reponse->assertOk();
        $reponse->assertSee(route('commande.my-verse'), false);
        $reponse->assertSee(route('commande.autre'), false);
    }

    public function test_la_page_404_pointe_vers_lancien_formulaire_libre(): void
    {
        $reponse = $this->get('/cette-page-nexiste-pas');

        $reponse->assertNotFound();
        $reponse->assertSee(route('commande.my-verse'), false);
    }

    /**
     * Le parcours « demande » V2 reste en ligne (compositeur, stock lié au
     * catalogue) pour les demandes déjà en cours — simplement plus lié
     * depuis l'accueil.
     */
    public function test_le_parcours_demande_v2_reste_accessible(): void
    {
        $this->get(route('commande.demande.creer'))->assertOk();
        $this->get(route('commande.demande.autre'))->assertOk();
        $this->get(route('commande.catalogue.creer'))->assertOk();
    }
}
