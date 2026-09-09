<?php

namespace Tests\Feature\V2;

use App\Models\Article;
use App\Models\CollectionCatalogue;
use App\Models\TypeArticle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BasculeV2Test extends TestCase
{
    use RefreshDatabase;

    public function test_slash_commande_sert_le_formulaire_de_demande_v2(): void
    {
        $collection = CollectionCatalogue::create(['nom' => 'C', 'slug' => 'c']);
        $type = TypeArticle::create(['nom' => 'T', 'slug' => 't', 'gere_tailles' => true, 'gere_couleurs' => true]);
        Article::create(['collection_id' => $collection->id, 'type_article_id' => $type->id, 'nom' => 'Art', 'slug' => 'art', 'prix' => 7000]);

        $this->get('/commande')
            ->assertOk()
            ->assertSee('On enregistre votre commande')
            ->assertSee('commandeDemande('); // composant Alpine du formulaire V2

        $this->assertSame(url('/commande'), route('commande.demande.creer'));
    }

    public function test_les_anciens_parcours_restent_en_ligne(): void
    {
        $this->get('/commande/catalogue')->assertOk();
        $this->get('/commande/my-verse')->assertOk();
        $this->get('/commande/autre')->assertOk();
    }
}
