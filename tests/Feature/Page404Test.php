<?php

namespace Tests\Feature;

use Tests\TestCase;

class Page404Test extends TestCase
{
    public function test_une_page_inexistante_affiche_le_404_personnalise(): void
    {
        $reponse = $this->get('/cette-page-nexiste-pas');

        $reponse->assertNotFound();
        $reponse->assertSee('Ce cintre est vide');
        $reponse->assertSee(route('accueil'), false);
    }
}
