<?php

namespace Tests\Feature\Catalogue;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BasculeTest extends TestCase
{
    use RefreshDatabase;

    public function test_laccueil_pointe_vers_le_nouveau_parcours_catalogue(): void
    {
        $reponse = $this->get(route('accueil'));

        $reponse->assertOk();
        $reponse->assertSee(route('commande.catalogue.creer', ['collection' => 'my_verse']), false);
        $reponse->assertSee(route('commande.catalogue.creer'), false);
    }

    public function test_la_page_404_pointe_vers_le_nouveau_parcours_catalogue(): void
    {
        $reponse = $this->get('/cette-page-nexiste-pas');

        $reponse->assertNotFound();
        $reponse->assertSee(route('commande.catalogue.creer'), false);
    }

    /**
     * Filet de sécurité de la bascule : les anciennes routes restent
     * fonctionnelles même si l'accueil ne les lie plus.
     */
    public function test_lancien_formulaire_reste_accessible_apres_la_bascule(): void
    {
        $this->get(route('commande.my-verse'))->assertOk();
        $this->get(route('commande.autre'))->assertOk();
    }
}
