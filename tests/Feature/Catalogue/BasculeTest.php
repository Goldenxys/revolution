<?php

namespace Tests\Feature\Catalogue;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BasculeTest extends TestCase
{
    use RefreshDatabase;

    public function test_laccueil_pointe_vers_le_parcours_de_demande_v2(): void
    {
        $reponse = $this->get(route('accueil'));

        $reponse->assertOk();
        $reponse->assertSee(route('commande.demande.creer'), false);
    }

    public function test_la_page_404_pointe_vers_le_parcours_de_demande_v2(): void
    {
        $reponse = $this->get('/cette-page-nexiste-pas');

        $reponse->assertNotFound();
        $reponse->assertSee(route('commande.demande.creer'), false);
    }

    /**
     * Filet de sécurité de la bascule (§11, Phase 6) : les anciens parcours
     * restent en ligne deux semaines même si l'accueil ne les lie plus.
     */
    public function test_les_anciens_parcours_restent_accessibles_apres_la_bascule(): void
    {
        $this->get(route('commande.my-verse'))->assertOk();
        $this->get(route('commande.autre'))->assertOk();
        $this->get(route('commande.catalogue.creer'))->assertOk();
    }
}
