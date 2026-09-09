<?php

namespace Tests\Feature\V2;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BasculeV2Test extends TestCase
{
    use RefreshDatabase;

    public function test_les_deux_parcours_v2_sont_servis(): void
    {
        $this->get('/commande')
            ->assertOk()
            ->assertSee('Je passe ma commande My Verse')
            ->assertSee('commandeDemande(');

        $this->get('/commande/autre-collection')->assertOk();

        $this->assertSame(url('/commande'), route('commande.demande.creer'));
        $this->assertSame(url('/commande/autre-collection'), route('commande.demande.autre'));
    }

    public function test_les_anciens_parcours_restent_en_ligne(): void
    {
        $this->get('/commande/catalogue')->assertOk();
        $this->get('/commande/my-verse')->assertOk();
        $this->get('/commande/autre')->assertOk();
    }
}
